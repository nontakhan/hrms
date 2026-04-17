<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/users.php');
}

$userId = (int) ($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    flash_set('error', 'ไม่พบผู้ใช้ที่ต้องการรีเซ็ตรหัสผ่าน');
    redirect('/admin/users.php');
}

try {
    $temporaryPassword = 'ChangeMe123';
    $stmt = Database::connection()->prepare(
        'UPDATE users
         SET password_hash = :password_hash, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
        'id' => $userId,
    ]);

    flash_set('success', 'รีเซ็ตรหัสผ่านเรียบร้อย รหัสชั่วคราวคือ ' . $temporaryPassword);
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถรีเซ็ตรหัสผ่านได้');
}

redirect('/admin/users.php');
