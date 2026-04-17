<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/users.php');
}

$username = trim((string) ($_POST['username'] ?? ''));
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$roleId = (int) ($_POST['role_id'] ?? 0);
$departmentId = (int) ($_POST['department_id'] ?? 0);
$teamId = (int) ($_POST['team_id'] ?? 0);
$headLevel = trim((string) ($_POST['head_level'] ?? ''));

if ($username === '' || $fullName === '' || $password === '' || $roleId <= 0) {
    flash_set('error', 'กรุณากรอกข้อมูลผู้ใช้ให้ครบ');
    redirect('/admin/users.php');
}

if (strlen($password) < 6) {
    flash_set('error', 'รหัสผ่านเริ่มต้นต้องมีอย่างน้อย 6 ตัวอักษร');
    redirect('/admin/users.php');
}

try {
    $pdo = Database::connection();
    $actor = Auth::user();
    $actorId = isset($actor['id']) ? (int) $actor['id'] : null;

    $roleStmt = $pdo->prepare('SELECT id, role_code FROM roles WHERE id = :id LIMIT 1');
    $roleStmt->execute(['id' => $roleId]);
    $role = $roleStmt->fetch();

    if (!$role) {
        flash_set('error', 'ไม่พบ role ที่เลือก');
        redirect('/admin/users.php');
    }

    $roleCode = (string) $role['role_code'];
    $resolvedTeamId = null;
    $resolvedHeadLevel = null;
    $resolvedDepartmentId = $departmentId > 0 ? $departmentId : null;

    if ($roleCode === 'TEAM_LEAD') {
        if ($teamId <= 0) {
            flash_set('error', 'ผู้ใช้ role ทีมนำต้องเลือกทีมนำ');
            redirect('/admin/users.php');
        }
        $resolvedTeamId = $teamId;
    }

    if ($roleCode === 'DEPARTMENT_HEAD') {
        if (!in_array($headLevel, ['group_head', 'unit_head'], true)) {
            flash_set('error', 'กรุณาเลือกระดับหัวหน้า');
            redirect('/admin/users.php');
        }
        $resolvedHeadLevel = $headLevel;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (
            username, password_hash, full_name, role_id, department_id, team_id, head_level, is_active
         ) VALUES (
            :username, :password_hash, :full_name, :role_id, :department_id, :team_id, :head_level, 1
         )'
    );
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'full_name' => $fullName,
        'role_id' => $roleId,
        'department_id' => $resolvedDepartmentId,
        'team_id' => $resolvedTeamId,
        'head_level' => $resolvedHeadLevel,
    ]);

    $newUserId = (int) $pdo->lastInsertId();

    audit_log(
        'admin_create_user',
        'user',
        $newUserId,
        [
            'username' => $username,
            'full_name' => $fullName,
            'role_id' => $roleId,
            'role_code' => $roleCode,
            'department_id' => $resolvedDepartmentId,
            'team_id' => $resolvedTeamId,
            'head_level' => $resolvedHeadLevel,
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'บันทึกผู้ใช้ใหม่เรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'username นี้ถูกใช้งานแล้ว'
        : 'ไม่สามารถบันทึกผู้ใช้ได้';

    flash_set('error', $message);
}

redirect('/admin/users.php');
