<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$selectedAction = trim((string) ($_GET['action'] ?? ''));
$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$selectedKeyword = trim((string) ($_GET['keyword'] ?? ''));
$selectedDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$selectedDateTo = trim((string) ($_GET['date_to'] ?? ''));

$rows = fetch_workflow_audit_logs([
    'action' => $selectedAction,
    'user_id' => $selectedUserId,
    'keyword' => $selectedKeyword,
    'date_from' => $selectedDateFrom,
    'date_to' => $selectedDateTo,
]);

$filename = 'workflow_history_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'วันเวลา',
    'ผู้ดำเนินการ',
    'รายการ',
    'ชนิดข้อมูล',
    'รหัสอ้างอิง',
    'รายละเอียด',
]);

foreach ($rows as $row) {
    $actorLabel = trim((string) ($row['full_name'] ?? ''));
    if ($actorLabel === '') {
        $actorLabel = trim((string) ($row['username'] ?? ''));
    }
    if ($actorLabel === '') {
        $actorLabel = 'ไม่ทราบผู้ใช้งาน';
    }

    $detailItems = [];
    foreach (($row['detail_array'] ?? []) as $key => $value) {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $detailItems[] = ucfirst(str_replace('_', ' ', (string) $key)) . ': ' . (string) $value;
    }

    fputcsv($output, [
        $row['created_at'] ?? '',
        $actorLabel,
        workflow_audit_action_label((string) ($row['action'] ?? '')),
        workflow_entity_label((string) ($row['entity_type'] ?? '')),
        $row['entity_id'] ?? '',
        implode(' | ', $detailItems),
    ]);
}

fclose($output);
exit;
