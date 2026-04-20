<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? app_config('app_name');
$authUser = Auth::user();
$roleCode = $authUser['role_code'] ?? null;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$navItems = [];

if ($roleCode === 'ADMIN') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'รายงาน', 'href' => base_url('admin/reports.php')],
        ['label' => 'ผู้ใช้ระบบ', 'href' => base_url('admin/users.php')],
        ['label' => 'ข้อมูลพื้นฐาน', 'href' => base_url('admin/master_data.php')],
        ['label' => 'ตั้งค่า Workflow', 'href' => base_url('admin/settings_workflow.php')],
        ['label' => 'ประวัติ Workflow', 'href' => base_url('admin/workflow_history.php')],
        ['label' => 'ตรวจระบบ', 'href' => base_url('admin/system_check.php')],
        ['label' => 'รหัสผ่านกลาง', 'href' => base_url('admin/settings_public_access.php')],
    ];
} elseif ($roleCode === 'TEAM_LEAD') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'คิวงานทีมนำ', 'href' => base_url('team/reports.php')],
        ['label' => 'หมวดความเสี่ยง', 'href' => base_url('team/categories.php')],
    ];
} elseif ($roleCode === 'DEPARTMENT_HEAD') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'คิวงานหัวหน้า', 'href' => base_url('head/reports.php')],
    ];
} elseif ($roleCode === 'DIRECTOR') {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
        ['label' => 'ภาพรวมผู้อำนวยการ', 'href' => base_url('director/dashboard.php')],
        ['label' => 'รายงานทั้งหมด', 'href' => base_url('director/reports.php')],
    ];
} elseif ($authUser) {
    $navItems = [
        ['label' => 'Dashboard', 'href' => base_url('dashboard.php')],
    ];
}

function nav_is_active(string $currentPath, string $href): bool
{
    $targetPath = parse_url($href, PHP_URL_PATH) ?: '';
    if ($targetPath === '') {
        return false;
    }

    if ($currentPath === $targetPath) {
        return true;
    }

    return $targetPath !== '/' && str_starts_with($currentPath, rtrim($targetPath, '/')) && $targetPath !== base_url('dashboard.php');
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html,
        body,
        button,
        input,
        select,
        textarea,
        table,
        .dataTables_wrapper {
            font-family: 'Sarabun', sans-serif;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #fff;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Sarabun', 'sans-serif'],
                    },
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
        <header class="sticky top-0 z-40 border-b border-white/70 bg-white/85 backdrop-blur">
            <div class="mx-auto max-w-7xl px-6 py-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-lg font-bold tracking-tight text-slate-900">
                            <?= e((string) app_config('app_name', 'HRMS2')) ?>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span><?= e((string) ($authUser['full_name'] ?? '')) ?></span>
                            <?php if (!empty($authUser['role_name'])): ?>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    <?= e((string) $authUser['role_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 xl:items-end">
                        <nav class="flex flex-wrap gap-2">
                            <?php foreach ($navItems as $item): ?>
                                <?php $active = nav_is_active($currentPath, (string) $item['href']); ?>
                                <a
                                    href="<?= e((string) $item['href']) ?>"
                                    class="<?= $active
                                        ? 'rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm'
                                        : 'rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200' ?>"
                                >
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
            </div>
        </header>
    <?php endif; ?>
