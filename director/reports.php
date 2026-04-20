<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('DIRECTOR');

$pageTitle = 'รายการรายงานสำหรับผู้อำนวยการ';
$reports = [];
$fiscalYears = fetch_fiscal_years();
$selectedFiscalYearId = isset($_GET['fiscal_year_id']) ? (int) ($_GET['fiscal_year_id'] ?? 0) : 0;
$selectedStatus = trim((string) ($_GET['status'] ?? ''));
$selectedDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$selectedDateTo = trim((string) ($_GET['date_to'] ?? ''));
$range = resolve_report_filter_range($selectedDateFrom, $selectedDateTo, $selectedFiscalYearId);

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

$pendingCount = count(array_filter($reports, static fn(array $report): bool => (string) $report['status'] === 'pending'));
$inProgressCount = count(array_filter($reports, static fn(array $report): bool => (string) $report['status'] === 'in_progress'));
$completedCount = count(array_filter($reports, static fn(array $report): bool => (string) $report['status'] === 'completed'));

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Director Report Queue</div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">รายการรายงานภาพรวม</h1>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    มุมมองแบบ read-only สำหรับผู้อำนวยการ ใช้ติดตามรายงานทั้งหมดตามช่วงเวลาและสถานะ โดยไม่สามารถแก้ไขหรือส่งต่อข้อมูลได้
                </p>
            </div>
            <a href="<?= e(base_url('director/dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard ผอ.
            </a>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">รายการที่พบ</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($reports)) ?></div>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="text-sm text-amber-700">รอรับเรื่อง</div>
                <div class="mt-2 text-3xl font-bold text-amber-900"><?= e((string) $pendingCount) ?></div>
            </article>
            <article class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
                <div class="text-sm text-violet-700">กำลังดำเนินการ</div>
                <div class="mt-2 text-3xl font-bold text-violet-900"><?= e((string) $inProgressCount) ?></div>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <div class="text-sm text-emerald-700">เสร็จสิ้น</div>
                <div class="mt-2 text-3xl font-bold text-emerald-900"><?= e((string) $completedCount) ?></div>
            </article>
        </div>

        <section class="mt-8 rounded-2xl border border-slate-200 p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">ตัวกรองและรายการรายงาน</h2>
                    <p class="mt-1 text-sm text-slate-500">กรองปีงบ สถานะ และช่วงวันที่ เพื่อดูเฉพาะกลุ่มรายงานที่ต้องการติดตาม</p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-600">ผลลัพธ์ <?= e((string) count($reports)) ?> รายการ</div>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
                <form method="get" class="mb-4 grid gap-3 rounded-2xl bg-slate-50 p-4 lg:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ปีงบประมาณ</label>
                        <select name="fiscal_year_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($fiscalYears as $year): ?>
                                <option value="<?= e((string) $year['id']) ?>" <?= $selectedFiscalYearId === (int) $year['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $year['year_label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">สถานะ</label>
                        <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ทั้งหมด</option>
                            <?php foreach (['pending' => 'รอรับเรื่อง', 'admin_review' => 'Admin พิจารณา', 'in_progress' => 'กำลังดำเนินการ', 'completed' => 'เสร็จสิ้น'] as $value => $label): ?>
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
                        <a href="<?= e(base_url('director/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-100">ล้างตัวกรอง</a>
                    </div>
                </form>

                <table id="directorReportsTable" class="display w-full text-sm">
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
                            <th>ดู</th>
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
                                <td><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"><?= e(report_status_label((string) $report['status'])) ?></span></td>
                                <td><?= e((string) $report['assignment_count']) ?></td>
                                <td><?= e((string) $report['report_delay_minutes']) ?> นาที</td>
                                <td><?= e((string) $report['reported_at']) ?></td>
                                <td><a href="<?= e(base_url('director/report_detail.php?id=' . $report['id'])) ?>" class="inline-flex rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">เปิด</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<script>
    $(function () {
        $('#directorReportsTable').DataTable({
            pageLength: 25,
            responsive: true,
            order: [[8, 'desc']],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' },
                zeroRecords: 'ไม่พบข้อมูล'
            }
        });
    });
</script>
<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
