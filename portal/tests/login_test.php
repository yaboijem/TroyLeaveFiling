<?php
declare(strict_types=1);

/**
 * Task 3 login tests.
 */

function login_attempt_count(mysqli $db, string $email, string $ip): int
{
    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND ip = ?');
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['c'] ?? 0);
}

return [
    'login rejects an unknown email' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        $e = portal_assert_api_error(
            static fn () => portal_handle_login(
                $db,
                ['email' => 'nobody@example.test', 'password' => 'whatever'],
                ['REMOTE_ADDR' => '127.0.0.1']
            ),
            'invalid_credentials',
            401
        );

        portal_assert_same('That email and password do not match.', $e->getMessage());
    },

    'login rejects a wrong password' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        $e = portal_assert_api_error(
            static fn () => portal_handle_login(
                $db,
                ['email' => 'jiriellsicangco@gmail.com', 'password' => 'wrong-password'],
                ['REMOTE_ADDR' => '127.0.0.1']
            ),
            'invalid_credentials',
            401
        );

        portal_assert_same('That email and password do not match.', $e->getMessage());
    },

    'login rejects an inactive account' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        portal_assert_api_error(
            static fn () => portal_handle_login(
                $db,
                ['email' => 'ana.lim@example.test', 'password' => portal_test_password()],
                ['REMOTE_ADDR' => '127.0.0.1']
            ),
            'account_inactive',
            403
        );
    },

    'login success rotates the token and sets the session' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        $stmt = $db->prepare("UPDATE employeefiletbl SET session_token = 'oldtoken', last_login = NULL WHERE idno = '1015'");
        $stmt->execute();
        $stmt->close();

        $result = portal_handle_login(
            $db,
            ['email' => 'jiriellsicangco@gmail.com', 'password' => portal_test_password()],
            ['REMOTE_ADDR' => '127.0.0.1']
        );

        portal_assert_same('1015', (string) ($result['data']['id'] ?? ''), 'returned user id');
        portal_assert_same('1015', (string) ($_SESSION['idno'] ?? ''), 'session idno');

        $row = $db->query("SELECT session_token, last_login FROM employeefiletbl WHERE idno = '1015'")->fetch_assoc();
        $token = (string) ($row['session_token'] ?? '');

        portal_assert_true(preg_match('/^[0-9a-f]{64}$/', $token) === 1, 'Expected a 64-char hex token.');
        portal_assert_true($token !== 'oldtoken', 'Token must rotate.');
        portal_assert_same($token, (string) ($_SESSION['session_token'] ?? ''), 'session token must match the database');
        portal_assert_true(!empty($row['last_login']), 'last_login must be set.');
    },

    'login clears previous failures on success' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        for ($i = 0; $i < 4; $i++) {
            portal_record_login_failure($db, 'jiriellsicangco@gmail.com', '127.0.0.1');
        }

        portal_assert_same(4, login_attempt_count($db, 'jiriellsicangco@gmail.com', '127.0.0.1'));

        portal_handle_login(
            $db,
            ['email' => 'jiriellsicangco@gmail.com', 'password' => portal_test_password()],
            ['REMOTE_ADDR' => '127.0.0.1']
        );

        portal_assert_same(0, login_attempt_count($db, 'jiriellsicangco@gmail.com', '127.0.0.1'));
    },

    'fifth failure throttles' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        for ($i = 0; $i < 5; $i++) {
            portal_record_login_failure($db, 'jiriellsicangco@gmail.com', '127.0.0.1');
        }

        portal_assert_api_error(
            static fn () => portal_handle_login(
                $db,
                ['email' => 'jiriellsicangco@gmail.com', 'password' => portal_test_password()],
                ['REMOTE_ADDR' => '127.0.0.1']
            ),
            'too_many_attempts',
            429
        );
    },

    'throttle window expires' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        $past = portal_now()->modify('-16 minutes')->format('Y-m-d H:i:s');

        for ($i = 0; $i < 5; $i++) {
            portal_record_login_failure($db, 'jiriellsicangco@gmail.com', '127.0.0.1', $past);
        }

        $result = portal_handle_login(
            $db,
            ['email' => 'jiriellsicangco@gmail.com', 'password' => portal_test_password()],
            ['REMOTE_ADDR' => '127.0.0.1']
        );

        portal_assert_same('1015', (string) ($result['data']['id'] ?? ''));
    },

    'login validates email and password' => function (): void {
        $db = portal_test_db();
        $_SESSION = [];

        $e = portal_assert_api_error(
            static fn () => portal_handle_login(
                $db,
                ['email' => '', 'password' => ''],
                ['REMOTE_ADDR' => '127.0.0.1']
            ),
            'validation_error',
            422
        );

        $errors = $e->details['errors'] ?? [];
        portal_assert_true(isset($errors['email']), 'Expected errors.email.');
        portal_assert_true(isset($errors['password']), 'Expected errors.password.');
    },

    'client ip ignores forwarded headers' => function (): void {
        portal_assert_same(
            '1.2.3.4',
            portal_client_ip([
                'REMOTE_ADDR' => '1.2.3.4',
                'HTTP_X_FORWARDED_FOR' => '9.9.9.9',
            ])
        );
        portal_assert_same('0.0.0.0', portal_client_ip([]));
    },
];
