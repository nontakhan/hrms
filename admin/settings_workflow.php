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
$editFiscalYearId = isset($_GET['edit_fiscal_year']) ? (int) $_GET['edit_fiscal_year'] : 0;
$editingFiscalYear = null;

foreach ($fiscalYears as $year) {
    if ((int) $year['id'] === $editFiscalYearId) {
        $editingFiscalYear = $year;
        break;
    }
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="space-y-6">
        <div class="rounded-[2rem] bg-white p-8 shadow-soft">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Workflow Settings</div>
                    <h1 class="text-3xl font-bold text-slate-900">ตั้งค่าปีงบประมาณและการมองเห็นเคส</h1>
                    <p class="mt-2 text-slate-600">ใช้กำหนดปีงบที่ใช้รันเลขเอกสาร และสิทธิ์มองเห็นเคสข้ามหัวหน้าสำหรับโครงสร้างงานที่ซับซ้อน เช่น กลุ่มการพยาบาล</p>
                </div>
                <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    กลับ Dashboard
                </a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-[2rem] bg-white p-8 shadow-soft">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">ปีงบประมาณ</h2>
                        <p class="mt-1 text-sm text-slate-600">ปีงบที่ active จะถูกใช้สำหรับรันเลข assignment ของทุกทีมนำ</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        ปีที่ใช้งานอยู่:
                        <span class="font-semibold text-slate-900">
                            <?= e((string) ($activeFiscalYear['year_label'] ?? '-')) ?>
                        </span>
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
                        <label class="mb-2 block text-sm font-medium text-slate-700">ปีแบบย่อ</label>
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
                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">
                            <?= $editingFiscalYear ? 'บันทึกการแก้ไขปีงบประมาณ' : 'เพิ่มปีงบประมาณ' ?>
                        </button>
                        <?php if ($editingFiscalYear): ?>
                            <a href="<?= e(base_url('admin/settings_workflow.php')) ?>" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">
                                ยกเลิกการแก้ไข
                            </a>
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
                                    <td class="px-4 py-3 font-medium text-slate-900">
                                        <?= e((string) $year['year_label']) ?> / <?= e((string) $year['year_short']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <?= e((string) $year['date_start']) ?> ถึง <?= e((string) $year['date_end']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $year['is_active'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= (int) $year['is_active'] === 1 ? 'ใช้งานอยู่' : 'ยังไม่ active' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?= e(base_url('admin/settings_workflow.php?edit_fiscal_year=' . $year['id'])) ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                                แก้ไข
                                            </a>
                                            <?php if ((int) $year['is_active'] !== 1): ?>
                                                <form action="<?= e(base_url('actions/admin_activate_fiscal_year.php')) ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="fiscal_year_id" value="<?= e((string) $year['id']) ?>">
                                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">
                                                        ตั้งเป็นปีที่ใช้งาน
                                                    </button>
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
            </section>

            <section class="rounded-[2rem] bg-white p-8 shadow-soft">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">สิทธิ์มองเห็นเคสข้ามหัวหน้า</h2>
                    <p class="mt-1 text-sm text-slate-600">ใช้กำหนดว่าใครสามารถมองเห็นเคสของทีมนำและหน่วยงานใดเพิ่มเติมได้ แม้ assignment จะส่งไปยังหัวหน้าคนอื่น</p>
                </div>

                <form action="<?= e(base_url('actions/admin_save_team_visibility.php')) ?>" method="post" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 p-6">
                    <?= csrf_field() ?>
                    <div class="grid gap-4 md:grid-cols-2">
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
                            <label class="mb-2 block text-sm font-medium text-slate-700">หน่วยงาน</label>
                            <select name="department_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="">เลือกหน่วยงาน</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= e((string) $department['id']) ?>"><?= e((string) $department['department_name']) ?></option>
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
                                    <option value="<?= e((string) $headUser['id']) ?>">
                                        <?= e((string) $headUser['full_name']) ?> (<?= e((string) ($headUser['department_name'] ?? '-')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">รูปแบบการมองเห็น</label>
                            <select name="visibility_type" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                                <option value="supervisor">หัวหน้ากลุ่ม/ผู้กำกับดูแล</option>
                                <option value="direct">เห็นโดยตรง</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">
                            บันทึกสิทธิ์มองเห็น
                        </button>
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
                                        <form action="<?= e(base_url('actions/admin_toggle_team_visibility_status.php')) ?>" method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="visibility_id" value="<?= e((string) $entry['id']) ?>">
                                            <input type="hidden" name="current_status" value="<?= e((string) $entry['is_active']) ?>">
                                            <button type="submit" class="rounded-lg <?= (int) $entry['is_active'] === 1 ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white' ?> px-3 py-2 text-xs font-semibold">
                                                <?= (int) $entry['is_active'] === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
