<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

return [
    'punch stores selfie and returns private photo url' => function (): void {
        $file = portal_test_image();
        $uuid = '22222222-0000-4000-8000-000000000001';
        $path = portal_config()['uploads'] . '/attendance/' . $uuid . '.jpg';
        try {
            $dto = portal_punch(portal_test_db(), '1015', 'time_in', [
                'client_uuid' => $uuid, 'latitude' => 14.5995, 'longitude' => 120.9842, 'accuracy_meters' => 10,
            ], $file);
            portal_assert_same(['path' => portal_photo_url($uuid)], $dto['photo']);
            portal_assert_true(is_file($path));
            portal_assert_same(realpath($path), portal_photo_response(portal_test_db(), '1015', $uuid)['path']);
        } finally {
            unlink($file['tmp_name']);
            if (is_file($path)) unlink($path);
        }
    },
    'missing upload is rejected' => function (): void {
        portal_assert_api_error(fn () => portal_validate_selfie(['error' => UPLOAD_ERR_NO_FILE]), 'selfie_required', 422);
    },
    'text file is rejected' => function (): void {
        $path = tempnam(sys_get_temp_dir(), 'troy-text-');
        file_put_contents($path, 'not an image');
        try {
            portal_assert_api_error(fn () => portal_validate_selfie(['tmp_name' => $path, 'size' => 12, 'error' => 0]), 'invalid_selfie', 422);
        } finally { unlink($path); }
    },
    'oversized upload is rejected' => function (): void {
        portal_assert_api_error(fn () => portal_validate_selfie(['size' => 6000001, 'error' => 0]), 'selfie_too_large', 422);
    },
    'png is stored as jpeg and resized' => function (): void {
        $file = portal_test_image();
        $dir = portal_config()['uploads'] . '/attendance';
        $uuid = '11111111-0000-4000-8000-000000000001';
        try {
            $relative = portal_store_selfie($file, $dir, $uuid);
            portal_assert_same("attendance/{$uuid}.jpg", $relative);
            $info = getimagesize(portal_config()['uploads'] . '/' . $relative);
            portal_assert_same('image/jpeg', $info['mime']);
            portal_assert_true($info[0] <= 800 && $info[1] <= 800);
        } finally {
            unlink($file['tmp_name']);
            if (is_file($dir . '/' . $uuid . '.jpg')) unlink($dir . '/' . $uuid . '.jpg');
        }
    },
    'photo url points at the owner endpoint' => function (): void {
        portal_assert_same('/TroyLeaveFiling/portal/api/attendance/photo/abc', portal_photo_url('abc'));
    },
    'photo response rejects another employee' => function (): void {
        $punch = portal_test_evidence();
        portal_assert_api_error(fn () => portal_photo_response(portal_test_db(), '1016', $punch['uuid']), 'not_found', 404);
    },
    'photo response returns a path inside uploads' => function (): void {
        $punch = portal_test_evidence();
        $file = portal_test_image();
        $path = portal_config()['uploads'] . '/attendance/' . $punch['uuid'] . '.jpg';
        try {
            $relative = portal_store_selfie($file, dirname($path), $punch['uuid']);
            $stmt = portal_test_db()->prepare('UPDATE attendance_punches SET photo_path=? WHERE id=?');
            $stmt->bind_param('si', $relative, $punch['id']);
            $stmt->execute();
            $response = portal_photo_response(portal_test_db(), '1015', $punch['uuid']);
            portal_assert_same(realpath($path), $response['path']);
            portal_assert_same('image/jpeg', $response['mime']);
        } finally {
            unlink($file['tmp_name']);
            if (is_file($path)) unlink($path);
        }
    },
    'uploads htaccess denies all direct access' => function (): void {
        $text = file_get_contents(dirname(__DIR__) . '/uploads/.htaccess');
        portal_assert_true(str_contains($text, 'Require all denied'));
        portal_assert_true(str_contains($text, 'Options -Indexes'));
    },
];
