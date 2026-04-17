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
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Department Head Review</div>
                <h1 class="text-3xl font-bold text-slate-900"><?= e((string) $record['incident_title']) ?></h1>
                <p class="mt-2 text-slate-600">เลข Assignment: <?= e((string) $record['assignment_no']) ?> | ทีมนำ <?= e((string) $record['team_code']) ?></p>
            </div>
            <a href="<?= e(base_url('head/reports.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับรายการงาน
            </a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ข้อมูลเหตุการณ์</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ทีมนำ</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['team_name']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ระดับปัจจุบัน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['level_code']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ประเภท</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['type_name']) ?></div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">หน่วยงาน</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $record['department_name']) ?></div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">เหตุผลที่ทีมนำส่งต่อ</div>
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

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">บันทึกแนวทางแก้ไข</h2>
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

                        <button type="submit" class="w-full rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">
                            บันทึกและส่งคืนให้ทีมนำ
                        </button>
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
