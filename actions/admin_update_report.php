<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/admin/reports.php');
}

$reportId = (int) ($_POST['report_id'] ?? 0);
$incidentTypeId = (int) ($_POST['incident_type_id'] ?? 0);
$incidentDepartmentId = (int) ($_POST['incident_department_id'] ?? 0);
$currentSeverityId = (int) ($_POST['current_severity_id'] ?? 0);
$changeReason = trim((string) ($_POST['change_reason'] ?? ''));

if ($reportId <= 0 || $incidentTypeId <= 0 || $incidentDepartmentId <= 0 || $currentSeverityId <= 0 || $changeReason === '') {
    flash_set('error', 'กรุณากรอกข้อมูลให้ครบ');
    redirect('/admin/report_detail.php?id=' . $reportId);
}

if (mb_strlen($changeReason) > 1000) {
    flash_set('error', 'เหตุผลการแก้ไขยาวเกินกำหนด');
    redirect('/admin/report_detail.php?id=' . $reportId);
}

try {
    $pdo = Database::connection();

    $reportStmt = $pdo->prepare(
        'SELECT id, current_severity_id, status
         FROM incident_reports
         WHERE id = :id
         LIMIT 1'
    );
    $reportStmt->execute(['id' => $reportId]);
    $report = $reportStmt->fetch();

    if (!$report) {
        flash_set('error', 'ไม่พบรายงานที่ต้องการแก้ไข');
        redirect('/admin/reports.php');
    }

    if ((string) $report['status'] === 'completed') {
        flash_set('error', 'ไม่สามารถแก้ไขรายงานที่ปิดงานแล้วได้');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $incidentTypeStmt = $pdo->prepare(
        'SELECT id
         FROM incident_types
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $incidentTypeStmt->execute(['id' => $incidentTypeId]);
    if (!(int) $incidentTypeStmt->fetchColumn()) {
        flash_set('error', 'ไม่พบประเภทเหตุการณ์ที่เลือก');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $departmentStmt = $pdo->prepare(
        'SELECT id
         FROM departments
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $departmentStmt->execute(['id' => $incidentDepartmentId]);
    if (!(int) $departmentStmt->fetchColumn()) {
        flash_set('error', 'ไม่พบหน่วยงานที่เลือก');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $severityCheckStmt = $pdo->prepare(
        'SELECT sl.id
         FROM severity_levels sl
         WHERE sl.id = :severity_id
           AND sl.incident_type_id = :incident_type_id
         LIMIT 1'
    );
    $severityCheckStmt->execute([
        'severity_id' => $currentSeverityId,
        'incident_type_id' => $incidentTypeId,
    ]);

    if (!(int) $severityCheckStmt->fetchColumn()) {
        flash_set('error', 'ระดับความรุนแรงไม่สอดคล้องกับประเภทเหตุการณ์');
        redirect('/admin/report_detail.php?id=' . $reportId);
    }

    $user = Auth::user();
    $userId = (int) ($user['id'] ?? 0);

    $pdo->beginTransaction();

    $newStatus = (string) $report['status'] === 'pending' ? 'admin_review' : (string) $report['status'];

    $updateStmt = $pdo->prepare(
        'UPDATE incident_reports
         SET incident_type_id = :incident_type_id,
             incident_department_id = :incident_department_id,
             current_severity_id = :current_severity_id,
             status = :status,
             updated_at = NOW()
         WHERE id = :id'
    );
    $updateStmt->execute([
        'incident_type_id' => $incidentTypeId,
        'incident_department_id' => $incidentDepartmentId,
        'current_severity_id' => $currentSeverityId,
        'status' => $newStatus,
        'id' => $reportId,
    ]);

    if ((int) $report['current_severity_id'] !== $currentSeverityId) {
        $historyStmt = $pdo->prepare(
            'INSERT INTO report_severity_histories (
                report_id, old_severity_id, new_severity_id, changed_by_user_id, changed_role_code, change_reason
             ) VALUES (
                :report_id, :old_severity_id, :new_severity_id, :changed_by_user_id, :changed_role_code, :change_reason
             )'
        );
        $historyStmt->execute([
            'report_id' => $reportId,
            'old_severity_id' => (int) $report['current_severity_id'],
            'new_severity_id' => $currentSeverityId,
            'changed_by_user_id' => $userId,
            'changed_role_code' => 'ADMIN',
            'change_reason' => $changeReason,
        ]);
    }

    $statusLogStmt = $pdo->prepare(
        'INSERT INTO report_status_logs (report_id, old_status, new_status, changed_by, note)
         VALUES (:report_id, :old_status, :new_status, :changed_by, :note)'
    );
    $statusLogStmt->execute([
        'report_id' => $reportId,
        'old_status' => $report['status'],
        'new_status' => $newStatus,
        'changed_by' => $userId,
        'note' => $changeReason,
    ]);

    audit_log(
        'admin_update_report',
        'incident_report',
        $reportId,
        [
            'incident_type_id' => $incidentTypeId,
            'incident_department_id' => $incidentDepartmentId,
            'old_severity_id' => (int) $report['current_severity_id'],
            'new_severity_id' => $currentSeverityId,
            'old_status' => (string) $report['status'],
            'new_status' => $newStatus,
            'change_reason' => $changeReason,
        ],
        $userId,
        $pdo
    );

    $pdo->commit();

    flash_set('success', 'อัปเดตรายงานเรียบร้อย');
} catch (Throwable) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('error', 'ไม่สามารถอัปเดตรายงานได้');
}

redirect('/admin/report_detail.php?id=' . $reportId);
