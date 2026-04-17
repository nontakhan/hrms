<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('TEAM_LEAD');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/team/reports.php');
}

$assignmentId = (int) ($_POST['assignment_id'] ?? 0);
$reportId = (int) ($_POST['report_id'] ?? 0);
$selectedCategoryId = (int) ($_POST['selected_category_id'] ?? 0);
$currentSeverityId = (int) ($_POST['current_severity_id'] ?? 0);
$problemAnalysis = trim((string) ($_POST['problem_analysis'] ?? ''));
$correctiveAction = trim((string) ($_POST['corrective_action'] ?? ''));
$preventiveAction = trim((string) ($_POST['preventive_action'] ?? ''));
$decisionType = trim((string) ($_POST['decision_type'] ?? ''));
$headUserId = (int) ($_POST['head_user_id'] ?? 0);
$decisionReason = trim((string) ($_POST['decision_reason'] ?? ''));

$user = Auth::user();
$userId = (int) ($user['id'] ?? 0);
$teamId = (int) ($user['team_id'] ?? 0);

if ($assignmentId <= 0 || $reportId <= 0 || $teamId <= 0 || $currentSeverityId <= 0 || $decisionReason === '') {
    flash_set('error', 'กรุณากรอกข้อมูลให้ครบ');
    redirect('/team/report_detail.php?assignment_id=' . $assignmentId);
}

if (!in_array($decisionType, ['resolved_by_team', 'forward_to_department_head'], true)) {
    flash_set('error', 'รูปแบบผลการพิจารณาไม่ถูกต้อง');
    redirect('/team/report_detail.php?assignment_id=' . $assignmentId);
}

if ($decisionType === 'resolved_by_team' && $correctiveAction === '') {
    flash_set('error', 'กรุณาระบุแนวทางแก้ไขก่อนส่งกลับ admin');
    redirect('/team/report_detail.php?assignment_id=' . $assignmentId);
}

if ($decisionType === 'forward_to_department_head' && $headUserId <= 0) {
    flash_set('error', 'กรุณาเลือกหัวหน้ากลุ่มงานหรือหัวหน้างาน');
    redirect('/team/report_detail.php?assignment_id=' . $assignmentId);
}

try {
    $pdo = Database::connection();

    $assignmentStmt = $pdo->prepare(
        'SELECT ra.*, ir.current_severity_id
         FROM report_assignments ra
         INNER JOIN incident_reports ir ON ir.id = ra.report_id
         WHERE ra.id = :assignment_id
           AND ra.report_id = :report_id
           AND ra.target_team_id = :team_id
         LIMIT 1'
    );
    $assignmentStmt->execute([
        'assignment_id' => $assignmentId,
        'report_id' => $reportId,
        'team_id' => $teamId,
    ]);
    $assignment = $assignmentStmt->fetch();

    if (!$assignment) {
        flash_set('error', 'ไม่พบ assignment ที่คุณรับผิดชอบ');
        redirect('/team/reports.php');
    }

    $reviewStmt = $pdo->prepare(
        'SELECT id
         FROM team_reviews
         WHERE assignment_id = :assignment_id
         ORDER BY id DESC
         LIMIT 1'
    );
    $reviewStmt->execute(['assignment_id' => $assignmentId]);
    $existingReviewId = (int) $reviewStmt->fetchColumn();

    $pdo->beginTransaction();

    if ($existingReviewId > 0) {
        $saveReviewStmt = $pdo->prepare(
            'UPDATE team_reviews
             SET selected_category_id = :selected_category_id,
                 problem_analysis = :problem_analysis,
                 corrective_action = :corrective_action,
                 preventive_action = :preventive_action,
                 decision_type = :decision_type,
                 decision_reason = :decision_reason,
                 reviewed_by = :reviewed_by,
                 reviewed_at = NOW(),
                 submitted_at = NOW()
             WHERE id = :id'
        );
        $saveReviewStmt->execute([
            'selected_category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : null,
            'problem_analysis' => $problemAnalysis !== '' ? $problemAnalysis : null,
            'corrective_action' => $correctiveAction !== '' ? $correctiveAction : null,
            'preventive_action' => $preventiveAction !== '' ? $preventiveAction : null,
            'decision_type' => $decisionType,
            'decision_reason' => $decisionReason,
            'reviewed_by' => $userId,
            'id' => $existingReviewId,
        ]);
    } else {
        $saveReviewStmt = $pdo->prepare(
            'INSERT INTO team_reviews (
                report_id, assignment_id, team_id, selected_category_id,
                problem_analysis, corrective_action, preventive_action,
                decision_type, decision_reason, reviewed_by, reviewed_at, submitted_at
             ) VALUES (
                :report_id, :assignment_id, :team_id, :selected_category_id,
                :problem_analysis, :corrective_action, :preventive_action,
                :decision_type, :decision_reason, :reviewed_by, NOW(), NOW()
             )'
        );
        $saveReviewStmt->execute([
            'report_id' => $reportId,
            'assignment_id' => $assignmentId,
            'team_id' => $teamId,
            'selected_category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : null,
            'problem_analysis' => $problemAnalysis !== '' ? $problemAnalysis : null,
            'corrective_action' => $correctiveAction !== '' ? $correctiveAction : null,
            'preventive_action' => $preventiveAction !== '' ? $preventiveAction : null,
            'decision_type' => $decisionType,
            'decision_reason' => $decisionReason,
            'reviewed_by' => $userId,
        ]);
    }

    if ((int) $assignment['current_severity_id'] !== $currentSeverityId) {
        $severityHistoryStmt = $pdo->prepare(
            'INSERT INTO report_severity_histories (
                report_id, old_severity_id, new_severity_id, changed_by_user_id, changed_role_code, change_reason
             ) VALUES (
                :report_id, :old_severity_id, :new_severity_id, :changed_by_user_id, :changed_role_code, :change_reason
             )'
        );
        $severityHistoryStmt->execute([
            'report_id' => $reportId,
            'old_severity_id' => (int) $assignment['current_severity_id'],
            'new_severity_id' => $currentSeverityId,
            'changed_by_user_id' => $userId,
            'changed_role_code' => 'TEAM_LEAD',
            'change_reason' => $decisionReason,
        ]);

        $updateSeverityStmt = $pdo->prepare(
            'UPDATE incident_reports
             SET current_severity_id = :current_severity_id, updated_at = NOW()
             WHERE id = :id'
        );
        $updateSeverityStmt->execute([
            'current_severity_id' => $currentSeverityId,
            'id' => $reportId,
        ]);
    }

    if ($decisionType === 'resolved_by_team') {
        $updateAssignmentStmt = $pdo->prepare(
            'UPDATE report_assignments
             SET assignment_status = :assignment_status,
                 target_head_user_id = NULL,
                 received_at = COALESCE(received_at, NOW())
             WHERE id = :id'
        );
        $updateAssignmentStmt->execute([
            'assignment_status' => 'returned_to_admin',
            'id' => $assignmentId,
        ]);

        $routeStmt = $pdo->prepare(
            'INSERT INTO assignment_route_logs (
                report_id, assignment_id, from_user_id, to_user_id, to_team_id, to_department_id, route_action, route_reason, route_note
             ) VALUES (
                :report_id, :assignment_id, :from_user_id, :to_user_id, :to_team_id, :to_department_id, :route_action, :route_reason, :route_note
             )'
        );
        $routeStmt->execute([
            'report_id' => $reportId,
            'assignment_id' => $assignmentId,
            'from_user_id' => $userId,
            'to_user_id' => null,
            'to_team_id' => $teamId,
            'to_department_id' => null,
            'route_action' => 'team_to_admin',
            'route_reason' => $decisionReason,
            'route_note' => 'Team resolved and returned to admin',
        ]);
    } else {
        $headStmt = $pdo->prepare(
            "SELECT u.id, u.department_id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
               AND u.is_active = 1
               AND r.role_code = 'DEPARTMENT_HEAD'
             LIMIT 1"
        );
        $headStmt->execute(['id' => $headUserId]);
        $head = $headStmt->fetch();

        if (!$head) {
            throw new RuntimeException('Department head not found');
        }

        $updateAssignmentStmt = $pdo->prepare(
            'UPDATE report_assignments
             SET assignment_status = :assignment_status,
                 target_head_user_id = :target_head_user_id,
                 received_at = COALESCE(received_at, NOW())
             WHERE id = :id'
        );
        $updateAssignmentStmt->execute([
            'assignment_status' => 'sent_to_department_head',
            'target_head_user_id' => $headUserId,
            'id' => $assignmentId,
        ]);

        $routeStmt = $pdo->prepare(
            'INSERT INTO assignment_route_logs (
                report_id, assignment_id, from_user_id, to_user_id, to_team_id, to_department_id, route_action, route_reason, route_note
             ) VALUES (
                :report_id, :assignment_id, :from_user_id, :to_user_id, :to_team_id, :to_department_id, :route_action, :route_reason, :route_note
             )'
        );
        $routeStmt->execute([
            'report_id' => $reportId,
            'assignment_id' => $assignmentId,
            'from_user_id' => $userId,
            'to_user_id' => $headUserId,
            'to_team_id' => $teamId,
            'to_department_id' => (int) ($head['department_id'] ?? 0) ?: null,
            'route_action' => 'team_to_department_head',
            'route_reason' => $decisionReason,
            'route_note' => 'Team forwarded to department head',
        ]);
    }

    $statusLogStmt = $pdo->prepare(
        'INSERT INTO report_status_logs (report_id, old_status, new_status, changed_by, note)
         VALUES (:report_id, :old_status, :new_status, :changed_by, :note)'
    );
    $statusLogStmt->execute([
        'report_id' => $reportId,
        'old_status' => 'in_progress',
        'new_status' => 'in_progress',
        'changed_by' => $userId,
        'note' => $decisionReason,
    ]);

    $pdo->commit();

    flash_set('success', 'บันทึกผลการพิจารณาเรียบร้อย');
} catch (Throwable) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('error', 'ไม่สามารถบันทึกผลการพิจารณาได้');
}

redirect('/team/report_detail.php?assignment_id=' . $assignmentId);
