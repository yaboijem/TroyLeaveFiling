<?php
declare(strict_types=1);

function portal_push_endpoint_allowed(string $endpoint): bool
{
    $url = parse_url($endpoint);
    if (!$url || ($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass'])
        || (isset($url['port']) && $url['port'] !== 443)) return false;
    $host = strtolower($url['host'] ?? '');
    return in_array($host, ['fcm.googleapis.com', 'web.push.apple.com', 'updates.push.services.mozilla.com'], true)
        || str_ends_with($host, '.push.services.mozilla.com')
        || str_ends_with($host, '.notify.windows.com');
}

function portal_push_public_key(): ?string
{
    $key = trim((string) (portal_config()['vapid']['public_key'] ?? ''));
    return $key !== '' ? $key : null;
}

function portal_push_subscribe(mysqli $db, string $idno, array $input): void
{
    $endpoint = $input['endpoint'] ?? null;
    $public = $input['keys']['p256dh'] ?? null;
    $auth = $input['keys']['auth'] ?? null;
    if (!is_string($endpoint) || strlen($endpoint) > 2048 || !filter_var($endpoint, FILTER_VALIDATE_URL)
        || parse_url($endpoint, PHP_URL_SCHEME) !== 'https'
        || !portal_push_endpoint_allowed($endpoint)
        || !is_string($public) || $public === '' || strlen($public) > 255
        || !is_string($auth) || $auth === '' || strlen($auth) > 255) {
        throw new PortalApiException('Provide a valid push endpoint and keys.', 'validation_error', 422);
    }
    $encoding = $input['content_encoding'] ?? 'aes128gcm';
    if (!in_array($encoding, ['aes128gcm','aesgcm'], true)) throw new PortalApiException('Invalid content encoding.', 'validation_error', 422);
    $hash = hash('sha256', $endpoint);
    $now = portal_now()->format('Y-m-d H:i:s');
    $stmt = $db->prepare('INSERT INTO push_subscriptions (employee_id,endpoint_hash,endpoint,public_key,auth_token,content_encoding,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE employee_id=VALUES(employee_id),public_key=VALUES(public_key),auth_token=VALUES(auth_token),content_encoding=VALUES(content_encoding),updated_at=VALUES(updated_at)');
    $stmt->bind_param('ssssssss', $idno, $hash, $endpoint, $public, $auth, $encoding, $now, $now);
    $stmt->execute();
    $stmt->close();
}

function portal_push_unsubscribe(mysqli $db, string $idno, string $endpoint): void
{
    $hash = hash('sha256', $endpoint);
    $stmt = $db->prepare('DELETE FROM push_subscriptions WHERE employee_id=? AND endpoint_hash=?');
    $stmt->bind_param('ss', $idno, $hash);
    $stmt->execute();
    $stmt->close();
}

function portal_push_payload(string $title, string $body, array $data): string
{
    return json_encode(['title' => $title, 'body' => $body, 'data' => $data], JSON_THROW_ON_ERROR);
}

function portal_push_send(mysqli $db, string $idno, string $title, string $body, array $data = []): void
{
    $vapid = portal_config()['vapid'];
    if (empty($vapid['public_key']) || empty($vapid['private_key']) || empty($vapid['subject'])) return;
    try {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (is_file($autoload)) require_once $autoload;
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) return;
        $sender = new \Minishlink\WebPush\WebPush(['VAPID' => [
            'subject' => $vapid['subject'], 'publicKey' => $vapid['public_key'], 'privateKey' => $vapid['private_key'],
        ]], [], 10, ['allow_redirects' => false]);
        $stmt = $db->prepare('SELECT * FROM push_subscriptions WHERE employee_id=?');
        $stmt->bind_param('s', $idno);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($rows as $row) {
            try {
                if (!portal_push_endpoint_allowed($row['endpoint'])) {
                    error_log('Portal push skipped unsupported endpoint host.');
                    continue;
                }
                $subscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $row['endpoint'], 'publicKey' => $row['public_key'], 'authToken' => $row['auth_token'], 'contentEncoding' => $row['content_encoding'],
                ]);
                $report = $sender->sendOneNotification($subscription, portal_push_payload($title, $body, $data));
                $status = $report->getResponse()?->getStatusCode();
                if (in_array($status, [404,410], true)) portal_push_unsubscribe($db, $idno, $row['endpoint']);
                elseif (!$report->isSuccess()) error_log('Portal push delivery failed: ' . $report->getReason());
            } catch (Throwable $e) { error_log('Portal push subscription error: ' . $e->getMessage()); }
        }
    } catch (Throwable $e) { error_log('Portal push unavailable: ' . $e->getMessage()); }
}

function portal_handle_vapid_key(mysqli $db, ?array $auth, array $params): array
{
    $key = portal_push_public_key();
    if ($key === null) throw new PortalApiException('Push notifications are not configured.', 'push_not_configured', 503);
    return ['public_key' => $key];
}

function portal_handle_push_subscribe(mysqli $db, ?array $auth, array $params): array
{
    $input = json_decode(file_get_contents('php://input') ?: '{}', true);
    portal_push_subscribe($db, (string) $auth['idno'], is_array($input) ? $input : []);
    return ['message' => 'Subscribed.'];
}

function portal_handle_push_unsubscribe(mysqli $db, ?array $auth, array $params): array
{
    $input = json_decode(file_get_contents('php://input') ?: '{}', true);
    portal_push_unsubscribe($db, (string) $auth['idno'], is_string($input['endpoint'] ?? null) ? $input['endpoint'] : '');
    return ['message' => 'Unsubscribed.'];
}
