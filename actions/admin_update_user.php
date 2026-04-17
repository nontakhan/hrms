<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/users.php');
}

$userId = (int) ($_POST['user_id'] ?? 0);
$username = trim((string) ($_POST['username'] ?? ''));
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$roleId = (int) ($_POST['role_id'] ?? 0);
$departmentId = (int) ($_POST['department_id'] ?? 0);
$teamId = (int) ($_POST['team_id'] ?? 0);
$headLevel = trim((string) ($_POST['head_level'] ?? ''));

if ($userId <= 0 || $username === '' || $fullName === '' || $roleId <= 0) {
    flash_set('error', 'กรุณากรอกข้อมูลผู้ใช้ให้ครบ');
    redirect('/admin/users.php?edit=' . $userId);
}

try {
    $pdo = Database::connection();
    $actor = Auth::user();
    $actorId = isset($actor['id']) ? (int) $actor['id'] : null;

    $existingStmt = $pdo->prepare(
        'SELECT id, username, full_name, role_id, department_id, team_id, head_level
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $existingStmt->execute(['id' => $userId]);
    $existingUser = $existingStmt->fetch();

    if (!$existingUser) {
        flash_set('error', 'ไม่พบผู้ใช้ที่ต้องการแก้ไข');
        redirect('/admin/users.php');
    }

    $roleStmt = $pdo->prepare('SELECT id, role_code FROM roles WHERE id = :id LIMIT 1');
    $roleStmt->execute(['id' => $roleId]);
    $role = $roleStmt->fetch();

    if (!$role) {
        flash_set('error', 'ไม่พบ role ที่เลือก');
        redirect('/admin/users.php?edit=' . $userId);
    }

    $roleCode = (string) $role['role_code'];
    $resolvedTeamId = null;
    $resolvedHeadLevel = null;
    $resolvedDepartmentId = $departmentId > 0 ? $departmentId : null;

    if ($roleCode === 'TEAM_LEAD') {
        if ($teamId <= 0) {
            flash_set('error', 'ผู้ใช้ role ทีมนำต้องเลือกทีมนำ');
            redirect('/admin/users.php?edit=' . $userId);
        }
        $resolvedTeamId = $teamId;
    }

    if ($roleCode === 'DEPARTMENT_HEAD') {
        if (!in_array($headLevel, ['group_head', 'unit_head'], true)) {
            flash_set('error', 'กรุณาเลือกระดับหัวหน้า');
            redirect('/admin/users.php?edit=' . $userId);
        }
        $resolvedHeadLevel = $headLevel;
    }

    $stmt = $pdo->prepare(
        'UPDATE users
         SET username = :username,
             full_name = :full_name,
             role_id = :role_id,
             department_id = :department_id,
             team_id = :team_id,
             head_level = :head_level,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'username' => $username,
        'full_name' => $fullName,
        'role_id' => $roleId,
        'department_id' => $resolvedDepartmentId,
        'team_id' => $resolvedTeamId,
        'head_level' => $resolvedHeadLevel,
        'id' => $userId,
    ]);

    audit_log(
        'admin_update_user',
        'user',
        $userId,
        [
            'before' => [
                'username' => $existingUser['username'],
                'full_name' => $existingUser['full_name'],
                'role_id' => (int) $existingUser['role_id'],
                'department_id' => $existingUser['department_id'] !== null ? (int) $existingUser['department_id'] : null,
                'team_id' => $existingUser['team_id'] !== null ? (int) $existingUser['team_id'] : null,
                'head_level' => $existingUser['head_level'],
            ],
            'after' => [
                'username' => $username,
                'full_name' => $fullName,
                'role_id' => $roleId,
                'role_code' => $roleCode,
                'department_id' => $resolvedDepartmentId,
                'team_id' => $resolvedTeamId,
                'head_level' => $resolvedHeadLevel,
            ],
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'บันทึกการแก้ไขผู้ใช้เรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'username นี้ถูกใช้งานแล้ว'
        : 'ไม่สามารถแก้ไขข้อมูลผู้ใช้ได้';

    flash_set('error', $message);
}

redirect('/admin/users.php');
