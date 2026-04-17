<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_workflow.php');
}

$teamId = (int) ($_POST['team_id'] ?? 0);
$departmentId = (int) ($_POST['department_id'] ?? 0);
$viewerUserId = (int) ($_POST['viewer_user_id'] ?? 0);
$visibilityType = trim((string) ($_POST['visibility_type'] ?? 'supervisor'));
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($teamId <= 0 || $departmentId <= 0 || $viewerUserId <= 0) {
    flash_set('error', 'กรุณากรอกข้อมูลสิทธิ์มองเห็นให้ครบ');
    redirect('/admin/settings_workflow.php');
}

if (!in_array($visibilityType, ['direct', 'supervisor'], true)) {
    $visibilityType = 'supervisor';
}

try {
    $pdo = Database::connection();

    $viewerStmt = $pdo->prepare(
        "SELECT u.id
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id
           AND u.is_active = 1
           AND r.role_code = 'DEPARTMENT_HEAD'
         LIMIT 1"
    );
    $viewerStmt->execute(['id' => $viewerUserId]);

    if (!$viewerStmt->fetch()) {
        flash_set('error', 'ผู้ที่เลือกต้องเป็นหัวหน้ากลุ่มงาน/หัวหน้างานที่ยัง active');
        redirect('/admin/settings_workflow.php');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO team_department_visibility (
            team_id, department_id, viewer_user_id, visibility_type, is_active
         ) VALUES (
            :team_id, :department_id, :viewer_user_id, :visibility_type, 1
         )
         ON DUPLICATE KEY UPDATE
            visibility_type = VALUES(visibility_type),
            is_active = 1'
    );
    $stmt->execute([
        'team_id' => $teamId,
        'department_id' => $departmentId,
        'viewer_user_id' => $viewerUserId,
        'visibility_type' => $visibilityType,
    ]);

    audit_log(
        'admin_save_team_visibility',
        'team_department_visibility',
        $teamId . ':' . $departmentId . ':' . $viewerUserId,
        [
            'team_id' => $teamId,
            'department_id' => $departmentId,
            'viewer_user_id' => $viewerUserId,
            'visibility_type' => $visibilityType,
            'is_active' => 1,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'บันทึกสิทธิ์มองเห็นเคสเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถบันทึกสิทธิ์มองเห็นเคสได้');
}

redirect('/admin/settings_workflow.php');
