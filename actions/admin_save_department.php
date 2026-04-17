<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/master_data.php');
}

$departmentCode = strtoupper(trim((string) ($_POST['department_code'] ?? '')));
$departmentName = trim((string) ($_POST['department_name'] ?? ''));
$departmentType = trim((string) ($_POST['department_type'] ?? 'general'));
$isNursingGroup = (int) ($_POST['is_nursing_group'] ?? 0) === 1 ? 1 : 0;

if ($departmentCode === '' || $departmentName === '') {
    flash_set('error', 'กรุณากรอกรหัสหน่วยงานและชื่อหน่วยงานให้ครบ');
    redirect('/admin/master_data.php');
}

if (!in_array($departmentType, ['general', 'clinical', 'support'], true)) {
    $departmentType = 'general';
}

try {
    $stmt = Database::connection()->prepare(
        'INSERT INTO departments (
            department_code, department_name, department_type, parent_department_id, is_nursing_group, is_active
         ) VALUES (
            :department_code, :department_name, :department_type, :parent_department_id, :is_nursing_group, 1
         )'
    );
    $stmt->execute([
        'department_code' => $departmentCode,
        'department_name' => $departmentName,
        'department_type' => $departmentType,
        'parent_department_id' => null,
        'is_nursing_group' => $isNursingGroup,
    ]);

    flash_set('success', 'บันทึกข้อมูลหน่วยงานเรียบร้อย');
} catch (Throwable $exception) {
    $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
        ? 'รหัสหน่วยงานนี้ถูกใช้แล้ว'
        : 'ไม่สามารถบันทึกข้อมูลหน่วยงานได้';

    flash_set('error', $message);
}

redirect('/admin/master_data.php');
