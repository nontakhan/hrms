<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('TEAM_LEAD');

$user = Auth::user();
$teamId = (int) ($user['team_id'] ?? 0);

if ($teamId <= 0) {
    flash_set('error', 'บัญชีนี้ยังไม่ถูกผูกกับทีมนำ');
    redirect('/dashboard.php');
}

$pageTitle = 'จัดการประเภทความเสี่ยงของทีมนำ';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$team = null;
$categories = [];

try {
    $teamStmt = Database::connection()->prepare(
        'SELECT id, team_code, team_name, description
         FROM teams
         WHERE id = :id
         LIMIT 1'
    );
    $teamStmt->execute(['id' => $teamId]);
    $team = $teamStmt->fetch();

    $categories = fetch_team_categories($teamId);
} catch (Throwable) {
    $team = null;
    $categories = [];
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
                <h2 class="text-lg font-semibold text-slate-900">เพิ่มประเภทความเสี่ยง</h2>
                <p class="mt-2 text-sm text-slate-500">ทีมนำสามารถเพิ่มโครงสร้างประเภทหลักและประเภทย่อยของตัวเองได้จากหน้านี้</p>

                <form action="<?= e(base_url('actions/team_save_category.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Parent Category</label>
                        <select name="parent_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ไม่มี (ระดับบนสุด)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e((string) $category['id']) ?>">
                                    <?= e(trim((string) (($category['category_code'] ? $category['category_code'] . ' - ' : '') . $category['category_name']))) ?>
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

                    <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                        บันทึกประเภทความเสี่ยง
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">รายการประเภทความเสี่ยงของทีมนำ</h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
                    <table id="teamCategoriesTable" class="display w-full text-sm">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อประเภท</th>
                                <th>Parent ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= e((string) ($category['category_code'] ?: '-')) ?></td>
                                    <td><?= e((string) $category['category_name']) ?></td>
                                    <td><?= e((string) ($category['parent_id'] ?: '-')) ?></td>
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
            order: [[1, 'asc']],
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
