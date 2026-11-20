<?php
declare(strict_types=1);
require_once __DIR__ . '/geocode.php';

function portal_home_location_payload(mysqli $db, string $idno): array
{
    $summary = portal_home_location_summary($db, $idno);
    return ['data' => [
        'work_arrangement' => $summary['work_arrangement'], 'status' => $summary['status'],
        'primary' => $summary['home_location'], 'pending' => $summary['pending'],
    ]];
}

function portal_submit_home_location(mysqli $db, string $idno, array $input, ?callable $geocoder = null): array
{
    $summary = portal_home_location_summary($db, $idno);
    if (!in_array($summary['work_arrangement'], ['wfh', 'hybrid'], true)) {
        throw new PortalApiException('Only WFH or hybrid employees may submit a home location.', 'not_wfh', 422);
    }
    $errors = [];
    foreach (['latitude' => 90, 'longitude' => 180] as $field => $limit) {
        if (!isset($input[$field]) || !is_numeric($input[$field]) || !is_finite((float) $input[$field]) || abs((float) $input[$field]) > $limit) {
            $errors[$field] = ['Provide valid coordinates.'];
        }
    }
    if ($errors) throw new PortalApiException('The given data was invalid.', 'validation_error', 422, ['errors' => $errors]);
    $lat = (float) $input['latitude'];
    $lng = (float) $input['longitude'];
    $label = trim((string) ($input['label'] ?? 'Home')) ?: 'Home';
    if (mb_strlen($label) > 255) throw new PortalApiException('Home label is too long.', 'validation_error', 422);
    try { $address = ($geocoder ?? 'portal_reverse_geocode')($lat, $lng) ?? []; }
    catch (Throwable) { $address = []; }
    $now = portal_now()->format('Y-m-d H:i:s');
    $db->begin_transaction();
    try {
        $stmt = $db->prepare('SELECT idno FROM dtr_employee_profile WHERE idno=? FOR UPDATE');
        $stmt->bind_param('s', $idno);
        $stmt->execute();
        $stmt->get_result();
        $stmt->close();
        $stmt = $db->prepare("UPDATE home_locations SET status='rejected',review_note='Superseded by a new submission.',updated_at=? WHERE employee_id=? AND status='pending'");
        $stmt->bind_param('ss', $now, $idno);
        $stmt->execute();
        $stmt->close();
        $text = $address['address_text'] ?? null;
        $street = $address['street'] ?? null;
        $city = $address['city'] ?? null;
        $province = $address['province'] ?? null;
        $stmt = $db->prepare("INSERT INTO home_locations (employee_id,label,latitude,longitude,radius_meters,address_text,street,city,province,status,created_at,updated_at) VALUES (?,?,?,?,200,?,?,?,?,'pending',?,?)");
        $stmt->bind_param('ssddssssss', $idno, $label, $lat, $lng, $text, $street, $city, $province, $now, $now);
        $stmt->execute();
        $id = (int) $db->insert_id;
        $stmt->close();
        $row = $db->query("SELECT * FROM home_locations WHERE id={$id}")->fetch_assoc();
        $db->commit();
        return portal_home_location_pin($row);
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}

function portal_review_home_location(mysqli $db, int $id, string $action, ?string $note, string $reviewedBy): array
{
    if (!in_array($action, ['approve', 'reject'], true)) throw new PortalApiException('Invalid review action.', 'invalid_action', 422);
    $db->begin_transaction();
    try {
        $stmt = $db->prepare('SELECT employee_id FROM home_locations WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $owner = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$owner) throw new PortalApiException('Not found.', 'not_found', 404);
        $stmt = $db->prepare('SELECT idno FROM dtr_employee_profile WHERE idno=? FOR UPDATE');
        $stmt->bind_param('s', $owner['employee_id']);
        $stmt->execute();
        $stmt->get_result();
        $stmt->close();
        $stmt = $db->prepare('SELECT * FROM home_locations WHERE id=? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) throw new PortalApiException('Not found.', 'not_found', 404);
        $status = $action === 'approve' ? 'approved' : 'rejected';
        if ($row['status'] === $status) {
            $db->commit();
            return portal_home_location_pin($row);
        }
        if ($row['status'] === 'rejected' || ($action === 'approve' && $row['status'] !== 'pending')) {
            throw new PortalApiException('This request is no longer reviewable. Submit a new home location.', 'invalid_action', 422);
        }
        $now = portal_now()->format('Y-m-d H:i:s');
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE home_locations SET status='rejected',review_note='Superseded by approved home location.',reviewed_by=?,reviewed_at=?,updated_at=? WHERE employee_id=? AND status='approved' AND id<>?");
            $stmt->bind_param('ssssi', $reviewedBy, $now, $now, $row['employee_id'], $id);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $db->prepare('UPDATE home_locations SET status=?,review_note=?,reviewed_by=?,reviewed_at=?,updated_at=? WHERE id=?');
        $stmt->bind_param('sssssi', $status, $note, $reviewedBy, $now, $now, $id);
        $stmt->execute();
        $stmt->close();
        portal_notify($db, (string) $row['employee_id'], 'Home location ' . $status, $note ?: 'HR has reviewed your home location.', ['home_location_id' => $id, 'status' => $status], "home-review:{$id}:{$status}");
        $row['status'] = $status;
        $db->commit();
        return portal_home_location_pin($row);
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}

function portal_cli_options(array $argv): array
{
    $options = [];
    foreach ($argv as $arg) {
        if (!str_starts_with($arg, '--')) continue;
        $parts = explode('=', substr($arg, 2), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }
    return $options;
}

function portal_parse_review_args(array $argv): array
{
    $opts = portal_cli_options($argv);
    if (isset($opts['approve']) === isset($opts['reject']) || !ctype_digit((string) ($opts['id'] ?? '')) || (int) $opts['id'] < 1) {
        throw new InvalidArgumentException('Use --approve|--reject --id=N [--note=...] [--by=HR]');
    }
    return ['action' => isset($opts['approve']) ? 'approve' : 'reject', 'id' => (int) $opts['id'], 'note' => isset($opts['note']) ? (string) $opts['note'] : null, 'by' => (string) ($opts['by'] ?? 'HR')];
}
