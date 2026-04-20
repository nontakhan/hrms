<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'System Check';
$checks = [];

try {
    Database::connection()->query('SELECT 1');
    $checks[] = ['label' => 'Database connection', 'status' => 'pass', 'detail' => 'เชื่อมต่อฐานข้อมูลได้'];
} catch (Throwable $exception) {
    $checks[] = ['label' => 'Database connection', 'status' => 'fail', 'detail' => 'เชื่อมต่อฐานข้อมูลไม่ได้: ' . $exception->getMessage()];
}

$publicPasswordHash = (string) setting('public_report_password_hash', '');
$checks[] = [
    'label' => 'Public report password',
    'status' => $publicPasswordHash !== '' ? 'pass' : 'warn',
    'detail' => $publicPasswordHash !== '' ? 'ตั้งค่ารหัสผ่านกลางแล้ว' : 'ยังไม่ได้ตั้งค่ารหัสผ่านกลาง',
];

$activeFiscalYear = active_fiscal_year();
$checks[] = [
    'label' => 'Active fiscal year',
    'status' => $activeFiscalYear !== null ? 'pass' : 'warn',
    'detail' => $activeFiscalYear !== null ? 'ปีงบที่ใช้งาน: ' . ($activeFiscalYear['year_label'] ?? '-') : 'ยังไม่ได้กำหนดปีงบที่ใช้งาน',
];

$uploadDir = (string) app_config('upload_dir', __DIR__ . '/../storage/uploads');
$checks[] = [
    'label' => 'Upload directory',
    'status' => is_dir($uploadDir) && is_writable($uploadDir) ? 'pass' : 'fail',
    'detail' => is_dir($uploadDir) && is_writable($uploadDir) ? 'โฟลเดอร์อัปโหลดพร้อมใช้งาน' : 'โฟลเดอร์อัปโหลดไม่มีหรือเขียนไม่ได้',
];

$logDir = realpath(__DIR__ . '/../storage/logs') ?: (__DIR__ . '/../storage/logs');
$checks[] = [
    'label' => 'Log directory',
    'status' => is_dir($logDir) && is_writable($logDir) ? 'pass' : 'fail',
    'detail' => is_dir($logDir) && is_writable($logDir) ? 'โฟลเดอร์ log พร้อมใช้งาน' : 'โฟลเดอร์ log ไม่มีหรือเขียนไม่ได้',
];

$counts = [
    'teams' => 0,
    'departments' => 0,
    'users' => 0,
    'director_users' => 0,
    'team_categories' => 0,
];

try {
    $pdo = Database::connection();
    $counts['teams'] = (int) $pdo->query('SELECT COUNT(*) FROM teams WHERE is_active = 1')->fetchColumn();
    $counts['departments'] = (int) $pdo->query('SELECT COUNT(*) FROM departments WHERE is_active = 1')->fetchColumn();
    $counts['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
    $counts['team_categories'] = (int) $pdo->query('SELECT COUNT(*) FROM risk_categories WHERE is_active = 1')->fetchColumn();
    $counts['director_users'] = (int) $pdo->query(
        "SELECT COUNT(*)
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.is_active = 1 AND r.role_code = 'DIRECTOR'"
    )->fetchColumn();
} catch (Throwable) {
    // keep defaults
}

$checks[] = ['label' => 'Active teams', 'status' => $counts['teams'] > 0 ? 'pass' : 'warn', 'detail' => 'ทีมนำที่ใช้งานได้: ' . $counts['teams']];
$checks[] = ['label' => 'Active departments', 'status' => $counts['departments'] > 0 ? 'pass' : 'warn', 'detail' => 'หน่วยงานที่ใช้งานได้: ' . $counts['departments']];
$checks[] = ['label' => 'Active users', 'status' => $counts['users'] > 0 ? 'pass' : 'warn', 'detail' => 'ผู้ใช้ที่ใช้งานได้: ' . $counts['users']];
$checks[] = ['label' => 'Director account', 'status' => $counts['director_users'] > 0 ? 'pass' : 'warn', 'detail' => 'บัญชีผู้อำนวยการที่ใช้งานได้: ' . $counts['director_users']];
$checks[] = ['label' => 'Team categories', 'status' => $counts['team_categories'] > 0 ? 'pass' : 'warn', 'detail' => 'หมวดความเสี่ยงที่ใช้งานได้: ' . $counts['team_categories']];

$summary = ['pass' => 0, 'warn' => 0, 'fail' => 0];
foreach ($checks as $check) {
    $summary[$check['status']]++;
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">System Check</div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">ตรวจความพร้อมก่อนใช้งานจริง</h1>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    ใช้ตรวจค่าพื้นฐานของระบบก่อนเปิดใช้งาน เช่น ฐานข้อมูล ปีงบ รหัสผ่านกลาง โฟลเดอร์จัดเก็บไฟล์ และข้อมูลตั้งต้นที่จำเป็นต่อการทำงานของระบบ
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?= e(base_url('DEPLOYMENT_CHECKLIST.md')) ?>" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 font-medium text-brand-700 transition hover:bg-brand-100">Deployment Checklist</a>
                <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">กลับ Dashboard</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl bg-emerald-50 p-5">
                <div class="text-sm font-medium text-emerald-700">พร้อม</div>
                <div class="mt-2 text-3xl font-bold text-emerald-900"><?= e((string) $summary['pass']) ?></div>
            </article>
            <article class="rounded-2xl bg-amber-50 p-5">
                <div class="text-sm font-medium text-amber-700">ควรตรวจเพิ่ม</div>
                <div class="mt-2 text-3xl font-bold text-amber-900"><?= e((string) $summary['warn']) ?></div>
            </article>
            <article class="rounded-2xl bg-rose-50 p-5">
                <div class="text-sm font-medium text-rose-700">ต้องแก้ก่อนใช้งาน</div>
                <div class="mt-2 text-3xl font-bold text-rose-900"><?= e((string) $summary['fail']) ?></div>
            </article>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">รายการตรวจ</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">สถานะ</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">รายละเอียด</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($checks as $check): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $check['label']) ?></td>
                            <td class="px-4 py-3">
                                <?php
                                $badgeClass = match ($check['status']) {
                                    'pass' => 'bg-emerald-100 text-emerald-700',
                                    'warn' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-rose-100 text-rose-700',
                                };
                                $badgeLabel = match ($check['status']) {
                                    'pass' => 'พร้อม',
                                    'warn' => 'ควรตรวจ',
                                    default => 'ต้องแก้',
                                };
                                ?>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $badgeClass ?>"><?= e($badgeLabel) ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= e((string) $check['detail']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
