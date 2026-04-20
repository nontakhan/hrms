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
$statusCards = [
    'pending' => ['label' => 'รอรับเรื่อง', 'count' => 0, 'tone' => 'bg-amber-50 text-amber-700 border-amber-200'],
    'admin_review' => ['label' => 'Admin กำลังพิจารณา', 'count' => 0, 'tone' => 'bg-sky-50 text-sky-700 border-sky-200'],
    'in_progress' => ['label' => 'กำลังดำเนินการ', 'count' => 0, 'tone' => 'bg-violet-50 text-violet-700 border-violet-200'],
    'completed' => ['label' => 'เสร็จสิ้น', 'count' => 0, 'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
];

function admin_queue_status_badge_class(string $status): string
{
    return match ($status) {
        'pending' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
        'admin_review' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
        'in_progress' => 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        default => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200',
    };
}

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

    foreach ($reports as $report) {
        $status = (string) ($report['status'] ?? '');
        if (isset($statusCards[$status])) {
            $statusCards[$status]['count']++;
        }
    }
} catch (Throwable) {
    $reports = [];
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Report Queue</div>
                <h1 class="text-3xl font-bold text-slate-900">รายการรายงานความเสี่ยง</h1>
                <p class="mt-2 max-w-3xl text-slate-600">รวมเคสทั้งหมดสำหรับ admin เพื่อรับเรื่อง ตรวจสอบข้อมูล ปรับระดับความรุนแรง และส่งต่อไปยังทีมนำได้จากคิวงานนี้</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?= e($exportUrl) ?>" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 font-medium text-emerald-700 transition hover:bg-emerald-100">Export CSV</a>
                <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">กลับ Dashboard</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($statusCards as $card): ?>
                <div class="rounded-2xl border p-5 <?= e($card['tone']) ?>">
                    <div class="text-sm font-medium"><?= e($card['label']) ?></div>
                    <div class="mt-2 text-3xl font-bold"><?= e((string) $card['count']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 rounded-[1.75rem] border border-slate-200 bg-white/90 p-5">
            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">ตัวกรองและรายการรายงาน</h2>
                    <p class="text-sm text-slate-500">เลือกปีงบ สถานะ และช่วงวันที่ เพื่อโฟกัสเฉพาะเคสที่ต้องจัดการ</p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-600">รายงานทั้งหมด <?= e((string) count($reports)) ?> รายการ</div>
            </div>

            <form method="get" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 lg:grid-cols-4">
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
                <div class="flex flex-wrap gap-3 lg:col-span-4">
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">กรองข้อมูล</button>
                    <a href="<?= e(base_url('admin/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-100">ล้างตัวกรอง</a>
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
                            <td><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"><?= e((string) $report['level_code']) ?></span></td>
                            <td><?= e((string) $report['department_name']) ?></td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= e(admin_queue_status_badge_class((string) $report['status'])) ?>">
                                    <?= e(report_status_label((string) $report['status'])) ?>
                                </span>
                            </td>
                            <td><?= e((string) $report['assignment_count']) ?></td>
                            <td><?= e((string) $report['report_delay_minutes']) ?> นาที</td>
                            <td><?= e((string) $report['reported_at']) ?></td>
                            <td><a href="<?= e(base_url('admin/report_detail.php?id=' . $report['id'])) ?>" class="inline-flex rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-700">ดูรายละเอียด</a></td>
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
            responsive: true,
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
