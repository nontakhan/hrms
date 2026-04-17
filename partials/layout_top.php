<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? app_config('app_name');
$authUser = Auth::user();
$roleCode = $authUser['role_code'] ?? null;

$navItems = [];

if ($roleCode === 'ADMIN') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'รายงาน', 'href' => base_url('admin/reports.php')],
        ['label' => 'ผู้ใช้', 'href' => base_url('admin/users.php')],
        ['label' => 'Master Data', 'href' => base_url('admin/master_data.php')],
        ['label' => 'Workflow Settings', 'href' => base_url('admin/settings_workflow.php')],
        ['label' => 'Password กลาง', 'href' => base_url('admin/settings_public_access.php')],
    ];
} elseif ($roleCode === 'TEAM_LEAD') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'คิวงาน', 'href' => base_url('team/reports.php')],
        ['label' => 'ประเภทความเสี่ยง', 'href' => base_url('team/categories.php')],
    ];
} elseif ($roleCode === 'DEPARTMENT_HEAD') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'คิวงานหัวหน้า', 'href' => base_url('head/reports.php')],
    ];
} elseif ($roleCode === 'DIRECTOR') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'ภาพรวม ผอ.', 'href' => base_url('director/dashboard.php')],
    ];
} elseif ($authUser) {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
    ];
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f3faf7',
                            100: '#d8f0e4',
                            500: '#1d7f5f',
                            600: '#16664b',
                            700: '#12513c',
                        },
                        accent: '#f4b942'
                    },
                    boxShadow: {
                        soft: '0 20px 50px rgba(15, 23, 42, 0.10)'
                    }
                }
            }
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(29,127,95,0.16),_transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef6f3_100%)]">
    <?php if ($authUser): ?>
        <header class="border-b border-white/70 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-lg font-bold text-slate-900"><?= e((string) app_config('app_name', 'HRMS2')) ?></div>
                    <div class="text-sm text-slate-500">
                        <?= e((string) ($authUser['full_name'] ?? '')) ?>
                        <?php if (!empty($authUser['role_name'])): ?>
                            | <?= e((string) $authUser['role_name']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:items-end">
                    <nav class="flex flex-wrap gap-2">
                        <?php foreach ($navItems as $item): ?>
                            <a href="<?= e((string) $item['href']) ?>" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                                <?= e((string) $item['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    <form action="<?= e(base_url('actions/logout_action.php')) ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            ออกจากระบบ
                        </button>
                    </form>
                </div>
            </div>
        </header>
    <?php endif; ?>
