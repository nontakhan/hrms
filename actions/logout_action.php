<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/dashboard.php');
}

Auth::logout();
flash_set('success', 'ออกจากระบบเรียบร้อย');
redirect('/login.php');
