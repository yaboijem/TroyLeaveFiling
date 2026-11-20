<?php
declare(strict_types=1);

function portal_handle_punch_request(mysqli $db, string $idno, string $type, array $post, array $files): array
{
    $errors = [];
    foreach (['latitude' => 90, 'longitude' => 180] as $field => $limit) {
        if (!isset($post[$field]) || !is_numeric($post[$field]) || !is_finite((float) $post[$field]) || abs((float) $post[$field]) > $limit) {
            $errors[$field] = ['Provide valid numeric ' . $field . '.'];
        }
    }
    if (isset($post['accuracy_meters']) && (
        !is_numeric($post['accuracy_meters']) || !is_finite((float) $post['accuracy_meters']) || (float) $post['accuracy_meters'] < 0
    )) {
        $errors['accuracy_meters'] = ['Provide a finite nonnegative accuracy in meters.'];
    }
    if ($errors !== []) throw new PortalApiException('The given data was invalid.', 'validation_error', 422, ['errors' => $errors]);
    $selfie = $files['selfie'] ?? ['error' => UPLOAD_ERR_NO_FILE];
    portal_validate_selfie($selfie);
    $uuid = $post['client_uuid'] ?? '';
    if (!is_string($uuid) || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) !== 1) {
        throw new PortalApiException('The given data was invalid.', 'validation_error', 422, ['errors' => ['client_uuid' => ['Provide a valid punch UUID.']]]);
    }
    $post['source'] = 'app';
    $post['notes'] = null;
    return [
        'status' => in_array($type, ['time_in', 'break_in'], true) ? 201 : 200,
        'payload' => ['data' => portal_punch($db, $idno, $type, $post, $selfie)],
    ];
}

function portal_open_shift(mysqli $db, string $idno): ?array
{
    $stmt = $db->prepare('SELECT * FROM attendance WHERE employee_id=? AND time_out IS NULL ORDER BY time_in DESC,id DESC LIMIT 1');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function portal_close_session(mysqli $db, string $idno, ?string $at, string $note, string $by): array
{
    $db->begin_transaction();
    try {
        $stmt = $db->prepare('SELECT idno FROM dtr_employee_profile WHERE idno=? FOR UPDATE');
        $stmt->bind_param('s', $idno);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$profile) {
            $stmt = $db->prepare('SELECT idno FROM employeefiletbl WHERE idno=? FOR UPDATE');
            $stmt->bind_param('s', $idno);
            $stmt->execute();
            $stmt->get_result();
            $stmt->close();
        }
        $shift = portal_open_shift($db, $idno);
        if ($shift === null) portal_attendance_conflict($db, $idno, 'There is no open shift to close.');
        $clock = $at === null ? portal_now() : DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $at, portal_tz());
        if (!$clock || ($at !== null && $clock->format('Y-m-d H:i:s') !== $at) || $clock < new DateTimeImmutable($shift['time_in'], portal_tz())) {
            throw new PortalApiException('Close time must be at or after time in.', 'validation_error', 422);
        }
        $time = $clock->format('Y-m-d H:i:s');
        $id = (int) $shift['id'];
        $stmt = $db->prepare("SELECT COALESCE(SUM(break_minutes),0) total FROM attendance_punches WHERE attendance_id=? AND type='break_out'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $breaks = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        $minutes = portal_compute_work_minutes($shift['time_in'], $time, $breaks);
        $now = portal_now()->format('Y-m-d H:i:s');
        $stmt = $db->prepare('UPDATE attendance SET time_out=?,work_minutes=?,break_minutes=?,updated_at=? WHERE id=?');
        $stmt->bind_param('siisi', $time, $minutes, $breaks, $now, $id);
        $stmt->execute();
        $stmt->close();
        $uuid = bin2hex(random_bytes(16));
        $uuid = substr($uuid,0,8) . '-' . substr($uuid,8,4) . '-' . substr($uuid,12,4) . '-' . substr($uuid,16,4) . '-' . substr($uuid,20);
        $notes = 'Closed by ' . $by . ($note !== '' ? ': ' . $note : '');
        $stmt = $db->prepare("INSERT INTO attendance_punches (attendance_id,employee_id,uuid,type,punched_at,source,notes,created_at) VALUES (?,?,?,'time_out',?,'admin',?,?)");
        $stmt->bind_param('isssss', $id, $idno, $uuid, $time, $notes, $now);
        $stmt->execute();
        $punchId = (int) $db->insert_id;
        $stmt->close();
        $header = $db->query("SELECT * FROM attendance WHERE id={$id}")->fetch_assoc();
        $punch = $db->query("SELECT * FROM attendance_punches WHERE id={$punchId}")->fetch_assoc();
        $db->commit();
        return ['attendance' => $header, 'punch' => $punch];
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}

function portal_shift_punches(mysqli $db, int $attendanceId): array
{
    $stmt = $db->prepare('SELECT * FROM attendance_punches WHERE attendance_id=? ORDER BY punched_at,id');
    $stmt->bind_param('i', $attendanceId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function portal_compute_work_minutes(string $timeIn, string $timeOut, int $breakMinutes): int
{
    $start = new DateTimeImmutable($timeIn, portal_tz());
    $end = new DateTimeImmutable($timeOut, portal_tz());
    return max(0, (int) floor(($end->getTimestamp() - $start->getTimestamp()) / 60) - $breakMinutes);
}

function portal_break_kinds(): array
{
    return ['15_min', '5_min', 'lunch_60', 'bio', 'phone', 'coaching', 'huddle', 'training'];
}

function portal_expected_end_at(?string $kind, string $startedAt): ?string
{
    $minutes = match ($kind) {
        '15_min' => 15,
        'lunch_60' => 60,
        default => null,
    };
    return $minutes === null ? null : (new DateTimeImmutable($startedAt, portal_tz()))->modify("+{$minutes} minutes")->format('Y-m-d H:i:s');
}

function portal_open_break(mysqli $db, string $idno): ?array
{
    $shift = portal_open_shift($db, $idno);
    if ($shift === null) {
        return null;
    }
    $punches = portal_shift_punches($db, (int) $shift['id']);
    $latest = $punches === [] ? null : $punches[count($punches) - 1];
    return $latest !== null && $latest['type'] === 'break_in' ? $latest : null;
}

function portal_punch_dto(array $punch, ?array $header, ?array $branch, array $flags = []): array
{
    return [
        'id' => (int) $punch['id'], 'uuid' => $punch['uuid'], 'type' => $punch['type'],
        'break_kind' => $punch['break_kind'], 'expected_end_at' => portal_iso($punch['expected_end_at']),
        'timestamp' => portal_iso($punch['punched_at']), 'synced_at' => portal_iso($punch['punched_at']),
        'is_offline' => false, 'is_late' => (bool) ($header['is_late'] ?? false),
        'is_early_timeout' => (bool) ($header['is_early_timeout'] ?? false),
        'work_minutes' => isset($header['work_minutes']) ? (int) $header['work_minutes'] : null,
        'break_minutes' => isset($punch['break_minutes']) ? (int) $punch['break_minutes'] : null,
        'is_overbreak' => (bool) $punch['is_overbreak'], 'source' => $punch['source'], 'notes' => $punch['notes'],
        'branch' => $branch === null ? null : ['id' => (int) $branch['id'], 'name' => $branch['name']],
        'gps_location' => [
            'latitude' => isset($punch['latitude']) ? (float) $punch['latitude'] : null,
            'longitude' => isset($punch['longitude']) ? (float) $punch['longitude'] : null,
            'accuracy_meters' => isset($punch['gps_accuracy_meters']) ? (float) $punch['gps_accuracy_meters'] : null,
            'distance_from_branch_meters' => isset($punch['distance_from_branch_meters']) ? (float) $punch['distance_from_branch_meters'] : null,
            'is_within_radius' => isset($punch['is_within_radius']) ? (bool) $punch['is_within_radius'] : null,
            'verified_against_type' => $punch['verified_against_type'],
            'verified_against_id' => isset($punch['verified_against_id']) ? (int) $punch['verified_against_id'] : null,
        ],
        'photo' => !empty($punch['photo_path']) ? ['path' => portal_photo_url($punch['uuid'])] : null,
        'fraud_flags' => $flags,
    ];
}

function portal_stored_punch_dto(mysqli $db, array $punch): array
{
    $id = (int) $punch['attendance_id'];
    $stmt = $db->prepare('SELECT * FROM attendance WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $header = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $branch = null;
    if (isset($header['branch_id'])) {
        $branchId = (int) $header['branch_id'];
        $stmt = $db->prepare('SELECT * FROM branches WHERE id=?');
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $branch = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
    $punchId = (int) $punch['id'];
    $stmt = $db->prepare('SELECT type,severity FROM fraud_flags WHERE punch_id=? ORDER BY id');
    $stmt->bind_param('i', $punchId);
    $stmt->execute();
    $flags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return portal_punch_dto($punch, $header, $branch, $flags);
}

function portal_punch_session(mysqli $db, string $idno): array
{
    $shift = portal_open_shift($db, $idno);
    if ($shift === null) {
        return ['open' => false, 'on_break' => false, 'time_in' => null, 'break' => null];
    }
    $punches = portal_shift_punches($db, (int) $shift['id']);
    $in = null;
    foreach ($punches as $punch) {
        if ($punch['type'] === 'time_in') {
            $in = portal_stored_punch_dto($db, $punch);
            break;
        }
    }
    $latest = $punches === [] ? null : $punches[count($punches) - 1];
    $onBreak = $latest !== null && $latest['type'] === 'break_in';
    return [
        'open' => true, 'on_break' => $onBreak, 'time_in' => $in,
        'break' => $onBreak ? portal_stored_punch_dto($db, $latest) : null,
    ];
}

function portal_work_punches(mysqli $db, string $idno): array
{
    $shift = portal_open_shift($db, $idno);
    if ($shift === null) {
        $stmt = $db->prepare('SELECT * FROM attendance WHERE employee_id=? ORDER BY time_in DESC,id DESC LIMIT 1');
        $stmt->bind_param('s', $idno);
        $stmt->execute();
        $shift = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$shift) {
        return [];
    }
    return array_map(
        static fn (array $punch): array => portal_stored_punch_dto($db, $punch),
        portal_shift_punches($db, (int) $shift['id'])
    );
}

function portal_attendance_conflict(mysqli $db, string $idno, string $message): never
{
    throw new PortalApiException($message, 'attendance_conflict', 409, [], portal_punch_session($db, $idno));
}

function portal_punch(mysqli $db, string $idno, string $type, array $input, ?array $selfie): array
{
    $writtenPhoto = null;
    $db->begin_transaction();
    try {
        // Serialize punches per employee, even when the new hire has no profile yet.
        $stmt = $db->prepare('SELECT idno FROM dtr_employee_profile WHERE idno=? FOR UPDATE');
        $stmt->bind_param('s', $idno);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$profile) {
            $stmt = $db->prepare('SELECT idno FROM employeefiletbl WHERE idno=? FOR UPDATE');
            $stmt->bind_param('s', $idno);
            $stmt->execute();
            $stmt->get_result();
            $stmt->close();
        }
        $uuid = (string) $input['client_uuid'];
        $stmt = $db->prepare('SELECT * FROM attendance_punches WHERE uuid=? LIMIT 1');
        $stmt->bind_param('s', $uuid);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($existing) {
            if ((string) $existing['employee_id'] !== $idno) {
                portal_attendance_conflict($db, $idno, 'This punch reference is already in use.');
            }
            $dto = portal_stored_punch_dto($db, $existing);
            $db->commit();
            return $dto;
        }
        portal_assert_punch_consent($db, $idno);
        $shift = portal_open_shift($db, $idno);
        if ($type === 'time_in' && $shift !== null) {
            portal_attendance_conflict($db, $idno, 'You are already clocked in.');
        }
        if ($type === 'time_out' && $shift === null) {
            portal_attendance_conflict($db, $idno, 'You have not clocked in yet today.');
        }
        $isBreak = in_array($type, ['break_in', 'break_out'], true);
        if ($isBreak && !portal_settings($db)['breaks_enabled']) {
            throw new PortalApiException('Break in/out is currently disabled by an administrator.', 'breaks_disabled', 422);
        }
        if ($isBreak && $shift === null) {
            portal_attendance_conflict($db, $idno, 'You have not clocked in yet today.');
        }
        $openBreak = portal_open_break($db, $idno);
        if ($type === 'break_in' && $openBreak !== null) {
            portal_attendance_conflict($db, $idno, 'You are already on break.');
        }
        if ($type === 'break_out' && $openBreak === null) {
            portal_attendance_conflict($db, $idno, 'You are not on break.');
        }
        if ($type === 'time_out' && $openBreak !== null) {
            portal_attendance_conflict($db, $idno, 'End your break first before clocking out.');
        }
        $breakKind = null;
        if ($type === 'break_in') {
            $breakKind = $input['break_kind'] ?? null;
            if (!in_array($breakKind, portal_break_kinds(), true)) {
                throw new PortalApiException('Select a valid break kind.', 'invalid_break_kind', 422);
            }
        }
        $latitude = isset($input['latitude']) ? (float) $input['latitude'] : null;
        $longitude = isset($input['longitude']) ? (float) $input['longitude'] : null;
        $accuracy = isset($input['accuracy_meters']) ? (float) $input['accuracy_meters'] : null;
        $location = portal_resolve_location($db, $idno, $latitude, $longitude, $accuracy);
        $stmt = $db->prepare('SELECT * FROM attendance_punches WHERE employee_id=? AND latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY punched_at DESC,id DESC LIMIT 1');
        $stmt->bind_param('s', $idno);
        $stmt->execute();
        $previousGps = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        $now = portal_now()->format('Y-m-d H:i:s');
        $source = $input['source'] ?? 'app';
        $notes = $input['notes'] ?? null;
        $expectedEnd = $type === 'break_in' ? portal_expected_end_at($breakKind, $now) : null;
        $breakMinutes = null;
        $overbreak = 0;
        if ($type === 'time_in') {
            $date = portal_now()->format('Y-m-d');
            $branchId = $location['branch_id'];
            $stmt = $db->prepare('INSERT INTO attendance (employee_id,branch_id,work_date,time_in,source,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->bind_param('sissssss', $idno, $branchId, $date, $now, $source, $notes, $now, $now);
            $stmt->execute();
            $attendanceId = (int) $db->insert_id;
            $stmt->close();
        } elseif ($type === 'time_out') {
            $attendanceId = (int) $shift['id'];
            $stmt = $db->prepare("SELECT COALESCE(SUM(break_minutes),0) total FROM attendance_punches WHERE attendance_id=? AND type='break_out'");
            $stmt->bind_param('i', $attendanceId);
            $stmt->execute();
            $totalBreak = (int) $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            $minutes = portal_compute_work_minutes($shift['time_in'], $now, $totalBreak);
            $stmt = $db->prepare('UPDATE attendance SET time_out=?,work_minutes=?,updated_at=? WHERE id=?');
            $stmt->bind_param('sisi', $now, $minutes, $now, $attendanceId);
            $stmt->execute();
            $stmt->close();
        } else {
            $attendanceId = (int) $shift['id'];
            if ($type === 'break_out') {
                $breakKind = $openBreak['break_kind'];
                $expectedEnd = $openBreak['expected_end_at'];
                $breakMinutes = portal_compute_work_minutes($openBreak['punched_at'], $now, 0);
                $overbreak = (int) ($breakMinutes > 60);
                $stmt = $db->prepare('UPDATE attendance SET break_minutes=break_minutes+?,is_overbreak=GREATEST(is_overbreak,?),updated_at=? WHERE id=?');
                $stmt->bind_param('iisi', $breakMinutes, $overbreak, $now, $attendanceId);
                $stmt->execute();
                $stmt->close();
            }
        }
        $distance = $location['distance_meters'];
        $within = (int) $location['is_within_radius'];
        $targetType = $location['verified_against_type'];
        $targetId = $location['verified_against_id'];
        $stmt = $db->prepare('INSERT INTO attendance_punches
            (attendance_id,employee_id,uuid,type,punched_at,latitude,longitude,gps_accuracy_meters,
            distance_from_branch_meters,is_within_radius,verified_against_type,verified_against_id,source,notes,created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('issssddddisisss', $attendanceId, $idno, $uuid, $type, $now, $latitude, $longitude, $accuracy, $distance, $within, $targetType, $targetId, $source, $notes, $now);
        $stmt->execute();
        $punchId = (int) $db->insert_id;
        $stmt->close();
        if ($isBreak) {
            $stmt = $db->prepare('UPDATE attendance_punches SET break_kind=?,expected_end_at=?,break_minutes=?,is_overbreak=? WHERE id=?');
            $stmt->bind_param('ssiii', $breakKind, $expectedEnd, $breakMinutes, $overbreak, $punchId);
            $stmt->execute();
            $stmt->close();
        }
        if ($selfie !== null) {
            $relative = portal_store_selfie($selfie, portal_config()['uploads'] . '/attendance', $uuid);
            $writtenPhoto = portal_config()['uploads'] . '/' . $relative;
            $stmt = $db->prepare('UPDATE attendance_punches SET photo_path=? WHERE id=?');
            $stmt->bind_param('si', $relative, $punchId);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $db->prepare('SELECT * FROM attendance_punches WHERE id=?');
        $stmt->bind_param('i', $punchId);
        $stmt->execute();
        $punch = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        portal_evaluate_fraud($db, $punch, $previousGps, $now);
        $dto = portal_stored_punch_dto($db, $punch);
        $db->commit();
        return $dto;
    } catch (Throwable $e) {
        $db->rollback();
        if ($writtenPhoto !== null && is_file($writtenPhoto)) {
            unlink($writtenPhoto);
        }
        throw $e;
    }
}
