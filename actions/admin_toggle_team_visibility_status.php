<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_workflow.php');
}

$visibilityId = (int) ($_POST['visibility_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 1);
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($visibilityId <= 0) {
    flash_set('error', 'ไม่พบรายการ visibility ที่ต้องการเปลี่ยนสถานะ');
    redirect('/admin/settings_workflow.php');
}

try {
    $pdo = Database::connection();
    $existingStmt = $pdo->prepare(
        'SELECT id, team_id, department_id, viewer_user_id, visibility_type, is_active
         FROM team_department_visibility
         WHERE id = :id
         LIMIT 1'
    );
    $existingStmt->execute(['id' => $visibilityId]);
    $entry = $existingStmt->fetch();

    if (!$entry) {
        flash_set('error', 'ไม่พบรายการ visibility ที่ต้องการเปลี่ยนสถานะ');
        redirect('/admin/settings_workflow.php');
    }

    $newStatus = $currentStatus === 1 ? 0 : 1;
    $stmt = $pdo->prepare(
        'UPDATE team_department_visibility
         SET is_active = :is_active
         WHERE id = :id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $visibilityId,
    ]);

    audit_log(
        'admin_toggle_team_visibility_status',
        'team_department_visibility',
        $visibilityId,
        [
            'team_id' => (int) $entry['team_id'],
            'department_id' => (int) $entry['department_id'],
            'viewer_user_id' => (int) $entry['viewer_user_id'],
            'visibility_type' => $entry['visibility_type'],
            'old_status' => (int) $entry['is_active'],
            'new_status' => $newStatus,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานสิทธิ์มองเห็นเรียบร้อย' : 'ปิดใช้งานสิทธิ์มองเห็นเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะสิทธิ์มองเห็นได้');
}

redirect('/admin/settings_workflow.php');
