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
    <section class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Internal Dashboard</div>
                <h1 class="text-3xl font-bold text-slate-900">ยินดีต้อนรับ <?= e((string) ($user['full_name'] ?? '')) ?></h1>
                <p class="mt-2 text-slate-600">บทบาท: <?= e((string) ($user['role_name'] ?? '')) ?></p>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">หน้าตั้งต้นนี้รวบทางลัดและสรุปสถานะพื้นฐานของระบบ เพื่อให้แต่ละบทบาทเข้าถึงงานหลัก เอกสารตั้งค่า และเครื่องมือสำคัญได้เร็วขึ้น</p>
            </div>
            <form action="<?= e(base_url('actions/logout_action.php')) ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">ออกจากระบบ</button>
            </form>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="text-sm text-amber-700">รอรับเรื่อง</div>
                <div class="mt-2 text-3xl font-bold text-amber-900"><?= e((string) $stats['pending']) ?></div>
            </div>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
                <div class="text-sm text-violet-700">กำลังดำเนินการ</div>
                <div class="mt-2 text-3xl font-bold text-violet-900"><?= e((string) $stats['in_progress']) ?></div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <div class="text-sm text-emerald-700">เสร็จสิ้น</div>
                <div class="mt-2 text-3xl font-bold text-emerald-900"><?= e((string) $stats['completed']) ?></div>
            </div>
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5">
                <div class="text-sm text-sky-700">รายงานวันนี้</div>
                <div class="mt-2 text-3xl font-bold text-sky-900"><?= e((string) $stats['today']) ?></div>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">ทางลัดการทำงาน</h2>
                <p class="mt-1 text-sm text-slate-500">แสดงเฉพาะเมนูที่เหมาะกับสิทธิ์ของคุณ เพื่อให้ไปยังงานหลักได้เร็วที่สุด</p>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <?php if (Auth::hasRole('ADMIN')): ?>
                        <a href="<?= e(base_url('admin/reports.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">จัดการรายงานความเสี่ยง</div>
                            <div class="mt-1 text-sm text-slate-500">รับเรื่อง ตรวจสอบ และส่งต่อทีมนำ</div>
                        </a>
                        <a href="<?= e(base_url('admin/master_data.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">จัดการข้อมูลพื้นฐาน</div>
                            <div class="mt-1 text-sm text-slate-500">ทีมนำ หน่วยงาน และประเภทความเสี่ยง</div>
                        </a>
                        <a href="<?= e(base_url('admin/users.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">จัดการผู้ใช้</div>
                            <div class="mt-1 text-sm text-slate-500">เพิ่ม แก้ไข ปิดใช้งาน และรีเซ็ตรหัสผ่าน</div>
                        </a>
                        <a href="<?= e(base_url('admin/settings_workflow.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">Workflow Settings</div>
                            <div class="mt-1 text-sm text-slate-500">ปีงบ เลขรัน และสิทธิ์มองเห็น</div>
                        </a>
                        <a href="<?= e(base_url('admin/settings_public_access.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">ตั้งค่า password กลาง</div>
                            <div class="mt-1 text-sm text-slate-500">ควบคุมการเข้าหน้ารายงานสาธารณะ</div>
                        </a>
                        <a href="<?= e(base_url('admin/workflow_history.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">Workflow History และ Audit</div>
                            <div class="mt-1 text-sm text-slate-500">ดูประวัติการตั้งค่าและกิจกรรมสำคัญ</div>
                        </a>
                    <?php endif; ?>

                    <?php if (Auth::hasRole('TEAM_LEAD')): ?>
                        <a href="<?= e(base_url('team/reports.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">งานของทีมนำ</div>
                            <div class="mt-1 text-sm text-slate-500">รับงาน พิจารณา และส่งต่อหรือส่งกลับ</div>
                        </a>
                        <a href="<?= e(base_url('team/categories.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">จัดการประเภทความเสี่ยง</div>
                            <div class="mt-1 text-sm text-slate-500">เพิ่ม แก้ไข และจัดลำดับหมวดของทีม</div>
                        </a>
                    <?php endif; ?>

                    <?php if (Auth::hasRole('DEPARTMENT_HEAD')): ?>
                        <a href="<?= e(base_url('head/reports.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">งานของหัวหน้ากลุ่มงาน/หัวหน้างาน</div>
                            <div class="mt-1 text-sm text-slate-500">บันทึกแนวทางแก้ไขและส่งคืนทีมนำ</div>
                        </a>
                    <?php endif; ?>

                    <?php if (Auth::hasRole('DIRECTOR')): ?>
                        <a href="<?= e(base_url('director/dashboard.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">Dashboard ผอ.</div>
                            <div class="mt-1 text-sm text-slate-500">ดูภาพรวม สถิติ และ drill-down แบบ read-only</div>
                        </a>
                        <a href="<?= e(base_url('director/reports.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                            <div class="font-semibold text-slate-900">รายการรายงานแบบอ่านอย่างเดียว</div>
                            <div class="mt-1 text-sm text-slate-500">ค้นหาและเปิดดูเคสโดยไม่แก้ไขข้อมูล</div>
                        </a>
                    <?php endif; ?>

                    <a href="<?= e(base_url('public/report_access.php')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                        <div class="font-semibold text-slate-900">ทดสอบหน้ารายงานสาธารณะ</div>
                        <div class="mt-1 text-sm text-slate-500">เข้า flow ผู้รายงานที่ใช้ password กลาง</div>
                    </a>
                    <a href="<?= e(base_url('DEPLOYMENT_CHECKLIST.md')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                        <div class="font-semibold text-slate-900">Deployment Checklist</div>
                        <div class="mt-1 text-sm text-slate-500">ตรวจความพร้อมก่อนขึ้นใช้งานจริง</div>
                    </a>
                    <a href="<?= e(base_url('SETUP_GUIDE.md')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                        <div class="font-semibold text-slate-900">Setup Guide</div>
                        <div class="mt-1 text-sm text-slate-500">คู่มือติดตั้งและตั้งค่าระบบ</div>
                    </a>
                    <a href="<?= e(base_url('ROADMAP.md')) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                        <div class="font-semibold text-slate-900">Roadmap โครงการ</div>
                        <div class="mt-1 text-sm text-slate-500">ดูความคืบหน้าและรายการงานที่ทำแล้ว</div>
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-6">
                <h2 class="text-lg font-semibold text-slate-900">ภาพรวมการใช้งาน</h2>
                <p class="mt-3 leading-7 text-slate-600">Dashboard นี้ออกแบบให้เป็นจุดเริ่มต้นร่วมของทุกบทบาทในระบบ โดยเน้น 2 อย่างคือ เห็นสถานะภาพรวมทันที และเข้าถึงงานหลักตามสิทธิ์ได้โดยไม่ต้องไล่หาเมนูหลายชั้น</p>
                <div class="mt-6 space-y-3">
                    <div class="rounded-2xl bg-white px-5 py-4">
                        <div class="font-semibold text-slate-900">เริ่มจากการ์ดสรุปด้านบน</div>
                        <div class="mt-1 text-sm text-slate-500">ใช้ดูว่าตอนนี้เคสค้าง เคสที่กำลังดำเนินการ และเคสที่เสร็จมีจำนวนประมาณเท่าไร</div>
                    </div>
                    <div class="rounded-2xl bg-white px-5 py-4">
                        <div class="font-semibold text-slate-900">ใช้ทางลัดตามบทบาท</div>
                        <div class="mt-1 text-sm text-slate-500">ระบบจะแสดงเฉพาะเมนูที่สัมพันธ์กับบทบาทของผู้ใช้เพื่อลดความสับสน</div>
                    </div>
                    <div class="rounded-2xl bg-white px-5 py-4">
                        <div class="font-semibold text-slate-900">ตรวจเอกสารก่อนขึ้นระบบจริง</div>
                        <div class="mt-1 text-sm text-slate-500">สามารถเข้าถึงคู่มือ setup, deployment และ roadmap ได้จากหน้าเดียว</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/layout_bottom.php'; ?>
