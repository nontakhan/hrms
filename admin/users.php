<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'จัดการผู้ใช้ระบบ';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$roles = fetch_roles();
$departments = fetch_all_departments();
$teams = fetch_all_teams();
$users = [];
$editingUser = null;
$editingUserId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

try {
    $stmt = Database::connection()->query(
        "SELECT
            u.id,
            u.username,
            u.full_name,
            u.head_level,
            u.is_active,
            u.last_login_at,
            r.role_name,
            r.role_code,
            d.department_name,
            t.team_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.department_id
         LEFT JOIN teams t ON t.id = u.team_id
         ORDER BY u.id DESC"
    );
    $users = $stmt->fetchAll();

    if ($editingUserId > 0) {
        $editStmt = Database::connection()->prepare(
            "SELECT
                u.id,
                u.username,
                u.full_name,
                u.role_id,
                u.department_id,
                u.team_id,
                u.head_level,
                u.is_active,
                r.role_code
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1"
        );
        $editStmt->execute(['id' => $editingUserId]);
        $editingUser = $editStmt->fetch() ?: null;
    }
} catch (Throwable) {
    $users = [];
    $editingUser = null;
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Users</div>
                <h1 class="text-3xl font-bold text-slate-900">จัดการผู้ใช้ระบบ</h1>
                <p class="mt-2 text-slate-600">เพิ่ม แก้ไข ปิดใช้งาน และรีเซ็ตรหัสผ่านผู้ใช้จากหน้าจอเดียว เพื่อให้สิทธิ์แต่ละ role พร้อมใช้งานจริง</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?= e(base_url('admin/master_data.php')) ?>" class="rounded-xl bg-slate-900 px-4 py-2 font-medium text-white transition hover:bg-slate-800">
                    ไปหน้า Master Data
                </a>
                <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    กลับ Dashboard
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900"><?= $editingUser ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' ?></h2>
                <form action="<?= e(base_url($editingUser ? 'actions/admin_update_user.php' : 'actions/admin_save_user.php')) ?>" method="post" class="mt-4 space-y-4">
                    <?= csrf_field() ?>
                    <?php if ($editingUser): ?>
                        <input type="hidden" name="user_id" value="<?= e((string) $editingUser['id']) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อผู้ใช้</label>
                        <input name="username" type="text" value="<?= e((string) ($editingUser['username'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อ-สกุล</label>
                        <input name="full_name" type="text" value="<?= e((string) ($editingUser['full_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                    </div>
                    <?php if (!$editingUser): ?>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Password เริ่มต้น</label>
                            <input name="password" type="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Role</label>
                        <select id="role_id" name="role_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500" required>
                            <option value="">เลือก role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= e((string) $role['id']) ?>" data-role-code="<?= e((string) $role['role_code']) ?>" <?= (int) ($editingUser['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $role['role_name']) ?> (<?= e((string) $role['role_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">หน่วยงาน</label>
                        <select name="department_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ไม่ระบุ</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= e((string) $department['id']) ?>" <?= (int) ($editingUser['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $department['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="team_wrap" class="hidden">
                        <label class="mb-2 block text-sm font-medium text-slate-700">ทีมนำ</label>
                        <select name="team_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ไม่ระบุ</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?= e((string) $team['id']) ?>" <?= (int) ($editingUser['team_id'] ?? 0) === (int) $team['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $team['team_code']) ?> - <?= e((string) $team['team_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="head_level_wrap" class="hidden">
                        <label class="mb-2 block text-sm font-medium text-slate-700">ระดับหัวหน้า</label>
                        <select name="head_level" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-brand-500">
                            <option value="">ไม่ระบุ</option>
                            <option value="group_head" <?= (($editingUser['head_level'] ?? '') === 'group_head') ? 'selected' : '' ?>>หัวหน้ากลุ่มงาน</option>
                            <option value="unit_head" <?= (($editingUser['head_level'] ?? '') === 'unit_head') ? 'selected' : '' ?>>หัวหน้างาน</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="flex-1 rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                            <?= $editingUser ? 'บันทึกการแก้ไข' : 'บันทึกผู้ใช้' ?>
                        </button>
                        <?php if ($editingUser): ?>
                            <a href="<?= e(base_url('admin/users.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">
                                ยกเลิก
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">รายการผู้ใช้ในระบบ</h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
                    <table id="usersTable" class="display w-full text-sm">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>ชื่อ</th>
                                <th>Role</th>
                                <th>หน่วยงาน</th>
                                <th>ทีมนำ</th>
                                <th>ระดับหัวหน้า</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $row): ?>
                                <tr>
                                    <td><?= e((string) $row['username']) ?></td>
                                    <td><?= e((string) $row['full_name']) ?></td>
                                    <td><?= e((string) $row['role_name']) ?></td>
                                    <td><?= e((string) ($row['department_name'] ?: '-')) ?></td>
                                    <td><?= e((string) ($row['team_name'] ?: '-')) ?></td>
                                    <td><?= e((string) ($row['head_level'] ?: '-')) ?></td>
                                    <td><?= (int) $row['is_active'] === 1 ? 'ใช้งาน' : 'ปิดใช้งาน' ?></td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?= e(base_url('admin/users.php?edit=' . $row['id'])) ?>" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">
                                                แก้ไข
                                            </a>
                                            <?php if ((int) $row['id'] !== (int) (Auth::user()['id'] ?? 0)): ?>
                                                <form action="<?= e(base_url('actions/admin_toggle_user_status.php')) ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= e((string) $row['id']) ?>">
                                                    <input type="hidden" name="current_status" value="<?= e((string) $row['is_active']) ?>">
                                                    <button type="submit" class="rounded-lg <?= (int) $row['is_active'] === 1 ? 'bg-rose-600' : 'bg-emerald-600' ?> px-3 py-2 text-xs font-semibold text-white">
                                                        <?= (int) $row['is_active'] === 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="<?= e(base_url('actions/admin_reset_user_password.php')) ?>" method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= e((string) $row['id']) ?>">
                                                <button type="submit" class="rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-slate-900">
                                                    รีเซ็ต Password
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
        $('#usersTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' },
                zeroRecords: 'ไม่พบข้อมูล'
            }
        });
    });

    const roleSelect = document.getElementById('role_id');
    const teamWrap = document.getElementById('team_wrap');
    const headLevelWrap = document.getElementById('head_level_wrap');

    function syncRoleFields() {
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const roleCode = selectedOption ? selectedOption.dataset.roleCode : '';
        teamWrap.classList.toggle('hidden', roleCode !== 'TEAM_LEAD');
        headLevelWrap.classList.toggle('hidden', roleCode !== 'DEPARTMENT_HEAD');
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', syncRoleFields);
        syncRoleFields();
    }
</script>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
