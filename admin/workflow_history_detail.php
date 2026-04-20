<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$auditId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($auditId <= 0) {
    flash_set('error', 'ไม่พบรายการประวัติที่ต้องการเปิดดู');
    redirect('/admin/workflow_history.php');
}

$log = fetch_workflow_audit_log_detail($auditId);

if (!$log) {
    flash_set('error', 'ไม่พบข้อมูล audit log ที่ต้องการ');
    redirect('/admin/workflow_history.php');
}

$pageTitle = 'Workflow Audit Detail';
$actorLabel = trim((string) ($log['full_name'] ?? ''));
if ($actorLabel === '') {
    $actorLabel = trim((string) ($log['username'] ?? ''));
}
if ($actorLabel === '') {
    $actorLabel = 'ไม่ทราบผู้ใช้งาน';
}

$entityUrl = workflow_entity_url($log);

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-6xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Workflow Audit Detail</div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900"><?= e(workflow_audit_action_label((string) $log['action'])) ?></h1>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Audit ID: <?= e((string) $log['id']) ?> · บันทึกเมื่อ <?= e((string) $log['created_at']) ?>
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <?php if ($entityUrl !== null): ?>
                    <a href="<?= e($entityUrl) ?>" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 font-medium text-brand-700 transition hover:bg-brand-100">
                        เปิดหน้าตั้งค่าที่เกี่ยวข้อง
                    </a>
                <?php endif; ?>
                <a href="<?= e(base_url('admin/workflow_history.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    กลับ Workflow History
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">ข้อมูลหลัก</h2>
                    <div class="mt-4 grid gap-4">
                        <article class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ผู้ดำเนินการ</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e($actorLabel) ?></div>
                        </article>
                        <article class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">ชนิดข้อมูล</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e(workflow_entity_label((string) $log['entity_type'])) ?></div>
                        </article>
                        <article class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">รหัสอ้างอิง</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) $log['entity_id']) ?></div>
                        </article>
                        <article class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">IP Address</div>
                            <div class="mt-1 font-semibold text-slate-900"><?= e((string) ($log['ip_address'] ?: '-')) ?></div>
                        </article>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">User Agent</h2>
                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm leading-7 break-all text-slate-700">
                        <?= e((string) ($log['user_agent'] ?: '-')) ?>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">รายละเอียดแบบอ่านง่าย</h2>
                    <p class="mt-1 text-sm text-slate-500">แยกแต่ละ field จาก detail json เพื่อให้อ่านย้อนหลังได้สะดวกกว่า raw format</p>

                    <div class="mt-4 space-y-3">
                        <?php if (($log['detail_array'] ?? []) === []): ?>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">ไม่มี detail เพิ่มเติมในรายการนี้</div>
                        <?php else: ?>
                            <?php foreach ($log['detail_array'] as $key => $value): ?>
                                <article class="rounded-xl bg-slate-50 p-4">
                                    <div class="text-sm text-slate-500"><?= e(ucfirst(str_replace('_', ' ', (string) $key))) ?></div>
                                    <pre class="mt-2 whitespace-pre-wrap break-words text-sm leading-7 text-slate-800"><?= e(is_array($value) ? pretty_json($value) : (string) $value) ?></pre>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Raw JSON</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs leading-6 text-slate-100"><?= e(pretty_json($log['detail_array'] ?? [])) ?></pre>
                </section>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
