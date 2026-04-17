<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$reportId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($reportId <= 0) {
    flash_set('error', 'ไม่พบรหัสรายงาน');
    redirect('/admin/reports.php');
}

$pageTitle = 'รายละเอียดรายงาน';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$report = null;
$assignments = [];
$teams = fetch_all_teams();
$incidentTypes = fetch_incident_types();
$departments = fetch_all_departments();
$severityOptions = fetch_severity_levels_by_type_code();

try {
    $reportStmt = Database::connection()->prepare(
        'SELECT
            ir.*,
            it.type_code,
            it.type_name,
            d.department_name,
            sl.level_code
         FROM incident_reports ir
         INNER JOIN incident_types it ON it.id = ir.incident_type_id
         INNER JOIN departments d ON d.id = ir.incident_department_id
         INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
         WHERE ir.id = :id
         LIMIT 1'
    );
    $reportStmt->execute(['id' => $reportId]);
    $report = $reportStmt->fetch();

    $assignmentStmt = Database::connection()->prepare(
        'SELECT
            ra.*,
            t.team_code,
            t.team_name
         FROM report_assignments ra
         INNER JOIN teams t ON t.id = ra.target_team_id
         WHERE ra.report_id = :report_id
         ORDER BY ra.id DESC'
    );
    $assignmentStmt->execute(['report_id' => $reportId]);
    $assignments = $assignmentStmt->fetchAll();
} catch (Throwable) {
    $report = null;
}

if (!$report) {
    flash_set('error', 'ไม่พบข้อมูลรายงาน');
    redirect('/admin/reports.php');
}

$severityByType = [];
foreach ($severityOptions as $severity) {
    $severityByType[$severity['type_code']][] = $severity;
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Report Detail</div>
                <h1 class="text-3xl font-bold text-slate-900"><?= e((string) $report['incident_title']) ?></h1>
                <p class="mt-2 text-slate-600">เลขรายงาน: <?= e((string) ($report['report_no'] ?: ('IR-' . $report['id']))) ?></p>
            </div>
            <a href="<?= e(base_url('admin/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับรายการ
            </a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ข้อมูลรายงาน</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ประเภท</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['type_name']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ระดับปัจจุบัน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['level_code']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">หน่วยงาน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['department_name']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">เวลาล่าช้า</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['report_delay_minutes']) ?> นาที</div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">รายละเอียดเหตุการณ์</div>
                        <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) $report['incident_detail']) ?></div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">การแก้ไขเบื้องต้น</div>
                        <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) ($report['initial_action'] ?? '-')) ?></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">แก้ไขข้อมูลเบื้องต้น</h2>
                    <form action="<?= e(base_url('actions/admin_update_report.php')) ?>" method="post" class="mt-4 grid gap-4 md:grid-cols-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="report_id" value="<?= e((string) $report['id']) ?>">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ประเภทเหตุการณ์</label>
                            <select name="incident_type_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <?php foreach ($incidentTypes as $type): ?>
                                    <option value="<?= e((string) $type['id']) ?>" <?= (int) $report['incident_type_id'] === (int) $type['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $type['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">หน่วยงาน</label>
                            <select name="incident_department_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= e((string) $department['id']) ?>" <?= (int) $report['incident_department_id'] === (int) $department['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $department['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ระดับความรุนแรง</label>
                            <select name="current_severity_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <?php foreach (($severityByType[$report['type_code']] ?? []) as $severity): ?>
                                    <option value="<?= e((string) $severity['id']) ?>" <?= (int) $report['current_severity_id'] === (int) $severity['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $severity['level_code']) ?> - <?= e((string) $severity['level_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">เหตุผลการแก้ไข</label>
                            <textarea name="change_reason" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">
                                บันทึกการแก้ไข
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ส่งต่อไปยังทีมนำ</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                        รายงาน 1 เรื่อง สามารถส่งไปหลายทีมนำได้ แต่ละครั้งจะสร้าง assignment และเลขรันของทีมนั้นแยกกัน
                    </p>
                    <form action="<?= e(base_url('actions/admin_assign_report.php')) ?>" method="post" class="mt-4 space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="report_id" value="<?= e((string) $report['id']) ?>">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ทีมนำ</label>
                            <select name="team_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="">เลือกทีมนำ</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= e((string) $team['id']) ?>">
                                        <?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">เหตุผลการส่งต่อ</label>
                            <textarea name="sent_reason" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white transition hover:bg-slate-800">
                            ส่งต่อไปยังทีมนำ
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Assignment ที่ส่งต่อแล้ว</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($assignments === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ยังไม่มีการส่งต่อรายงานนี้</div>
                        <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-900">
                                                <?= e((string) $assignment['assignment_no']) ?> - <?= e((string) $assignment['team_code']) ?>
                                            </div>
                                            <div class="mt-1 text-sm text-slate-500"><?= e((string) $assignment['team_name']) ?></div>
                                        </div>
                                        <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600">
                                            <?= e((string) $assignment['assignment_status']) ?>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-sm leading-7 text-slate-700">
                                        เหตุผล: <?= e((string) $assignment['sent_reason']) ?>
                                    </div>
                                    <div class="mt-2 text-xs text-slate-500">
                                        ส่งต่อเมื่อ <?= e((string) $assignment['assigned_at']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
