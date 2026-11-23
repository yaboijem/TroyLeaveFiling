<?php
declare(strict_types=1);

function admin_branch_auth(string $usertype = 'admin'): array
{
    return ['idno' => '1015', 'usertype' => $usertype, 'employeename' => 'SICANGCO, JIRIEL L.'];
}

function admin_branch_payload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Marketing',
        'code' => 'MKT',
        'address' => '123 Example St',
        'latitude' => 14.55,
        'longitude' => 121.02,
        'radius_meters' => 300,
        'accuracy_ceiling_meters' => 100,
        'accuracy_allowance_meters' => 30,
        'is_active' => true,
    ], $overrides);
}

function admin_branch_find(array $branches, string $code): ?array
{
    foreach ($branches as $branch) {
        if ($branch['code'] === $code) {
            return $branch;
        }
    }

    return null;
}

return [
    'branch list returns usage counts' => function (): void {
        $result = portal_admin_branches_index(portal_test_db(), admin_branch_auth(), []);
        $main = admin_branch_find($result['data'], 'MAIN');

        portal_assert_true($main !== null, 'MAIN branch missing.');
        portal_assert_same(5, $main['employee_count'], 'employee count');
        portal_assert_same(0, $main['attendance_count'], 'attendance count');
        portal_assert_same(true, $main['is_active'], 'is_active');
    },

    'branch list filters active status' => function (): void {
        $db = portal_test_db();
        $db->query('UPDATE branches SET is_active = 0 WHERE id = 2');

        $inactive = portal_admin_branches_index($db, admin_branch_auth(), ['status' => 'inactive']);
        portal_assert_same(1, count($inactive['data']), 'inactive count');
        portal_assert_same('ANNEX', $inactive['data'][0]['code'], 'inactive branch');

        $active = portal_admin_branches_index($db, admin_branch_auth(), ['status' => 'active']);
        portal_assert_same(1, count($active['data']), 'active count');
        portal_assert_same('MAIN', $active['data'][0]['code'], 'active branch');
    },

    'branch list searches name and code' => function (): void {
        $db = portal_test_db();

        $byName = portal_admin_branches_index($db, admin_branch_auth(), ['search' => 'annex']);
        portal_assert_same(1, count($byName['data']), 'name search count');
        portal_assert_same('ANNEX', $byName['data'][0]['code'], 'name search');

        $byCode = portal_admin_branches_index($db, admin_branch_auth(), ['search' => 'MAI']);
        portal_assert_same(1, count($byCode['data']), 'code search count');
        portal_assert_same('MAIN', $byCode['data'][0]['code'], 'code search');
    },

    'branch search treats percent literally' => function (): void {
        $result = portal_admin_branches_index(portal_test_db(), admin_branch_auth(), ['search' => '%']);

        portal_assert_same(0, count($result['data']), 'a bare percent must not match everything');
    },

    'create branch returns dto' => function (): void {
        $result = portal_admin_branches_store(portal_test_db(), admin_branch_auth(), admin_branch_payload(['code' => 'mkt']));
        $branch = $result['data'];

        portal_assert_same('MKT', $branch['code'], 'code is uppercased');
        portal_assert_same('Marketing', $branch['name'], 'name');
        portal_assert_same(true, $branch['is_active'], 'is_active');
        portal_assert_same(0, $branch['employee_count'], 'employee count');
        portal_assert_same(0, $branch['attendance_count'], 'attendance count');
    },

    'create duplicate code is rejected' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_branches_store(portal_test_db(), admin_branch_auth(), admin_branch_payload(['code' => 'MAIN'])),
            'validation_error',
            422
        );

        portal_assert_true(isset($error->details['errors']['code']), 'Expected a code error.');
    },

    'create rejects out-of-range coordinates' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_branches_store(portal_test_db(), admin_branch_auth(), admin_branch_payload(['latitude' => 91])),
            'validation_error',
            422
        );

        portal_assert_true(isset($error->details['errors']['latitude']), 'Expected a latitude error.');
    },

    'update branch merges omitted fields' => function (): void {
        $result = portal_admin_branches_update(portal_test_db(), admin_branch_auth(), 1, ['name' => 'Head Office']);
        $branch = $result['data'];

        portal_assert_same('Head Office', $branch['name'], 'updated name');
        portal_assert_same('MAIN', $branch['code'], 'code preserved');
        portal_assert_same(14.5995, $branch['latitude'], 'latitude preserved');
        portal_assert_same(5, $branch['employee_count'], 'employee count preserved');
    },

    'update duplicate code is rejected' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_branches_update(portal_test_db(), admin_branch_auth(), 1, ['code' => 'ANNEX']),
            'validation_error',
            422
        );

        portal_assert_true(isset($error->details['errors']['code']), 'Expected a code error.');
    },

    'unknown branch update returns 404' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_branches_update(portal_test_db(), admin_branch_auth(), 99999, ['name' => 'Nope']),
            'not_found',
            404
        );
    },

    'approver cannot list branches' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_branches_index(portal_test_db(), admin_branch_auth('approver'), []),
            'forbidden',
            403
        );
    },

    'deactivate blocked while employees assigned' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_branches_deactivate(portal_test_db(), admin_branch_auth(), 1),
            'branch_has_employees',
            409
        );

        portal_assert_same(5, (int) ($error->details['employees'] ?? 0), 'employee count');
    },

    'deactivate succeeds at zero employees' => function (): void {
        $db = portal_test_db();
        $db->query('DELETE FROM dtr_employee_profile WHERE branch_id = 1');

        $result = portal_admin_branches_deactivate($db, admin_branch_auth(), 1);

        portal_assert_same(false, $result['data']['is_active'], 'branch must be inactive');
        portal_assert_same(0, $result['data']['employee_count'], 'employee count');
    },

    'activate restores branch' => function (): void {
        $db = portal_test_db();
        $db->query('UPDATE branches SET is_active = 0 WHERE id = 1');

        $result = portal_admin_branches_activate($db, admin_branch_auth(), 1);

        portal_assert_same(true, $result['data']['is_active'], 'branch must be active');
    },

    'delete blocked by employees' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_branches_delete(portal_test_db(), admin_branch_auth(), 1),
            'branch_in_use',
            409
        );

        portal_assert_same(5, (int) ($error->details['employees'] ?? 0), 'employee count');
        portal_assert_same(0, (int) ($error->details['attendance'] ?? 0), 'attendance count');
    },

    'delete blocked by attendance history' => function (): void {
        $db = portal_test_db();
        $db->query('DELETE FROM dtr_employee_profile WHERE branch_id = 1');
        $db->query(
            "INSERT INTO attendance
                (idno, branch_id, work_date, time_in, time_out, break_minutes, work_minutes,
                 is_late, is_early_timeout, is_overbreak, source, notes, created_at, updated_at)
             VALUES
                ('1015', 1, '2026-11-19', '2026-11-19 08:00:00', '2026-11-19 17:00:00',
                 0, 540, 0, 0, 0, 'app', NULL, NOW(), NOW())"
        );

        $error = portal_assert_api_error(
            fn () => portal_admin_branches_delete($db, admin_branch_auth(), 1),
            'branch_in_use',
            409
        );

        portal_assert_same(0, (int) ($error->details['employees'] ?? 0), 'employee count');
        portal_assert_same(1, (int) ($error->details['attendance'] ?? 0), 'attendance count');
    },

    'delete succeeds when unused' => function (): void {
        $db = portal_test_db();

        $result = portal_admin_branches_delete($db, admin_branch_auth(), 2);

        portal_assert_same(true, $result['data']['deleted'], 'deleted flag');
        portal_assert_same(0, (int) $db->query('SELECT COUNT(*) AS c FROM branches WHERE id = 2')->fetch_assoc()['c'], 'branch still exists');
    },

    'patch cannot deactivate with employees' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_branches_update(portal_test_db(), admin_branch_auth(), 1, ['is_active' => false]),
            'branch_has_employees',
            409
        );

        portal_assert_same(5, (int) ($error->details['employees'] ?? 0), 'employee count');
    },

    'unknown branch lifecycle returns 404' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_branches_deactivate(portal_test_db(), admin_branch_auth(), 99999),
            'not_found',
            404
        );
        portal_assert_api_error(
            fn () => portal_admin_branches_delete(portal_test_db(), admin_branch_auth(), 99999),
            'not_found',
            404
        );
    },

    'approver cannot deactivate or delete' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_branches_deactivate(portal_test_db(), admin_branch_auth('approver'), 1),
            'forbidden',
            403
        );
        portal_assert_api_error(
            fn () => portal_admin_branches_delete(portal_test_db(), admin_branch_auth('approver'), 1),
            'forbidden',
            403
        );
    },
];
