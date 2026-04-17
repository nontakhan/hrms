<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('DIRECTOR');

$selectedFiscalYearId = isset($_GET['fiscal_year_id']) ? (int) $_GET['fiscal_year_id'] : 0;
$selectedDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$selectedDateTo = trim((string) ($_GET['date_to'] ?? ''));
$range = resolve_report_filter_range($selectedDateFrom, $selectedDateTo, $selectedFiscalYearId);

$rows = [];

try {
    $conditions = [];
    $params = [];

    if ($range['date_from'] !== null) {
        $conditions[] = 'DATE(ir.reported_at) >= :date_from';
        $params['date_from'] = $range['date_from'];
    }

    if ($range['date_to'] !== null) {
        $conditions[] = 'DATE(ir.reported_at) <= :date_to';
        $params['date_to'] = $range['date_to'];
    }

    $whereSql = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

    $stmt = Database::connection()->prepare(
        "SELECT
            ir.report_no,
            ir.incident_title,
            d.department_name,
            it.type_name,
            sl.level_code,
            ir.status,
            ir.report_delay_minutes,
            ir.reported_at
         FROM incident_reports ir
         INNER JOIN departments d ON d.id = ir.incident_department_id
         INNER JOIN incident_types it ON it.id = ir.incident_type_id
         INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
         " . $whereSql . "
         ORDER BY ir.id DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Throwable) {
    flash_set('error', 'ไม่สามารถ export รายงานได้');
    redirect('director/dashboard.php');
}

$filename = 'director_reports_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'เลขรายงาน',
    'หัวข้อ',
    'หน่วยงาน',
    'ประเภท',
    'ระดับความรุนแรง',
    'สถานะ',
    'เวลาล่าช้า(นาที)',
    'วันที่รายงาน',
]);

foreach ($rows as $row) {
    fputcsv($output, [
        $row['report_no'] ?: '',
        $row['incident_title'] ?: '',
        $row['department_name'] ?: '',
        $row['type_name'] ?: '',
        $row['level_code'] ?: '',
        $row['status'] ?: '',
        $row['report_delay_minutes'] ?: 0,
        $row['reported_at'] ?: '',
    ]);
}

fclose($output);
exit;
