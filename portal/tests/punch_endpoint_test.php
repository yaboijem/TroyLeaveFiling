<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

function endpoint_test_input(): array
{
    return ['client_uuid' => '44444444-0000-4000-8000-000000000001', 'latitude' => '14.5995', 'longitude' => '120.9842', 'accuracy_meters' => '10'];
}

return [
    'time in returns 201 and a dto' => function (): void {
        $file = portal_test_image();
        $input = endpoint_test_input();
        $path = portal_config()['uploads'] . '/attendance/' . $input['client_uuid'] . '.jpg';
        try {
            $result = portal_handle_punch_request(portal_test_db(), '1015', 'time_in', $input, ['selfie' => $file]);
            portal_assert_same(201, $result['status']);
            $dto = $result['payload']['data'];
            portal_assert_same('time_in', $dto['type']);
            portal_assert_same($input['client_uuid'], $dto['uuid']);
            portal_assert_same(1, $dto['branch']['id']);
            portal_assert_same(portal_photo_url($input['client_uuid']), $dto['photo']['path']);
        } finally { unlink($file['tmp_name']); if (is_file($path)) unlink($path); }
    },
    'time out returns 200' => function (): void {
        portal_test_evidence();
        $file = portal_test_image();
        $input = endpoint_test_input();
        $path = portal_config()['uploads'] . '/attendance/' . $input['client_uuid'] . '.jpg';
        try {
            portal_assert_same(200, portal_handle_punch_request(portal_test_db(), '1015', 'time_out', $input, ['selfie' => $file])['status']);
        } finally { unlink($file['tmp_name']); if (is_file($path)) unlink($path); }
    },
    'missing latitude is a validation error' => function (): void {
        $e = portal_assert_api_error(fn () => portal_handle_punch_request(portal_test_db(), '1015', 'time_in', [], []), 'validation_error', 422);
        portal_assert_true(!empty($e->details['errors']['latitude'][0]));
    },
    'missing selfie is rejected' => function (): void {
        portal_assert_api_error(fn () => portal_handle_punch_request(portal_test_db(), '1015', 'time_in', endpoint_test_input(), []), 'selfie_required', 422);
    },
    'duplicate uuid returns same id' => function (): void {
        $file = portal_test_image();
        $input = endpoint_test_input();
        $path = portal_config()['uploads'] . '/attendance/' . $input['client_uuid'] . '.jpg';
        try {
            $first = portal_handle_punch_request(portal_test_db(), '1015', 'time_in', $input, ['selfie' => $file]);
            $again = portal_handle_punch_request(portal_test_db(), '1015', 'time_in', $input, ['selfie' => $file]);
            portal_assert_same($first['payload']['data']['id'], $again['payload']['data']['id']);
        } finally { unlink($file['tmp_name']); if (is_file($path)) unlink($path); }
    },
];
