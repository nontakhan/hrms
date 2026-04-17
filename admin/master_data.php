<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'จัดการข้อมูลพื้นฐาน';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$teams = fetch_all_teams();
$departments = fetch_all_departments();
$allTeamCategories = [];

try {
    $stmt = Database::connection()->query(
        'SELECT rc.id, rc.team_id, rc.parent_id, rc.category_name, rc.category_code, t.team_code, t.team_name
         FROM risk_categories rc
         INNER JOIN teams t ON t.id = rc.team_id
         WHERE rc.is_active = 1
         ORDER BY t.team_code ASC, rc.category_name ASC'
    );
    $allTeamCategories = $stmt->fetchAll();
} catch (Throwable) {
    $allTeamCategories = [];
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Master Data</div>
                <h1 class="text-3xl font-bold text-slate-900">จัดการข้อมูลพื้นฐาน</h1>
                <p class="mt-2 text-slate-600">ใช้สำหรับดูแลทีมนำ หน่วยงาน และประเภทความเสี่ยงของแต่ละทีมนำ</p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard
            </a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">เพิ่มทีมนำ</h2>
                <form action="<?= e(base_url('actions/admin_save_team.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รหัสทีมนำ</label>
                        <input name="team_code" type="text" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อทีมนำ</label>
                        <input name="team_name" type="text" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รายละเอียด</label>
                        <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                        บันทึกทีมนำ
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">เพิ่มหน่วยงาน</h2>
                <form action="<?= e(base_url('actions/admin_save_department.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รหัสหน่วยงาน</label>
                        <input name="department_code" type="text" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อหน่วยงาน</label>
                        <input name="department_name" type="text" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ประเภท</label>
                        <select name="department_type" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="general">general</option>
                            <option value="clinical">clinical</option>
                            <option value="support">support</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input name="is_nursing_group" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        เป็นกลุ่มงานการพยาบาล
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">
                        บันทึกหน่วยงาน
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">เพิ่มประเภทความเสี่ยงของทีมนำ</h2>
                <form action="<?= e(base_url('actions/admin_save_team_category.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
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
                        <label class="mb-2 block text-sm font-medium text-slate-700">Parent Category</label>
                        <select name="parent_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ไม่มี (ระดับบนสุด)</option>
                            <?php foreach ($allTeamCategories as $category): ?>
                                <option value="<?= e((string) $category['id']) ?>">
                                    <?= e((string) $category['team_code']) ?> - <?= e((string) $category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รหัสประเภท</label>
                        <input name="category_code" type="text" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อประเภท</label>
                        <input name="category_name" type="text" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-3 font-semibold text-slate-900 transition hover:bg-amber-400">
                        บันทึกประเภทความเสี่ยง
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">ทีมนำในระบบ</h2>
                <div class="mt-4 space-y-3">
                    <?php foreach ($teams as $team): ?>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900"><?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?></div>
                            <?php if (!empty($team['description'])): ?>
                                <div class="mt-1 text-sm text-slate-500"><?= e((string) $team['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">หน่วยงานในระบบ</h2>
                <div class="mt-4 space-y-3">
                    <?php foreach ($departments as $department): ?>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900"><?= e((string) $department['department_name']) ?></div>
                            <div class="mt-1 text-xs text-slate-500">
                                <?= e((string) ($department['department_code'] ?: '-')) ?>
                                | <?= e((string) ($department['department_type'] ?: 'general')) ?>
                                <?php if ((int) ($department['is_nursing_group'] ?? 0) === 1): ?>
                                    | กลุ่มการพยาบาล
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">ประเภทความเสี่ยงของทีมนำ</h2>
                <div class="mt-4 space-y-3">
                    <?php foreach ($allTeamCategories as $category): ?>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900"><?= e((string) $category['team_code']) ?> - <?= e((string) $category['category_name']) ?></div>
                            <div class="mt-1 text-xs text-slate-500"><?= e((string) ($category['category_code'] ?: '-')) ?></div>
                        </div>
                    <?php endforeach; ?>
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
