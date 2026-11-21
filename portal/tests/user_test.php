<?php
declare(strict_types=1);

/**
 * Task 3 user DTO tests.
 */

return [
    'user dto shape' => function (): void {
        $dto = portal_user_dto(portal_test_db(), '1015');

        portal_assert_true(is_array($dto), 'Expected a user DTO.');
        portal_assert_same(1015, $dto['id'], 'id');
        portal_assert_same('1015', $dto['employee_id'], 'employee_id');
        portal_assert_same('SICANGCO, JIRIEL L.', $dto['name'], 'name');
        portal_assert_same('jiriellsicangco@gmail.com', $dto['email'], 'email');
        portal_assert_same(true, $dto['is_active'], 'is_active');
        portal_assert_same(['Admin'], $dto['roles'], 'roles');

        $employee = $dto['employee'];
        portal_assert_same(1015, $employee['id'], 'employee.id');
        portal_assert_same('JIRIEL L.', $employee['first_name'], 'first_name');
        portal_assert_null($employee['middle_name']);
        portal_assert_same('SICANGCO', $employee['last_name'], 'last_name');
        portal_assert_same('SICANGCO, JIRIEL L.', $employee['full_name'], 'full_name');
        portal_assert_same('OPERATIONS - PROJECT SITE', $employee['department'], 'department');
        portal_assert_same('GENERAL FOREMAN', $employee['position'], 'position');
        portal_assert_null($employee['date_hired']);
        portal_assert_same('onsite', $employee['work_arrangement'], 'work_arrangement');
        portal_assert_same('none', $employee['home_location_status'], 'home_location_status');
        portal_assert_null($employee['home_location']);
        portal_assert_same('Main Office', $employee['branch']['name'] ?? null, 'branch.name');
        portal_assert_same('MAIN', $employee['branch']['code'] ?? null, 'branch.code');
    },

    'name without comma' => function (): void {
        $dto = portal_user_dto(portal_test_db(), '1017');

        portal_assert_true(is_array($dto));
        portal_assert_same('LANIE L. SICANGCO', $dto['employee']['first_name']);
        portal_assert_same('', $dto['employee']['last_name']);
    },

    'approver role' => function (): void {
        $dto = portal_user_dto(portal_test_db(), '1016');

        portal_assert_true(is_array($dto));
        portal_assert_same(['Approver'], $dto['roles']);
    },

    'missing profile still returns a user' => function (): void {
        $db = portal_test_db();
        $db->query("DELETE FROM dtr_employee_profile WHERE idno = '1015'");

        $dto = portal_user_dto($db, '1015');

        portal_assert_true(is_array($dto), 'Expected a user even without a profile.');
        portal_assert_same('onsite', $dto['employee']['work_arrangement']);
        portal_assert_null($dto['employee']['branch']);
    },

    'unknown employee returns null' => function (): void {
        portal_assert_null(portal_user_dto(portal_test_db(), '999999'));
    },

    'user dto exposes consent flags' => function (): void {
        $db = portal_test_db();
        portal_consent_upsert($db, '1015', 'gps_location', true);
        portal_consent_upsert($db, '1015', 'biometric_photos', false);

        $dto = portal_user_dto($db, '1015');

        portal_assert_same([
            'biometric_photos' => false,
            'gps_location' => true,
            'device_alerts' => false,
        ], $dto['employee']['consents'], 'employee.consents');
    },

    'user dto survives mixed table collations' => function (): void {
        $db = portal_test_db();
        $db->query('ALTER TABLE dtr_employee_profile CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');

        try {
            $dto = portal_user_dto($db, '1015');
            portal_assert_true(is_array($dto), 'Expected a user DTO with mixed collations.');
            portal_assert_same('SICANGCO, JIRIEL L.', $dto['name'], 'name');
        } finally {
            $db->query('ALTER TABLE dtr_employee_profile CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    },
];
