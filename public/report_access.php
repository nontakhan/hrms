<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (Auth::canAccessPublicReport()) {
    redirect('/public/report_create.php');
}

$pageTitle = 'ยืนยันรหัสก่อนรายงาน';
$flashError = flash_get('error');

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-10">
    <section class="w-full max-w-xl rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="mb-6">
            <div class="mb-3 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Public Report Access</div>
            <h1 class="text-3xl font-bold text-slate-900">ยืนยันสิทธิ์ก่อนรายงานความเสี่ยง</h1>
            <p class="mt-2 text-slate-600">
                กรุณากรอกรหัสผ่านกลางที่ได้รับจากผู้ดูแลระบบ เพื่อเข้าใช้งานหน้ารายงานความเสี่ยง
            </p>
        </div>

        <form action="<?= e(base_url('actions/public_access_action.php')) ?>" method="post" class="space-y-5">
            <?= csrf_field() ?>
            <div>
                <label for="access_password" class="mb-2 block text-sm font-medium text-slate-700">รหัสผ่านกลาง</label>
                <input id="access_password" name="access_password" type="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                ยืนยันและไปยังหน้ารายงาน
            </button>
        </form>
    </section>
</main>

<?php if ($flashError): ?>
<script>
    Swal.fire({icon: 'error', title: 'ไม่สามารถเข้าใช้งานได้', text: <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
