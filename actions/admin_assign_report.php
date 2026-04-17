<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/reports.php');
}

$reportId = (int) ($_POST['report_id'] ?? 0);
$teamId = (int) ($_POST['team_id'] ?? 0);
$sentReason = trim((string) ($_POST['sent_reason'] ?? ''));

if ($reportId <= 0 || $teamId <= 0 || $sentReason === '') {
    flash_set('error', 'กรุณาเลือกทีมนำและระบุเหตุผลการส่งต่อ');
    redirect('/admin/report_detail.php?id=' . $reportId);
}

if (mb_strlen($sentReason) > 1000) {
    flash_set('error', 'เหตุผลการส่งต่อยาวเกินกำหนด');
    redirect('/admin/report_detail.php?id=' . $reportId);
}

try {
    $pdo = Database::connection();
    $user = Auth::user();
    $userId = (int) ($user['id'] ?? 0);

    $reportStmt = $pdo->prepare('SELECT id, status FROM incident_reports WHERE id = :id LIMIT 1');
    $reportStmt->execute(['id' => $reportId]);
    $report = $reportStmt->fetch();

    if (!$report) {
        flash_set('error', 'ไม่พบรายงานที่ต้องการส่งต่อ');
        redirect('/admin/reports.php');
    }

    if ((string) $report['status'] === 'completed') {
        flash_set('error', 'ไม่สามารถส่งต่อรายงานที่ปิดงานแล้วได้');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $teamStmt = $pdo->prepare('SELECT id, team_code FROM teams WHERE id = :id AND is_active = 1 LIMIT 1');
    $teamStmt->execute(['id' => $teamId]);
    $team = $teamStmt->fetch();

    if (!$team) {
        flash_set('error', 'ไม่พบทีมนำที่เลือก');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $existingStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM report_assignments
         WHERE report_id = :report_id
           AND target_team_id = :team_id
           AND assignment_status <> "cancelled"'
    );
    $existingStmt->execute([
        'report_id' => $reportId,
        'team_id' => $teamId,
    ]);

    if ((int) $existingStmt->fetchColumn() > 0) {
        flash_set('error', 'รายงานนี้ถูกส่งต่อไปยังทีมนำนี้แล้ว');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $fiscalYear = active_fiscal_year();
    if (!$fiscalYear) {
        flash_set('error', 'ยังไม่ได้ตั้งค่าปีงบประมาณที่ใช้งาน');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $pdo->beginTransaction();

    $runningStmt = $pdo->prepare(
        'SELECT id, last_number
         FROM team_running_numbers
         WHERE team_id = :team_id AND fiscal_year_id = :fiscal_year_id
         LIMIT 1
         FOR UPDATE'
    );
    $runningStmt->execute([
        'team_id' => $teamId,
        'fiscal_year_id' => (int) $fiscalYear['id'],
    ]);
    $running = $runningStmt->fetch();

    if ($running) {
        $nextNumber = (int) $running['last_number'] + 1;
        $updateRunningStmt = $pdo->prepare(
            'UPDATE team_running_numbers
             SET last_number = :last_number, updated_at = NOW()
             WHERE id = :id'
        );
        $updateRunningStmt->execute([
            'last_number' => $nextNumber,
            'id' => (int) $running['id'],
        ]);
    } else {
        $nextNumber = 1;
        $insertRunningStmt = $pdo->prepare(
            'INSERT INTO team_running_numbers (team_id, fiscal_year_id, last_number)
             VALUES (:team_id, :fiscal_year_id, :last_number)'
        );
        $insertRunningStmt->execute([
            'team_id' => $teamId,
            'fiscal_year_id' => (int) $fiscalYear['id'],
            'last_number' => $nextNumber,
        ]);
    }

    $assignmentNo = trim((string) $team['team_code']) . ' ' . $nextNumber . '/' . trim((string) $fiscalYear['year_short']);

    $assignmentStmt = $pdo->prepare(
        'INSERT INTO report_assignments (
            report_id, target_team_id, target_head_user_id, assignment_no,
            fiscal_year_id, running_no, from_user_id, sent_reason, assigned_at, assignment_status
         ) VALUES (
            :report_id, :target_team_id, :target_head_user_id, :assignment_no,
            :fiscal_year_id, :running_no, :from_user_id, :sent_reason, NOW(), :assignment_status
         )'
    );
    $assignmentStmt->execute([
        'report_id' => $reportId,
        'target_team_id' => $teamId,
        'target_head_user_id' => null,
        'assignment_no' => $assignmentNo,
        'fiscal_year_id' => (int) $fiscalYear['id'],
        'running_no' => $nextNumber,
        'from_user_id' => $userId,
        'sent_reason' => $sentReason,
        'assignment_status' => 'sent_to_team',
    ]);

    $assignmentId = (int) $pdo->lastInsertId();

    $routeStmt = $pdo->prepare(
        'INSERT INTO assignment_route_logs (
            report_id, assignment_id, from_user_id, to_user_id, to_team_id, to_department_id, route_action, route_reason, route_note
         ) VALUES (
            :report_id, :assignment_id, :from_user_id, :to_user_id, :to_team_id, :to_department_id, :route_action, :route_reason, :route_note
         )'
    );
    $routeStmt->execute([
        'report_id' => $reportId,
        'assignment_id' => $assignmentId,
        'from_user_id' => $userId,
        'to_user_id' => null,
        'to_team_id' => $teamId,
        'to_department_id' => null,
        'route_action' => 'admin_to_team',
        'route_reason' => $sentReason,
        'route_note' => 'Admin assigned report to team',
    ]);

    $newStatus = in_array((string) $report['status'], ['pending', 'admin_review'], true) ? 'in_progress' : (string) $report['status'];

    $updateReportStmt = $pdo->prepare(
        'UPDATE incident_reports
         SET status = :status, updated_at = NOW()
         WHERE id = :id'
    );
    $updateReportStmt->execute([
        'status' => $newStatus,
        'id' => $reportId,
    ]);

    $statusLogStmt = $pdo->prepare(
        'INSERT INTO report_status_logs (report_id, old_status, new_status, changed_by, note)
         VALUES (:report_id, :old_status, :new_status, :changed_by, :note)'
    );
    $statusLogStmt->execute([
        'report_id' => $reportId,
        'old_status' => $report['status'],
        'new_status' => $newStatus,
        'changed_by' => $userId,
        'note' => 'Assigned to team ' . $team['team_code'],
    ]);

    audit_log(
        'admin_assign_report',
        'report_assignment',
        $assignmentId,
        [
            'report_id' => $reportId,
            'team_id' => $teamId,
            'team_code' => (string) $team['team_code'],
            'assignment_no' => $assignmentNo,
            'fiscal_year_id' => (int) $fiscalYear['id'],
            'running_no' => $nextNumber,
            'sent_reason' => $sentReason,
            'old_status' => (string) $report['status'],
            'new_status' => $newStatus,
        ],
        $userId,
        $pdo
    );

    $pdo->commit();

    flash_set('success', 'ส่งต่อรายงานเรียบร้อย เลข assignment คือ ' . $assignmentNo);
} catch (Throwable) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('error', 'ไม่สามารถส่งต่อรายงานได้');
}

redirect('/admin/report_detail.php?id=' . $reportId);
