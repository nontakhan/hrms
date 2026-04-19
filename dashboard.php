<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

Auth::requireLogin();

$user = Auth::user();
$pageTitle = 'Dashboard';
$stats = [
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'today' => 0,
];

try {
    $pdo = Database::connection();

    $statusStmt = $pdo->query(
        "SELECT status, COUNT(*) AS total
         FROM incident_reports
         GROUP BY status"
    );

    foreach ($statusStmt->fetchAll() as $row) {
        $status = (string) $row['status'];
        $total = (int) $row['total'];

        if (array_key_exists($status, $stats)) {
            $stats[$status] = $total;
        }
    }

    $todayStmt = $pdo->query(
        "SELECT COUNT(*) FROM incident_reports WHERE DATE(reported_at) = CURDATE()"
    );
    $stats['today'] = (int) $todayStmt->fetchColumn();
} catch (Throwable) {
    // Keep defaults if database is not fully ready.
}

require __DIR__ . '/partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Internal Dashboard</div>
                <h1 class="text-3xl font-bold text-slate-900">ยินดีต้อนรับ <?= e((string) ($user['full_name'] ?? '')) ?></h1>
                <p class="mt-2 text-slate-600">บทบาท: <?= e((string) ($user['role_name'] ?? '')) ?></p>
            </div>
            <form action="<?= e(base_url('actions/logout_action.php')) ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                    ออกจากระบบ
                </button>
            </form>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">รอรับเรื่อง</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['pending']) ?></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">กำลังดำเนินการ</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['in_progress']) ?></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">เสร็จสิ้น</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['completed']) ?></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">รายงานวันนี้</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['today']) ?></div>
            </div>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">ทางลัดการทำงาน</h2>
                <div class="mt-4 grid gap-3">
                    <?php if (Auth::hasRole('ADMIN')): ?>
                        <a href="<?= e(base_url('admin/settings_public_access.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">ตั้งค่า password กลาง</a>
                        <a href="<?= e(base_url('admin/settings_workflow.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">ตั้งค่าปีงบประมาณและ workflow</a>
                        <a href="<?= e(base_url('admin/workflow_history.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">Workflow History และ Audit</a>
                        <a href="<?= e(base_url('admin/reports.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">จัดการรายงานความเสี่ยง</a>
                        <a href="<?= e(base_url('admin/master_data.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">จัดการข้อมูลพื้นฐาน</a>
                        <a href="<?= e(base_url('admin/users.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">จัดการผู้ใช้ระบบ</a>
                    <?php endif; ?>

                    <?php if (Auth::hasRole('TEAM_LEAD')): ?>
                        <a href="<?= e(base_url('team/reports.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">งานของทีมนำ</a>
                        <a href="<?= e(base_url('team/categories.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">จัดการประเภทความเสี่ยงของทีมนำ</a>
                    <?php endif; ?>

                    <?php if (Auth::hasRole('DEPARTMENT_HEAD')): ?>
                        <a href="<?= e(base_url('head/reports.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">งานของหัวหน้ากลุ่มงาน/หัวหน้างาน</a>
                    <?php endif; ?>

                    <?php if (Auth::hasRole('DIRECTOR')): ?>
                        <a href="<?= e(base_url('director/dashboard.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">Dashboard ผอ.</a>
                        <a href="<?= e(base_url('director/reports.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">รายการรายงานแบบอ่านอย่างเดียว</a>
                    <?php endif; ?>

                    <a href="<?= e(base_url('public/report_access.php')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">ทดสอบหน้ารายงานสาธารณะ</a>
                    <a href="<?= e(base_url('ROADMAP.md')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">ดู Roadmap โครงการ</a>
                    <a href="<?= e(base_url('DEPLOYMENT_CHECKLIST.md')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">Deployment Checklist</a>
                    <a href="<?= e(base_url('SETUP_GUIDE.md')) ?>" class="rounded-xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">Setup Guide</a>
                </div>
            </div>

            <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-slate-600">
                หน้านี้เป็น dashboard เริ่มต้นของระบบ ใช้รวมทางลัดตามสิทธิ์ของผู้ใช้แต่ละบทบาท เพื่อให้เข้าถึงงานหลัก เอกสารตั้งค่าระบบ และข้อมูลสำหรับเตรียมขึ้นใช้งานจริงได้เร็วขึ้น
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/layout_bottom.php'; ?>
