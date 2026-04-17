<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$categoryId = (int) ($_POST['category_id'] ?? 0);
$teamId = (int) ($_POST['team_id'] ?? 0);
$parentId = (int) ($_POST['parent_id'] ?? 0);
$categoryCode = trim((string) ($_POST['category_code'] ?? ''));
$categoryName = trim((string) ($_POST['category_name'] ?? ''));

if ($categoryId <= 0 || $teamId <= 0 || $categoryName === '') {
    flash_set('error', 'กรุณากรอกข้อมูลประเภทความเสี่ยงให้ครบ');
    redirect('/admin/master_data.php');
}

try {
    $pdo = Database::connection();
    $resolvedParentId = null;

    if ($parentId > 0) {
        if ($parentId === $categoryId) {
            flash_set('error', 'ไม่สามารถเลือก parent เป็นรายการเดียวกันได้');
            redirect('/admin/master_data.php');
        }

        $parentStmt = $pdo->prepare(
            'SELECT id
             FROM risk_categories
             WHERE id = :id AND team_id = :team_id AND is_active = 1
             LIMIT 1'
        );
        $parentStmt->execute([
            'id' => $parentId,
            'team_id' => $teamId,
        ]);

        if (!$parentStmt->fetch()) {
            flash_set('error', 'Parent category ต้องอยู่ในทีมนำเดียวกัน');
            redirect('/admin/master_data.php');
        }

        $resolvedParentId = $parentId;
    }

    $stmt = $pdo->prepare(
        'UPDATE risk_categories
         SET team_id = :team_id,
             parent_id = :parent_id,
             category_name = :category_name,
             category_code = :category_code,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'team_id' => $teamId,
        'parent_id' => $resolvedParentId,
        'category_name' => $categoryName,
        'category_code' => $categoryCode !== '' ? strtoupper($categoryCode) : null,
        'id' => $categoryId,
    ]);

    flash_set('success', 'บันทึกการแก้ไขประเภทความเสี่ยงเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถแก้ไขประเภทความเสี่ยงได้');
}

redirect('/admin/master_data.php');
