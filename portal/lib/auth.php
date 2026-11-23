<?php
declare(strict_types=1);

/**
 * Session identity, account state and small value helpers.
 */

function portal_session_identity(): ?array
{
    $idno = $_SESSION['idno'] ?? null;
    $token = $_SESSION['session_token'] ?? null;

    if ($idno === null || $token === null) {
        return null;
    }

    $idno = trim((string) $idno);
    $token = trim((string) $token);

    if ($idno === '' || $token === '') {
        return null;
    }

    return ['idno' => $idno, 'session_token' => $token];
}

function portal_auth_check(mysqli $db, array $session): ?array
{
    $idno = trim((string) ($session['idno'] ?? ''));
    $token = (string) ($session['session_token'] ?? '');

    if ($idno === '' || $token === '') {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM employeefiletbl WHERE idno = ? LIMIT 1');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        return null;
    }

    if (!portal_active($row['active'] ?? null)) {
        return null;
    }

    $stored = (string) ($row['session_token'] ?? '');

    if ($stored === '' || !hash_equals($stored, $token)) {
        return null;
    }

    return $row;
}

function portal_require_auth(mysqli $db): array
{
    $identity = portal_session_identity();

    if ($identity === null) {
        throw new PortalApiException('Please sign in to continue.', 'unauthenticated', 401);
    }

    $row = portal_auth_check($db, $identity);

    if ($row === null) {
        throw new PortalApiException(
            'You are already logged in on another device.',
            'session_expired',
            401
        );
    }

    return $row;
}

function portal_require_role(?array $auth, array $roles): array
{
    if ($auth === null) {
        throw new PortalApiException('Please sign in to continue.', 'unauthenticated', 401);
    }

    $role = strtolower(trim((string) ($auth['usertype'] ?? '')));
    $allowed = array_map(
        static fn (string $allowedRole): string => strtolower(trim($allowedRole)),
        $roles
    );

    if ($role === '' || !in_array($role, $allowed, true)) {
        throw new PortalApiException('You do not have access to this area.', 'forbidden', 403);
    }

    return $auth;
}

function portal_active(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }

    if ($value === null) {
        return false;
    }

    $normalized = strtoupper(trim((string) $value));

    return in_array($normalized, ['YES', '1', 'TRUE'], true);
}

function portal_roles(string $usertype): array
{
    return match (strtolower(trim($usertype))) {
        'admin' => ['Admin'],
        'approver' => ['Approver'],
        default => ['Employee'],
    };
}

function portal_split_name(string $full): array
{
    $full = trim($full);
    $comma = strpos($full, ',');

    if ($comma === false) {
        return ['first_name' => $full, 'last_name' => ''];
    }

    return [
        'first_name' => trim(substr($full, $comma + 1)),
        'last_name' => trim(substr($full, 0, $comma)),
    ];
}
