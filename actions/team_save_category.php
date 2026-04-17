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
$parentId = (int) ($_POST['parent_id'] ?? 0);
$categoryCode = strtoupper(trim((string) ($_POST['category_code'] ?? '')));
$categoryName = trim((string) ($_POST['category_name'] ?? ''));
$sortOrderInput = (int) ($_POST['sort_order'] ?? 0);
$sortOrder = $sortOrderInput > 0 ? $sortOrderInput : 0;

if ($teamId <= 0 || $categoryName === '') {
    flash_set('error', 'กรุณากรอกชื่อประเภทความเสี่ยงให้ครบ');
    redirect('/team/categories.php');
}

try {
    $pdo = Database::connection();
    $resolvedParentId = null;

    if ($parentId > 0) {
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

    if ($sortOrder <= 0) {
        $sortStmt = $pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1
             FROM risk_categories
             WHERE team_id = :team_id'
        );
        $sortStmt->execute(['team_id' => $teamId]);
        $sortOrder = (int) $sortStmt->fetchColumn();
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO risk_categories (
            team_id, parent_id, category_name, category_code, sort_order, is_active, created_by
         ) VALUES (
            :team_id, :parent_id, :category_name, :category_code, :sort_order, 1, :created_by
         )'
    );
    $insertStmt->execute([
        'team_id' => $teamId,
        'parent_id' => $resolvedParentId,
        'category_name' => $categoryName,
        'category_code' => $categoryCode !== '' ? $categoryCode : null,
        'sort_order' => $sortOrder,
        'created_by' => $userId > 0 ? $userId : null,
    ]);

    flash_set('success', 'บันทึกประเภทความเสี่ยงของทีมนำเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถบันทึกประเภทความเสี่ยงได้');
}

redirect('/team/categories.php');
