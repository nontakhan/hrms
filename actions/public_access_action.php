<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/public/report_access.php');
}

$inputPassword = (string) ($_POST['access_password'] ?? '');
$storedHash = (string) setting('public_report_password_hash', '');

if ($storedHash === '') {
    $storedHash = password_hash('1234', PASSWORD_DEFAULT);
}

if (!password_verify($inputPassword, $storedHash)) {
    flash_set('error', 'รหัสผ่านกลางไม่ถูกต้อง');
    redirect('/public/report_access.php');
}

Auth::grantPublicReportAccess();
flash_set('success', 'ยืนยันสิทธิ์เรียบร้อย');
redirect('/public/report_create.php');
