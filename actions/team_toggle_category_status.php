<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('TEAM_LEAD');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/team/categories.php');
}

$user = Auth::user();
$teamId = (int) ($user['team_id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 1);

if ($teamId <= 0 || $categoryId <= 0) {
    flash_set('error', 'ไม่พบประเภทความเสี่ยงที่ต้องการเปลี่ยนสถานะ');
    redirect('/team/categories.php');
}

try {
    $newStatus = $currentStatus === 1 ? 0 : 1;
    $stmt = Database::connection()->prepare(
        'UPDATE risk_categories
         SET is_active = :is_active,
             updated_at = NOW()
         WHERE id = :id AND team_id = :team_id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $categoryId,
        'team_id' => $teamId,
    ]);

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานประเภทความเสี่ยงเรียบร้อย' : 'ปิดใช้งานประเภทความเสี่ยงเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะประเภทความเสี่ยงได้');
}

redirect('/team/categories.php');
