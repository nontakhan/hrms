<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? app_config('app_name');
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
