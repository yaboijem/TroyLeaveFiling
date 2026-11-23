<?php
declare(strict_types=1);

function admin_employee_auth(string $usertype = 'admin'): array
{
    return ['idno' => '1015', 'usertype' => $usertype, 'employeename' => 'SICANGCO, JIRIEL L.'];
}

function admin_employee_ids(array $result): array
{
    return array_map(static fn (array $row): string => (string) $row['idno'], $result['data']);
}

function admin_employee_pin(mysqli $db, string $idno, string $status): void
{
    $stmt = $db->prepare(
        "INSERT INTO home_locations (idno,label,latitude,longitude,radius_meters,status,created_at,updated_at)
         VALUES (?, 'Home', 14.5995, 120.9842, 200, ?, NOW(), NOW())"
    );
    $stmt->bind_param('ss', $idno, $status);
    $stmt->execute();
    $stmt->close();
}

return [
    'employee list defaults to active employees' => function (): void {
        $result = portal_admin_employees_index(portal_test_db(), admin_employee_auth(), []);

        portal_assert_same(5, $result['meta']['total'], 'active employee count');
        portal_assert_true(!in_array('1018', admin_employee_ids($result), true), 'inactive employee must be hidden');
        portal_assert_true(in_array('1020', admin_employee_ids($result), true), 'active employee without a profile must be listed');
    },

    'employee list includes inactive when asked' => function (): void {
        $result = portal_admin_employees_index(portal_test_db(), admin_employee_auth(), ['active' => '0']);

        portal_assert_same(['1018'], admin_employee_ids($result), 'inactive employees');
    },

    'employee search matches name email and idno' => function (): void {
        $db = portal_test_db();
        $auth = admin_employee_auth();

        portal_assert_same(['1018'], admin_employee_ids(portal_admin_employees_index($db, $auth, ['search' => 'ana', 'active' => 'all'])), 'name search');
        portal_assert_same(['1018'], admin_employee_ids(portal_admin_employees_index($db, $auth, ['search' => 'ana.lim@', 'active' => 'all'])), 'email search');
        portal_assert_same(['1019'], admin_employee_ids(portal_admin_employees_index($db, $auth, ['search' => '1019', 'active' => 'all'])), 'id search');
    },

    'employee search treats percent literally' => function (): void {
        $result = portal_admin_employees_index(
            portal_test_db(),
            admin_employee_auth(),
            ['search' => '%', 'active' => 'all']
        );

        portal_assert_same(0, $result['meta']['total'], 'a bare percent must not match everything');
    },

    'employee list filters by branch and unassigned' => function (): void {
        $db = portal_test_db();
        $auth = admin_employee_auth();

        $branch = portal_admin_employees_index($db, $auth, ['branch_id' => '1', 'active' => 'all']);
        portal_assert_same(5, $branch['meta']['total'], 'branch employee count');

        $unassigned = portal_admin_employees_index($db, $auth, ['branch_id' => 'unassigned', 'active' => 'all']);
        portal_assert_same(['1020'], admin_employee_ids($unassigned), 'unassigned employees');
    },

    'employee list filters by arrangement' => function (): void {
        $db = portal_test_db();
        $auth = admin_employee_auth();

        $onsite = portal_admin_employees_index($db, $auth, ['arrangement' => 'onsite', 'active' => 'all']);
        portal_assert_true(in_array('1020', admin_employee_ids($onsite), true), 'missing profiles count as onsite');
        portal_assert_true(!in_array('1019', admin_employee_ids($onsite), true), 'wfh must not be onsite');

        $wfh = portal_admin_employees_index($db, $auth, ['arrangement' => 'wfh', 'active' => 'all']);
        portal_assert_same(['1019'], admin_employee_ids($wfh), 'wfh employees');
    },

    'employee list filters by home pin status' => function (): void {
        $db = portal_test_db();
        admin_employee_pin($db, '1016', 'approved');
        admin_employee_pin($db, '1019', 'pending');

        $auth = admin_employee_auth();
        portal_assert_same(['1016'], admin_employee_ids(portal_admin_employees_index($db, $auth, ['home_location_status' => 'approved', 'active' => 'all'])), 'approved');
        portal_assert_same(['1019'], admin_employee_ids(portal_admin_employees_index($db, $auth, ['home_location_status' => 'pending', 'active' => 'all'])), 'pending');

        $none = portal_admin_employees_index($db, $auth, ['home_location_status' => 'none', 'active' => 'all']);
        portal_assert_same(4, $none['meta']['total'], 'no-pin employees');
        portal_assert_true(!in_array('1016', admin_employee_ids($none), true), 'approved pin must not be none');
        portal_assert_true(!in_array('1019', admin_employee_ids($none), true), 'pending pin must not be none');
    },

    'employee list paginates' => function (): void {
        $result = portal_admin_employees_index(
            portal_test_db(),
            admin_employee_auth(),
            ['page' => 2, 'per_page' => 2, 'active' => 'all']
        );

        portal_assert_same(6, $result['meta']['total'], 'total');
        portal_assert_same(3, $result['meta']['last_page'], 'last page');
        portal_assert_same(2, $result['meta']['current_page'], 'current page');
        portal_assert_same(3, $result['meta']['from'], 'from');
        portal_assert_same(4, $result['meta']['to'], 'to');
        portal_assert_same(2, count($result['data']), 'page rows');
    },

    'employee dto exposes effective onsite for missing profile' => function (): void {
        $result = portal_admin_employees_index(
            portal_test_db(),
            admin_employee_auth(),
            ['search' => '1020']
        );

        $row = $result['data'][0];
        portal_assert_same(false, $row['has_profile'], 'has_profile');
        portal_assert_same('onsite', $row['work_arrangement'], 'effective arrangement');
        portal_assert_null($row['branch'], 'branch');
        portal_assert_same('none', $row['home_location_status'], 'home status');
    },

    'approver cannot list employees' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_employees_index(portal_test_db(), admin_employee_auth('approver'), []),
            'forbidden',
            403
        );
    },
];
