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
$userId = (int) ($user['id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$currentStatus = (int) ($_POST['current_status'] ?? 1);

if ($teamId <= 0 || $categoryId <= 0) {
    flash_set('error', 'ไม่พบประเภทความเสี่ยงที่ต้องการเปลี่ยนสถานะ');
    redirect('/team/categories.php');
}

try {
    $pdo = Database::connection();
    $categoryStmt = $pdo->prepare(
        'SELECT category_name, is_active
         FROM risk_categories
         WHERE id = :id AND team_id = :team_id
         LIMIT 1'
    );
    $categoryStmt->execute([
        'id' => $categoryId,
        'team_id' => $teamId,
    ]);
    $category = $categoryStmt->fetch();

    if (!$category) {
        flash_set('error', 'ไม่พบประเภทความเสี่ยงที่ต้องการเปลี่ยนสถานะ');
        redirect('/team/categories.php');
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
            redirect('/team/categories.php');
        }
    }

    $stmt = $pdo->prepare(
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

    audit_log(
        'team_toggle_category_status',
        'risk_category',
        $categoryId,
        [
            'category_name' => $category['category_name'],
            'old_status' => (int) $category['is_active'],
            'new_status' => $newStatus,
            'team_id' => $teamId,
        ],
        $userId,
        $pdo
    );

    flash_set('success', $newStatus === 1 ? 'เปิดใช้งานประเภทความเสี่ยงเรียบร้อย' : 'ปิดใช้งานประเภทความเสี่ยงเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถเปลี่ยนสถานะประเภทความเสี่ยงได้');
}

redirect('/team/categories.php');
