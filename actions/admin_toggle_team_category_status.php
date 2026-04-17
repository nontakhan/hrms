<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$categoryId = (int) ($_POST['category_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 1);
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($categoryId <= 0) {
    flash_set('error', 'ไม่พบประเภทความเสี่ยงที่ต้องการเปลี่ยนสถานะ');
    redirect('/admin/master_data.php');
}

try {
    $pdo = Database::connection();
    $categoryStmt = $pdo->prepare(
        'SELECT category_name, is_active
         FROM risk_categories
         WHERE id = :id
         LIMIT 1'
    );
    $categoryStmt->execute(['id' => $categoryId]);
    $category = $categoryStmt->fetch();

    if (!$category) {
        flash_set('error', 'ไม่พบประเภทความเสี่ยงที่ต้องการเปลี่ยนสถานะ');
        redirect('/admin/master_data.php');
    }

    $newStatus = $currentStatus === 1 ? 0 : 1;

    if ($newStatus === 0) {
        $usageStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM team_reviews
             WHERE selected_category_id = :category_id'
        );
        $usageStmt->execute(['category_id' => $categoryId]);
        if ((int) $usageStmt->fetchColumn() > 0) {
            flash_set('error', 'ไม่สามารถปิดใช้งานประเภทความเสี่ยงที่ถูกใช้งานในผลพิจารณาแล้วได้');
            redirect('/admin/master_data.php');
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE risk_categories SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'is_active' => $newStatus,
        'id' => $categoryId,
    ]);

    audit_log(
        'admin_toggle_team_category_status',
        'risk_category',
        $categoryId,
        [
            'category_name' => $category['category_name'],
            'old_status' => (int) $category['is_active'],
            'new_status' => $newStatus,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานประเภทความเสี่ยงเรียบร้อย' : 'ปิดใช้งานประเภทความเสี่ยงเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะประเภทความเสี่ยงได้');
}

redirect('/admin/master_data.php');
