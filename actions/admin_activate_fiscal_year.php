<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_workflow.php');
}

$fiscalYearId = (int) ($_POST['fiscal_year_id'] ?? 0);
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($fiscalYearId <= 0) {
    flash_set('error', 'ไม่พบปีงบประมาณที่ต้องการเปิดใช้งาน');
    redirect('/admin/settings_workflow.php');
}

try {
    $pdo = Database::connection();
    $yearStmt = $pdo->prepare('SELECT id, year_label, year_short FROM fiscal_years WHERE id = :id LIMIT 1');
    $yearStmt->execute(['id' => $fiscalYearId]);
    $year = $yearStmt->fetch();

    if (!$year) {
        flash_set('error', 'ไม่พบปีงบประมาณที่ต้องการเปิดใช้งาน');
        redirect('/admin/settings_workflow.php');
    }

    $pdo->beginTransaction();

    $pdo->exec('UPDATE fiscal_years SET is_active = 0, updated_at = NOW()');

    $stmt = $pdo->prepare(
        'UPDATE fiscal_years
         SET is_active = 1, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute(['id' => $fiscalYearId]);

    upsert_setting('active_fiscal_year_id', (string) $fiscalYearId, $actorId, 'Current active fiscal year');

    audit_log(
        'admin_activate_fiscal_year',
        'fiscal_year',
        $fiscalYearId,
        [
            'year_label' => $year['year_label'],
            'year_short' => $year['year_short'],
            'active' => true,
        ],
        $actorId,
        $pdo
    );

    $pdo->commit();

    flash_set('success', 'ตั้งค่าปีงบประมาณที่ใช้งานเรียบร้อย');
} catch (Throwable) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', 'ไม่สามารถตั้งค่าปีงบประมาณได้');
}

redirect('/admin/settings_workflow.php');
