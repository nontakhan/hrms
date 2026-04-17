<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$departmentId = (int) ($_POST['department_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 1);
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($departmentId <= 0) {
    flash_set('error', 'ไม่พบหน่วยงานที่ต้องการเปลี่ยนสถานะ');
    redirect('/admin/master_data.php');
}

try {
    $pdo = Database::connection();
    $departmentStmt = $pdo->prepare(
        'SELECT department_code, is_active
         FROM departments
         WHERE id = :id
         LIMIT 1'
    );
    $departmentStmt->execute(['id' => $departmentId]);
    $department = $departmentStmt->fetch();

    if (!$department) {
        flash_set('error', 'ไม่พบหน่วยงานที่ต้องการเปลี่ยนสถานะ');
        redirect('/admin/master_data.php');
    }

    $newStatus = $currentStatus === 1 ? 0 : 1;

    if ($newStatus === 0) {
        $activeUsersStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM users
             WHERE department_id = :department_id AND is_active = 1'
        );
        $activeUsersStmt->execute(['department_id' => $departmentId]);
        if ((int) $activeUsersStmt->fetchColumn() > 0) {
            flash_set('error', 'ไม่สามารถปิดใช้งานหน่วยงานที่ยังมีผู้ใช้งาน active อยู่ได้');
            redirect('/admin/master_data.php');
        }

        $activeReportsStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM incident_reports
             WHERE status NOT IN ('completed', 'cancelled')
               AND (
                   incident_department_id = :department_id
                   OR reporter_department_id = :department_id
                   OR related_department_id = :department_id
               )"
        );
        $activeReportsStmt->execute(['department_id' => $departmentId]);
        if ((int) $activeReportsStmt->fetchColumn() > 0) {
            flash_set('error', 'ไม่สามารถปิดใช้งานหน่วยงานที่ยังมีรายงานค้างอยู่ได้');
            redirect('/admin/master_data.php');
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE departments SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $departmentId,
    ]);

    audit_log(
        'admin_toggle_department_status',
        'department',
        $departmentId,
        [
            'department_code' => $department['department_code'],
            'old_status' => (int) $department['is_active'],
            'new_status' => $newStatus,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานหน่วยงานเรียบร้อย' : 'ปิดใช้งานหน่วยงานเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะหน่วยงานได้');
}

redirect('/admin/master_data.php');
