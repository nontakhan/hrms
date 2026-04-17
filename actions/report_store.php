<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/public/report_create.php');
}

if (!Auth::canAccessPublicReport()) {
    flash_set('error', 'สิทธิ์เข้าใช้งานหน้ารายงานหมดอายุ');
    redirect('/public/report_access.php');
}

$input = [
    'incident_title' => trim((string) ($_POST['incident_title'] ?? '')),
    'incident_type' => trim((string) ($_POST['incident_type'] ?? '')),
    'incident_datetime' => trim((string) ($_POST['incident_datetime'] ?? '')),
    'incident_department_id' => trim((string) ($_POST['incident_department_id'] ?? '')),
    'severity_code' => trim((string) ($_POST['severity_code'] ?? '')),
    'incident_detail' => trim((string) ($_POST['incident_detail'] ?? '')),
    'initial_action' => trim((string) ($_POST['initial_action'] ?? '')),
    'reporter_name' => trim((string) ($_POST['reporter_name'] ?? '')),
    'reporter_phone' => trim((string) ($_POST['reporter_phone'] ?? '')),
];

old_set($input);

foreach (['incident_title', 'incident_type', 'incident_datetime', 'incident_department_id', 'severity_code', 'incident_detail'] as $field) {
    if ($input[$field] === '') {
        flash_set('error', 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ');
        redirect('/public/report_create.php');
    }
}

if (mb_strlen($input['incident_title']) > 255 || mb_strlen($input['incident_detail']) > 5000 || mb_strlen($input['initial_action']) > 3000) {
    flash_set('error', 'ข้อมูลที่กรอกยาวเกินกว่าที่ระบบกำหนด');
    redirect('/public/report_create.php');
}

if ($input['reporter_phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,50}$/', $input['reporter_phone'])) {
    flash_set('error', 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
    redirect('/public/report_create.php');
}

$allowedSeverities = $input['incident_type'] === 'CLINICAL'
    ? ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I']
    : ['1', '2', '3', '4'];

if (!in_array($input['severity_code'], $allowedSeverities, true)) {
    flash_set('error', 'ระดับความรุนแรงไม่สอดคล้องกับประเภทเหตุการณ์');
    redirect('/public/report_create.php');
}

if (!ctype_digit($input['incident_department_id'])) {
    flash_set('error', 'หน่วยงานที่เกิดเหตุไม่ถูกต้อง');
    redirect('/public/report_create.php');
}

try {
    $incidentAt = new DateTimeImmutable($input['incident_datetime']);
    $reportedAt = new DateTimeImmutable('now');
    $delayMinutes = max(0, (int) floor(($reportedAt->getTimestamp() - $incidentAt->getTimestamp()) / 60));

    $pdo = Database::connection();

    $incidentTypeStmt = $pdo->prepare('SELECT id FROM incident_types WHERE type_code = :type_code LIMIT 1');
    $incidentTypeStmt->execute(['type_code' => $input['incident_type']]);
    $incidentTypeId = (int) $incidentTypeStmt->fetchColumn();

    $departmentStmt = $pdo->prepare('SELECT id FROM departments WHERE id = :id AND is_active = 1 LIMIT 1');
    $departmentStmt->execute(['id' => (int) $input['incident_department_id']]);
    $incidentDepartmentId = (int) $departmentStmt->fetchColumn();

    $severityStmt = $pdo->prepare(
        'SELECT sl.id
         FROM severity_levels sl
         INNER JOIN incident_types it ON it.id = sl.incident_type_id
         WHERE it.type_code = :type_code AND sl.level_code = :level_code
         LIMIT 1'
    );
    $severityStmt->execute([
        'type_code' => $input['incident_type'],
        'level_code' => $input['severity_code'],
    ]);
    $severityId = (int) $severityStmt->fetchColumn();

    if ($incidentTypeId === 0 || $severityId === 0 || $incidentDepartmentId === 0) {
        flash_set('error', 'ข้อมูลประเภทหรือความรุนแรงยังไม่ถูกตั้งค่าในระบบ');
        redirect('/public/report_create.php');
    }

    $pdo->beginTransaction();

    $reportNo = 'IR-' . date('Ymd-His');

    $stmt = $pdo->prepare(
        'INSERT INTO incident_reports (
            report_no, reporter_name, reporter_phone, incident_department_id, incident_type_id,
            reported_severity_id, current_severity_id, incident_title, incident_detail,
            initial_action, incident_datetime, reported_at, report_delay_minutes, status
        ) VALUES (
            :report_no, :reporter_name, :reporter_phone, :incident_department_id, :incident_type_id,
            :reported_severity_id, :current_severity_id, :incident_title, :incident_detail,
            :initial_action, :incident_datetime, :reported_at, :report_delay_minutes, :status
        )'
    );

    $stmt->execute([
        'report_no' => $reportNo,
        'reporter_name' => $input['reporter_name'] !== '' ? $input['reporter_name'] : null,
        'reporter_phone' => $input['reporter_phone'] !== '' ? $input['reporter_phone'] : null,
        'incident_department_id' => $incidentDepartmentId,
        'incident_type_id' => $incidentTypeId,
        'reported_severity_id' => $severityId,
        'current_severity_id' => $severityId,
        'incident_title' => $input['incident_title'],
        'incident_detail' => $input['incident_detail'],
        'initial_action' => $input['initial_action'] !== '' ? $input['initial_action'] : null,
        'incident_datetime' => $incidentAt->format('Y-m-d H:i:s'),
        'reported_at' => $reportedAt->format('Y-m-d H:i:s'),
        'report_delay_minutes' => $delayMinutes,
        'status' => 'pending',
    ]);

    $reportId = (int) $pdo->lastInsertId();

    $historyStmt = $pdo->prepare(
        'INSERT INTO report_severity_histories (
            report_id, old_severity_id, new_severity_id, changed_by_user_id, changed_role_code, change_reason
        ) VALUES (
            :report_id, :old_severity_id, :new_severity_id, :changed_by_user_id, :changed_role_code, :change_reason
        )'
    );

    $historyStmt->execute([
        'report_id' => $reportId,
        'old_severity_id' => null,
        'new_severity_id' => $severityId,
        'changed_by_user_id' => (int) setting('system_user_id', 1),
        'changed_role_code' => 'PUBLIC_REPORTER',
        'change_reason' => 'Initial severity selected by reporter',
    ]);

    if (isset($_FILES['attachment']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        $maxFileSize = 5 * 1024 * 1024;
        $uploadDir = app_config('upload_dir');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = (string) $_FILES['attachment']['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('ชนิดไฟล์แนบไม่รองรับ');
        }

        $fileSize = (int) ($_FILES['attachment']['size'] ?? 0);
        if ($fileSize <= 0 || $fileSize > $maxFileSize) {
            throw new RuntimeException('ไฟล์แนบต้องมีขนาดไม่เกิน 5 MB');
        }

        $safeName = uniqid('attachment_', true) . ($extension !== '' ? '.' . $extension : '');
        $targetPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            throw new RuntimeException('อัปโหลดไฟล์แนบไม่สำเร็จ');
        }

        $attachmentStmt = $pdo->prepare(
            'INSERT INTO incident_attachments (report_id, file_name, file_path, file_type, file_size)
             VALUES (:report_id, :file_name, :file_path, :file_type, :file_size)'
        );

        $attachmentStmt->execute([
            'report_id' => $reportId,
            'file_name' => $originalName,
            'file_path' => $targetPath,
            'file_type' => (string) ($_FILES['attachment']['type'] ?? ''),
            'file_size' => $fileSize,
        ]);
    }

    $pdo->commit();

    old_clear();
    Auth::revokePublicReportAccess();
    flash_set('success', 'บันทึกรายงานความเสี่ยงเรียบร้อยแล้ว');
    redirect('/public/report_access.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('error', 'ไม่สามารถบันทึกรายงานได้: ' . $exception->getMessage());
    redirect('/public/report_create.php');
}
