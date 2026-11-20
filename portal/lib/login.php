<?php
declare(strict_types=1);

/**
 * JSON login, login throttling, /api/me and logout.
 */

const PORTAL_LOGIN_MAX_ATTEMPTS = 5;
const PORTAL_LOGIN_WINDOW_MINUTES = 15;

function portal_client_ip(array $server): string
{
    $ip = trim((string) ($server['REMOTE_ADDR'] ?? ''));

    return $ip !== '' ? $ip : '0.0.0.0';
}

function portal_login_throttled(mysqli $db, string $email, string $ip, ?string $now = null): bool
{
    $now = $now ?? portal_now()->format('Y-m-d H:i:s');
    $cutoff = (new DateTimeImmutable($now, portal_tz()))
        ->modify('-' . PORTAL_LOGIN_WINDOW_MINUTES . ' minutes')
        ->format('Y-m-d H:i:s');

    $email = strtolower(trim($email));

    $stmt = $db->prepare(
        'SELECT COUNT(*) AS c FROM login_attempts
          WHERE email = ? AND ip = ? AND attempted_at >= ?'
    );
    $stmt->bind_param('sss', $email, $ip, $cutoff);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['c'] ?? 0) >= PORTAL_LOGIN_MAX_ATTEMPTS;
}

function portal_record_login_failure(mysqli $db, string $email, string $ip, ?string $now = null): void
{
    $now = $now ?? portal_now()->format('Y-m-d H:i:s');
    $email = strtolower(trim($email));

    $stmt = $db->prepare('INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $email, $ip, $now);
    $stmt->execute();
    $stmt->close();
}

function portal_clear_login_failures(mysqli $db, string $email, string $ip): void
{
    $email = strtolower(trim($email));

    $stmt = $db->prepare('DELETE FROM login_attempts WHERE email = ? AND ip = ?');
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $stmt->close();
}

function portal_handle_login(mysqli $db, array $input, array $server): array
{
    if ($input === []) {
        $raw = file_get_contents('php://input');

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $input = $decoded;
            }
        }
    }

    $email = trim((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $errors = [];

    if ($email === '') {
        $errors['email'] = ['Enter your email.'];
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = ['Enter a valid email address.'];
    }

    if ($password === '') {
        $errors['password'] = ['Enter your password.'];
    }

    if ($errors !== []) {
        throw new PortalApiException(
            'The given data was invalid.',
            'validation_error',
            422,
            ['errors' => $errors]
        );
    }

    $ip = portal_client_ip($server);

    if (portal_login_throttled($db, $email, $ip)) {
        throw new PortalApiException(
            'Too many attempts. Wait a few minutes and try again.',
            'too_many_attempts',
            429
        );
    }

    $stmt = $db->prepare(
        'SELECT employeefiletbl.*, departmentfiletbl.deptname
           FROM employeefiletbl
      LEFT JOIN departmentfiletbl ON departmentfiletbl.deptcode = employeefiletbl.deptcode
          WHERE email = ?
          LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        portal_record_login_failure($db, $email, $ip);
        usleep((int) (portal_config()['login_sleep_us'] ?? 0));
        throw new PortalApiException('That email and password do not match.', 'invalid_credentials', 401);
    }

    if (!password_verify($password, (string) $row['password'])) {
        portal_record_login_failure($db, $email, $ip);
        usleep((int) (portal_config()['login_sleep_us'] ?? 0));
        throw new PortalApiException('That email and password do not match.', 'invalid_credentials', 401);
    }

    if (!portal_active($row['active'] ?? null)) {
        portal_record_login_failure($db, $email, $ip);
        throw new PortalApiException('This account is inactive. Contact HR.', 'account_inactive', 403);
    }

    $idno = (string) $row['idno'];
    $token = bin2hex(random_bytes(32));

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $stmt = $db->prepare('UPDATE employeefiletbl SET session_token = ?, last_login = NOW() WHERE idno = ?');
    $stmt->bind_param('ss', $token, $idno);
    $stmt->execute();
    $stmt->close();

    $_SESSION['idno'] = $idno;
    $_SESSION['email'] = (string) $row['email'];
    $_SESSION['deptcode'] = (string) ($row['deptcode'] ?? '');
    $_SESSION['divcode'] = (string) ($row['divcode'] ?? '');
    $_SESSION['admin_or_user'] = (string) ($row['usertype'] ?? '');
    $_SESSION['last_login_timestamp'] = time();
    $_SESSION['session_token'] = $token;

    portal_clear_login_failures($db, $email, $ip);

    return ['data' => portal_user_dto($db, $idno)];
}

function portal_handle_me(mysqli $db, array $auth, array $params): array
{
    $dto = portal_user_dto($db, (string) $auth['idno']);

    if ($dto === null) {
        throw new PortalApiException('Please sign in to continue.', 'unauthenticated', 401);
    }

    return ['data' => $dto];
}

function portal_handle_logout(mysqli $db, array $auth, array $params): array
{
    $idno = (string) ($auth['idno'] ?? '');

    if ($idno !== '') {
        $stmt = $db->prepare('UPDATE employeefiletbl SET session_token = NULL WHERE idno = ?');
        $stmt->bind_param('s', $idno);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    return ['message' => 'Logged out.'];
}
