<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/settings_public_access.php');
}

$password = (string) ($_POST['public_password'] ?? '');
$passwordConfirm = (string) ($_POST['public_password_confirm'] ?? '');

if (mb_strlen($password) < 4) {
    flash_set('error', 'Password กลางต้องยาวอย่างน้อย 4 ตัวอักษร');
    redirect('/admin/settings_public_access.php');
}

if ($password !== $passwordConfirm) {
    flash_set('error', 'Password กลางและการยืนยันรหัสไม่ตรงกัน');
    redirect('/admin/settings_public_access.php');
}

try {
    $user = Auth::user();
    upsert_setting(
        'public_report_password_hash',
        password_hash($password, PASSWORD_DEFAULT),
        isset($user['id']) ? (int) $user['id'] : null,
        'Hash password for public report access'
    );

    flash_set('success', 'บันทึก password กลางเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถบันทึก password กลางได้');
}

redirect('/admin/settings_public_access.php');
