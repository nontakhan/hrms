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

if ($teamId <= 0) {
    flash_set('error', 'ไม่พบทีมนำที่ต้องการเปลี่ยนสถานะ');
    redirect('/admin/master_data.php');
}

try {
    $newStatus = $currentStatus === 1 ? 0 : 1;
    $stmt = Database::connection()->prepare(
        'UPDATE teams SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $teamId,
    ]);

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานทีมนำเรียบร้อย' : 'ปิดใช้งานทีมนำเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะทีมนำได้');
}

redirect('/admin/master_data.php');
