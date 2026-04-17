<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$teamId = (int) ($_POST['team_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 1);
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($teamId <= 0) {
    flash_set('error', 'ไม่พบทีมนำที่ต้องการเปลี่ยนสถานะ');
    redirect('/admin/master_data.php');
}

try {
    $pdo = Database::connection();
    $teamStmt = $pdo->prepare('SELECT team_code, is_active FROM teams WHERE id = :id LIMIT 1');
    $teamStmt->execute(['id' => $teamId]);
    $team = $teamStmt->fetch();

    if (!$team) {
        flash_set('error', 'ไม่พบทีมนำที่ต้องการเปลี่ยนสถานะ');
        redirect('/admin/master_data.php');
    }

    $newStatus = $currentStatus === 1 ? 0 : 1;

    if ($newStatus === 0) {
        $activeUsersStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM users
             WHERE team_id = :team_id AND is_active = 1'
        );
        $activeUsersStmt->execute(['team_id' => $teamId]);
        $activeUsers = (int) $activeUsersStmt->fetchColumn();

        if ($activeUsers > 0) {
            flash_set('error', 'ไม่สามารถปิดใช้งานทีมนำที่ยังมีผู้ใช้งาน active อยู่ได้');
            redirect('/admin/master_data.php');
        }

        $activeAssignmentsStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM report_assignments
             WHERE target_team_id = :team_id
               AND assignment_status NOT IN ('completed', 'cancelled')"
        );
        $activeAssignmentsStmt->execute(['team_id' => $teamId]);
        $activeAssignments = (int) $activeAssignmentsStmt->fetchColumn();

        if ($activeAssignments > 0) {
            flash_set('error', 'ไม่สามารถปิดใช้งานทีมนำที่ยังมี assignment ค้างอยู่ได้');
            redirect('/admin/master_data.php');
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE teams SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $teamId,
    ]);

    audit_log(
        'admin_toggle_team_status',
        'team',
        $teamId,
        [
            'team_code' => $team['team_code'],
            'old_status' => (int) $team['is_active'],
            'new_status' => $newStatus,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานทีมนำเรียบร้อย' : 'ปิดใช้งานทีมนำเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะทีมนำได้');
}

redirect('/admin/master_data.php');
