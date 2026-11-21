<?php
declare(strict_types=1);

function portal_validate_selfie(array $file): void
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new PortalApiException('Capture a selfie before punching.', 'selfie_required', 422);
    }
    if (($file['size'] ?? 0) > 6000000 || (($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE)) {
        throw new PortalApiException('The selfie must not exceed 6 MB.', 'selfie_too_large', 422);
    }
    $path = $file['tmp_name'] ?? '';
    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK || !is_file($path)) {
        throw new PortalApiException('Upload a valid JPEG, PNG or WebP selfie.', 'invalid_selfie', 422);
    }
    $info = @getimagesize($path);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !$info || $info['mime'] !== $mime) {
        throw new PortalApiException('Upload a valid JPEG, PNG or WebP selfie.', 'invalid_selfie', 422);
    }
    // Bound the allocation before GD decodes the compressed pixel buffer.
    $pixels = (float) $info[0] * (float) $info[1];
    $limit = trim((string) ini_get('memory_limit'));
    $bytes = (float) $limit;
    if (str_ends_with(strtoupper($limit), 'G')) $bytes *= 1024 ** 3;
    elseif (str_ends_with(strtoupper($limit), 'M')) $bytes *= 1024 ** 2;
    elseif (str_ends_with(strtoupper($limit), 'K')) $bytes *= 1024;
    if ($info[0] > 8192 || $info[1] > 8192 || $pixels > 4000000
        || ($bytes > 0 && memory_get_usage(true) + $pixels * 8 + 16000000 > $bytes)) {
        throw new PortalApiException('The selfie dimensions are too large. Capture a smaller image.', 'invalid_selfie', 422);
    }
}

function portal_store_selfie(array $file, string $directory, string $uuid): string
{
    portal_validate_selfie($file);
    if (preg_match('/^[0-9a-f-]{36}$/i', $uuid) !== 1) {
        throw new PortalApiException('Invalid punch reference.', 'validation_error', 422);
    }
    $image = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$image) {
        throw new PortalApiException('The selfie could not be decoded.', 'invalid_selfie', 422);
    }
    try {
        $ratio = min(1, 800 / max(imagesx($image), imagesy($image)));
        $scaled = imagescale($image, max(1, (int) round(imagesx($image) * $ratio)), max(1, (int) round(imagesy($image) * $ratio)));
        if (!$scaled) throw new RuntimeException('Could not resize selfie.');
        try {
            if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
                throw new RuntimeException('Could not create private photo directory.');
            }
            if (!imagejpeg($scaled, $directory . '/' . $uuid . '.jpg', 70)) {
                throw new RuntimeException('Could not store selfie.');
            }
        } finally { imagedestroy($scaled); }
    } finally { imagedestroy($image); }
    return 'attendance/' . $uuid . '.jpg';
}

function portal_photo_url(string $uuid): string
{
    return rtrim(portal_config()['public_base'], '/') . '/api/attendance/photo/' . $uuid;
}

function portal_private_photo_path(?string $relative): ?string
{
    if (!$relative) return null;
    $root = realpath(portal_config()['uploads']);
    $path = $root === false ? false : realpath($root . '/' . $relative);
    if ($root === false || $path === false || !is_file($path)) return null;
    $prefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
    return str_starts_with(str_replace('\\', '/', $path), $prefix) ? $path : null;
}

function portal_photo_response(mysqli $db, string $idno, string $uuid): array
{
    $stmt = $db->prepare('SELECT photo_path FROM attendance_punches WHERE idno=? AND uuid=? LIMIT 1');
    $stmt->bind_param('ss', $idno, $uuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $path = portal_private_photo_path($row['photo_path'] ?? null);
    if ($path === null) throw new PortalApiException('Not found.', 'not_found', 404);
    return ['path' => $path, 'mime' => 'image/jpeg', 'filename' => $uuid . '.jpg'];
}

function portal_handle_photo_request(mysqli $db, array $auth, array $params): never
{
    $photo = portal_photo_response($db, (string) $auth['idno'], $params['uuid']);
    session_write_close();
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($photo['path']));
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($photo['path']);
    exit;
}
