<?php
declare(strict_types=1);

function home_test_submit(?callable $geocoder = null): array
{
    return portal_submit_home_location(portal_test_db(), '1019', ['latitude' => 14.5, 'longitude' => 121, 'label' => 'Home'], $geocoder ?? static fn () => null);
}

return [
    'onsite cannot submit' => function (): void {
        portal_assert_api_error(fn () => portal_submit_home_location(portal_test_db(), '1015', []), 'not_wfh', 422);
    },
    'wfh submit creates pending pin' => function (): void {
        $pin = home_test_submit(static fn () => ['address_text' => 'Makati', 'street' => '12 Rizal St', 'city' => 'Makati', 'province' => 'Metro Manila']);
        portal_assert_same('pending', $pin['status']);
        portal_assert_same(200, $pin['radius_meters']);
        portal_assert_same('12 Rizal St', $pin['street']);
        portal_assert_same(14.5, $pin['latitude']);
    },
    'second submit supersedes the first' => function (): void {
        $first = home_test_submit();
        home_test_submit();
        $row = portal_test_db()->query("SELECT * FROM home_locations WHERE id={$first['id']}")->fetch_assoc();
        portal_assert_same('rejected', $row['status']);
        portal_assert_same('Superseded by a new submission.', $row['review_note']);
        portal_assert_same(1, (int) portal_test_db()->query("SELECT COUNT(*) n FROM home_locations WHERE status='pending'")->fetch_assoc()['n']);
    },
    'payload status reflects approved pin' => function (): void {
        $pin = home_test_submit();
        portal_review_home_location(portal_test_db(), $pin['id'], 'approve', null, 'HR');
        $payload = portal_home_location_payload(portal_test_db(), '1019')['data'];
        portal_assert_same('approved', $payload['status']);
        portal_assert_same($pin['id'], $payload['primary']['id']);
        portal_assert_null($payload['pending']);
        portal_assert_same('approved', portal_user_dto(portal_test_db(), '1019')['employee']['home_location_status']);
    },
    'payload exposes pending pin' => function (): void {
        $pin = home_test_submit();
        $payload = portal_home_location_payload(portal_test_db(), '1019')['data'];
        portal_assert_same('pending', $payload['status']);
        portal_assert_same($pin['id'], $payload['pending']['id']);
    },
    'approve sets reviewer fields' => function (): void {
        $pin = home_test_submit();
        portal_set_test_now('2026-11-20 09:00:00');
        portal_review_home_location(portal_test_db(), $pin['id'], 'approve', 'Verified', 'Ana');
        $row = portal_test_db()->query("SELECT * FROM home_locations WHERE id={$pin['id']}")->fetch_assoc();
        portal_assert_same('Ana', $row['reviewed_by']);
        portal_assert_same('Verified', $row['review_note']);
        portal_assert_same('2026-11-20 09:00:00', $row['reviewed_at']);
    },
    'reject returns status none' => function (): void {
        $pin = home_test_submit();
        portal_review_home_location(portal_test_db(), $pin['id'], 'reject', null, 'HR');
        portal_assert_same('none', portal_home_location_payload(portal_test_db(), '1019')['data']['status']);
    },
    'unknown id is not found' => function (): void {
        portal_assert_api_error(fn () => portal_review_home_location(portal_test_db(), 999999, 'approve', null, 'HR'), 'not_found', 404);
    },
    'invalid action is rejected' => function (): void {
        portal_assert_api_error(fn () => portal_review_home_location(portal_test_db(), 1, 'invalid', null, 'HR'), 'invalid_action', 422);
    },
    'geocode failure still submits' => function (): void {
        $pin = home_test_submit(static function () { throw new RuntimeException('offline'); });
        foreach (['address_text','street','city','province'] as $key) portal_assert_null($pin[$key]);
    },
    'nominatim parsing' => function (): void {
        portal_assert_same(['address_text' => 'Full address', 'street' => '12 Rizal St', 'city' => 'Makati', 'province' => 'Metro Manila'],
            portal_parse_nominatim(['display_name' => 'Full address', 'address' => ['road' => 'Rizal St', 'house_number' => '12', 'city' => 'Makati', 'state' => 'Metro Manila']]));
    },
    'review args parse' => function (): void {
        portal_assert_same(['action' => 'approve', 'id' => 7, 'note' => 'Looks good', 'by' => 'Ana'], portal_parse_review_args(['--approve','--id=7','--note=Looks good','--by=Ana']));
    },
    'review sends a notification' => function (): void {
        $pin = home_test_submit();
        portal_review_home_location(portal_test_db(), $pin['id'], 'approve', null, 'HR');
        portal_review_home_location(portal_test_db(), $pin['id'], 'approve', null, 'HR');
        portal_assert_same(1, portal_unread_count(portal_test_db(), '1019'));
    },
];
