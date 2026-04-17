<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_workflow.php');
}

$fiscalYearId = (int) ($_POST['fiscal_year_id'] ?? 0);
$yearLabel = trim((string) ($_POST['year_label'] ?? ''));
$yearShort = trim((string) ($_POST['year_short'] ?? ''));
$dateStart = trim((string) ($_POST['date_start'] ?? ''));
$dateEnd = trim((string) ($_POST['date_end'] ?? ''));
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($fiscalYearId <= 0 || $yearLabel === '' || $yearShort === '' || $dateStart === '' || $dateEnd === '') {
    flash_set('error', 'กรุณากรอกข้อมูลปีงบประมาณให้ครบ');
    redirect('/admin/settings_workflow.php');
}

if ($dateStart > $dateEnd) {
    flash_set('error', 'วันที่เริ่มต้องไม่มากกว่าวันที่สิ้นสุด');
    redirect('/admin/settings_workflow.php');
}

try {
    $pdo = Database::connection();
    $existingStmt = $pdo->prepare(
        'SELECT year_label, year_short, date_start, date_end
         FROM fiscal_years
         WHERE id = :id
         LIMIT 1'
    );
    $existingStmt->execute(['id' => $fiscalYearId]);
    $existingYear = $existingStmt->fetch();

    if (!$existingYear) {
        flash_set('error', 'ไม่พบปีงบประมาณที่ต้องการแก้ไข');
        redirect('/admin/settings_workflow.php');
    }

    $stmt = $pdo->prepare(
        'UPDATE fiscal_years
         SET year_label = :year_label,
             year_short = :year_short,
             date_start = :date_start,
             date_end = :date_end,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'year_label' => $yearLabel,
        'year_short' => $yearShort,
        'date_start' => $dateStart,
        'date_end' => $dateEnd,
        'id' => $fiscalYearId,
    ]);

    audit_log(
        'admin_update_fiscal_year',
        'fiscal_year',
        $fiscalYearId,
        [
            'before' => [
                'year_label' => $existingYear['year_label'],
                'year_short' => $existingYear['year_short'],
                'date_start' => $existingYear['date_start'],
                'date_end' => $existingYear['date_end'],
            ],
            'after' => [
                'year_label' => $yearLabel,
                'year_short' => $yearShort,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ],
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'บันทึกการแก้ไขปีงบประมาณเรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'ข้อมูลปีงบประมาณนี้ซ้ำกับรายการเดิม'
        : 'ไม่สามารถแก้ไขปีงบประมาณได้';
    flash_set('error', $message);
}

redirect('/admin/settings_workflow.php');
