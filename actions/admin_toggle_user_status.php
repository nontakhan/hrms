<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/users.php');
}

$userId = (int) ($_POST['user_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 1);
$currentAdminId = (int) (Auth::user()['id'] ?? 0);

if ($userId <= 0) {
    flash_set('error', 'ไม่พบผู้ใช้ที่ต้องการเปลี่ยนสถานะ');
    redirect('/admin/users.php');
}

if ($userId === $currentAdminId) {
    flash_set('error', 'ไม่สามารถปิดใช้งานบัญชีของตัวเองได้');
    redirect('/admin/users.php');
}

try {
    $newStatus = $currentStatus === 1 ? 0 : 1;
    $stmt = Database::connection()->prepare(
        'UPDATE users
         SET is_active = :is_active, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $userId,
    ]);

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานผู้ใช้เรียบร้อย' : 'ปิดใช้งานผู้ใช้เรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะผู้ใช้ได้');
}

redirect('/admin/users.php');
