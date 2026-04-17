<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'จัดการรายงานความเสี่ยง';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$reports = [];
$fiscalYears = fetch_fiscal_years();
$selectedFiscalYearId = isset($_GET['fiscal_year_id']) ? (int) $_GET['fiscal_year_id'] : 0;
$selectedStatus = trim((string) ($_GET['status'] ?? ''));
$selectedDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$selectedDateTo = trim((string) ($_GET['date_to'] ?? ''));
$range = resolve_report_filter_range($selectedDateFrom, $selectedDateTo, $selectedFiscalYearId);
$exportUrl = build_query_url('actions/admin_export_reports.php', [
    'fiscal_year_id' => $selectedFiscalYearId > 0 ? $selectedFiscalYearId : '',
    'status' => $selectedStatus,
    'date_from' => $range['date_from'],
    'date_to' => $range['date_to'],
]);

try {
    $sql = <<<SQL
        SELECT
            ir.id,
            ir.report_no,
            ir.incident_title,
            ir.status,
            ir.reported_at,
            ir.report_delay_minutes,
            it.type_name,
            sl.level_code,
            d.department_name,
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
    $reports = $stmt->fetchAll();
} catch (Throwable) {
    $reports = [];
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Report Queue</div>
                <h1 class="text-3xl font-bold text-slate-900">รายการรายงานความเสี่ยง</h1>
                <p class="mt-2 text-slate-600">สำหรับรับเรื่อง ตรวจสอบ แก้ไขข้อมูล และส่งต่อไปยังทีมนำ พร้อมกรองข้อมูลตามปีงบและช่วงวันที่ได้</p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
            <form method="get" class="mb-4 grid gap-3 rounded-2xl bg-slate-50 p-4 lg:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">ปีงบประมาณ</label>
                    <select name="fiscal_year_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($fiscalYears as $year): ?>
                            <option value="<?= e((string) $year['id']) ?>" <?= $selectedFiscalYearId === (int) $year['id'] ? 'selected' : '' ?>>
                                <?= e((string) $year['year_label']) ?> (<?= e((string) $year['date_start']) ?> - <?= e((string) $year['date_end']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">สถานะ</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                        <option value="">ทั้งหมด</option>
                        <?php foreach (['pending' => 'รอรับเรื่อง', 'admin_review' => 'Admin กำลังพิจารณา', 'in_progress' => 'กำลังดำเนินการ', 'completed' => 'เสร็จสิ้น'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">วันที่รายงานจาก</label>
                    <input name="date_from" type="date" value="<?= e($range['date_from'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">วันที่รายงานถึง</label>
                    <input name="date_to" type="date" value="<?= e($range['date_to'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                </div>
                <div class="lg:col-span-4 flex flex-wrap gap-3">
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">กรองข้อมูล</button>
                    <a href="<?= e(base_url('admin/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-100">ล้างตัวกรอง</a>
                    <a href="<?= e($exportUrl) ?>" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 font-medium text-emerald-700 transition hover:bg-emerald-100">Export CSV</a>
                </div>
            </form>

            <table id="reportsTable" class="display w-full text-sm">
                <thead>
                    <tr>
                        <th>เลขรายงาน</th>
                        <th>หัวข้อ</th>
                        <th>ประเภท</th>
                        <th>ระดับ</th>
                        <th>หน่วยงาน</th>
                        <th>สถานะ</th>
                        <th>ส่งต่อแล้ว</th>
                        <th>เวลาล่าช้า</th>
                        <th>วันที่รายงาน</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= e((string) ($report['report_no'] ?: ('IR-' . $report['id']))) ?></td>
                            <td><?= e((string) $report['incident_title']) ?></td>
                            <td><?= e((string) $report['type_name']) ?></td>
                            <td><?= e((string) $report['level_code']) ?></td>
                            <td><?= e((string) $report['department_name']) ?></td>
                            <td><?= e((string) $report['status']) ?></td>
                            <td><?= e((string) $report['assignment_count']) ?></td>
                            <td><?= e((string) $report['report_delay_minutes']) ?> นาที</td>
                            <td><?= e((string) $report['reported_at']) ?></td>
                            <td>
                                <a href="<?= e(base_url('admin/report_detail.php?id=' . $report['id'])) ?>" class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php if ($flashError): ?>
<script>
    Swal.fire({icon: 'error', title: 'ไม่สำเร็จ', text: <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>});
</script>
<?php endif; ?>
<?php if ($flashSuccess): ?>
<script>
    Swal.fire({icon: 'success', title: 'สำเร็จ', text: <?= json_encode($flashSuccess, JSON_UNESCAPED_UNICODE) ?>});
</script>
<?php endif; ?>

<script>
    $(function () {
        $('#reportsTable').DataTable({
            pageLength: 10,
            order: [[8, 'desc']],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
                paginate: {
                    previous: 'ก่อนหน้า',
                    next: 'ถัดไป'
                },
                zeroRecords: 'ไม่พบข้อมูล'
            }
        });
    });
</script>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
