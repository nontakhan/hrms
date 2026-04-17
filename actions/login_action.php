<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/login.php');
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

old_set(['username' => $username]);

if ($username === '' || $password === '') {
    flash_set('error', 'กรุณากรอก username และ password');
    redirect('/login.php');
}

try {
    $sql = <<<SQL
        SELECT u.id, u.username, u.password_hash, u.full_name, r.role_code, r.role_name
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.username = :username AND u.is_active = 1
        LIMIT 1
    SQL;

    $stmt = Database::connection()->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        flash_set('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        redirect('/login.php');
    }

    unset($user['password_hash']);
    Auth::login($user);
    old_clear();
    redirect('/dashboard.php');
} catch (Throwable) {
    flash_set('error', 'ยังไม่สามารถเชื่อมต่อระบบผู้ใช้ได้');
    redirect('/login.php');
}
