<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_workflow.php');
}

$teamId = (int) ($_POST['team_id'] ?? 0);
$fiscalYearId = (int) ($_POST['fiscal_year_id'] ?? 0);
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($teamId <= 0 || $fiscalYearId <= 0) {
    flash_set('error', 'ไม่พบข้อมูลทีมนำหรือปีงบประมาณที่ต้องการรีเซ็ตเลขรัน');
    redirect('/admin/settings_workflow.php');
}

try {
    $pdo = Database::connection();

    $summaryStmt = $pdo->prepare(
        'SELECT t.team_code, fy.year_label, fy.year_short
         FROM teams t
         INNER JOIN fiscal_years fy ON fy.id = :fiscal_year_id
         WHERE t.id = :team_id
         LIMIT 1'
    );
    $summaryStmt->execute([
        'team_id' => $teamId,
        'fiscal_year_id' => $fiscalYearId,
    ]);
    $summary = $summaryStmt->fetch();

    if (!$summary) {
        flash_set('error', 'ไม่พบทีมนำหรือปีงบประมาณที่ต้องการรีเซ็ตเลขรัน');
        redirect('/admin/settings_workflow.php');
    }

    $assignmentStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM report_assignments
         WHERE target_team_id = :team_id
           AND fiscal_year_id = :fiscal_year_id'
    );
    $assignmentStmt->execute([
        'team_id' => $teamId,
        'fiscal_year_id' => $fiscalYearId,
    ]);
    $assignmentCount = (int) $assignmentStmt->fetchColumn();

    if ($assignmentCount > 0) {
        flash_set('error', 'ไม่สามารถรีเซ็ตเลขรันได้ เพราะปีงบนี้มี assignment ถูกออกเลขไปแล้ว');
        redirect('/admin/settings_workflow.php');
    }

    $currentStmt = $pdo->prepare(
        'SELECT id, last_number
         FROM team_running_numbers
         WHERE team_id = :team_id
           AND fiscal_year_id = :fiscal_year_id
         LIMIT 1'
    );
    $currentStmt->execute([
        'team_id' => $teamId,
        'fiscal_year_id' => $fiscalYearId,
    ]);
    $current = $currentStmt->fetch();

    if (!$current) {
        flash_set('error', 'ยังไม่มีเลขรันที่ต้องรีเซ็ตสำหรับรายการนี้');
        redirect('/admin/settings_workflow.php');
    }

    $stmt = $pdo->prepare(
        'UPDATE team_running_numbers
         SET last_number = 0, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute(['id' => (int) $current['id']]);

    audit_log(
        'admin_reset_team_running_number',
        'team_running_numbers',
        (int) $current['id'],
        [
            'team_id' => $teamId,
            'team_code' => $summary['team_code'],
            'fiscal_year_id' => $fiscalYearId,
            'year_label' => $summary['year_label'],
            'year_short' => $summary['year_short'],
            'old_last_number' => (int) $current['last_number'],
            'new_last_number' => 0,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'รีเซ็ตเลขรันเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถรีเซ็ตเลขรันได้');
}

redirect('/admin/settings_workflow.php');
