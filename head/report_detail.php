<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('DEPARTMENT_HEAD');

$assignmentId = isset($_GET['assignment_id']) ? (int) $_GET['assignment_id'] : 0;
$user = Auth::user();
$userId = (int) ($user['id'] ?? 0);

if ($assignmentId <= 0 || $userId <= 0) {
    flash_set('error', 'ไม่พบงานที่ต้องการเปิด');
    redirect('/head/reports.php');
}

$pageTitle = 'รายละเอียดงานหัวหน้ากลุ่มงาน';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$record = null;
$review = null;
$routeLogs = [];

function head_assignment_status_badge_class(string $status): string
{
    return match ($status) {
        'sent' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
        'received' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
        'in_progress' => 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200',
        'returned_to_team' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        default => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200',
    };
}

try {
    $stmt = Database::connection()->prepare(
        'SELECT
            ra.id AS assignment_id,
            ra.assignment_no,
            ra.assignment_status,
            ra.sent_reason,
            ra.assigned_at,
            ir.id AS report_id,
            ir.report_no,
            ir.incident_title,
            ir.incident_detail,
            ir.initial_action,
            ir.report_delay_minutes,
            ir.reported_at,
            it.type_name,
            sl.level_code,
            d.department_name,
            t.team_code,
            t.team_name
         FROM report_assignments ra
         INNER JOIN incident_reports ir ON ir.id = ra.report_id
         INNER JOIN incident_types it ON it.id = ir.incident_type_id
         INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
         INNER JOIN departments d ON d.id = ir.incident_department_id
         INNER JOIN teams t ON t.id = ra.target_team_id
         WHERE ra.id = :assignment_id
           AND ra.target_head_user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([
        'assignment_id' => $assignmentId,
        'user_id' => $userId,
    ]);
    $record = $stmt->fetch();

    $reviewStmt = Database::connection()->prepare(
        'SELECT *
         FROM department_head_reviews
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
    flash_set('error', 'ไม่พบงานของหัวหน้ากลุ่มงาน/หัวหน้างานนี้');
    redirect('/head/reports.php');
}

$routeLogs = fetch_assignment_route_logs($assignmentId);

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Department Head Review</div>
                <h1 class="text-3xl font-bold text-slate-900"><?= e((string) $record['incident_title']) ?></h1>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">Assignment <?= e((string) $record['assignment_no']) ?></span>
                    <span class="rounded-full px-3 py-1 font-medium <?= e(head_assignment_status_badge_class((string) $record['assignment_status'])) ?>">
                        <?= e((string) $record['assignment_status']) ?>
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">ทีมนำ <?= e((string) $record['team_code']) ?></span>
                </div>
                <p class="mt-3 max-w-3xl text-slate-600">หน้าสำหรับหัวหน้ากลุ่มงานหรือหัวหน้างานใช้บันทึกแนวทางแก้ไขของหน่วยงาน และส่งผลกลับไปยังทีมนำเพื่อสรุปกลับ admin</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="#head-review-form" class="rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">บันทึกแนวทางแก้ไข</a>
                <a href="<?= e(base_url('head/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">กลับรายการงาน</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ทีมนำที่ส่งมา</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $record['team_name']) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ระดับความรุนแรง</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $record['level_code']) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ประเภทเหตุการณ์</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $record['type_name']) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">หน่วยงาน</div>
                <div class="mt-2 text-lg font-semibold text-slate-900"><?= e((string) $record['department_name']) ?></div>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">ข้อมูลเหตุการณ์และเหตุผลที่ทีมนำส่งต่อ</h2>
                            <p class="mt-1 text-sm text-slate-500">อ่านบริบทของเคสและเหตุผลจากทีมนำก่อนกำหนดแนวทางแก้ไขของหน่วยงาน</p>
                        </div>
                        <a href="#head-review-form" class="rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-200">ไปฟอร์มบันทึกผล</a>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ทีมนำ</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['team_name']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">วันที่ได้รับมอบหมาย</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['assigned_at']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 md:col-span-2">
                            <div class="text-sm text-slate-500">เหตุผลที่ทีมนำส่งต่อ</div>
                            <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) $record['sent_reason']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 md:col-span-2">
                            <div class="text-sm text-slate-500">รายละเอียดเหตุการณ์</div>
                            <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) $record['incident_detail']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 md:col-span-2">
                            <div class="text-sm text-slate-500">การแก้ไขเบื้องต้น</div>
                            <div class="mt-2 whitespace-pre-line leading-7 text-slate-700"><?= e((string) ($record['initial_action'] ?? '-')) ?></div>
                        </div>
                    </div>
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
                                    <?php if ((string) ($route['route_note'] ?? '') !== ''): ?>
                                        <div class="mt-1 text-xs text-slate-500"><?= e((string) $route['route_note']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div id="head-review-form" class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">บันทึกแนวทางแก้ไข</h2>
                    <p class="mt-1 text-sm text-slate-500">ระบุแนวทางแก้ไขและหมายเหตุของหน่วยงาน ก่อนส่งผลกลับให้ทีมนำ</p>
                    <form action="<?= e(base_url('actions/head_submit_review.php')) ?>" method="post" class="mt-4 space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="assignment_id" value="<?= e((string) $record['assignment_id']) ?>">
                        <input type="hidden" name="report_id" value="<?= e((string) $record['report_id']) ?>">

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">แนวทางแก้ไข</label>
                            <textarea name="review_action" rows="5" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required><?= e((string) ($review['review_action'] ?? '')) ?></textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">หมายเหตุเพิ่มเติม</label>
                            <textarea name="review_note" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500"><?= e((string) ($review['review_note'] ?? '')) ?></textarea>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">บันทึกและส่งคืนให้ทีมนำ</button>
                    </form>
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
