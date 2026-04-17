<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$departmentId = (int) ($_POST['department_id'] ?? 0);
$departmentCode = strtoupper(trim((string) ($_POST['department_code'] ?? '')));
$departmentName = trim((string) ($_POST['department_name'] ?? ''));
$departmentType = trim((string) ($_POST['department_type'] ?? 'general'));
$isNursingGroup = (int) ($_POST['is_nursing_group'] ?? 0) === 1 ? 1 : 0;
$actorId = (int) (Auth::user()['id'] ?? 0);

if ($departmentId <= 0 || $departmentCode === '' || $departmentName === '') {
    flash_set('error', 'กรุณากรอกข้อมูลหน่วยงานให้ครบ');
    redirect('/admin/master_data.php');
}

if (!in_array($departmentType, ['general', 'clinical', 'support'], true)) {
    $departmentType = 'general';
}

try {
    $pdo = Database::connection();
    $existingStmt = $pdo->prepare(
        'SELECT department_code, department_name, department_type, is_nursing_group
         FROM departments
         WHERE id = :id
         LIMIT 1'
    );
    $existingStmt->execute(['id' => $departmentId]);
    $existingDepartment = $existingStmt->fetch();

    if (!$existingDepartment) {
        flash_set('error', 'ไม่พบหน่วยงานที่ต้องการแก้ไข');
        redirect('/admin/master_data.php');
    }

    $stmt = $pdo->prepare(
        'UPDATE departments
         SET department_code = :department_code,
             department_name = :department_name,
             department_type = :department_type,
             is_nursing_group = :is_nursing_group,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'department_code' => $departmentCode,
        'department_name' => $departmentName,
        'department_type' => $departmentType,
        'is_nursing_group' => $isNursingGroup,
        'id' => $departmentId,
    ]);

    audit_log(
        'admin_update_department',
        'department',
        $departmentId,
        [
            'before' => [
                'department_code' => $existingDepartment['department_code'],
                'department_name' => $existingDepartment['department_name'],
                'department_type' => $existingDepartment['department_type'],
                'is_nursing_group' => (int) $existingDepartment['is_nursing_group'],
            ],
            'after' => [
                'department_code' => $departmentCode,
                'department_name' => $departmentName,
                'department_type' => $departmentType,
                'is_nursing_group' => $isNursingGroup,
            ],
        ],
        $actorId,
        $pdo
    );

    flash_set('success', 'บันทึกการแก้ไขหน่วยงานเรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'รหัสหน่วยงานนี้ถูกใช้งานแล้ว'
        : 'ไม่สามารถแก้ไขหน่วยงานได้';

    flash_set('error', $message);
}

redirect('/admin/master_data.php');
