<?php
declare(strict_types=1);

function portal_distance_meters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;
    return 6371000.0 * 2 * atan2(sqrt(max(0, min(1, $a))), sqrt(max(0, 1 - $a)));
}

function portal_verify_coordinates(
    ?float $targetLat, ?float $targetLng, float $radiusMeters,
    ?float $latitude, ?float $longitude, ?float $accuracyMeters, float $accuracyAllowanceMeters
): array {
    if ($targetLat === null || $targetLng === null || $latitude === null || $longitude === null) {
        return ['distance_meters' => null, 'is_within_radius' => false, 'accuracy_meters' => $accuracyMeters, 'reason' => 'missing_coordinates'];
    }
    $distance = portal_distance_meters($targetLat, $targetLng, $latitude, $longitude);
    $allowance = min(max(0, $accuracyMeters ?? 0), max(0, $accuracyAllowanceMeters));
    $within = $distance <= $radiusMeters + $allowance;
    return [
        'distance_meters' => round($distance, 2), 'is_within_radius' => $within,
        'accuracy_meters' => $accuracyMeters, 'reason' => $within ? null : 'out_of_range',
    ];
}

function portal_resolve_location(mysqli $db, string $idno, ?float $latitude, ?float $longitude, ?float $accuracy): array
{
    $stmt = $db->prepare('SELECT p.work_arrangement, b.* FROM dtr_employee_profile p LEFT JOIN branches b ON b.id=p.branch_id WHERE p.idno=? LIMIT 1');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $branch = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    $arrangement = $branch['work_arrangement'] ?? 'onsite';
    $ceiling = (float) ($branch['accuracy_ceiling_meters'] ?? portal_config()['branch']['accuracy_ceiling_meters']);
    $allowance = (float) ($branch['accuracy_allowance_meters'] ?? 0);
    if ($accuracy !== null && $accuracy > $ceiling) {
        throw new PortalApiException('GPS signal is too weak to verify this punch. Move near a window or outdoors and try again.', 'gps_accuracy_too_poor', 422);
    }
    $summary = portal_home_location_summary($db, $idno);
    $home = $summary['home_location'];
    if (in_array($arrangement, ['wfh', 'hybrid'], true) && $home === null) {
        if ($summary['status'] === 'pending') {
            throw new PortalApiException('Your home location is pending HR approval.', 'home_location_pending', 422);
        }
        throw new PortalApiException('Set and get approval for your home location before clocking in.', 'home_location_required', 422);
    }
    $candidates = [];
    if ($arrangement !== 'wfh') {
        $candidates[] = portal_verify_coordinates(
            isset($branch['latitude']) ? (float) $branch['latitude'] : null,
            isset($branch['longitude']) ? (float) $branch['longitude'] : null,
            (float) ($branch['radius_meters'] ?? 0), $latitude, $longitude, $accuracy, $allowance
        ) + ['verified_against_type' => 'branch', 'verified_against_id' => isset($branch['id']) ? (int) $branch['id'] : null];
    }
    if ($arrangement !== 'onsite' && $home !== null) {
        $candidates[] = portal_verify_coordinates(
            (float) $home['latitude'], (float) $home['longitude'], (float) $home['radius_meters'],
            $latitude, $longitude, $accuracy, $allowance
        ) + ['verified_against_type' => 'home_location', 'verified_against_id' => (int) $home['id']];
    }
    usort($candidates, static fn (array $a, array $b): int => ($a['distance_meters'] ?? INF) <=> ($b['distance_meters'] ?? INF));
    foreach ($candidates as $candidate) {
        if ($candidate['is_within_radius']) {
            return $candidate + ['branch_id' => isset($branch['id']) ? (int) $branch['id'] : null];
        }
    }
    $target = $arrangement === 'hybrid' ? null : ($arrangement === 'wfh' ? 'home_location' : 'branch');
    $message = match ($arrangement) {
        'hybrid' => 'You are outside both your office and home allowed areas.',
        'wfh' => 'You are outside the allowed GPS radius for your approved home location.',
        default => 'You are outside the allowed GPS radius for your assigned branch.',
    };
    throw new PortalApiException($message, 'gps_out_of_range', 422, [
        'distance_meters' => $candidates[0]['distance_meters'] ?? null,
        'verified_against_type' => $target,
    ]);
}
