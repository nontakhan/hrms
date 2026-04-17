<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('TEAM_LEAD');

$assignmentId = isset($_GET['assignment_id']) ? (int) $_GET['assignment_id'] : 0;
$user = Auth::user();
$teamId = (int) ($user['team_id'] ?? 0);

if ($assignmentId <= 0 || $teamId <= 0) {
    flash_set('error', 'ไม่พบงานที่ต้องการเปิด');
    redirect('/team/reports.php');
}

$pageTitle = 'รายละเอียดงานทีมนำ';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$record = null;
$categories = fetch_team_categories($teamId);
$departmentHeads = fetch_department_heads();
$review = null;
$severityHistory = [];
$routeLogs = [];

try {
    $stmt = Database::connection()->prepare(
        'SELECT
            ra.id AS assignment_id,
            ra.assignment_no,
            ra.assignment_status,
            ra.sent_reason,
            ra.assigned_at,
            ra.target_head_user_id,
            ir.id AS report_id,
            ir.report_no,
            ir.incident_title,
            ir.incident_detail,
            ir.initial_action,
            ir.current_severity_id,
            ir.report_delay_minutes,
            ir.reported_at,
            it.type_code,
            it.type_name,
            sl.level_code,
            d.department_name
         FROM report_assignments ra
         INNER JOIN incident_reports ir ON ir.id = ra.report_id
         INNER JOIN incident_types it ON it.id = ir.incident_type_id
         INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
         INNER JOIN departments d ON d.id = ir.incident_department_id
         WHERE ra.id = :assignment_id AND ra.target_team_id = :team_id
         LIMIT 1'
    );
    $stmt->execute([
        'assignment_id' => $assignmentId,
        'team_id' => $teamId,
    ]);
    $record = $stmt->fetch();

    $reviewStmt = Database::connection()->prepare(
        'SELECT *
         FROM team_reviews
         WHERE assignment_id = :assignment_id
         ORDER BY id DESC
         LIMIT 1'
    );
    $reviewStmt->execute(['assignment_id' => $assignmentId]);
    $review = $reviewStmt->fetch();
} catch (Throwable) {
    $record = null;
}

if (!$record) {
    flash_set('error', 'ไม่พบ assignment ของทีมนำนี้');
    redirect('/team/reports.php');
}

$severityHistory = fetch_report_severity_history((int) $record['report_id']);
$routeLogs = fetch_assignment_route_logs($assignmentId);

$severityOptions = fetch_severity_levels_by_type_code((string) $record['type_code']);

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Team Lead Review</div>
                <h1 class="text-3xl font-bold text-slate-900"><?= e((string) $record['incident_title']) ?></h1>
                <p class="mt-2 text-slate-600">เลข Assignment: <?= e((string) $record['assignment_no']) ?></p>
            </div>
            <a href="<?= e(base_url('team/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับรายการงาน
            </a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-6">
                <div id="severity-history" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ข้อมูลเหตุการณ์</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ประเภท</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['type_name']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ระดับปัจจุบัน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['level_code']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">หน่วยงาน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['department_name']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">เวลาล่าช้า</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['report_delay_minutes']) ?> นาที</div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">เหตุผลที่ admin ส่งต่อ</div>
                        <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) $record['sent_reason']) ?></div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">รายละเอียดเหตุการณ์</div>
                        <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) $record['incident_detail']) ?></div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">การแก้ไขเบื้องต้น</div>
                        <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) ($record['initial_action'] ?? '-')) ?></div>
                    </div>
                </div>

                <div id="route-log" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ประวัติระดับความรุนแรง</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($severityHistory === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ยังไม่มีประวัติ</div>
                        <?php else: ?>
                            <?php foreach ($severityHistory as $history): ?>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="font-semibold text-slate-900"><?= e((string) ($history['old_level_code'] ?: '-')) ?> -> <?= e((string) ($history['new_level_code'] ?: '-')) ?></div>
                                    <div class="mt-1 text-sm text-slate-600"><?= e((string) ($history['full_name'] ?: $history['changed_role_code'])) ?> | <?= e((string) $history['changed_at']) ?></div>
                                    <div class="mt-2 text-sm leading-7 text-slate-700"><?= e((string) ($history['change_reason'] ?: '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">บันทึกผลการพิจารณา</h2>
                    <form action="<?= e(base_url('actions/team_submit_review.php')) ?>" method="post" class="mt-4 space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="assignment_id" value="<?= e((string) $record['assignment_id']) ?>">
                        <input type="hidden" name="report_id" value="<?= e((string) $record['report_id']) ?>">

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ประเภทความเสี่ยงของทีมนำ</label>
                            <select name="selected_category_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                                <option value="">เลือกประเภท</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e((string) $category['id']) ?>" <?= (int) ($review['selected_category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= e(trim((string) (($category['category_code'] ? $category['category_code'] . ' - ' : '') . $category['category_name']))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ระดับความรุนแรงที่พิจารณาแล้ว</label>
                            <select name="current_severity_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <?php foreach ($severityOptions as $severity): ?>
                                    <option value="<?= e((string) $severity['id']) ?>" <?= (int) $record['current_severity_id'] === (int) $severity['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $severity['level_code']) ?> - <?= e((string) $severity['level_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">วิเคราะห์ปัญหา</label>
                            <textarea name="problem_analysis" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500"><?= e((string) ($review['problem_analysis'] ?? '')) ?></textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">แนวทางแก้ไข</label>
                            <textarea name="corrective_action" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500"><?= e((string) ($review['corrective_action'] ?? '')) ?></textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">การป้องกันซ้ำ</label>
                            <textarea name="preventive_action" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500"><?= e((string) ($review['preventive_action'] ?? '')) ?></textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ผลการพิจารณา</label>
                            <select id="decision_type" name="decision_type" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="resolved_by_team" <?= (($review['decision_type'] ?? '') === 'resolved_by_team') ? 'selected' : '' ?>>ทีมนำแก้ไขได้ ส่งกลับ admin</option>
                                <option value="forward_to_department_head" <?= (($review['decision_type'] ?? '') === 'forward_to_department_head') ? 'selected' : '' ?>>ทีมนำแก้ไขไม่ได้ ส่งต่อหัวหน้ากลุ่มงาน/หัวหน้างาน</option>
                            </select>
                        </div>

                        <div id="head_user_wrap" class="<?= (($review['decision_type'] ?? '') === 'forward_to_department_head') ? '' : 'hidden' ?>">
                            <label class="mb-2 block text-sm font-medium text-slate-700">เลือกหัวหน้ากลุ่มงาน/หัวหน้างาน</label>
                            <select name="head_user_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                                <option value="">เลือกผู้รับ</option>
                                <?php foreach ($departmentHeads as $head): ?>
                                    <option value="<?= e((string) $head['id']) ?>" <?= (int) ($record['target_head_user_id'] ?? 0) === (int) $head['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $head['full_name']) ?> (<?= e((string) $head['department_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">เหตุผลการดำเนินการ/ส่งต่อ</label>
                            <textarea name="decision_reason" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required><?= e((string) ($review['decision_reason'] ?? '')) ?></textarea>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">
                            บันทึกผลการพิจารณา
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">เส้นทางการส่งต่อ</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($routeLogs === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ยังไม่มี route log</div>
                        <?php else: ?>
                            <?php foreach ($routeLogs as $route): ?>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="font-semibold text-slate-900"><?= e((string) $route['route_action']) ?></div>
                                    <div class="mt-1 text-sm text-slate-600"><?= e((string) ($route['from_user_name'] ?: '-')) ?> | <?= e((string) $route['created_at']) ?></div>
                                    <div class="mt-2 text-sm leading-7 text-slate-700">เหตุผล: <?= e((string) ($route['route_reason'] ?: '-')) ?></div>
                                    <div class="mt-1 text-xs text-slate-500"><?= e((string) ($route['route_note'] ?: '')) ?></div>
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

<script>
    const decisionType = document.getElementById('decision_type');
    const headWrap = document.getElementById('head_user_wrap');
    if (decisionType && headWrap) {
        decisionType.addEventListener('change', function () {
            headWrap.classList.toggle('hidden', this.value !== 'forward_to_department_head');
        });
    }
</script>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
