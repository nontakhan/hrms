<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'ระบบรายงานความเสี่ยง รพ.เทพา';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');

require __DIR__ . '/partials/layout_top.php';
?>
<main class="mx-auto flex min-h-screen max-w-7xl items-center px-6 py-10">
    <div class="grid w-full gap-8 lg:grid-cols-[1.15fr_0.85fr]">
        <section class="rounded-[2rem] bg-white/80 p-8 shadow-soft backdrop-blur lg:p-12">
            <div class="mb-8 inline-flex items-center gap-3 rounded-full bg-brand-50 px-4 py-2 text-sm font-medium text-brand-700">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                Risk Management System
            </div>
            <h1 class="max-w-3xl text-4xl font-bold leading-tight text-slate-900 lg:text-5xl">
                ระบบรายงานความเสี่ยง
                <span class="block text-brand-700">โรงพยาบาลเทพา</span>
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                ใช้สำหรับรายงาน ติดตาม ส่งต่อ และสรุปความเสี่ยงของโรงพยาบาลอย่างเป็นระบบ
                รองรับผู้รายงานทั่วไป, admin ระบบ, ทีมนำ, หัวหน้ากลุ่มงาน/หัวหน้างาน และผู้บริหาร
            </p>

            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <div class="text-sm text-slate-500">ผู้รายงาน</div>
                    <div class="mt-2 text-xl font-semibold text-slate-900">ไม่ต้อง Login</div>
                    <div class="mt-2 text-sm text-slate-600">เข้าใช้ด้วย password กลางก่อนส่งรายงาน</div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <div class="text-sm text-slate-500">ทีมนำ</div>
                    <div class="mt-2 text-xl font-semibold text-slate-900">จัดหมวดได้เอง</div>
                    <div class="mt-2 text-sm text-slate-600">รองรับประเภทความเสี่ยงแบบยืดหยุ่นตามทีม</div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <div class="text-sm text-slate-500">ผู้บริหาร</div>
                    <div class="mt-2 text-xl font-semibold text-slate-900">ดูภาพรวม</div>
                    <div class="mt-2 text-sm text-slate-600">ติดตามรายงานและ dashboard แบบ read-only</div>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] bg-slate-900 p-8 text-white shadow-soft lg:p-10">
            <div class="mb-6">
                <h2 class="text-2xl font-bold">เริ่มใช้งาน</h2>
                <p class="mt-2 text-sm leading-7 text-slate-300">
                    เลือกเส้นทางใช้งานตามบทบาทของคุณ
                </p>
            </div>

            <div class="space-y-4">
                <a href="<?= e(base_url('public/report_access.php')) ?>" class="block rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10">
                    <div class="text-sm text-accent">สำหรับผู้รายงาน</div>
                    <div class="mt-1 text-lg font-semibold">รายงานความเสี่ยง</div>
                </a>
                <a href="<?= e(base_url('login.php')) ?>" class="block rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10">
                    <div class="text-sm text-accent">สำหรับเจ้าหน้าที่</div>
                    <div class="mt-1 text-lg font-semibold">เข้าสู่ระบบ</div>
                </a>
                <a href="<?= e(base_url('ROADMAP.md')) ?>" class="block rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10">
                    <div class="text-sm text-accent">สำหรับติดตามงาน</div>
                    <div class="mt-1 text-lg font-semibold">ดู Roadmap โครงการ</div>
                </a>
            </div>
        </section>
    </div>
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

<?php require __DIR__ . '/partials/layout_bottom.php'; ?>
