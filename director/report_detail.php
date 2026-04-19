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

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Director Report Detail</div>
                <h1 class="text-3xl font-bold text-slate-900"><?= e((string) $report['incident_title']) ?></h1>
                <p class="mt-2 text-slate-600">เลขรายงาน: <?= e((string) ($report['report_no'] ?: ('IR-' . $report['id']))) ?></p>
            </div>
            <a href="<?= e(base_url('director/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับรายการรายงาน
            </a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ข้อมูลรายงาน</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4"><div class="text-sm text-slate-500">ประเภท</div><div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['type_name']) ?></div></div>
                        <div class="rounded-xl bg-slate-50 p-4"><div class="text-sm text-slate-500">ระดับปัจจุบัน</div><div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['level_code']) ?></div></div>
                        <div class="rounded-xl bg-slate-50 p-4"><div class="text-sm text-slate-500">หน่วยงาน</div><div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['department_name']) ?></div></div>
                        <div class="rounded-xl bg-slate-50 p-4"><div class="text-sm text-slate-500">เวลาล่าช้า</div><div class="mt-1 font-semibold text-slate-900"><?= e((string) $report['report_delay_minutes']) ?> นาที</div></div>
                    </div>
                    <div class="mt-4 rounded-xl bg-slate-50 p-4"><div class="text-sm text-slate-500">รายละเอียดเหตุการณ์</div><div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) $report['incident_detail']) ?></div></div>
                    <div class="mt-4 rounded-xl bg-slate-50 p-4"><div class="text-sm text-slate-500">การแก้ไขเบื้องต้น</div><div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) ($report['initial_action'] ?? '-')) ?></div></div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-6">
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
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Assignment ที่เกี่ยวข้อง</h2>
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
                                        <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600"><?= e((string) $assignment['assignment_status']) ?></div>
                                    </div>
                                    <div class="mt-3 text-sm leading-7 text-slate-700">เหตุผล: <?= e((string) $assignment['sent_reason']) ?></div>
                                    <div class="mt-2 text-xs text-slate-500">ส่งต่อเมื่อ <?= e((string) $assignment['assigned_at']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">เส้นทางการส่งต่อ</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($routeLogs === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ยังไม่มี route log สำหรับ assignment ล่าสุด</div>
                        <?php else: ?>
                            <?php foreach ($routeLogs as $route): ?>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="font-semibold text-slate-900"><?= e((string) $route['route_action']) ?></div>
                                    <div class="mt-1 text-sm text-slate-600">จาก <?= e((string) ($route['from_user_name'] ?: '-')) ?> ถึง <?= e((string) ($route['to_user_name'] ?: ($route['team_code'] ?: '-'))) ?> เมื่อ <?= e((string) $route['created_at']) ?></div>
                                    <div class="mt-2 text-sm leading-7 text-slate-700">เหตุผล: <?= e((string) ($route['route_reason'] ?: '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
