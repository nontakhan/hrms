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

if ($departmentId <= 0) {
    flash_set('error', 'ไม่พบหน่วยงานที่ต้องการเปลี่ยนสถานะ');
    redirect('/admin/master_data.php');
}

try {
    $newStatus = $currentStatus === 1 ? 0 : 1;
    $stmt = Database::connection()->prepare(
        'UPDATE departments SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $departmentId,
    ]);

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานหน่วยงานเรียบร้อย' : 'ปิดใช้งานหน่วยงานเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะหน่วยงานได้');
}

redirect('/admin/master_data.php');
