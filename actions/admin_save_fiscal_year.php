<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_workflow.php');
}

$yearLabel = trim((string) ($_POST['year_label'] ?? ''));
$yearShort = trim((string) ($_POST['year_short'] ?? ''));
$dateStart = trim((string) ($_POST['date_start'] ?? ''));
$dateEnd = trim((string) ($_POST['date_end'] ?? ''));
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($yearLabel === '' || $yearShort === '' || $dateStart === '' || $dateEnd === '') {
    flash_set('error', 'กรุณากรอกข้อมูลปีงบประมาณให้ครบ');
    redirect('/admin/settings_workflow.php');
}

if ($dateStart > $dateEnd) {
    flash_set('error', 'วันที่เริ่มต้องไม่มากกว่าวันที่สิ้นสุด');
    redirect('/admin/settings_workflow.php');
}

try {
    $pdo = Database::connection();
    $stmt = $pdo->prepare(
        'INSERT INTO fiscal_years (year_label, year_short, date_start, date_end, is_active)
         VALUES (:year_label, :year_short, :date_start, :date_end, 0)'
    );
    $stmt->execute([
        'year_label' => $yearLabel,
        'year_short' => $yearShort,
        'date_start' => $dateStart,
        'date_end' => $dateEnd,
    ]);

    $fiscalYearId = (int) $pdo->lastInsertId();
    audit_log(
        'admin_create_fiscal_year',
        'fiscal_year',
        $fiscalYearId,
        [
            'year_label' => $yearLabel,
            'year_short' => $yearShort,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'เพิ่มปีงบประมาณเรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'ปีงบประมาณนี้ถูกบันทึกแล้ว'
        : 'ไม่สามารถบันทึกปีงบประมาณได้';
    flash_set('error', $message);
}

redirect('/admin/settings_workflow.php');
