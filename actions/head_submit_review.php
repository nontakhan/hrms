<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('DEPARTMENT_HEAD');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'คำขอไม่ถูกต้อง');
    redirect('/head/reports.php');
}

$assignmentId = (int) ($_POST['assignment_id'] ?? 0);
$reportId = (int) ($_POST['report_id'] ?? 0);
$reviewAction = trim((string) ($_POST['review_action'] ?? ''));
$reviewNote = trim((string) ($_POST['review_note'] ?? ''));

$user = Auth::user();
$userId = (int) ($user['id'] ?? 0);
$departmentId = (int) ($user['department_id'] ?? 0);

if ($assignmentId <= 0 || $reportId <= 0 || $userId <= 0 || $reviewAction === '') {
    flash_set('error', 'กรุณากรอกข้อมูลให้ครบ');
    redirect('/head/report_detail.php?assignment_id=' . $assignmentId);
}

try {
    $pdo = Database::connection();

    $assignmentStmt = $pdo->prepare(
        'SELECT *
         FROM report_assignments
         WHERE id = :assignment_id
           AND report_id = :report_id
           AND target_head_user_id = :user_id
         LIMIT 1'
    );
    $assignmentStmt->execute([
        'assignment_id' => $assignmentId,
        'report_id' => $reportId,
        'user_id' => $userId,
    ]);
    $assignment = $assignmentStmt->fetch();

    if (!$assignment) {
        flash_set('error', 'ไม่พบงานที่คุณรับผิดชอบ');
        redirect('/head/reports.php');
    }

    $reviewStmt = $pdo->prepare(
        'SELECT id
         FROM department_head_reviews
         WHERE assignment_id = :assignment_id
         ORDER BY id DESC
         LIMIT 1'
    );
    $reviewStmt->execute(['assignment_id' => $assignmentId]);
    $existingReviewId = (int) $reviewStmt->fetchColumn();

    $pdo->beginTransaction();

    if ($existingReviewId > 0) {
        $saveReviewStmt = $pdo->prepare(
            'UPDATE department_head_reviews
             SET department_id = :department_id,
                 review_action = :review_action,
                 review_note = :review_note,
                 reviewed_by = :reviewed_by,
                 reviewed_at = NOW(),
                 returned_to_team_at = NOW()
             WHERE id = :id'
        );
        $saveReviewStmt->execute([
            'department_id' => $departmentId > 0 ? $departmentId : 1,
            'review_action' => $reviewAction,
            'review_note' => $reviewNote !== '' ? $reviewNote : null,
            'reviewed_by' => $userId,
            'id' => $existingReviewId,
        ]);
    } else {
        $saveReviewStmt = $pdo->prepare(
            'INSERT INTO department_head_reviews (
                report_id, assignment_id, department_id, review_action, review_note, reviewed_by, reviewed_at, returned_to_team_at
             ) VALUES (
                :report_id, :assignment_id, :department_id, :review_action, :review_note, :reviewed_by, NOW(), NOW()
             )'
        );
        $saveReviewStmt->execute([
            'report_id' => $reportId,
            'assignment_id' => $assignmentId,
            'department_id' => $departmentId > 0 ? $departmentId : 1,
            'review_action' => $reviewAction,
            'review_note' => $reviewNote !== '' ? $reviewNote : null,
            'reviewed_by' => $userId,
        ]);
    }

    $updateAssignmentStmt = $pdo->prepare(
        'UPDATE report_assignments
         SET assignment_status = :assignment_status
         WHERE id = :id'
    );
    $updateAssignmentStmt->execute([
        'assignment_status' => 'returned_to_team',
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
        'to_team_id' => (int) $assignment['target_team_id'],
        'to_department_id' => $departmentId > 0 ? $departmentId : null,
        'route_action' => 'department_head_to_team',
        'route_reason' => $reviewAction,
        'route_note' => $reviewNote !== '' ? $reviewNote : 'Department head returned assignment to team',
    ]);

    $statusLogStmt = $pdo->prepare(
        'INSERT INTO report_status_logs (report_id, old_status, new_status, changed_by, note)
         VALUES (:report_id, :old_status, :new_status, :changed_by, :note)'
    );
    $statusLogStmt->execute([
        'report_id' => $reportId,
        'old_status' => 'in_progress',
        'new_status' => 'in_progress',
        'changed_by' => $userId,
        'note' => 'Department head returned to team',
    ]);

    $pdo->commit();

    flash_set('success', 'บันทึกแนวทางแก้ไขและส่งคืนทีมนำเรียบร้อย');
} catch (Throwable) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('error', 'ไม่สามารถบันทึกผลการพิจารณาได้');
}

redirect('/head/report_detail.php?assignment_id=' . $assignmentId);
