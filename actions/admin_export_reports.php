<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$selectedFiscalYearId = isset($_GET['fiscal_year_id']) ? (int) $_GET['fiscal_year_id'] : 0;
$selectedStatus = trim((string) ($_GET['status'] ?? ''));
$selectedDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$selectedDateTo = trim((string) ($_GET['date_to'] ?? ''));
$range = resolve_report_filter_range($selectedDateFrom, $selectedDateTo, $selectedFiscalYearId);

$rows = [];

try {
    $sql = <<<SQL
        SELECT
            ir.report_no,
            ir.incident_title,
            it.type_name,
            sl.level_code,
            d.department_name,
            ir.status,
            ir.report_delay_minutes,
            ir.reported_at,
            (
                SELECT COUNT(*)
                FROM report_assignments ra
                WHERE ra.report_id = ir.id
            ) AS assignment_count
        FROM incident_reports ir
        INNER JOIN incident_types it ON it.id = ir.incident_type_id
        INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
        INNER JOIN departments d ON d.id = ir.incident_department_id
        WHERE 1 = 1
    SQL;

    $params = [];

    if ($selectedStatus !== '' && in_array($selectedStatus, ['pending', 'admin_review', 'in_progress', 'completed'], true)) {
        $sql .= ' AND ir.status = :status';
        $params['status'] = $selectedStatus;
    }

    if ($range['date_from'] !== null) {
        $sql .= ' AND DATE(ir.reported_at) >= :date_from';
        $params['date_from'] = $range['date_from'];
    }

    if ($range['date_to'] !== null) {
        $sql .= ' AND DATE(ir.reported_at) <= :date_to';
        $params['date_to'] = $range['date_to'];
    }

    $sql .= ' ORDER BY ir.id DESC';

    $stmt = Database::connection()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถ export รายงานได้');
    redirect('admin/reports.php');
}

$filename = 'admin_reports_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'เลขรายงาน',
    'หัวข้อ',
    'ประเภท',
    'ระดับความรุนแรง',
    'หน่วยงาน',
    'สถานะ',
    'จำนวนการส่งต่อ',
    'เวลาล่าช้า(นาที)',
    'วันที่รายงาน',
]);

foreach ($rows as $row) {
    fputcsv($output, [
        $row['report_no'] ?: '',
        $row['incident_title'] ?: '',
        $row['type_name'] ?: '',
        $row['level_code'] ?: '',
        $row['department_name'] ?: '',
        $row['status'] ?: '',
        $row['assignment_count'] ?: 0,
        $row['report_delay_minutes'] ?: 0,
        $row['reported_at'] ?: '',
    ]);
}

fclose($output);
exit;
