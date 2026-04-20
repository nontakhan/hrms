<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('DIRECTOR');

$reportId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($reportId <= 0) {
    flash_set('error', 'ไม่พบรหัสรายงาน');
    redirect('/director/reports.php');
}

$pageTitle = 'รายละเอียดรายงานสำหรับผู้อำนวยการ';
$report = null;
$assignments = [];
$severityHistory = fetch_report_severity_history($reportId);
$routeLogs = [];

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
    redirect('/director/reports.php');
}

if ($assignments !== []) {
    $routeLogs = fetch_assignment_route_logs((int) $assignments[0]['id']);
}

$reportNo = (string) ($report['report_no'] ?: ('IR-' . $report['id']));
$statusLabel = report_status_label((string) $report['status']);
$incidentDateTime = $report['incident_datetime'] ? thai_datetime((string) $report['incident_datetime']) : '-';
$reportedAt = $report['reported_at'] ? thai_datetime((string) $report['reported_at']) : '-';
$delayMinutes = $report['report_delay_minutes'] !== null ? number_format((float) $report['report_delay_minutes']) . ' นาที' : '-';

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">
                    Director Read-only View
                </div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900"><?= e((string) $report['incident_title']) ?></h1>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    มุมมองนี้สำหรับผู้อำนวยการใช้ติดตามภาพรวมของรายงานความเสี่ยง เส้นทางการส่งต่อ และความคืบหน้าของแต่ละทีม โดยไม่สามารถแก้ไขข้อมูลได้
                </p>
                <div class="mt-5 flex flex-wrap gap-3 text-sm">
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">เลขรายงาน <?= e($reportNo) ?></span>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 font-medium text-emerald-700"><?= e($statusLabel) ?></span>
                    <span class="rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-700">ระดับ <?= e((string) $report['level_code']) ?></span>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="#severity-history" class="rounded-xl border border-slate-200 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    ประวัติความรุนแรง
                </a>
                <a href="#route-log" class="rounded-xl border border-slate-200 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    เส้นทางการส่งต่อ
                </a>
                <a href="<?= e(base_url('director/reports.php')) ?>" class="rounded-xl bg-brand-600 px-4 py-2 font-semibold text-white transition hover:bg-brand-700">
                    กลับไปรายการรายงาน
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">หน่วยงานที่เกิดเหตุ</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $report['department_name']) ?></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ประเภทเหตุการณ์</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $report['type_name']) ?></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">วันที่เกิดเหตุ</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e($incidentDateTime) ?></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ระยะเวลาจนถึงรายงาน</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e($delayMinutes) ?></div>
            </article>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 p-6">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">ข้อมูลเหตุการณ์</h2>
                        <p class="mt-1 text-sm text-slate-500">ใช้ดูบริบทของเคสและข้อมูลเบื้องต้นที่ผู้รายงานส่งเข้ามา</p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">วันที่รายงาน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e($reportedAt) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">สถานะปัจจุบัน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e($statusLabel) ?></div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl bg-slate-50 p-5">
                        <div class="text-sm text-slate-500">รายละเอียดเหตุการณ์</div>
                        <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700"><?= e((string) $report['incident_detail']) ?></div>
                    </div>

                    <div class="mt-4 rounded-2xl bg-slate-50 p-5">
                        <div class="text-sm text-slate-500">การแก้ไขเบื้องต้น</div>
                        <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700"><?= e((string) ($report['initial_action'] ?: '-')) ?></div>
                    </div>
                </section>

                <section id="severity-history" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ประวัติการเปลี่ยนระดับความรุนแรง</h2>
                    <p class="mt-1 text-sm text-slate-500">แสดงทุกครั้งที่มีการทบทวนหรือปรับระดับความรุนแรงของเหตุการณ์</p>

                    <div class="mt-5 space-y-3">
                        <?php if ($severityHistory === []): ?>
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                                ยังไม่มีประวัติการเปลี่ยนระดับความรุนแรง
                            </div>
                        <?php else: ?>
                            <?php foreach ($severityHistory as $history): ?>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="text-base font-semibold text-slate-900">
                                            <?= e((string) ($history['old_level_code'] ?: '-')) ?>
                                            <span class="mx-1 text-slate-400">→</span>
                                            <?= e((string) ($history['new_level_code'] ?: '-')) ?>
                                        </div>
                                        <div class="text-xs font-medium text-slate-500"><?= e(thai_datetime((string) $history['changed_at'])) ?></div>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600">
                                        โดย <?= e((string) ($history['full_name'] ?: $history['changed_role_code'])) ?>
                                    </div>
                                    <div class="mt-3 text-sm leading-7 text-slate-700">
                                        เหตุผล: <?= e((string) ($history['change_reason'] ?: '-')) ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">รายการ assignment ที่เกี่ยวข้อง</h2>
                    <p class="mt-1 text-sm text-slate-500">สรุปว่ารายงานนี้ถูกส่งให้ทีมใดบ้าง และแต่ละทีมมีสถานะอย่างไร</p>

                    <div class="mt-5 space-y-3">
                        <?php if ($assignments === []): ?>
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                                ยังไม่มีการส่งต่อรายงานนี้
                            </div>
                        <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-base font-semibold text-slate-900">
                                                <?= e((string) $assignment['assignment_no']) ?> - <?= e((string) $assignment['team_code']) ?>
                                            </div>
                                            <div class="mt-1 text-sm text-slate-500"><?= e((string) $assignment['team_name']) ?></div>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600">
                                            <?= e((string) $assignment['assignment_status']) ?>
                                        </span>
                                    </div>
                                    <div class="mt-3 text-sm leading-7 text-slate-700">
                                        เหตุผลที่ส่งต่อ: <?= e((string) ($assignment['sent_reason'] ?: '-')) ?>
                                    </div>
                                    <div class="mt-3 text-xs text-slate-500">
                                        ส่งต่อเมื่อ <?= e(thai_datetime((string) $assignment['assigned_at'])) ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="route-log" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">เส้นทางการส่งต่อ</h2>
                    <p class="mt-1 text-sm text-slate-500">ใช้ติดตามว่ารายงานเคลื่อนผ่านใครบ้างตั้งแต่ต้นจนถึงสถานะล่าสุด</p>

                    <div class="mt-5 space-y-3">
                        <?php if ($routeLogs === []): ?>
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-6 text-sm text-slate-500">
                                ยังไม่มี route log สำหรับ assignment ล่าสุด
                            </div>
                        <?php else: ?>
                            <?php foreach ($routeLogs as $route): ?>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="text-base font-semibold text-slate-900"><?= e((string) $route['route_action']) ?></div>
                                        <div class="text-xs font-medium text-slate-500"><?= e(thai_datetime((string) $route['created_at'])) ?></div>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600">
                                        จาก <?= e((string) ($route['from_user_name'] ?: '-')) ?>
                                        ไปยัง <?= e((string) ($route['to_user_name'] ?: ($route['team_code'] ?: '-'))) ?>
                                    </div>
                                    <div class="mt-3 text-sm leading-7 text-slate-700">
                                        เหตุผล: <?= e((string) ($route['route_reason'] ?: '-')) ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
