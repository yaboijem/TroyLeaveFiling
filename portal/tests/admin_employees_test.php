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

    'bulk branch only updates branch and keeps arrangement' => function (): void {
        $db = portal_test_db();
        $result = portal_admin_assignments_bulk($db, admin_employee_auth(), ['idnos' => ['1015'], 'branch_id' => 2]);

        portal_assert_same(1, $result['data']['updated'], 'updated');
        portal_assert_same(0, $result['data']['created'], 'created');
        portal_assert_same(0, $result['data']['skipped'], 'skipped');

        $profile = $db->query("SELECT * FROM dtr_employee_profile WHERE idno = '1015'")->fetch_assoc();
        portal_assert_same('2', (string) $profile['branch_id'], 'branch');
        portal_assert_same('onsite', $profile['work_arrangement'], 'arrangement');
    },

    'bulk arrangement only updates arrangement and keeps branch' => function (): void {
        $db = portal_test_db();
        $result = portal_admin_assignments_bulk($db, admin_employee_auth(), ['idnos' => ['1015'], 'work_arrangement' => 'wfh']);

        portal_assert_same(1, $result['data']['updated'], 'updated');

        $profile = $db->query("SELECT * FROM dtr_employee_profile WHERE idno = '1015'")->fetch_assoc();
        portal_assert_same('1', (string) $profile['branch_id'], 'branch');
        portal_assert_same('wfh', $profile['work_arrangement'], 'arrangement');
    },

    'bulk both updates both' => function (): void {
        $db = portal_test_db();
        portal_admin_assignments_bulk($db, admin_employee_auth(), [
            'idnos' => ['1015'],
            'branch_id' => 2,
            'work_arrangement' => 'hybrid',
        ]);

        $profile = $db->query("SELECT * FROM dtr_employee_profile WHERE idno = '1015'")->fetch_assoc();
        portal_assert_same('2', (string) $profile['branch_id'], 'branch');
        portal_assert_same('hybrid', $profile['work_arrangement'], 'arrangement');
    },

    'bulk rejects no changes' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_assignments_bulk(portal_test_db(), admin_employee_auth(), ['idnos' => ['1015']]),
            'validation_error',
            422
        );

        portal_assert_true(isset($error->details['errors']['assignment']), 'Expected an assignment error.');
    },

    'bulk rejects inactive branch' => function (): void {
        $db = portal_test_db();
        $db->query('UPDATE branches SET is_active = 0 WHERE id = 2');

        $error = portal_assert_api_error(
            fn () => portal_admin_assignments_bulk($db, admin_employee_auth(), ['idnos' => ['1015'], 'branch_id' => 2]),
            'validation_error',
            422
        );

        portal_assert_true(isset($error->details['errors']['branch_id']), 'Expected a branch error.');
    },

    'bulk creates missing profile with onsite default' => function (): void {
        $db = portal_test_db();
        $result = portal_admin_assignments_bulk($db, admin_employee_auth(), ['idnos' => ['1020'], 'branch_id' => 1]);

        portal_assert_same(1, $result['data']['created'], 'created');

        $profile = $db->query("SELECT * FROM dtr_employee_profile WHERE idno = '1020'")->fetch_assoc();
        portal_assert_same('1', (string) $profile['branch_id'], 'branch');
        portal_assert_same('onsite', $profile['work_arrangement'], 'default arrangement');
    },

    'bulk arrangement only creates profile with null branch' => function (): void {
        $db = portal_test_db();
        $result = portal_admin_assignments_bulk($db, admin_employee_auth(), ['idnos' => ['1020'], 'work_arrangement' => 'wfh']);

        portal_assert_same(1, $result['data']['created'], 'created');

        $profile = $db->query("SELECT * FROM dtr_employee_profile WHERE idno = '1020'")->fetch_assoc();
        portal_assert_null($profile['branch_id'], 'branch');
        portal_assert_same('wfh', $profile['work_arrangement'], 'arrangement');
    },

    'bulk skips inactive employees' => function (): void {
        $db = portal_test_db();
        $result = portal_admin_assignments_bulk($db, admin_employee_auth(), ['idnos' => ['1018', '1015'], 'branch_id' => 2]);

        portal_assert_same(1, $result['data']['updated'], 'updated');
        portal_assert_same(1, $result['data']['skipped'], 'skipped');

        $profile = $db->query("SELECT * FROM dtr_employee_profile WHERE idno = '1018'")->fetch_assoc();
        portal_assert_same('1', (string) $profile['branch_id'], 'inactive employee must be untouched');
    },

    'bulk skips unknown idnos' => function (): void {
        $result = portal_admin_assignments_bulk(
            portal_test_db(),
            admin_employee_auth(),
            ['idnos' => ['999999', '1015'], 'branch_id' => 2]
        );

        portal_assert_same(1, $result['data']['updated'], 'updated');
        portal_assert_same(1, $result['data']['skipped'], 'skipped');
    },

    'bulk warns when wfh has no approved pin' => function (): void {
        $result = portal_admin_assignments_bulk(
            portal_test_db(),
            admin_employee_auth(),
            ['idnos' => ['1019'], 'work_arrangement' => 'wfh']
        );

        portal_assert_same(1, $result['data']['warnings']['no_home_pin'], 'no_home_pin');
        portal_assert_same(0, $result['data']['warnings']['no_branch_for_onsite'], 'no_branch_for_onsite');
    },

    'bulk warns when onsite has no branch' => function (): void {
        $result = portal_admin_assignments_bulk(
            portal_test_db(),
            admin_employee_auth(),
            ['idnos' => ['1020'], 'work_arrangement' => 'onsite']
        );

        portal_assert_same(0, $result['data']['warnings']['no_home_pin'], 'no_home_pin');
        portal_assert_same(1, $result['data']['warnings']['no_branch_for_onsite'], 'no_branch_for_onsite');
    },

    'bulk handles 101 idnos in two chunks' => function (): void {
        $db = portal_test_db();

        $stmt = $db->prepare(
            "INSERT INTO employeefiletbl
                (idno, employeename, deptcode, divcode, position, employmentstatus, email,
                 active, usertype, password, session_token, last_login, imagesrc)
             VALUES (?, ?, 'ADMIN', 'HO', 'STAFF', 'Regular', ?, 'YES', '', ?, NULL, NULL, '')"
        );
        $password = password_hash('Password123!', PASSWORD_BCRYPT);

        for ($i = 0; $i < 101; $i++) {
            $idno = 'T' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $name = 'Bulk Test ' . $i;
            $email = 'bulk' . $i . '@example.test';
            $stmt->bind_param('ssss', $idno, $name, $email, $password);
            $stmt->execute();
        }
        $stmt->close();

        $idnos = [];
        for ($i = 0; $i < 101; $i++) {
            $idnos[] = 'T' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        }

        $result = portal_admin_assignments_bulk($db, admin_employee_auth(), ['idnos' => $idnos, 'branch_id' => 1]);

        portal_assert_same(101, $result['data']['created'], 'created');
        $count = (int) $db->query("SELECT COUNT(*) AS c FROM dtr_employee_profile WHERE idno LIKE 'T%'")->fetch_assoc()['c'];
        portal_assert_same(101, $count, 'profile count');
    },

    'bulk rejects more than 2000 idnos' => function (): void {
        $idnos = [];
        for ($i = 0; $i < 2001; $i++) {
            $idnos[] = 'X' . $i;
        }

        $error = portal_assert_api_error(
            fn () => portal_admin_assignments_bulk(portal_test_db(), admin_employee_auth(), ['idnos' => $idnos, 'branch_id' => 1]),
            'validation_error',
            422
        );

        portal_assert_true(isset($error->details['errors']['idnos']), 'Expected an idnos error.');
    },

    'preview returns counts and writes nothing' => function (): void {
        $db = portal_test_db();
        $result = portal_admin_assignments_bulk(
            $db,
            admin_employee_auth(),
            ['idnos' => ['1015'], 'branch_id' => 2],
            true
        );

        portal_assert_same(1, $result['data']['updated'], 'updated');
        $profile = $db->query("SELECT * FROM dtr_employee_profile WHERE idno = '1015'")->fetch_assoc();
        portal_assert_same('1', (string) $profile['branch_id'], 'preview must not write');
    },

    'approver cannot bulk assign' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_assignments_bulk(portal_test_db(), admin_employee_auth('approver'), ['idnos' => ['1015'], 'branch_id' => 2]),
            'forbidden',
            403
        );
    },
];
