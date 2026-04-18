<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_workflow.php');
}

$visibilityId = (int) ($_POST['visibility_id'] ?? 0);
$teamId = (int) ($_POST['team_id'] ?? 0);
$departmentId = (int) ($_POST['department_id'] ?? 0);
$viewerUserId = (int) ($_POST['viewer_user_id'] ?? 0);
$visibilityType = trim((string) ($_POST['visibility_type'] ?? 'supervisor'));
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($visibilityId <= 0 || $teamId <= 0 || $departmentId <= 0 || $viewerUserId <= 0) {
    flash_set('error', 'กรุณากรอกข้อมูลสิทธิ์มองเห็นให้ครบ');
    redirect('/admin/settings_workflow.php');
}

if (!in_array($visibilityType, ['direct', 'supervisor'], true)) {
    $visibilityType = 'supervisor';
}

try {
    $pdo = Database::connection();

    $existingStmt = $pdo->prepare(
        'SELECT team_id, department_id, viewer_user_id, visibility_type, is_active
         FROM team_department_visibility
         WHERE id = :id
         LIMIT 1'
    );
    $existingStmt->execute(['id' => $visibilityId]);
    $existingEntry = $existingStmt->fetch();

    if (!$existingEntry) {
        flash_set('error', 'ไม่พบรายการ visibility ที่ต้องการแก้ไข');
        redirect('/admin/settings_workflow.php');
    }

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

    $duplicateStmt = $pdo->prepare(
        'SELECT id
         FROM team_department_visibility
         WHERE team_id = :team_id
           AND department_id = :department_id
           AND viewer_user_id = :viewer_user_id
           AND id <> :id
         LIMIT 1'
    );
    $duplicateStmt->execute([
        'team_id' => $teamId,
        'department_id' => $departmentId,
        'viewer_user_id' => $viewerUserId,
        'id' => $visibilityId,
    ]);

    if ($duplicateStmt->fetch()) {
        flash_set('error', 'มีรายการ visibility เดิมสำหรับทีมนำ หน่วยงาน และหัวหน้าคนนี้อยู่แล้ว');
        redirect('/admin/settings_workflow.php');
    }

    $stmt = $pdo->prepare(
        'UPDATE team_department_visibility
         SET team_id = :team_id,
             department_id = :department_id,
             viewer_user_id = :viewer_user_id,
             visibility_type = :visibility_type
         WHERE id = :id'
    );
    $stmt->execute([
        'team_id' => $teamId,
        'department_id' => $departmentId,
        'viewer_user_id' => $viewerUserId,
        'visibility_type' => $visibilityType,
        'id' => $visibilityId,
    ]);

    audit_log(
        'admin_update_team_visibility',
        'team_department_visibility',
        $visibilityId,
        [
            'before' => [
                'team_id' => (int) $existingEntry['team_id'],
                'department_id' => (int) $existingEntry['department_id'],
                'viewer_user_id' => (int) $existingEntry['viewer_user_id'],
                'visibility_type' => $existingEntry['visibility_type'],
                'is_active' => (int) $existingEntry['is_active'],
            ],
            'after' => [
                'team_id' => $teamId,
                'department_id' => $departmentId,
                'viewer_user_id' => $viewerUserId,
                'visibility_type' => $visibilityType,
            ],
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'บันทึกการแก้ไขสิทธิ์มองเห็นเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถแก้ไขสิทธิ์มองเห็นได้');
}

redirect('/admin/settings_workflow.php');
