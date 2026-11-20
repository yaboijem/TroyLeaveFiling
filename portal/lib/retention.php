<?php
declare(strict_types=1);
require_once __DIR__ . '/photos.php';

function portal_purge(mysqli $db, int $days, bool $dryRun = false, ?string $now = null): array
{
    if ($days < 30) throw new InvalidArgumentException('Retention must be at least 30 days.');
    $clock = $now === null ? portal_now() : new DateTimeImmutable($now, portal_tz());
    $cutoff = $clock->modify("-{$days} days")->format('Y-m-d H:i:s');
    $counts = ['headers' => 0, 'punches' => 0, 'photos' => 0, 'login_attempts' => 0];
    $afterId = 0;
    do {
        $paths = [];
        $db->begin_transaction();
        try {
            $stmt = $db->prepare('SELECT id FROM attendance WHERE time_out IS NOT NULL AND time_out<? AND id>? ORDER BY id LIMIT 500 FOR UPDATE');
            $stmt->bind_param('si', $cutoff, $afterId);
            $stmt->execute();
            $headers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            foreach ($headers as $header) {
                $id = (int) $header['id'];
                $afterId = $id;
                $stmt = $db->prepare('SELECT photo_path FROM attendance_punches WHERE attendance_id=?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $punches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $counts['headers']++;
                $counts['punches'] += count($punches);
                foreach ($punches as $punch) {
                    $path = portal_private_photo_path($punch['photo_path']);
                    if ($path !== null) $paths[$path] = true;
                }
                if (!$dryRun) {
                    $stmt = $db->prepare('DELETE FROM fraud_flags WHERE attendance_id=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();
                    $stmt = $db->prepare('DELETE FROM attendance WHERE id=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $db->commit();
        } catch (Throwable $e) { $db->rollback(); throw $e; }
        foreach (array_keys($paths) as $path) {
            if ($dryRun || unlink($path)) $counts['photos']++;
        }
    } while (count($headers) === 500);
    $loginCutoff = $clock->modify('-30 days')->format('Y-m-d H:i:s');
    $stmt = $db->prepare($dryRun ? 'SELECT COUNT(*) n FROM login_attempts WHERE attempted_at<?' : 'DELETE FROM login_attempts WHERE attempted_at<?');
    $stmt->bind_param('s', $loginCutoff);
    $stmt->execute();
    $counts['login_attempts'] = $dryRun ? (int) $stmt->get_result()->fetch_assoc()['n'] : $stmt->affected_rows;
    $stmt->close();
    return $counts;
}
