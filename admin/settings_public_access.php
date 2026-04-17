<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('ADMIN');

$pageTitle = 'ตั้งค่า Password กลาง';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$currentHashExists = setting('public_report_password_hash', '') !== '';

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-5xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Admin Settings</div>
                <h1 class="text-3xl font-bold text-slate-900">ตั้งค่า Password กลางสำหรับหน้ารายงาน</h1>
                <p class="mt-2 text-slate-600">
                    รหัสนี้ใช้สำหรับยืนยันสิทธิ์ก่อนเข้าใช้งานหน้ารายงานสาธารณะ และจะถูกเก็บแบบ hash ในฐานข้อมูล
                </p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard
            </a>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <form action="<?= e(base_url('actions/update_public_password.php')) ?>" method="post" class="rounded-2xl border border-slate-200 p-6">
                <?= csrf_field() ?>
                <div>
                    <label for="public_password" class="mb-2 block text-sm font-medium text-slate-700">Password กลางใหม่</label>
                    <input id="public_password" name="public_password" type="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required minlength="4">
                </div>
                <div class="mt-5">
                    <label for="public_password_confirm" class="mb-2 block text-sm font-medium text-slate-700">ยืนยัน Password กลาง</label>
                    <input id="public_password_confirm" name="public_password_confirm" type="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required minlength="4">
                </div>
                <button type="submit" class="mt-6 rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">
                    บันทึกการตั้งค่า
                </button>
            </form>

            <div class="rounded-2xl bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-slate-900">สถานะปัจจุบัน</h2>
                <div class="mt-4 rounded-xl bg-white px-4 py-4 shadow-sm">
                    <div class="text-sm text-slate-500">Password กลาง</div>
                    <div class="mt-2 text-xl font-semibold <?= $currentHashExists ? 'text-brand-700' : 'text-amber-600' ?>">
                        <?= $currentHashExists ? 'ตั้งค่าแล้ว' : 'ยังไม่ได้ตั้งค่า' ?>
                    </div>
                </div>
                <div class="mt-4 text-sm leading-7 text-slate-600">
                    เมื่อมีการเปลี่ยน password กลาง ผู้ใช้ที่เข้าใช้งานหน้ารายงานใหม่จะต้องใช้รหัสล่าสุดเสมอ
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

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
