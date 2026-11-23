<?php
declare(strict_types=1);

function gps_test_home(mysqli $db, string $status, float $lat = 14.5995, float $lng = 120.9842): int
{
    $stmt = $db->prepare("INSERT INTO home_locations (idno,label,latitude,longitude,radius_meters,status,created_at,updated_at) VALUES ('1019','Home',?,?,200,?,NOW(),NOW())");
    $stmt->bind_param('dds', $lat, $lng, $status);
    $stmt->execute();
    return (int) $db->insert_id;
}

return [
    'same point is zero' => function (): void {
        portal_assert_same(0.0, portal_distance_meters(14.5, 121.0, 14.5, 121.0));
    },
    'one degree latitude is about 111.2 km' => function (): void {
        $d = portal_distance_meters(0, 0, 1, 0);
        portal_assert_true($d > 111100 && $d < 111300);
    },
    'distance is symmetric' => function (): void {
        portal_assert_same(portal_distance_meters(14, 120, 15, 121), portal_distance_meters(15, 121, 14, 120));
    },
    'accuracy allowance is capped' => function (): void {
        $lat = rad2deg(219.99 / 6371000);
        foreach ([30.0, 300.0] as $accuracy) {
            portal_assert_true(portal_verify_coordinates(0, 0, 200, $lat, 0, $accuracy, 20)['is_within_radius']);
            portal_assert_true(!portal_verify_coordinates(0, 0, 200, rad2deg(221 / 6371000), 0, $accuracy, 20)['is_within_radius']);
        }
        portal_assert_true(!portal_verify_coordinates(0, 0, 200, $lat, 0, null, 20)['is_within_radius']);
    },
    'boundary is inclusive' => function (): void {
        $lat = rad2deg(200 / 6371000);
        $distance = portal_distance_meters(0, 0, $lat, 0);
        portal_assert_true(portal_verify_coordinates(0, 0, $distance, $lat, 0, null, 0)['is_within_radius']);
    },
    'missing coordinates fail verification' => function (): void {
        $result = portal_verify_coordinates(null, 0, 200, 0, 0, null, 0);
        portal_assert_null($result['distance_meters']);
        portal_assert_same(false, $result['is_within_radius']);
    },
    'onsite outside returns gps_out_of_range' => function (): void {
        $e = portal_assert_api_error(fn () => portal_resolve_location(portal_test_db(), '1015', 15, 121, 10), 'gps_out_of_range', 422);
        portal_assert_true($e->details['distance_meters'] > 200);
        portal_assert_same('branch', $e->details['verified_against_type']);
    },
    'poor accuracy rejects before distance' => function (): void {
        portal_assert_api_error(fn () => portal_resolve_location(portal_test_db(), '1015', 15, 121, 101), 'gps_accuracy_too_poor', 422);
    },
    'wfh without home pin asks for one' => function (): void {
        portal_assert_api_error(fn () => portal_resolve_location(portal_test_db(), '1019', 14.5995, 120.9842, 10), 'home_location_required', 422);
    },
    'wfh with pending pin says pending' => function (): void {
        gps_test_home(portal_test_db(), 'pending');
        portal_assert_api_error(fn () => portal_resolve_location(portal_test_db(), '1019', 14.5995, 120.9842, 10), 'home_location_pending', 422);
    },
    'wfh inside approved pin passes' => function (): void {
        $id = gps_test_home(portal_test_db(), 'approved');
        $result = portal_resolve_location(portal_test_db(), '1019', 14.5995, 120.9842, 10);
        portal_assert_same('home_location', $result['verified_against_type']);
        portal_assert_same($id, $result['verified_against_id']);
        portal_assert_true($result['is_within_radius']);
    },
    'hybrid picks the nearest passing geofence' => function (): void {
        $db = portal_test_db();
        $db->query("UPDATE dtr_employee_profile SET work_arrangement='hybrid' WHERE idno='1019'");
        gps_test_home($db, 'approved', 14.6005);
        portal_assert_same('home_location', portal_resolve_location($db, '1019', 14.6004, 120.9842, 10)['verified_against_type']);
        portal_assert_same('branch', portal_resolve_location($db, '1019', 14.5980, 120.9842, 10)['verified_against_type']);
    },
    'hybrid outside both reports null target' => function (): void {
        $db = portal_test_db();
        $db->query("UPDATE dtr_employee_profile SET work_arrangement='hybrid' WHERE idno='1019'");
        gps_test_home($db, 'approved');
        $e = portal_assert_api_error(fn () => portal_resolve_location($db, '1019', 15, 121, 10), 'gps_out_of_range', 422);
        portal_assert_null($e->details['verified_against_type']);
    },
    'inactive branch is not a punch target' => function (): void {
        $db = portal_test_db();
        $db->query('UPDATE branches SET is_active = 0 WHERE id = 1');

        $e = portal_assert_api_error(
            fn () => portal_resolve_location($db, '1015', 14.5995, 120.9842, 10),
            'gps_out_of_range',
            422
        );

        portal_assert_same('branch', $e->details['verified_against_type']);
        portal_assert_null($e->details['distance_meters']);
    },
];
