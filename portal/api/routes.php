<?php
declare(strict_types=1);

/**
 * Route table. Later tasks add their endpoints here.
 */

return [
    'GET /api/push/vapid-public-key' => 'portal_handle_vapid_key',
    'POST /api/push/subscribe' => 'portal_handle_push_subscribe',
    'DELETE /api/push/subscribe' => 'portal_handle_push_unsubscribe',
    'GET /api/home-location' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_home_location_payload($db, (string) $auth['idno']),
    'POST /api/home-location' => static function (mysqli $db, ?array $auth, array $params): array {
        $input = $_POST ?: json_decode(file_get_contents('php://input') ?: '{}', true);
        return ['status' => 201, 'payload' => ['data' => portal_submit_home_location($db, (string) $auth['idno'], is_array($input) ? $input : [])]];
    },
    'GET /api/notifications' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_notifications_page($db, (string) $auth['idno'], (int) ($_GET['page'] ?? 1), (int) ($_GET['per_page'] ?? 20)),
    'GET /api/notifications/unread-count' => 'portal_handle_notification_unread',
    'POST /api/notifications/{id}/read' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_mark_read($db, (string) $auth['idno'], $params['id']) ?? throw new PortalApiException('Not found.', 'not_found', 404),
    'POST /api/notifications/read-all' => static fn (mysqli $db, ?array $auth, array $params): array
        => ['marked' => portal_mark_all_read($db, (string) $auth['idno'])],
    'DELETE /api/notifications/{id}' => static fn (mysqli $db, ?array $auth, array $params): array
        => ['deleted' => portal_delete_notification($db, (string) $auth['idno'], $params['id'])],
    'DELETE /api/notifications' => static fn (mysqli $db, ?array $auth, array $params): array
        => ['deleted' => portal_delete_all($db, (string) $auth['idno'])],
    'POST /api/attendance/time-in' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_handle_punch_request($db, (string) $auth['idno'], 'time_in', $_POST, $_FILES),
    'POST /api/attendance/time-out' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_handle_punch_request($db, (string) $auth['idno'], 'time_out', $_POST, $_FILES),
    'POST /api/attendance/break-in' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_handle_punch_request($db, (string) $auth['idno'], 'break_in', $_POST, $_FILES),
    'POST /api/attendance/break-out' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_handle_punch_request($db, (string) $auth['idno'], 'break_out', $_POST, $_FILES),
    'GET /api/attendance/session' => static fn (mysqli $db, ?array $auth, array $params): array
        => ['data' => portal_punch_session($db, (string) $auth['idno'])],
    'GET /api/attendance/work' => static fn (mysqli $db, ?array $auth, array $params): array
        => ['data' => portal_work_punches($db, (string) $auth['idno']), 'breaks_enabled' => portal_settings($db)['breaks_enabled']],
    'GET /api/attendance/history' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_handle_history_request($db, (string) $auth['idno'], $_GET),
    'GET /api/attendance/photo/{uuid}' => 'portal_handle_photo_request',
    'GET /api/settings' => 'portal_handle_settings',
    'GET /api/employee/consent' => 'portal_handle_consent_index',
    'POST /api/employee/consent' => 'portal_handle_consent_store',
    'GET /api/admin/attendance' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_attendance_index($db, $auth ?? [], $_GET),
    'GET /api/admin/attendance/{id}/photo' => 'portal_handle_admin_attendance_photo',
    'GET /api/admin/departments' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_departments_index($db, $auth ?? [], $_GET),
    'GET /api/admin/settings' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_settings_show($db, $auth ?? [], $params),
    'PATCH /api/admin/settings' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_settings_update($db, $auth ?? [], portal_request_input()),

    'GET /api/admin/branches' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_branches_index($db, $auth ?? [], $_GET),
    'POST /api/admin/branches' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_branches_store($db, $auth ?? [], portal_request_input()),
    'PATCH /api/admin/branches/{id}' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_branches_update($db, $auth ?? [], (int) $params['id'], portal_request_input()),
    'POST /api/admin/branches/{id}/deactivate' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_branches_deactivate($db, $auth ?? [], (int) $params['id']),
    'POST /api/admin/branches/{id}/activate' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_branches_activate($db, $auth ?? [], (int) $params['id']),
    'DELETE /api/admin/branches/{id}' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_branches_delete($db, $auth ?? [], (int) $params['id']),

    'GET /api/admin/employees' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_employees_index($db, $auth ?? [], $_GET),
    'POST /api/admin/employees/assignments/preview' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_assignments_bulk($db, $auth ?? [], portal_request_input(), true),
    'POST /api/admin/employees/assignments/bulk' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_assignments_bulk($db, $auth ?? [], portal_request_input()),

    'GET /api/admin/home-locations' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_home_locations_index($db, $auth ?? [], $_GET),
    'GET /api/admin/home-locations/{id}' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_home_locations_show($db, $auth ?? [], (int) $params['id']),
    'POST /api/admin/home-locations/{id}/review' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_admin_home_locations_review($db, $auth ?? [], (int) $params['id'], portal_request_input()),

    'POST /api/login' => [
        'public' => true,
        'handler' => static fn (mysqli $db, ?array $auth, array $params): array
            => portal_handle_login($db, $_POST, $_SERVER),
    ],

    'GET /api/me' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_handle_me($db, $auth ?? [], $params),

    'POST /api/logout' => static fn (mysqli $db, ?array $auth, array $params): array
        => portal_handle_logout($db, $auth ?? [], $params),
];
