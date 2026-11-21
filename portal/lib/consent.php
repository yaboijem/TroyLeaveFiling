<?php
declare(strict_types=1);

function portal_has_consent(mysqli $db, string $idno, string $type): bool
{
    $stmt = $db->prepare('SELECT granted FROM consents WHERE idno=? AND type=? LIMIT 1');
    $stmt->bind_param('ss', $idno, $type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool) ($row['granted'] ?? false);
}

function portal_consent_summary(mysqli $db, string $idno): array
{
    $summary = ['biometric_photos' => false, 'gps_location' => false, 'device_alerts' => false];

    $stmt = $db->prepare('SELECT type, granted FROM consents WHERE idno=?');
    $stmt->bind_param('s', $idno);
    $stmt->execute();

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        if (array_key_exists($row['type'], $summary)) {
            $summary[$row['type']] = (bool) $row['granted'];
        }
    }

    $stmt->close();

    return $summary;
}

function portal_assert_punch_consent(mysqli $db, string $idno): void
{
    foreach (['gps_location' => 'gps_consent_required', 'biometric_photos' => 'biometric_consent_required'] as $type => $code) {
        if (!portal_has_consent($db, $idno, $type)) {
            throw new PortalApiException(
                $type === 'gps_location' ? 'Grant GPS location consent before punching.' : 'Grant biometric photo consent before punching.',
                $code, 403
            );
        }
    }
}

function portal_consent_dto(array $row): array
{
    return [
        'id' => (int) $row['id'], 'type' => $row['type'], 'granted' => (bool) $row['granted'],
        'granted_at' => portal_iso($row['granted_at']), 'revoked_at' => portal_iso($row['revoked_at']),
        'updated_at' => portal_iso($row['updated_at']),
    ];
}

function portal_consent_list(mysqli $db, string $idno): array
{
    $stmt = $db->prepare('SELECT * FROM consents WHERE idno=? ORDER BY id');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $data = array_map('portal_consent_dto', $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    return [
        'data' => $data, 'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => max(1, count($data)), 'total' => count($data)],
    ];
}

function portal_consent_upsert(mysqli $db, string $idno, string $type, bool $granted): array
{
    if (!in_array($type, ['biometric_photos', 'gps_location', 'device_alerts'], true)) {
        throw new PortalApiException('The given data was invalid.', 'validation_error', 422, ['errors' => ['type' => ['Select a valid consent type.']]]);
    }
    $now = portal_now()->format('Y-m-d H:i:s');
    $grantTime = $granted ? $now : null;
    $revokeTime = $granted ? null : $now;
    $value = (int) $granted;
    $stmt = $db->prepare('INSERT INTO consents (idno,type,granted,granted_at,revoked_at,updated_at)
        VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE granted=VALUES(granted),
        granted_at=IF(VALUES(granted)=1,VALUES(granted_at),granted_at),
        revoked_at=VALUES(revoked_at),updated_at=VALUES(updated_at)');
    $stmt->bind_param('ssisss', $idno, $type, $value, $grantTime, $revokeTime, $now);
    $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare('SELECT * FROM consents WHERE idno=? AND type=?');
    $stmt->bind_param('ss', $idno, $type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return portal_consent_dto($row);
}

function portal_handle_consent_index(mysqli $db, ?array $auth, array $params): array
{
    return portal_consent_list($db, (string) $auth['idno']);
}

function portal_handle_consent_store(mysqli $db, ?array $auth, array $params): array
{
    $input = $_POST ?: json_decode(file_get_contents('php://input') ?: '{}', true);
    if (!is_array($input) || !is_string($input['type'] ?? null) || !is_bool($input['granted'] ?? null)) {
        throw new PortalApiException('The given data was invalid.', 'validation_error', 422, ['errors' => ['granted' => ['Provide a consent type and boolean granted value.']]]);
    }
    return ['data' => portal_consent_upsert($db, (string) $auth['idno'], $input['type'], $input['granted'])];
}
