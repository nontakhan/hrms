<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'ตั้งค่าการไหลงาน';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$fiscalYears = fetch_fiscal_years();
$teams = fetch_all_teams();
$departments = fetch_all_departments();
$headUsers = fetch_department_head_users();
$visibilityEntries = fetch_team_department_visibility_entries();
$activeFiscalYear = active_fiscal_year();
$runningSummary = fetch_team_running_number_summary(isset($activeFiscalYear['id']) ? (int) $activeFiscalYear['id'] : null);
$editFiscalYearId = isset($_GET['edit_fiscal_year']) ? (int) $_GET['edit_fiscal_year'] : 0;
$editingFiscalYear = null;
$editVisibilityId = isset($_GET['edit_visibility']) ? (int) $_GET['edit_visibility'] : 0;
$editingVisibility = null;

foreach ($fiscalYears as $year) {
    if ((int) $year['id'] === $editFiscalYearId) {
        $editingFiscalYear = $year;
        break;
    }
}

foreach ($visibilityEntries as $entry) {
    if ((int) $entry['id'] === $editVisibilityId) {
        $editingVisibility = $entry;
        break;
    }
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="space-y-6">
        <div class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Workflow Settings</div>
                    <h1 class="text-3xl font-bold text-slate-900">ตั้งค่าปีงบประมาณและการมองเห็นเคส</h1>
                    <p class="mt-2 max-w-3xl text-slate-600">ใช้กำหนดปีงบที่ใช้รันเลข assignment ตรวจเลขรันล่าสุดรายทีมนำ และควบคุมสิทธิ์มองเห็นเคสข้ามหัวหน้าสำหรับโครงสร้างงานที่ซับซ้อน</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="<?= e(base_url('admin/workflow_history.php')) ?>" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 font-medium text-brand-700 transition hover:bg-brand-100">ดูประวัติการตั้งค่า</a>
                    <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">กลับ Dashboard</a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ปีงบที่มี</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($fiscalYears)) ?></div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <div class="text-sm text-emerald-700">ปีงบที่ active</div>
                <div class="mt-2 text-3xl font-bold text-emerald-900"><?= e((string) ($activeFiscalYear['year_label'] ?? '-')) ?></div>
            </div>
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5">
                <div class="text-sm text-sky-700">รายการ visibility</div>
                <div class="mt-2 text-3xl font-bold text-sky-900"><?= e((string) count($visibilityEntries)) ?></div>
            </div>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
                <div class="text-sm text-violet-700">เลขรันที่ติดตาม</div>
                <div class="mt-2 text-3xl font-bold text-violet-900"><?= e((string) count($runningSummary)) ?></div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section id="fiscal-years" class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">ปีงบประมาณ</h2>
                        <p class="mt-1 text-sm text-slate-600">ปีงบที่ active จะถูกใช้สำหรับรันเลข assignment ของทุกทีมนำ</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        ปีที่ใช้งานอยู่:
                        <span class="font-semibold text-slate-900"><?= e((string) ($activeFiscalYear['year_label'] ?? '-')) ?></span>
                    </div>
                </div>

                <form action="<?= e(base_url($editingFiscalYear ? 'actions/admin_update_fiscal_year.php' : 'actions/admin_save_fiscal_year.php')) ?>" method="post" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 p-6 md:grid-cols-2">
                    <?= csrf_field() ?>
                    <?php if ($editingFiscalYear): ?>
                        <input type="hidden" name="fiscal_year_id" value="<?= e((string) $editingFiscalYear['id']) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ปีงบประมาณ</label>
                        <input name="year_label" type="text" maxlength="10" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" placeholder="2569" value="<?= e((string) ($editingFiscalYear['year_label'] ?? '')) ?>" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ปีย่อ</label>
                        <input name="year_short" type="text" maxlength="10" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" placeholder="69" value="<?= e((string) ($editingFiscalYear['year_short'] ?? '')) ?>" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">วันที่เริ่ม</label>
                        <input name="date_start" type="date" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" value="<?= e((string) ($editingFiscalYear['date_start'] ?? '')) ?>" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">วันที่สิ้นสุด</label>
                        <input name="date_end" type="date" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" value="<?= e((string) ($editingFiscalYear['date_end'] ?? '')) ?>" required>
                    </div>
                    <div class="flex flex-wrap gap-3 md:col-span-2">
                        <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700"><?= $editingFiscalYear ? 'บันทึกการแก้ไขปีงบประมาณ' : 'เพิ่มปีงบประมาณ' ?></button>
                        <?php if ($editingFiscalYear): ?>
                            <a href="<?= e(base_url('admin/settings_workflow.php')) ?>" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">ยกเลิกการแก้ไข</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">ปีงบ</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">ช่วงวันที่</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">สถานะ</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($fiscalYears as $year): ?>
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $year['year_label']) ?> / <?= e((string) $year['year_short']) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= e((string) $year['date_start']) ?> ถึง <?= e((string) $year['date_end']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $year['is_active'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= (int) $year['is_active'] === 1 ? 'ใช้งานอยู่' : 'ยังไม่ active' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?= e(base_url('admin/settings_workflow.php?edit_fiscal_year=' . $year['id'])) ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">แก้ไข</a>
                                            <?php if ((int) $year['is_active'] !== 1): ?>
                                                <form action="<?= e(base_url('actions/admin_activate_fiscal_year.php')) ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="fiscal_year_id" value="<?= e((string) $year['id']) ?>">
                                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">ตั้งเป็นปีที่ใช้งาน</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="px-3 py-2 text-xs font-medium text-emerald-700">กำลังใช้งาน</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="running-numbers" class="mt-6 rounded-2xl border border-slate-200 p-6">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">เลขรันล่าสุดรายทีมนำ</h3>
                        <p class="mt-1 text-sm text-slate-600">ใช้ตรวจสอบความต่อเนื่องของเลข assignment และรีเซ็ตได้เฉพาะกรณีที่ปลอดภัย</p>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">ทีมนำ</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">ปีงบ</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">เลขล่าสุด</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">ตัวอย่างเลขถัดไป</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php foreach ($runningSummary as $row): ?>
                                    <?php $nextNumber = (int) $row['last_number'] + 1; ?>
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $row['team_code']) ?> - <?= e((string) $row['team_name']) ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e((string) $row['year_label']) ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e((string) $row['last_number']) ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e((string) $row['team_code']) ?> <?= e((string) $nextNumber) ?>/<?= e((string) $row['year_short']) ?></td>
                                        <td class="px-4 py-3">
                                            <?php if ((int) $row['last_number'] > 0): ?>
                                                <form action="<?= e(base_url('actions/admin_reset_team_running_number.php')) ?>" method="post" onsubmit="return confirm('ยืนยันการรีเซ็ตเลขรันของทีมนำนี้เป็น 0? ใช้ได้เฉพาะกรณีที่ยังไม่มี assignment ในปีงบนี้เท่านั้น');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="team_id" value="<?= e((string) $row['team_id']) ?>">
                                                    <input type="hidden" name="fiscal_year_id" value="<?= e((string) ($activeFiscalYear['id'] ?? 0)) ?>">
                                                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white">รีเซ็ตเลขรัน</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-500">ยังไม่ต้องรีเซ็ต</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($runningSummary === []): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-4 text-center text-slate-500">ยังไม่มีข้อมูลเลขรันสำหรับปีงบที่กำหนด</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="visibility" class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">สิทธิ์มองเห็นเคสข้ามหัวหน้า</h2>
                    <p class="mt-1 text-sm text-slate-600">กำหนดว่าใครสามารถมองเห็นเคสของทีมนำและหน่วยงานใดเพิ่มได้ แม้ assignment จะส่งไปยังหัวหน้าคนอื่น</p>
                </div>

                <form action="<?= e(base_url($editingVisibility ? 'actions/admin_update_team_visibility.php' : 'actions/admin_save_team_visibility.php')) ?>" method="post" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 p-6">
                    <?= csrf_field() ?>
                    <?php if ($editingVisibility): ?>
                        <input type="hidden" name="visibility_id" value="<?= e((string) $editingVisibility['id']) ?>">
                    <?php endif; ?>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ทีมนำ</label>
                            <select name="team_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="">เลือกทีมนำ</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= e((string) $team['id']) ?>" <?= (int) ($editingVisibility['team_id'] ?? 0) === (int) $team['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">หน่วยงาน</label>
                            <select name="department_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="">เลือกหน่วยงาน</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= e((string) $department['id']) ?>" <?= (int) ($editingVisibility['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $department['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">ผู้ที่มองเห็นเพิ่ม</label>
                            <select name="viewer_user_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="">เลือกหัวหน้าที่จะเห็นเคส</option>
                                <?php foreach ($headUsers as $headUser): ?>
                                    <option value="<?= e((string) $headUser['id']) ?>" <?= (int) ($editingVisibility['viewer_user_id'] ?? 0) === (int) $headUser['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $headUser['full_name']) ?> (<?= e((string) ($headUser['department_name'] ?? '-')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">รูปแบบการมองเห็น</label>
                            <select name="visibility_type" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="supervisor" <?= (($editingVisibility['visibility_type'] ?? '') === 'supervisor') ? 'selected' : '' ?>>หัวหน้ากลุ่ม/ผู้กำกับดูแล</option>
                                <option value="direct" <?= (($editingVisibility['visibility_type'] ?? '') === 'direct') ? 'selected' : '' ?>>เห็นโดยตรง</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700"><?= $editingVisibility ? 'บันทึกการแก้ไขสิทธิ์มองเห็น' : 'บันทึกสิทธิ์มองเห็น' ?></button>
                        <?php if ($editingVisibility): ?>
                            <a href="<?= e(base_url('admin/settings_workflow.php')) ?>" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">ยกเลิกการแก้ไข</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">ทีมนำ</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">หน่วยงาน</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">ผู้เห็นเคส</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">ประเภท</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">สถานะ</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($visibilityEntries as $entry): ?>
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $entry['team_code']) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= e((string) $entry['department_name']) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= e((string) $entry['viewer_name']) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= e((string) $entry['visibility_type']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $entry['is_active'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= (int) $entry['is_active'] === 1 ? 'active' : 'inactive' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?= e(base_url('admin/settings_workflow.php?edit_visibility=' . $entry['id'])) ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">แก้ไข</a>
                                            <form action="<?= e(base_url('actions/admin_toggle_team_visibility_status.php')) ?>" method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="visibility_id" value="<?= e((string) $entry['id']) ?>">
                                                <input type="hidden" name="current_status" value="<?= e((string) $entry['is_active']) ?>">
                                                <button type="submit" class="rounded-lg <?= (int) $entry['is_active'] === 1 ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white' ?> px-3 py-2 text-xs font-semibold">
                                                    <?= (int) $entry['is_active'] === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($visibilityEntries === []): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-slate-500">ยังไม่มีการตั้งค่าสิทธิ์มองเห็นเคส</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
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
