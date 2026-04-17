<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$teamCode = strtoupper(trim((string) ($_POST['team_code'] ?? '')));
$teamName = trim((string) ($_POST['team_name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));

if ($teamCode === '' || $teamName === '') {
    flash_set('error', 'กรุณากรอกรหัสทีมนำและชื่อทีมนำให้ครบ');
    redirect('/admin/master_data.php');
}

try {
    $stmt = Database::connection()->prepare(
        'INSERT INTO teams (team_code, team_name, description, is_active)
         VALUES (:team_code, :team_name, :description, 1)'
    );
    $stmt->execute([
        'team_code' => $teamCode,
        'team_name' => $teamName,
        'description' => $description !== '' ? $description : null,
    ]);

    flash_set('success', 'บันทึกข้อมูลทีมนำเรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'รหัสทีมนำนี้ถูกใช้แล้ว'
        : 'ไม่สามารถบันทึกข้อมูลทีมนำได้';

    flash_set('error', $message);
}

redirect('/admin/master_data.php');
