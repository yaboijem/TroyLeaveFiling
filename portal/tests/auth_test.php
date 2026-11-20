<?php
declare(strict_types=1);

/**
 * Task 3 auth tests.
 */

function auth_set_token(mysqli $db, string $idno, ?string $token): void
{
    $stmt = $db->prepare('UPDATE employeefiletbl SET session_token = ? WHERE idno = ?');
    $stmt->bind_param('ss', $token, $idno);
    $stmt->execute();
    $stmt->close();
}

return [
    'active parsing' => function (): void {
        $cases = [
            ['YES', true],
            ['yes', true],
            ['1', true],
            ['TRUE', true],
            ['true', true],
            [1, true],
            ['NO', false],
            ['0', false],
            ['FALSE', false],
            ['', false],
            [null, false],
            [0, false],
        ];

        foreach ($cases as [$value, $expected]) {
            portal_assert_same($expected, portal_active($value), 'portal_active(' . var_export($value, true) . ')');
        }
    },

    'role mapping' => function (): void {
        portal_assert_same(['Admin'], portal_roles('admin'));
        portal_assert_same(['Admin'], portal_roles('ADMIN '));
        portal_assert_same(['Approver'], portal_roles('approver'));
        portal_assert_same(['Approver'], portal_roles('Approver'));
        portal_assert_same(['Employee'], portal_roles('user'));
        portal_assert_same(['Employee'], portal_roles(''));
    },

    'token match accepts' => function (): void {
        $db = portal_test_db();
        auth_set_token($db, '1015', 'abc');

        $row = portal_auth_check($db, ['idno' => '1015', 'session_token' => 'abc']);

        portal_assert_true($row !== null, 'Expected the employee row.');
        portal_assert_same('1015', (string) $row['idno']);
    },

    'token mismatch rejects' => function (): void {
        $db = portal_test_db();
        auth_set_token($db, '1015', 'abc');

        portal_assert_null(portal_auth_check($db, ['idno' => '1015', 'session_token' => 'def']));
    },

    'inactive account rejects' => function (): void {
        $db = portal_test_db();
        auth_set_token($db, '1018', 'abc');

        portal_assert_null(portal_auth_check($db, ['idno' => '1018', 'session_token' => 'abc']));
    },

    'missing session rejects' => function (): void {
        $db = portal_test_db();

        portal_assert_null(portal_auth_check($db, []));

        $_SESSION = [];
        portal_assert_null(portal_session_identity());
    },

    'logout clears only that employee token' => function (): void {
        $db = portal_test_db();
        auth_set_token($db, '1015', 'token-1015');
        auth_set_token($db, '1016', 'token-1016');

        $_SESSION = ['idno' => '1015', 'session_token' => 'token-1015'];
        portal_handle_logout($db, portal_test_employee('1015'), []);

        $a = $db->query("SELECT session_token FROM employeefiletbl WHERE idno = '1015'")->fetch_assoc();
        $b = $db->query("SELECT session_token FROM employeefiletbl WHERE idno = '1016'")->fetch_assoc();

        portal_assert_null($a['session_token'] ?? null, '1015 token must be cleared.');
        portal_assert_same('token-1016', $b['session_token'] ?? null, '1016 token must be untouched.');
    },
];
