<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$teamId = (int) ($_POST['team_id'] ?? 0);
$parentId = (int) ($_POST['parent_id'] ?? 0);
$categoryCode = trim((string) ($_POST['category_code'] ?? ''));
$categoryName = trim((string) ($_POST['category_name'] ?? ''));
$sortOrderInput = (int) ($_POST['sort_order'] ?? 0);
$userId = (int) (Auth::user()['id'] ?? 0);

if ($teamId <= 0 || $categoryName === '') {
    flash_set('error', 'กรุณาเลือกทีมนำและกรอกชื่อประเภทความเสี่ยง');
    redirect('/admin/master_data.php');
}

try {
    $pdo = Database::connection();

    $teamStmt = $pdo->prepare('SELECT id FROM teams WHERE id = :id AND is_active = 1 LIMIT 1');
    $teamStmt->execute(['id' => $teamId]);

    if (!$teamStmt->fetch()) {
        flash_set('error', 'ไม่พบทีมนำที่เลือก');
        redirect('/admin/master_data.php');
    }

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
        $parent = $parentStmt->fetch();

        if (!$parent) {
            flash_set('error', 'Parent category ต้องอยู่ในทีมนำเดียวกัน');
            redirect('/admin/master_data.php');
        }

        $resolvedParentId = $parentId;
    }

    $sortOrder = $sortOrderInput > 0 ? $sortOrderInput : 0;
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
        'category_code' => $categoryCode !== '' ? strtoupper($categoryCode) : null,
        'sort_order' => $sortOrder,
        'created_by' => $userId > 0 ? $userId : null,
    ]);

    $categoryId = (int) $pdo->lastInsertId();
    audit_log(
        'admin_create_team_category',
        'risk_category',
        $categoryId,
        [
            'team_id' => $teamId,
            'parent_id' => $resolvedParentId,
            'category_name' => $categoryName,
            'category_code' => $categoryCode !== '' ? strtoupper($categoryCode) : null,
            'sort_order' => $sortOrder,
            'is_active' => 1,
        ],
        $userId,
        $pdo
    );

    flash_set('success', 'บันทึกประเภทความเสี่ยงของทีมนำเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถบันทึกประเภทความเสี่ยงได้');
}

redirect('/admin/master_data.php');
