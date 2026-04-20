<?php

declare(strict_types=1);

$appConfigPath = __DIR__ . '/config/app.php';
$dbConfigPath = __DIR__ . '/config/database.php';
$schemaPath = __DIR__ . '/risk_management_schema.sql';
$seedPath = __DIR__ . '/risk_management_seed.sql';
$uploadDir = __DIR__ . '/storage/uploads';
$logDir = __DIR__ . '/storage/logs';

$appConfig = is_file($appConfigPath) ? require $appConfigPath : [];
$dbConfig = is_file($dbConfigPath) ? require $dbConfigPath : [];

$checks = [];
$dbStatus = [
    'connected' => false,
    'message' => 'ยังไม่ได้ทดสอบ',
    'schema_ready' => false,
    'active_fiscal_year' => false,
    'user_count' => 0,
    'team_count' => 0,
];

$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'fileinfo'];
foreach ($requiredExtensions as $extension) {
    $loaded = extension_loaded($extension);
    $checks[] = [
        'label' => 'PHP extension: ' . $extension,
        'status' => $loaded ? 'pass' : 'fail',
        'detail' => $loaded ? 'พร้อมใช้งาน' : 'ยังไม่ได้เปิดใช้งาน',
    ];
}

$checks[] = [
    'label' => 'PHP version',
    'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'pass' : 'warn',
    'detail' => 'Current: ' . PHP_VERSION . ' | Recommended: 8.1+',
];

$checks[] = [
    'label' => 'Config: app.php',
    'status' => is_file($appConfigPath) ? 'pass' : 'fail',
    'detail' => is_file($appConfigPath) ? 'พบไฟล์ config/app.php' : 'ไม่พบไฟล์ config/app.php',
];

$checks[] = [
    'label' => 'Config: database.php',
    'status' => is_file($dbConfigPath) ? 'pass' : 'fail',
    'detail' => is_file($dbConfigPath) ? 'พบไฟล์ config/database.php' : 'ไม่พบไฟล์ config/database.php',
];

$checks[] = [
    'label' => 'Schema file',
    'status' => is_file($schemaPath) ? 'pass' : 'fail',
    'detail' => is_file($schemaPath) ? 'พบไฟล์ risk_management_schema.sql' : 'ไม่พบไฟล์ schema',
];

$checks[] = [
    'label' => 'Seed file',
    'status' => is_file($seedPath) ? 'pass' : 'warn',
    'detail' => is_file($seedPath) ? 'พบไฟล์ risk_management_seed.sql' : 'ไม่พบไฟล์ seed',
];

$checks[] = [
    'label' => 'Upload directory',
    'status' => is_dir($uploadDir) && is_writable($uploadDir) ? 'pass' : 'fail',
    'detail' => is_dir($uploadDir) && is_writable($uploadDir) ? 'พร้อมใช้งาน' : 'ไม่มีหรือเขียนไม่ได้',
];

$checks[] = [
    'label' => 'Log directory',
    'status' => is_dir($logDir) && is_writable($logDir) ? 'pass' : 'fail',
    'detail' => is_dir($logDir) && is_writable($logDir) ? 'พร้อมใช้งาน' : 'ไม่มีหรือเขียนไม่ได้',
];

if (is_file($dbConfigPath) && extension_loaded('pdo_mysql')) {
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) ($dbConfig['host'] ?? '127.0.0.1'),
            (int) ($dbConfig['port'] ?? 3306),
            (string) ($dbConfig['dbname'] ?? ''),
            (string) ($dbConfig['charset'] ?? 'utf8mb4')
        );

        $pdo = new PDO(
            $dsn,
            (string) ($dbConfig['username'] ?? ''),
            (string) ($dbConfig['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $dbStatus['connected'] = true;
        $dbStatus['message'] = 'เชื่อมต่อฐานข้อมูลได้';

        $tableStmt = $pdo->query("SHOW TABLES LIKE 'incident_reports'");
        $dbStatus['schema_ready'] = (bool) $tableStmt->fetchColumn();

        if ($dbStatus['schema_ready']) {
            $fyStmt = $pdo->query(
                "SELECT COUNT(*)
                 FROM system_settings
                 WHERE setting_key = 'active_fiscal_year_id'
                   AND COALESCE(setting_value, '') <> ''"
            );
            $dbStatus['active_fiscal_year'] = ((int) $fyStmt->fetchColumn()) > 0;

            $dbStatus['user_count'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $dbStatus['team_count'] = (int) $pdo->query('SELECT COUNT(*) FROM teams')->fetchColumn();
        }
    } catch (Throwable $exception) {
        $dbStatus['message'] = 'เชื่อมต่อฐานข้อมูลไม่ได้: ' . $exception->getMessage();
    }
}

$checks[] = [
    'label' => 'Database connection',
    'status' => $dbStatus['connected'] ? 'pass' : 'fail',
    'detail' => $dbStatus['message'],
];

$checks[] = [
    'label' => 'Database schema',
    'status' => $dbStatus['schema_ready'] ? 'pass' : 'warn',
    'detail' => $dbStatus['schema_ready'] ? 'พบตารางหลักของระบบแล้ว' : 'ยังไม่พบตารางหลัก ให้ import risk_management_schema.sql',
];

$checks[] = [
    'label' => 'Active fiscal year',
    'status' => $dbStatus['active_fiscal_year'] ? 'pass' : 'warn',
    'detail' => $dbStatus['active_fiscal_year'] ? 'ตั้งค่า active fiscal year แล้ว' : 'ยังไม่ได้ตั้งค่า active fiscal year',
];

$checks[] = [
    'label' => 'Users in database',
    'status' => $dbStatus['user_count'] > 0 ? 'pass' : 'warn',
    'detail' => 'จำนวนผู้ใช้: ' . $dbStatus['user_count'],
];

$checks[] = [
    'label' => 'Teams in database',
    'status' => $dbStatus['team_count'] > 0 ? 'pass' : 'warn',
    'detail' => 'จำนวนทีมนำ: ' . $dbStatus['team_count'],
];

$summary = ['pass' => 0, 'warn' => 0, 'fail' => 0];
foreach ($checks as $check) {
    $summary[$check['status']]++;
}

function badge_class(string $status): string
{
    return match ($status) {
        'pass' => 'bg-emerald-100 text-emerald-700',
        'warn' => 'bg-amber-100 text-amber-700',
        default => 'bg-rose-100 text-rose-700',
    };
}

function badge_label(string $status): string
{
    return match ($status) {
        'pass' => 'พร้อม',
        'warn' => 'ควรตรวจ',
        default => 'ต้องแก้',
    };
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install | Tepha Risk Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html,
        body,
        button,
        input,
        select,
        textarea,
        table {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(29,127,95,0.16),_transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef6f3_100%)]">
    <main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
        <section class="rounded-[2rem] bg-white p-8 shadow-xl shadow-slate-200/60">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="mb-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">Server Install</div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">ตัวช่วยตรวจความพร้อมก่อนติดตั้งบน server</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        หน้านี้ใช้ตรวจความพร้อมของเครื่อง server, config, ฐานข้อมูล และไฟล์สำคัญก่อนเปิดใช้งานจริง โดยจะรายงานสถานะอย่างปลอดภัยและไม่แก้ไขข้อมูลอัตโนมัติ
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="./DEPLOYMENT_CHECKLIST.md" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 font-medium text-emerald-700 transition hover:bg-emerald-100">Deployment Checklist</a>
                    <a href="./SETUP_GUIDE.md" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">Setup Guide</a>
                    <a href="./UAT_CHECKLIST.md" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">UAT Checklist</a>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl bg-emerald-50 p-5">
                    <div class="text-sm font-medium text-emerald-700">พร้อม</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-900"><?= htmlspecialchars((string) $summary['pass']) ?></div>
                </article>
                <article class="rounded-2xl bg-amber-50 p-5">
                    <div class="text-sm font-medium text-amber-700">ควรตรวจเพิ่ม</div>
                    <div class="mt-2 text-3xl font-bold text-amber-900"><?= htmlspecialchars((string) $summary['warn']) ?></div>
                </article>
                <article class="rounded-2xl bg-rose-50 p-5">
                    <div class="text-sm font-medium text-rose-700">ต้องแก้ก่อนใช้งาน</div>
                    <div class="mt-2 text-3xl font-bold text-rose-900"><?= htmlspecialchars((string) $summary['fail']) ?></div>
                </article>
            </div>

            <div class="mt-8 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="overflow-hidden rounded-2xl border border-slate-200">
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
                                    <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars((string) $check['label']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= badge_class((string) $check['status']) ?>">
                                            <?= htmlspecialchars(badge_label((string) $check['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) $check['detail']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Config ปัจจุบัน</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-700">
                            <div class="rounded-xl bg-slate-50 p-4">Base URL: <strong><?= htmlspecialchars((string) ($appConfig['base_url'] ?? '-')) ?></strong></div>
                            <div class="rounded-xl bg-slate-50 p-4">DB Host: <strong><?= htmlspecialchars((string) ($dbConfig['host'] ?? '-')) ?></strong></div>
                            <div class="rounded-xl bg-slate-50 p-4">DB Name: <strong><?= htmlspecialchars((string) ($dbConfig['dbname'] ?? '-')) ?></strong></div>
                            <div class="rounded-xl bg-slate-50 p-4">Upload Dir: <strong><?= htmlspecialchars($uploadDir) ?></strong></div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-900">ขั้นตอนติดตั้งที่แนะนำ</h2>
                        <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm leading-7 text-slate-700">
                            <li>แก้ไฟล์ `config/app.php` และ `config/database.php` ให้ตรงกับ server จริง</li>
                            <li>สร้างฐานข้อมูลใหม่ แล้ว import `risk_management_schema.sql`</li>
                            <li>ถ้าต้องการข้อมูลตัวอย่าง ให้ import `risk_management_seed.sql` เพิ่มใน environment ใหม่</li>
                            <li>เปิด `install.php` หน้านี้เพื่อตรวจว่า database, โฟลเดอร์ และค่าพื้นฐานพร้อมแล้ว</li>
                            <li>เข้าสู่ระบบด้วย admin แล้วตั้งค่า password กลาง, ปีงบ, users, teams, departments และ visibility</li>
                            <li>ทดสอบตาม `UAT_CHECKLIST.md` ก่อนเปิดใช้งานจริง</li>
                        </ol>
                    </section>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
