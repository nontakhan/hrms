<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'เข้าสู่ระบบ';
$flashError = flash_get('error');

require __DIR__ . '/partials/layout_top.php';
?>
<main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-10">
    <section class="grid w-full max-w-5xl gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="rounded-[2rem] bg-gradient-to-br from-slate-900 via-slate-800 to-brand-700 p-8 text-white shadow-soft">
            <div class="inline-flex rounded-full bg-white/10 px-3 py-1 text-sm font-medium backdrop-blur">Staff Login</div>
            <h1 class="mt-5 text-3xl font-bold tracking-tight">เข้าสู่ระบบสำหรับเจ้าหน้าที่</h1>
            <p class="mt-4 text-sm leading-7 text-white/85">
                สำหรับผู้ใช้งานภายในระบบ เช่น ผู้ดูแลระบบ ทีมนำ หัวหน้ากลุ่มงาน หัวหน้างาน และผู้อำนวยการ ใช้เข้าสู่ระบบเพื่อติดตามและบริหารจัดการรายงานความเสี่ยง
            </p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl bg-white/10 p-5 backdrop-blur">
                    <div class="text-sm font-semibold">รองรับหลายบทบาท</div>
                    <div class="mt-2 text-sm leading-7 text-white/85">
                        ระบบจะแสดงเมนูและสิทธิ์ตามบทบาทของผู้ใช้งานโดยอัตโนมัติหลังเข้าสู่ระบบ
                    </div>
                </article>
                <article class="rounded-2xl bg-white/10 p-5 backdrop-blur">
                    <div class="text-sm font-semibold">ติดตามเคสได้ครบ</div>
                    <div class="mt-2 text-sm leading-7 text-white/85">
                        สามารถดูประวัติการส่งต่อ การเปลี่ยนระดับความรุนแรง และภาพรวมรายงานได้จากหน้า dashboard ของแต่ละบทบาท
                    </div>
                </article>
            </div>
        </div>

        <div class="rounded-[2rem] bg-white p-8 shadow-soft">
            <div class="mb-6">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">กรอกชื่อผู้ใช้และรหัสผ่าน</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                    หากเข้าสู่ระบบสำเร็จ ระบบจะพาไปยังหน้า dashboard ตามสิทธิ์ของบัญชีผู้ใช้งาน
                </p>
            </div>

            <form action="<?= e(base_url('actions/login_action.php')) ?>" method="post" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label for="username" class="mb-2 block text-sm font-medium text-slate-700">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="<?= e((string) old('username')) ?>"
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                        placeholder="กรอกชื่อผู้ใช้"
                        required
                    >
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                        placeholder="กรอกรหัสผ่าน"
                        required
                    >
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                    เข้าสู่ระบบ
                </button>
            </form>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-7 text-slate-600">
                หากไม่สามารถเข้าสู่ระบบได้ กรุณาติดต่อผู้ดูแลระบบเพื่อขอรับบัญชีผู้ใช้หรือรีเซ็ตรหัสผ่าน
            </div>
        </div>
    </section>
</main>

<?php if ($flashError): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'เข้าสู่ระบบไม่สำเร็จ',
        text: <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>
    });
</script>
<?php endif; ?>

<?php require __DIR__ . '/partials/layout_bottom.php'; ?>
