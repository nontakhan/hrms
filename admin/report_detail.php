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
$severityHistory = fetch_report_severity_history($reportId);
$routeLogs = fetch_assignment_route_logs(0);

function admin_report_detail_status_badge_class(string $status): string
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

if ($assignments !== []) {
    $routeLogs = fetch_assignment_route_logs((int) $assignments[0]['id']);
}

$severityByType = [];
foreach ($severityOptions as $severity) {
    $severityByType[$severity['type_code']][] = $severity;
}

$reportNo = (string) ($report['report_no'] ?: ('IR-' . $report['id']));
$delayMinutes = $report['report_delay_minutes'] !== null ? (string) $report['report_delay_minutes'] . ' นาที' : '-';

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Report Detail</div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900"><?= e((string) $report['incident_title']) ?></h1>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">เลขรายงาน <?= e($reportNo) ?></span>
                    <span class="rounded-full px-3 py-1 font-medium <?= e(admin_report_detail_status_badge_class((string) $report['status'])) ?>">
                        <?= e(report_status_label((string) $report['status'])) ?>
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">ส่งต่อแล้ว <?= e((string) count($assignments)) ?> ทีม</span>
                </div>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    จัดการข้อมูลรายงาน ปรับระดับความรุนแรง และส่งต่อไปยังทีมนำจากหน้าเดียว โดยแยกข้อมูลสำคัญ ประวัติ และ assignment ให้อ่านง่ายขึ้น
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="#assign-team" class="rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">ส่งต่อไปยังทีมนำ</a>
                <a href="<?= e(base_url('admin/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">กลับรายการ</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ประเภทเหตุการณ์</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $report['type_name']) ?></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ระดับความรุนแรงปัจจุบัน</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $report['level_code']) ?></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">หน่วยงานที่เกิดเหตุ</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $report['department_name']) ?></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">เวลาล่าช้าก่อนรายงาน</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e($delayMinutes) ?></div>
            </article>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">สรุปข้อมูลรายงาน</h2>
                            <p class="mt-1 text-sm text-slate-500">ข้อมูลหลักที่ admin ใช้ตรวจสอบก่อนตัดสินใจแก้ไขหรือส่งต่อ</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <a href="#edit-report" class="rounded-full bg-slate-100 px-3 py-2 font-medium text-slate-600 transition hover:bg-slate-200">แก้ไขข้อมูล</a>
                            <a href="#severity-history" class="rounded-full bg-slate-100 px-3 py-2 font-medium text-slate-600 transition hover:bg-slate-200">ประวัติความรุนแรง</a>
                            <a href="#route-log" class="rounded-full bg-slate-100 px-3 py-2 font-medium text-slate-600 transition hover:bg-slate-200">เส้นทางการส่งต่อ</a>
                        </div>
                    </div>

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
                            <div class="mt-1 font-semibold text-slate-900"><?= e($delayMinutes) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">วันที่รายงาน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['reported_at']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">วันเวลาเกิดเหตุ</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['incident_datetime']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 md:col-span-2">
                            <div class="text-sm text-slate-500">รายละเอียดเหตุการณ์</div>
                            <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) $report['incident_detail']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 md:col-span-2">
                            <div class="text-sm text-slate-500">การแก้ไขเบื้องต้น</div>
                            <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) ($report['initial_action'] ?? '-')) ?></div>
                        </div>
                    </div>
                </section>

                <section id="edit-report" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ปรับข้อมูลรายงานและระดับความรุนแรง</h2>
                    <p class="mt-1 text-sm text-slate-500">ใช้เมื่อ admin ต้องแก้ประเภทเหตุการณ์ หน่วยงาน หรือระดับความรุนแรงของเคส</p>
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
                        <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            ทุกครั้งที่แก้ระดับความรุนแรง ระบบจะบันทึกประวัติย้อนหลังให้อัตโนมัติ
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">เหตุผลการแก้ไข</label>
                            <textarea name="change_reason" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required></textarea>
                            <p class="mt-2 text-xs text-slate-500">เหตุผลนี้จะถูกบันทึกในประวัติการเปลี่ยนระดับความรุนแรงทุกครั้ง</p>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">บันทึกการแก้ไข</button>
                        </div>
                    </form>
                </section>

                <section id="severity-history" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ประวัติการเปลี่ยนระดับความรุนแรง</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($severityHistory === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ยังไม่มีประวัติการเปลี่ยนระดับความรุนแรง</div>
                        <?php else: ?>
                            <?php foreach ($severityHistory as $history): ?>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="font-semibold text-slate-900"><?= e((string) ($history['old_level_code'] ?: '-')) ?> -> <?= e((string) ($history['new_level_code'] ?: '-')) ?></div>
                                    <div class="mt-1 text-sm text-slate-600">โดย <?= e((string) ($history['full_name'] ?: $history['changed_role_code'])) ?> เมื่อ <?= e((string) $history['changed_at']) ?></div>
                                    <div class="mt-2 text-sm leading-7 text-slate-700">เหตุผล: <?= e((string) ($history['change_reason'] ?: '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section id="assign-team" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ส่งต่อไปยังทีมนำ</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600">รายงาน 1 เรื่องสามารถส่งไปหลายทีมนำได้ แต่ละทีมจะมี assignment และเลขรันของตัวเองแยกกัน</p>
                    <form action="<?= e(base_url('actions/admin_assign_report.php')) ?>" method="post" class="mt-4 space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="report_id" value="<?= e((string) $report['id']) ?>">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ทีมนำ</label>
                            <select name="team_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="">เลือกทีมนำ</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= e((string) $team['id']) ?>"><?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">เหตุผลการส่งต่อ</label>
                            <textarea name="sent_reason" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white transition hover:bg-slate-800">ส่งต่อไปยังทีมนำ</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Assignment ที่ส่งต่อแล้ว</h2>
                    <p class="mt-1 text-sm text-slate-500">ใช้ติดตามว่าเคสนี้ถูกส่งไปทีมไหนบ้าง และแต่ละทีมอยู่สถานะใด</p>
                    <div class="mt-4 space-y-3">
                        <?php if ($assignments === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ยังไม่มีการส่งต่อรายงานนี้</div>
                        <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-900"><?= e((string) $assignment['assignment_no']) ?> - <?= e((string) $assignment['team_code']) ?></div>
                                            <div class="mt-1 text-sm text-slate-500"><?= e((string) $assignment['team_name']) ?></div>
                                        </div>
                                        <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200"><?= e((string) $assignment['assignment_status']) ?></div>
                                    </div>
                                    <div class="mt-3 text-sm leading-7 text-slate-700">เหตุผล: <?= e((string) $assignment['sent_reason']) ?></div>
                                    <div class="mt-2 text-xs text-slate-500">ส่งต่อเมื่อ <?= e((string) $assignment['assigned_at']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="route-log" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">เส้นทางการส่งต่อ</h2>
                    <p class="mt-1 text-sm text-slate-500">ประวัติการเคลื่อนของ assignment ล่าสุด เพื่อใช้ตรวจสอบการส่งต่อย้อนหลัง</p>
                    <div class="mt-4 space-y-3">
                        <?php if ($routeLogs === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ยังไม่มี route log สำหรับ assignment ล่าสุด</div>
                        <?php else: ?>
                            <?php foreach ($routeLogs as $route): ?>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="font-semibold text-slate-900"><?= e((string) $route['route_action']) ?></div>
                                    <div class="mt-1 text-sm text-slate-600">
                                        จาก <?= e((string) ($route['from_user_name'] ?: '-')) ?>
                                        ถึง <?= e((string) ($route['to_user_name'] ?: ($route['team_code'] ?: ($route['department_name'] ?: '-')))) ?>
                                        เมื่อ <?= e((string) $route['created_at']) ?>
                                    </div>
                                    <div class="mt-2 text-sm leading-7 text-slate-700">เหตุผล: <?= e((string) ($route['route_reason'] ?: '-')) ?></div>
                                    <?php if ((string) ($route['route_note'] ?? '') !== ''): ?>
                                        <div class="mt-1 text-xs text-slate-500"><?= e((string) $route['route_note']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
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
