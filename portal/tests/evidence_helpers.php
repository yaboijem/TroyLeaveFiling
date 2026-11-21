<?php
declare(strict_types=1);

function portal_test_evidence(string $at = '2026-11-19 08:00:00', ?string $out = null, ?string $photo = null): array
{
    $db = portal_test_db();
    $stmt = $db->prepare("INSERT INTO attendance (idno,branch_id,work_date,time_in,time_out,created_at,updated_at) VALUES ('1015',1,DATE(?),?,?,?,?)");
    $stmt->bind_param('sssss', $at, $at, $out, $at, $at);
    $stmt->execute();
    $attendanceId = (int) $db->insert_id;
    $uuid = sprintf('%08x-0000-4000-8000-%012x', random_int(1, 0x7fffffff), random_int(1, 0x7fffffff));
    $stmt = $db->prepare("INSERT INTO attendance_punches (attendance_id,idno,uuid,type,punched_at,latitude,longitude,is_within_radius,photo_path,created_at) VALUES (?,'1015',?,'time_in',?,14.5995,120.9842,1,?,?)");
    $stmt->bind_param('issss', $attendanceId, $uuid, $at, $photo, $at);
    $stmt->execute();
    $id = (int) $db->insert_id;
    return $db->query("SELECT * FROM attendance_punches WHERE id={$id}")->fetch_assoc();
}

function portal_test_image(): array
{
    $path = tempnam(sys_get_temp_dir(), 'troy-photo-');
    $image = imagecreatetruecolor(1600, 800);
    imagepng($image, $path);
    imagedestroy($image);
    return ['tmp_name' => $path, 'size' => filesize($path), 'error' => UPLOAD_ERR_OK, 'name' => 'selfie.png'];
}
