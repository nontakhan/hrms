<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'Workflow History';
$selectedAction = trim((string) ($_GET['action'] ?? ''));
$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$selectedKeyword = trim((string) ($_GET['keyword'] ?? ''));
$selectedDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$selectedDateTo = trim((string) ($_GET['date_to'] ?? ''));
$actionOptions = workflow_audit_actions();
$actorOptions = fetch_workflow_audit_actors();
$exportUrl = build_query_url('actions/admin_export_workflow_history.php', [
    'action' => $selectedAction,
    'user_id' => $selectedUserId > 0 ? $selectedUserId : '',
    'keyword' => $selectedKeyword,
    'date_from' => $selectedDateFrom,
    'date_to' => $selectedDateTo,
]);
$summary = fetch_workflow_audit_summary([
    'action' => $selectedAction,
    'user_id' => $selectedUserId,
    'keyword' => $selectedKeyword,
    'date_from' => $selectedDateFrom,
    'date_to' => $selectedDateTo,
]);
$logs = fetch_workflow_audit_logs([
    'action' => $selectedAction,
    'user_id' => $selectedUserId,
    'keyword' => $selectedKeyword,
    'date_from' => $selectedDateFrom,
    'date_to' => $selectedDateTo,
]);

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Workflow History</div>
                <h1 class="text-3xl font-bold text-slate-900">ประวัติการตั้งค่า workflow</h1>
                <p class="mt-2 text-slate-600">ใช้ตรวจสอบย้อนหลังว่าใครแก้ปีงบประมาณ เปลี่ยน visibility หรือรีเซ็ตเลขรันเมื่อใด เพื่อช่วยติดตามการตั้งค่าระบบได้ชัดเจนขึ้น</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?= e(base_url('admin/settings_workflow.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    กลับหน้าตั้งค่า workflow
                </a>
                <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    กลับ Dashboard
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm font-medium text-slate-500">รายการทั้งหมด</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $summary['total']) ?></div>
            </div>
            <?php foreach ($summary['by_action'] as $item): ?>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <div class="text-sm font-medium text-slate-500"><?= e((string) $item['label']) ?></div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $item['total']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
            <form method="get" class="mb-4 grid gap-3 rounded-2xl bg-slate-50 p-4 lg:grid-cols-6">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">รายการที่เปลี่ยน</label>
                    <select name="action" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($actionOptions as $actionCode => $actionLabel): ?>
                            <option value="<?= e($actionCode) ?>" <?= $selectedAction === $actionCode ? 'selected' : '' ?>>
                                <?= e($actionLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">ค้นหาคำในประวัติ</label>
                    <input name="keyword" type="text" value="<?= e($selectedKeyword) ?>" placeholder="เช่น 2569, team_id, IM, visibility" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">ผู้ดำเนินการ</label>
                    <select name="user_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($actorOptions as $actor): ?>
                            <?php
                            $actorName = trim((string) ($actor['full_name'] ?? ''));
                            if ($actorName === '') {
                                $actorName = trim((string) ($actor['username'] ?? ''));
                            }
                            ?>
                            <option value="<?= e((string) $actor['id']) ?>" <?= $selectedUserId === (int) $actor['id'] ? 'selected' : '' ?>>
                                <?= e($actorName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">วันที่เริ่มต้น</label>
                    <input name="date_from" type="date" value="<?= e($selectedDateFrom) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">วันที่สิ้นสุด</label>
                    <input name="date_to" type="date" value="<?= e($selectedDateTo) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">กรองข้อมูล</button>
                    <a href="<?= e(base_url('admin/workflow_history.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-100">ล้างตัวกรอง</a>
                    <a href="<?= e($exportUrl) ?>" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 font-medium text-emerald-700 transition hover:bg-emerald-100">Export CSV</a>
                </div>
            </form>

            <table id="workflowHistoryTable" class="display w-full text-sm">
                <thead>
                    <tr>
                        <th>วันเวลา</th>
                        <th>ผู้ดำเนินการ</th>
                        <th>รายการ</th>
                        <th>ชนิดข้อมูล</th>
                        <th>รหัสอ้างอิง</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $actorLabel = trim((string) ($log['full_name'] ?? ''));
                        if ($actorLabel === '') {
                            $actorLabel = trim((string) ($log['username'] ?? ''));
                        }
                        if ($actorLabel === '') {
                            $actorLabel = 'ไม่ทราบผู้ใช้งาน';
                        }

                        $detailItems = [];
                        foreach (($log['detail_array'] ?? []) as $key => $value) {
                            if (is_array($value)) {
                                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            } elseif (is_bool($value)) {
                                $value = $value ? 'true' : 'false';
                            }

                            $detailItems[] = ucfirst(str_replace('_', ' ', (string) $key)) . ': ' . (string) $value;
                        }
                        ?>
                        <tr>
                            <td><?= e((string) $log['created_at']) ?></td>
                            <td><?= e($actorLabel) ?></td>
                            <td>
                                <a href="<?= e(workflow_audit_detail_url((int) $log['id'])) ?>" class="font-medium text-brand-700 hover:underline">
                                    <?= e(workflow_audit_action_label((string) $log['action'])) ?>
                                </a>
                            </td>
                            <td><?= e(workflow_entity_label((string) $log['entity_type'])) ?></td>
                            <td>
                                <?php $entityUrl = workflow_entity_url($log); ?>
                                <?php if ($entityUrl !== null): ?>
                                    <a href="<?= e($entityUrl) ?>" class="font-medium text-brand-700 hover:underline">
                                        <?= e((string) $log['entity_id']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= e((string) $log['entity_id']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($detailItems === []): ?>
                                    <span class="text-slate-400">-</span>
                                <?php else: ?>
                                    <div class="space-y-1">
                                        <?php foreach ($detailItems as $item): ?>
                                            <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-700"><?= e($item) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    $(function () {
        $('#workflowHistoryTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    });
</script>
<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
