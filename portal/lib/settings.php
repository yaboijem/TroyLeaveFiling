<?php
declare(strict_types=1);

function portal_settings(mysqli $db): array
{
    $row = $db->query('SELECT breaks_enabled FROM app_settings WHERE id=1')->fetch_assoc();
    return ['breaks_enabled' => (bool) ($row['breaks_enabled'] ?? true)];
}

function portal_handle_settings(mysqli $db, ?array $auth, array $params): array
{
    return ['data' => portal_settings($db)];
}
