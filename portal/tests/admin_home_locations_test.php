<?php
declare(strict_types=1);

function admin_home_auth(string $usertype = 'admin'): array
{
    return ['idno' => '1015', 'usertype' => $usertype, 'employeename' => 'SICANGCO, JIRIEL L.'];
}

function admin_home_pin(
    mysqli $db,
    string $idno,
    string $status,
    string $label = 'Home',
    string $street = '123 Test St',
    string $address = '123 Test St, Manila'
): int {
    $stmt = $db->prepare(
        'INSERT INTO home_locations
            (idno, label, latitude, longitude, radius_meters, address_text, street, city, province,
             status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $latitude = 14.6;
    $longitude = 121.0;
    $radius = 200;
    $city = 'Manila';
    $province = 'Metro Manila';
    $stmt->bind_param(
        'ssddisssss',
        $idno,
        $label,
        $latitude,
        $longitude,
        $radius,
        $address,
        $street,
        $city,
        $province,
        $status
    );
    $stmt->execute();
    $id = (int) $db->insert_id;
    $stmt->close();

    return $id;
}

return [
    'home list defaults to pending' => function (): void {
        $db = portal_test_db();
        $pending = admin_home_pin($db, '1019', 'pending', 'Pending Home');
        admin_home_pin($db, '1016', 'approved', 'Approved Home');

        $result = portal_admin_home_locations_index($db, admin_home_auth(), []);

        portal_assert_same(1, count($result['data']), 'pending rows');
        portal_assert_same($pending, $result['data'][0]['id'], 'pending pin id');
        portal_assert_same('1019', $result['data'][0]['employee']['idno'], 'employee idno');
        portal_assert_same('pending', $result['data'][0]['status'], 'status');
        portal_assert_same(1, $result['meta']['counts']['pending'], 'pending count');
        portal_assert_same(1, $result['meta']['counts']['approved'], 'approved count');
        portal_assert_same(0, $result['meta']['counts']['rejected'], 'rejected count');
    },

    'home list filters status' => function (): void {
        $db = portal_test_db();
        admin_home_pin($db, '1019', 'pending');
        $approved = admin_home_pin($db, '1016', 'approved');

        $result = portal_admin_home_locations_index($db, admin_home_auth(), ['status' => 'approved']);

        portal_assert_same(1, count($result['data']), 'approved rows');
        portal_assert_same($approved, $result['data'][0]['id'], 'approved id');
    },

    'home list counts statuses' => function (): void {
        $db = portal_test_db();
        admin_home_pin($db, '1015', 'pending');
        admin_home_pin($db, '1016', 'approved');
        admin_home_pin($db, '1017', 'rejected');

        $result = portal_admin_home_locations_index($db, admin_home_auth(), ['status' => 'all']);

        portal_assert_same(3, count($result['data']), 'all rows');
        portal_assert_same(['pending' => 1, 'approved' => 1, 'rejected' => 1], $result['meta']['counts'], 'counts');
    },

    'home search matches employee and address' => function (): void {
        $db = portal_test_db();
        admin_home_pin($db, '1016', 'pending', 'Home', 'Acacia Lane');
        admin_home_pin($db, '1017', 'pending', 'Home', 'Banaba Street');

        $auth = admin_home_auth();
        $byEmployee = portal_admin_home_locations_index($db, $auth, ['search' => 'JIREL']);
        portal_assert_same(1, count($byEmployee['data']), 'employee search');
        portal_assert_same('1016', $byEmployee['data'][0]['employee']['idno'], 'employee match');

        $byAddress = portal_admin_home_locations_index($db, $auth, ['search' => 'Acacia']);
        portal_assert_same(1, count($byAddress['data']), 'address search');
        portal_assert_same('1016', $byAddress['data'][0]['employee']['idno'], 'address match');
    },

    'home search treats percent literally' => function (): void {
        $db = portal_test_db();
        $withPercent = admin_home_pin($db, '1016', 'pending', 'Home', '100% Street', '100% Street');
        admin_home_pin($db, '1017', 'pending', 'Home', 'Plain Street', 'Plain Street');

        $result = portal_admin_home_locations_index($db, admin_home_auth(), ['search' => '%']);

        portal_assert_same(1, count($result['data']), 'percent must match only the literal percent');
        portal_assert_same($withPercent, $result['data'][0]['id'], 'literal percent row');
    },

    'home list paginates' => function (): void {
        $db = portal_test_db();
        admin_home_pin($db, '1016', 'pending');
        admin_home_pin($db, '1017', 'pending');

        $result = portal_admin_home_locations_index(
            $db,
            admin_home_auth(),
            ['page' => 2, 'per_page' => 1]
        );

        portal_assert_same(2, $result['meta']['total'], 'total');
        portal_assert_same(2, $result['meta']['last_page'], 'last page');
        portal_assert_same(2, $result['meta']['current_page'], 'current page');
        portal_assert_same(1, count($result['data']), 'page rows');
    },

    'home detail includes current approved pin' => function (): void {
        $db = portal_test_db();
        $approved = admin_home_pin($db, '1019', 'approved', 'Old Home');
        $pending = admin_home_pin($db, '1019', 'pending', 'New Home');

        $result = portal_admin_home_locations_show($db, admin_home_auth(), $pending);

        portal_assert_same($pending, $result['data']['id'], 'pin id');
        portal_assert_true($result['data']['current_approved'] !== null, 'current approved pin');
        portal_assert_same($approved, $result['data']['current_approved']['id'], 'approved pin id');
        portal_assert_same('Old Home', $result['data']['current_approved']['label'], 'approved label');
    },

    'home detail returns 404 for unknown id' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_home_locations_show(portal_test_db(), admin_home_auth(), 99999),
            'not_found',
            404
        );
    },

    'employee role cannot list home locations' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_home_locations_index(portal_test_db(), admin_home_auth(''), []),
            'forbidden',
            403
        );
    },

    'approver can list home locations' => function (): void {
        $db = portal_test_db();
        admin_home_pin($db, '1019', 'pending');

        $result = portal_admin_home_locations_index($db, admin_home_auth('approver'), []);

        portal_assert_same(1, count($result['data']), 'approver can review');
    },
];
