<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('TEAM_LEAD');

$user = Auth::user();
$teamId = (int) ($user['team_id'] ?? 0);

if ($teamId <= 0) {
    flash_set('error', 'บัญชีนี้ยังไม่ได้ผูกกับทีมนำ');
    redirect('/dashboard.php');
}

$pageTitle = 'จัดการประเภทความเสี่ยงของทีมนำ';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$team = null;
$categories = [];
$categoryOptions = [];
$editingCategory = null;
$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$categoryTree = [];

try {
    $teamStmt = Database::connection()->prepare(
        'SELECT id, team_code, team_name, description
         FROM teams
         WHERE id = :id
         LIMIT 1'
    );
    $teamStmt->execute(['id' => $teamId]);
    $team = $teamStmt->fetch();

    $categories = fetch_team_categories($teamId, true);
    $categoryOptions = flatten_category_tree($categories, $teamId, $editId);
    $categoryTree = flatten_category_tree($categories, $teamId);

    if ($editId > 0) {
        $editStmt = Database::connection()->prepare(
            'SELECT id, team_id, parent_id, category_name, category_code, sort_order, is_active
             FROM risk_categories
             WHERE id = :id AND team_id = :team_id
             LIMIT 1'
        );
        $editStmt->execute([
            'id' => $editId,
            'team_id' => $teamId,
        ]);
        $editingCategory = $editStmt->fetch() ?: null;
    }
} catch (Throwable) {
    $team = null;
    $categories = [];
    $categoryOptions = [];
    $categoryTree = [];
}

if (!$team) {
    flash_set('error', 'ไม่พบข้อมูลทีมนำของผู้ใช้งาน');
    redirect('/dashboard.php');
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Team Categories</div>
                <h1 class="text-3xl font-bold text-slate-900">จัดการประเภทความเสี่ยงของทีมนำ</h1>
                <p class="mt-2 text-slate-600"><?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?></p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard
            </a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900"><?= $editingCategory ? 'แก้ไขประเภทความเสี่ยง' : 'เพิ่มประเภทความเสี่ยง' ?></h2>
                <p class="mt-2 text-sm text-slate-500">กำหนด parent-child และลำดับการแสดงผลของหมวดความเสี่ยงได้จากหน้าจอนี้</p>

                <form action="<?= e(base_url($editingCategory ? 'actions/team_update_category.php' : 'actions/team_save_category.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <?php if ($editingCategory): ?>
                        <input type="hidden" name="category_id" value="<?= e((string) $editingCategory['id']) ?>">
                    <?php endif; ?>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Parent Category</label>
                        <select name="parent_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ไม่มี (ระดับบนสุด)</option>
                            <?php foreach ($categoryOptions as $category): ?>
                                <option value="<?= e((string) $category['id']) ?>" <?= (int) ($editingCategory['parent_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e(category_option_label($category)) ?>
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
                        <button type="submit" class="flex-1 rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                            <?= $editingCategory ? 'บันทึกการแก้ไข' : 'บันทึกประเภทความเสี่ยง' ?>
                        </button>
                        <?php if ($editingCategory): ?>
                            <a href="<?= e(base_url('team/categories.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">
                                ยกเลิก
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">รายการประเภทความเสี่ยงของทีมนำ</h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
                    <table id="teamCategoriesTable" class="display w-full text-sm">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>รหัส</th>
                                <th>ชื่อประเภท</th>
                                <th>Parent</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryTree as $category): ?>
                                <?php
                                $parentName = '-';
                                foreach ($categories as $parentCategory) {
                                    if ((int) $parentCategory['id'] === (int) ($category['parent_id'] ?? 0)) {
                                        $parentName = trim((string) (($parentCategory['category_code'] ? $parentCategory['category_code'] . ' - ' : '') . $parentCategory['category_name']));
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= e((string) ($category['sort_order'] ?? '-')) ?></td>
                                    <td><?= e((string) ($category['category_code'] ?: '-')) ?></td>
                                    <td><?= e(category_option_label($category)) ?></td>
                                    <td><?= e($parentName) ?></td>
                                    <td><?= (int) ($category['is_active'] ?? 0) === 1 ? 'ใช้งาน' : 'ปิดใช้งาน' ?></td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?= e(base_url('team/categories.php?edit_id=' . $category['id'])) ?>" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">
                                                แก้ไข
                                            </a>
                                            <form action="<?= e(base_url('actions/team_toggle_category_status.php')) ?>" method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="category_id" value="<?= e((string) $category['id']) ?>">
                                                <input type="hidden" name="current_status" value="<?= e((string) $category['is_active']) ?>">
                                                <button type="submit" class="rounded-lg <?= (int) ($category['is_active'] ?? 0) === 1 ? 'bg-rose-600' : 'bg-emerald-600' ?> px-3 py-2 text-xs font-semibold text-white">
                                                    <?= (int) ($category['is_active'] ?? 0) === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
    $(function () {
        $('#teamCategoriesTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc'], [2, 'asc']],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' },
                zeroRecords: 'ไม่พบข้อมูล'
            }
        });
    });
</script>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
