<?php
declare(strict_types=1);

/**
 * JSON responses and request guards.
 */

function portal_security_headers(): array
{
    return [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'no-referrer',
        'Permissions-Policy' => 'camera=(self), geolocation=(self), microphone=()',
        'Content-Security-Policy' => "default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'; worker-src 'self' blob:; manifest-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
    ];
}

function portal_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    foreach (portal_security_headers() as $name => $value) {
        header($name . ': ' . $value);
    }

    header_remove('X-Powered-By');
}

function portal_error_payload(string $message, string $code, array $details = [], ?array $errors = null): array
{
    $payload = [
        'message' => $message,
        'code' => $code,
        'details' => $details,
    ];

    if ($errors !== null) {
        $payload['errors'] = $errors;
    }

    return $payload;
}

function portal_json(array $payload, int $status = 200): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        portal_send_security_headers();
    }

    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function portal_error(string $message, string $code, int $status = 400, array $details = []): never
{
    portal_json(portal_error_payload($message, $code, $details), $status);
}

function portal_validation_error(array $errors): never
{
    portal_json(
        portal_error_payload('The given data was invalid.', 'validation_error', [], $errors),
        422
    );
}

function portal_is_ajax(array $server): bool
{
    return ($server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function portal_require_ajax(): void
{
    if (!portal_is_ajax($_SERVER)) {
        portal_error('This endpoint requires an AJAX request.', 'invalid_request', 400);
    }
}
