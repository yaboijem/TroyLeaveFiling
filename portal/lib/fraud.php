<?php
declare(strict_types=1);

function portal_evaluate_fraud(mysqli $db, array $punch, ?array $previousGps, string $punchedAt): array
{
    $candidates = [];
    if (isset($punch['is_within_radius']) && (int) $punch['is_within_radius'] === 0) {
        $candidates['out_of_radius'] = ['medium', []];
    }
    if ($previousGps && isset($previousGps['latitude'], $previousGps['longitude'], $punch['latitude'], $punch['longitude'])) {
        $seconds = (new DateTimeImmutable($punchedAt, portal_tz()))->getTimestamp()
            - (new DateTimeImmutable($previousGps['punched_at'], portal_tz()))->getTimestamp();
        if ($seconds > 0) {
            $speed = portal_distance_meters((float) $previousGps['latitude'], (float) $previousGps['longitude'], (float) $punch['latitude'], (float) $punch['longitude']) / $seconds * 3.6;
            if ($speed > 120) $candidates['impossible_jump'] = ['high', ['estimated_speed_kmh' => round($speed, 2)]];
        }
    }
    $cutoff = (new DateTimeImmutable($punchedAt, portal_tz()))->modify('-1 minute')->format('Y-m-d H:i:s');
    $stmt = $db->prepare('SELECT id FROM attendance_punches WHERE employee_id=? AND type=? AND id<>? AND punched_at>=? AND punched_at<=? LIMIT 1');
    $stmt->bind_param('ssiss', $punch['employee_id'], $punch['type'], $punch['id'], $cutoff, $punchedAt);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) $candidates['rapid_clock'] = ['medium', []];
    $stmt->close();
    $flags = [];
    foreach ($candidates as $type => [$severity, $details]) {
        $json = json_encode($details, JSON_THROW_ON_ERROR);
        $stmt = $db->prepare('INSERT IGNORE INTO fraud_flags (attendance_id,punch_id,type,severity,details,created_at) VALUES (?,?,?,?,?,?)');
        $stmt->bind_param('iissss', $punch['attendance_id'], $punch['id'], $type, $severity, $json, $punchedAt);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $flags[] = ['type' => $type, 'severity' => $severity];
            try {
                portal_notify($db, (string) $punch['employee_id'], 'Attendance needs review', 'An attendance check was flagged: ' . $type, ['type' => $type, 'punch_id' => (int) $punch['id']], "fraud:{$punch['id']}:{$type}");
            } catch (Throwable $e) { error_log('Portal fraud notification: ' . $e->getMessage()); }
        }
        $stmt->close();
    }
    return $flags;
}
