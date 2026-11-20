<?php
declare(strict_types=1);

return [
    'consent defaults to denied' => function (): void {
        portal_assert_same(false, portal_has_consent(portal_test_db(), '1020', 'gps_location'));
    },
    'punch requires gps first' => function (): void {
        portal_assert_api_error(fn () => portal_assert_punch_consent(portal_test_db(), '1020'), 'gps_consent_required', 403);
    },
    'punch requires photos second' => function (): void {
        portal_consent_upsert(portal_test_db(), '1020', 'gps_location', true);
        portal_assert_api_error(fn () => portal_assert_punch_consent(portal_test_db(), '1020'), 'biometric_consent_required', 403);
    },
    'both granted allows a punch' => function (): void {
        portal_assert_punch_consent(portal_test_db(), '1015');
    },
    'grant sets granted_at and clears revoked_at' => function (): void {
        $db = portal_test_db();
        portal_consent_upsert($db, '1020', 'gps_location', false);
        portal_set_test_now('2026-11-20 08:00:00');
        $dto = portal_consent_upsert($db, '1020', 'gps_location', true);
        portal_assert_same(true, $dto['granted']);
        portal_assert_same('2026-11-20T08:00:00+08:00', $dto['granted_at']);
        portal_assert_null($dto['revoked_at']);
    },
    'revoke sets revoked_at and keeps granted_at' => function (): void {
        $db = portal_test_db();
        portal_set_test_now('2026-11-20 08:00:00');
        $grant = portal_consent_upsert($db, '1020', 'gps_location', true);
        portal_set_test_now('2026-11-20 09:00:00');
        $dto = portal_consent_upsert($db, '1020', 'gps_location', false);
        portal_assert_same(false, $dto['granted']);
        portal_assert_same($grant['granted_at'], $dto['granted_at']);
        portal_assert_same('2026-11-20T09:00:00+08:00', $dto['revoked_at']);
    },
    'consent list returns a paginated envelope' => function (): void {
        $result = portal_handle_consent_index(portal_test_db(), ['idno' => '1015'], []);
        portal_assert_same(2, $result['meta']['total']);
        portal_assert_true(isset($result['data'], $result['links']));
        portal_assert_same(true, $result['data'][0]['granted']);
    },
    'invalid type is rejected' => function (): void {
        portal_assert_api_error(fn () => portal_consent_upsert(portal_test_db(), '1015', 'invalid', true), 'validation_error', 422);
    },
    'consent handler reads JSON booleans' => function (): void {
        $_POST = ['type' => 'device_alerts', 'granted' => false];
        $result = portal_handle_consent_store(portal_test_db(), ['idno' => '1015'], []);
        portal_assert_same(false, $result['data']['granted']);
        $_POST = [];
    },
];
