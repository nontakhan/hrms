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
    $userId = isset($user['id']) ? (int) $user['id'] : null;

    upsert_setting(
        'public_report_password_hash',
        password_hash($password, PASSWORD_DEFAULT),
        $userId,
        'Hash password for public report access'
    );

    audit_log(
        'admin_update_public_report_password',
        'system_setting',
        'public_report_password_hash',
        [
            'setting_key' => 'public_report_password_hash',
            'password_changed' => true,
        ],
        $userId
    );

    flash_set('success', 'บันทึก password กลางเรียบร้อย');
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถบันทึก password กลางได้');
}

redirect('/admin/settings_public_access.php');
