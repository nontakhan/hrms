<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$teamId = (int) ($_POST['team_id'] ?? 0);
$teamCode = strtoupper(trim((string) ($_POST['team_code'] ?? '')));
$teamName = trim((string) ($_POST['team_name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));

if ($teamId <= 0 || $teamCode === '' || $teamName === '') {
    flash_set('error', 'กรุณากรอกข้อมูลทีมนำให้ครบ');
    redirect('/admin/master_data.php');
}

try {
    $stmt = Database::connection()->prepare(
        'UPDATE teams
         SET team_code = :team_code,
             team_name = :team_name,
             description = :description,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'team_code' => $teamCode,
        'team_name' => $teamName,
        'description' => $description !== '' ? $description : null,
        'id' => $teamId,
    ]);

    flash_set('success', 'บันทึกการแก้ไขทีมนำเรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'รหัสทีมนำนี้ถูกใช้งานแล้ว'
        : 'ไม่สามารถแก้ไขทีมนำได้';

    flash_set('error', $message);
}

redirect('/admin/master_data.php');
