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
$parentId = (int) ($_POST['parent_id'] ?? 0);
$categoryCode = strtoupper(trim((string) ($_POST['category_code'] ?? '')));
$categoryName = trim((string) ($_POST['category_name'] ?? ''));
$sortOrder = max(1, (int) ($_POST['sort_order'] ?? 1));

if ($teamId <= 0 || $categoryId <= 0 || $categoryName === '') {
    flash_set('error', 'กรุณากรอกข้อมูลประเภทความเสี่ยงให้ครบ');
    redirect('/team/categories.php');
}

try {
    $pdo = Database::connection();

    $categoryStmt = $pdo->prepare(
        'SELECT id
         FROM risk_categories
         WHERE id = :id AND team_id = :team_id
         LIMIT 1'
    );
    $categoryStmt->execute([
        'id' => $categoryId,
        'team_id' => $teamId,
    ]);

    if (!$categoryStmt->fetch()) {
        flash_set('error', 'ไม่พบประเภทความเสี่ยงที่ต้องการแก้ไข');
        redirect('/team/categories.php');
    }

    $resolvedParentId = null;

    if ($parentId > 0) {
        if ($parentId === $categoryId) {
            flash_set('error', 'ไม่สามารถเลือก parent เป็นรายการเดียวกันได้');
            redirect('/team/categories.php');
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
            redirect('/team/categories.php');
        }

        $resolvedParentId = $parentId;
    }

    $stmt = $pdo->prepare(
        'UPDATE risk_categories
         SET parent_id = :parent_id,
             category_name = :category_name,
             category_code = :category_code,
             sort_order = :sort_order,
             updated_at = NOW()
         WHERE id = :id AND team_id = :team_id'
    );
    $stmt->execute([
        'parent_id' => $resolvedParentId,
        'category_name' => $categoryName,
        'category_code' => $categoryCode !== '' ? $categoryCode : null,
        'sort_order' => $sortOrder,
        'id' => $categoryId,
        'team_id' => $teamId,
    ]);

    flash_set('success', 'บันทึกการแก้ไขประเภทความเสี่ยงเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถแก้ไขประเภทความเสี่ยงได้');
}

redirect('/team/categories.php');
