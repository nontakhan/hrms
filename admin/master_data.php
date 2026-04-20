<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'จัดการข้อมูลพื้นฐาน';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$teams = [];
$departments = [];
$allTeamCategories = [];
$editType = trim((string) ($_GET['edit_type'] ?? ''));
$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$editingTeam = null;
$editingDepartment = null;
$editingCategory = null;
$editingCategoryOptions = [];
$categoryTree = [];
$teamSearch = trim((string) ($_GET['team_q'] ?? ''));
$departmentSearch = trim((string) ($_GET['department_q'] ?? ''));
$categorySearch = trim((string) ($_GET['category_q'] ?? ''));

try {
    $teams = Database::connection()->query(
        'SELECT id, team_code, team_name, description, is_active
         FROM teams
         ORDER BY team_code ASC, team_name ASC'
    )->fetchAll();

    $departments = Database::connection()->query(
        'SELECT id, department_code, department_name, department_type, parent_department_id, is_nursing_group, is_active
         FROM departments
         ORDER BY department_name ASC'
    )->fetchAll();

    $allTeamCategories = Database::connection()->query(
        'SELECT rc.id, rc.team_id, rc.parent_id, rc.category_name, rc.category_code, rc.sort_order, rc.is_active, t.team_code, t.team_name
         FROM risk_categories rc
         INNER JOIN teams t ON t.id = rc.team_id
         ORDER BY t.team_code ASC, rc.sort_order ASC, rc.category_name ASC'
    )->fetchAll();

    if ($editType === 'team' && $editId > 0) {
        $stmt = Database::connection()->prepare(
            'SELECT id, team_code, team_name, description, is_active
             FROM teams
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $editId]);
        $editingTeam = $stmt->fetch() ?: null;
    }

    if ($editType === 'department' && $editId > 0) {
        $stmt = Database::connection()->prepare(
            'SELECT id, department_code, department_name, department_type, is_nursing_group, is_active
             FROM departments
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $editId]);
        $editingDepartment = $stmt->fetch() ?: null;
    }

    if ($editType === 'category' && $editId > 0) {
        $stmt = Database::connection()->prepare(
            'SELECT id, team_id, parent_id, category_name, category_code, sort_order, is_active
             FROM risk_categories
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $editId]);
        $editingCategory = $stmt->fetch() ?: null;
        if ($editingCategory) {
            $editingCategoryOptions = flatten_category_tree(
                $allTeamCategories,
                (int) $editingCategory['team_id'],
                (int) $editingCategory['id']
            );
        }
    }

    $categoryTree = flatten_category_tree($allTeamCategories);
} catch (Throwable) {
    $teams = [];
    $departments = [];
    $allTeamCategories = [];
    $categoryTree = [];
}

$filteredTeams = array_values(array_filter(
    $teams,
    static function (array $team) use ($teamSearch): bool {
        if ($teamSearch === '') {
            return true;
        }

        $haystack = mb_strtolower(trim((string) (($team['team_code'] ?? '') . ' ' . ($team['team_name'] ?? '') . ' ' . ($team['description'] ?? ''))));
        return str_contains($haystack, mb_strtolower($teamSearch));
    }
));

$filteredDepartments = array_values(array_filter(
    $departments,
    static function (array $department) use ($departmentSearch): bool {
        if ($departmentSearch === '') {
            return true;
        }

        $haystack = mb_strtolower(trim((string) (($department['department_code'] ?? '') . ' ' . ($department['department_name'] ?? '') . ' ' . ($department['department_type'] ?? ''))));
        return str_contains($haystack, mb_strtolower($departmentSearch));
    }
));

$filteredCategoryTree = array_values(array_filter(
    $categoryTree,
    static function (array $category) use ($categorySearch): bool {
        if ($categorySearch === '') {
            return true;
        }

        $haystack = mb_strtolower(trim((string) (($category['team_code'] ?? '') . ' ' . ($category['category_code'] ?? '') . ' ' . ($category['category_name'] ?? ''))));
        return str_contains($haystack, mb_strtolower($categorySearch));
    }
));

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Master Data</div>
                <h1 class="text-3xl font-bold text-slate-900">จัดการข้อมูลพื้นฐาน</h1>
                <p class="mt-2 max-w-3xl text-slate-600">ดูแลทีมนำ หน่วยงาน และประเภทความเสี่ยงจากหน้าเดียว โดยแยกโซน “เพิ่มหรือแก้ไขข้อมูล” ออกจาก “รายการข้อมูลในระบบ” ให้ใช้ง่ายขึ้น</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?= e(base_url('admin/users.php')) ?>" class="rounded-xl bg-slate-900 px-4 py-3 font-medium text-white transition hover:bg-slate-800">จัดการผู้ใช้</a>
                <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">กลับ Dashboard</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">ทีมนำในระบบ</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($teams)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">หน่วยงานในระบบ</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($departments)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">หมวดความเสี่ยงทั้งหมด</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($allTeamCategories)) ?></div>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900"><?= $editingTeam ? 'แก้ไขทีมนำ' : 'เพิ่มทีมนำ' ?></h2>
                <p class="mt-1 text-sm text-slate-500">ใช้ส่วนนี้สำหรับดูแลรหัสและชื่อทีมนำที่ใช้ใน workflow ส่งต่อเคส</p>
                <form action="<?= e(base_url($editingTeam ? 'actions/admin_update_team.php' : 'actions/admin_save_team.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <?php if ($editingTeam): ?>
                        <input type="hidden" name="team_id" value="<?= e((string) $editingTeam['id']) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รหัสทีมนำ</label>
                        <input name="team_code" type="text" value="<?= e((string) ($editingTeam['team_code'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อทีมนำ</label>
                        <input name="team_name" type="text" value="<?= e((string) ($editingTeam['team_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รายละเอียด</label>
                        <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500"><?= e((string) ($editingTeam['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="flex-1 rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700"><?= $editingTeam ? 'บันทึกการแก้ไข' : 'บันทึกทีมนำ' ?></button>
                        <?php if ($editingTeam): ?>
                            <a href="<?= e(base_url('admin/master_data.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">ยกเลิก</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900"><?= $editingDepartment ? 'แก้ไขหน่วยงาน' : 'เพิ่มหน่วยงาน' ?></h2>
                <p class="mt-1 text-sm text-slate-500">ดูแลหน่วยงานที่อ้างอิงในการรายงาน การส่งต่อ และสิทธิ์มองเห็นในระบบ</p>
                <form action="<?= e(base_url($editingDepartment ? 'actions/admin_update_department.php' : 'actions/admin_save_department.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <?php if ($editingDepartment): ?>
                        <input type="hidden" name="department_id" value="<?= e((string) $editingDepartment['id']) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รหัสหน่วยงาน</label>
                        <input name="department_code" type="text" value="<?= e((string) ($editingDepartment['department_code'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อหน่วยงาน</label>
                        <input name="department_name" type="text" value="<?= e((string) ($editingDepartment['department_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ประเภท</label>
                        <select name="department_type" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <?php $selectedDepartmentType = (string) ($editingDepartment['department_type'] ?? 'general'); ?>
                            <option value="general" <?= $selectedDepartmentType === 'general' ? 'selected' : '' ?>>general</option>
                            <option value="clinical" <?= $selectedDepartmentType === 'clinical' ? 'selected' : '' ?>>clinical</option>
                            <option value="support" <?= $selectedDepartmentType === 'support' ? 'selected' : '' ?>>support</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input name="is_nursing_group" type="checkbox" value="1" <?= (int) ($editingDepartment['is_nursing_group'] ?? 0) === 1 ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        เป็นกลุ่มงานการพยาบาล
                    </label>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="flex-1 rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800"><?= $editingDepartment ? 'บันทึกการแก้ไข' : 'บันทึกหน่วยงาน' ?></button>
                        <?php if ($editingDepartment): ?>
                            <a href="<?= e(base_url('admin/master_data.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">ยกเลิก</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900"><?= $editingCategory ? 'แก้ไขประเภทความเสี่ยง' : 'เพิ่มประเภทความเสี่ยงของทีมนำ' ?></h2>
                <p class="mt-1 text-sm text-slate-500">จัดหมวดความเสี่ยงแบบ parent-child ให้แต่ละทีมนำเลือกใช้ในขั้นพิจารณาเคส</p>
                <form action="<?= e(base_url($editingCategory ? 'actions/admin_update_team_category.php' : 'actions/admin_save_team_category.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <?php if ($editingCategory): ?>
                        <input type="hidden" name="category_id" value="<?= e((string) $editingCategory['id']) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ทีมนำ</label>
                        <select name="team_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                            <option value="">เลือกทีมนำ</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?= e((string) $team['id']) ?>" <?= (int) ($editingCategory['team_id'] ?? 0) === (int) $team['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Parent Category</label>
                        <select name="parent_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ไม่มี (ระดับบนสุด)</option>
                            <?php $parentOptions = $editingCategory ? $editingCategoryOptions : flatten_category_tree($allTeamCategories); ?>
                            <?php foreach ($parentOptions as $category): ?>
                                <option value="<?= e((string) $category['id']) ?>" <?= (int) ($editingCategory['parent_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $category['team_code']) ?> - <?= e(category_option_label($category)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">รหัสประเภท</label>
                        <input name="category_code" type="text" value="<?= e((string) ($editingCategory['category_code'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อประเภท</label>
                        <input name="category_name" type="text" value="<?= e((string) ($editingCategory['category_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ลำดับการแสดงผล</label>
                        <input name="sort_order" type="number" min="1" value="<?= e((string) ($editingCategory['sort_order'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="flex-1 rounded-xl bg-amber-500 px-4 py-3 font-semibold text-slate-900 transition hover:bg-amber-400"><?= $editingCategory ? 'บันทึกการแก้ไข' : 'บันทึกประเภทความเสี่ยง' ?></button>
                        <?php if ($editingCategory): ?>
                            <a href="<?= e(base_url('admin/master_data.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">ยกเลิก</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">ทีมนำในระบบ</h2>
                        <p class="mt-1 text-sm text-slate-500">ค้นหา เปิดแก้ไข และเปิดหรือปิดใช้งานข้อมูลทีมนำ</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"><?= e((string) count($filteredTeams)) ?> รายการ</span>
                </div>
                <form method="get" class="mt-4 flex gap-3">
                    <input type="text" name="team_q" value="<?= e($teamSearch) ?>" placeholder="ค้นหาทีมนำ" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                    <input type="hidden" name="department_q" value="<?= e($departmentSearch) ?>">
                    <input type="hidden" name="category_q" value="<?= e($categorySearch) ?>">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">ค้นหา</button>
                </form>
                <div class="mt-4 space-y-3">
                    <?php foreach ($filteredTeams as $team): ?>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900"><?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?></div>
                                    <?php if (!empty($team['description'])): ?>
                                        <div class="mt-1 text-sm text-slate-500"><?= e((string) $team['description']) ?></div>
                                    <?php endif; ?>
                                    <div class="mt-2 text-xs text-slate-500"><?= (int) $team['is_active'] === 1 ? 'ใช้งาน' : 'ปิดใช้งาน' ?></div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?= e(base_url('admin/master_data.php?edit_type=team&edit_id=' . $team['id'])) ?>" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">แก้ไข</a>
                                    <form action="<?= e(base_url('actions/admin_toggle_team_status.php')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="team_id" value="<?= e((string) $team['id']) ?>">
                                        <input type="hidden" name="current_status" value="<?= e((string) $team['is_active']) ?>">
                                        <button type="submit" class="rounded-lg <?= (int) $team['is_active'] === 1 ? 'bg-rose-600' : 'bg-emerald-600' ?> px-3 py-2 text-xs font-semibold text-white">
                                            <?= (int) $team['is_active'] === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($filteredTeams === []): ?>
                        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">ไม่พบข้อมูลทีมนำตามคำค้น</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">หน่วยงานในระบบ</h2>
                        <p class="mt-1 text-sm text-slate-500">ใช้ดูแลหน่วยงานที่อ้างอิงในรายงานและ workflow</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"><?= e((string) count($filteredDepartments)) ?> รายการ</span>
                </div>
                <form method="get" class="mt-4 flex gap-3">
                    <input type="hidden" name="team_q" value="<?= e($teamSearch) ?>">
                    <input type="text" name="department_q" value="<?= e($departmentSearch) ?>" placeholder="ค้นหาหน่วยงาน" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                    <input type="hidden" name="category_q" value="<?= e($categorySearch) ?>">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">ค้นหา</button>
                </form>
                <div class="mt-4 space-y-3">
                    <?php foreach ($filteredDepartments as $department): ?>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900"><?= e((string) $department['department_name']) ?></div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        <?= e((string) ($department['department_code'] ?: '-')) ?>
                                        | <?= e((string) ($department['department_type'] ?: 'general')) ?>
                                        <?php if ((int) ($department['is_nursing_group'] ?? 0) === 1): ?>
                                            | กลุ่มงานการพยาบาล
                                        <?php endif; ?>
                                        | <?= (int) $department['is_active'] === 1 ? 'ใช้งาน' : 'ปิดใช้งาน' ?>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?= e(base_url('admin/master_data.php?edit_type=department&edit_id=' . $department['id'])) ?>" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">แก้ไข</a>
                                    <form action="<?= e(base_url('actions/admin_toggle_department_status.php')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="department_id" value="<?= e((string) $department['id']) ?>">
                                        <input type="hidden" name="current_status" value="<?= e((string) $department['is_active']) ?>">
                                        <button type="submit" class="rounded-lg <?= (int) $department['is_active'] === 1 ? 'bg-rose-600' : 'bg-emerald-600' ?> px-3 py-2 text-xs font-semibold text-white">
                                            <?= (int) $department['is_active'] === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($filteredDepartments === []): ?>
                        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">ไม่พบข้อมูลหน่วยงานตามคำค้น</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">ประเภทความเสี่ยงของทีมนำ</h2>
                        <p class="mt-1 text-sm text-slate-500">ค้นหา ตรวจ parent-child และจัดการสถานะหมวดของแต่ละทีม</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"><?= e((string) count($filteredCategoryTree)) ?> รายการ</span>
                </div>
                <form method="get" class="mt-4 flex gap-3">
                    <input type="hidden" name="team_q" value="<?= e($teamSearch) ?>">
                    <input type="hidden" name="department_q" value="<?= e($departmentSearch) ?>">
                    <input type="text" name="category_q" value="<?= e($categorySearch) ?>" placeholder="ค้นหา category" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">ค้นหา</button>
                </form>
                <div class="mt-4 space-y-3">
                    <?php foreach ($filteredCategoryTree as $category): ?>
                        <?php
                        $parentLabel = '-';
                        foreach ($allTeamCategories as $parentCategory) {
                            if ((int) $parentCategory['id'] === (int) ($category['parent_id'] ?? 0)) {
                                $parentLabel = (string) $parentCategory['team_code'] . ' - ' . trim((string) (($parentCategory['category_code'] ? $parentCategory['category_code'] . ' - ' : '') . $parentCategory['category_name']));
                                break;
                            }
                        }
                        ?>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900"><?= e((string) $category['team_code']) ?> - <?= e(category_option_label($category)) ?></div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        <?= e((string) ($category['category_code'] ?: '-')) ?>
                                        | ลำดับ <?= e((string) ($category['sort_order'] ?: '-')) ?>
                                        | parent <?= e($parentLabel) ?>
                                        | <?= (int) $category['is_active'] === 1 ? 'ใช้งาน' : 'ปิดใช้งาน' ?>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?= e(base_url('admin/master_data.php?edit_type=category&edit_id=' . $category['id'])) ?>" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">แก้ไข</a>
                                    <form action="<?= e(base_url('actions/admin_toggle_team_category_status.php')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="category_id" value="<?= e((string) $category['id']) ?>">
                                        <input type="hidden" name="current_status" value="<?= e((string) $category['is_active']) ?>">
                                        <button type="submit" class="rounded-lg <?= (int) $category['is_active'] === 1 ? 'bg-rose-600' : 'bg-emerald-600' ?> px-3 py-2 text-xs font-semibold text-white">
                                            <?= (int) $category['is_active'] === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($filteredCategoryTree === []): ?>
                        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">ไม่พบข้อมูล category ตามคำค้น</div>
                    <?php endif; ?>
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
