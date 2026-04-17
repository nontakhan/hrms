<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'เข้าสู่ระบบ';
$flashError = flash_get('error');

require __DIR__ . '/partials/layout_top.php';
?>
<main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-10">
    <section class="w-full max-w-xl rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">เข้าสู่ระบบเจ้าหน้าที่</h1>
            <p class="mt-2 text-slate-600">ส่วนนี้สำหรับ admin, ทีมนำ, หัวหน้ากลุ่มงาน/หัวหน้างาน และผู้อำนวยการ</p>
        </div>

        <form action="<?= e(base_url('actions/login_action.php')) ?>" method="post" class="space-y-5">
            <?= csrf_field() ?>
            <div>
                <label for="username" class="mb-2 block text-sm font-medium text-slate-700">Username</label>
                <input id="username" name="username" type="text" value="<?= e((string) old('username')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                เข้าสู่ระบบ
            </button>
        </form>
    </section>
</main>

<?php if ($flashError): ?>
<script>
    Swal.fire({icon: 'error', title: 'เข้าสู่ระบบไม่สำเร็จ', text: <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>});
</script>
<?php endif; ?>

<?php require __DIR__ . '/partials/layout_bottom.php'; ?>
