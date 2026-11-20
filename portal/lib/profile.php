<?php
declare(strict_types=1);

function portal_set_profile(mysqli $db, string $idno, ?int $branchId, ?string $arrangement): array
{
    $stmt = $db->prepare('SELECT idno FROM employeefiletbl WHERE idno=?');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$employee) throw new PortalApiException('Employee not found.', 'not_found', 404);
    if ($arrangement !== null && !in_array($arrangement, ['onsite','wfh','hybrid'], true)) {
        throw new PortalApiException('Invalid work arrangement.', 'validation_error', 422);
    }
    if ($branchId !== null) {
        $stmt = $db->prepare('SELECT id FROM branches WHERE id=?');
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $branch = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$branch) throw new PortalApiException('Branch not found.', 'validation_error', 422);
    }
    $now = portal_now()->format('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO dtr_employee_profile (idno,branch_id,work_arrangement,created_at,updated_at) VALUES (?,?,COALESCE(?,'onsite'),?,?) ON DUPLICATE KEY UPDATE branch_id=COALESCE(?,branch_id),work_arrangement=COALESCE(?,work_arrangement),updated_at=VALUES(updated_at)");
    $stmt->bind_param('sisssis', $idno, $branchId, $arrangement, $now, $now, $branchId, $arrangement);
    $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare('SELECT p.*,b.name branch_name FROM dtr_employee_profile p LEFT JOIN branches b ON b.id=p.branch_id WHERE p.idno=?');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['idno' => $idno, 'branch_id' => isset($row['branch_id']) ? (int) $row['branch_id'] : null, 'work_arrangement' => $row['work_arrangement'],
        'branch' => isset($row['branch_name']) ? ['id' => (int) $row['branch_id'], 'name' => $row['branch_name']] : null];
}

function portal_parse_set_profile_args(array $argv): array
{
    $opts = portal_cli_options($argv);
    if (empty($opts['idno']) || (isset($opts['branch']) && (!ctype_digit((string) $opts['branch']) || (int) $opts['branch'] < 1))) throw new InvalidArgumentException('Use --idno=N [--branch=N] [--arrangement=onsite|wfh|hybrid]');
    return ['idno' => (string) $opts['idno'], 'branch' => isset($opts['branch']) ? (int) $opts['branch'] : null, 'arrangement' => isset($opts['arrangement']) ? (string) $opts['arrangement'] : null];
}

function portal_parse_close_session_args(array $argv): array
{
    $opts = portal_cli_options($argv);
    if (empty($opts['idno'])) throw new InvalidArgumentException('Use --idno=N [--at=...] [--note=...] [--by=HR]');
    return ['idno' => (string) $opts['idno'], 'at' => isset($opts['at']) ? (string) $opts['at'] : null, 'note' => (string) ($opts['note'] ?? ''), 'by' => (string) ($opts['by'] ?? 'HR')];
}
