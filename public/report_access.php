<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (Auth::canAccessPublicReport()) {
    redirect('/public/report_create.php');
}

$pageTitle = 'ยืนยันสิทธิ์ก่อนรายงานความเสี่ยง';
$flashError = flash_get('error');

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-10">
    <section class="grid w-full max-w-5xl gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="rounded-[2rem] bg-gradient-to-br from-brand-700 via-brand-600 to-cyan-600 p-8 text-white shadow-soft">
            <div class="inline-flex rounded-full bg-white/15 px-3 py-1 text-sm font-medium backdrop-blur">Public Report Access</div>
            <h1 class="mt-5 text-3xl font-bold tracking-tight">ยืนยันสิทธิ์ก่อนเข้าสู่หน้ารายงานความเสี่ยง</h1>
            <p class="mt-4 text-sm leading-7 text-white/85">
                สำหรับผู้ใช้งานทั่วไปที่ต้องการแจ้งเหตุการณ์ความเสี่ยงของโรงพยาบาล กรุณากรอกรหัสผ่านกลางที่ได้รับจากผู้ดูแลระบบก่อนเข้าใช้งานแบบฟอร์มรายงาน
            </p>

            <div class="mt-8 space-y-4">
                <article class="rounded-2xl bg-white/10 p-5 backdrop-blur">
                    <div class="text-sm font-semibold">สิ่งที่ทำได้หลังผ่านการยืนยัน</div>
                    <div class="mt-2 text-sm leading-7 text-white/85">
                        เปิดหน้าฟอร์มรายงานความเสี่ยง กรอกรายละเอียดเหตุการณ์ เลือกระดับความรุนแรง และแนบไฟล์ประกอบได้ตามสิทธิ์ของผู้รายงานทั่วไป
                    </div>
                </article>
                <article class="rounded-2xl bg-white/10 p-5 backdrop-blur">
                    <div class="text-sm font-semibold">หมายเหตุ</div>
                    <div class="mt-2 text-sm leading-7 text-white/85">
                        ระบบใช้รหัสนี้เพื่อยืนยันการเข้าถึงหน้ารายงานเท่านั้น และจะไม่บันทึกรหัสผ่านกลางลงในข้อมูลรายงาน
                    </div>
                </article>
            </div>
        </div>

        <div class="rounded-[2rem] bg-white p-8 shadow-soft">
            <div class="mb-6">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">กรอกรหัสผ่านกลาง</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                    เมื่อยืนยันสำเร็จ ระบบจะพาไปยังหน้ารายงานความเสี่ยงทันที
                </p>
            </div>

            <form action="<?= e(base_url('actions/public_access_action.php')) ?>" method="post" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label for="access_password" class="mb-2 block text-sm font-medium text-slate-700">รหัสผ่านกลาง</label>
                    <input
                        id="access_password"
                        name="access_password"
                        type="password"
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                        placeholder="กรอกรหัสผ่านกลาง"
                        required
                    >
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white transition hover:bg-brand-700">
                    ยืนยันและเข้าสู่หน้ารายงาน
                </button>
            </form>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-7 text-slate-600">
                หากไม่ทราบรหัสผ่านกลาง กรุณาติดต่อผู้ดูแลระบบหรือหน่วยงานที่รับผิดชอบการจัดการความเสี่ยงของโรงพยาบาล
            </div>
        </div>
    </section>
</main>

<?php if ($flashError): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'ไม่สามารถเข้าใช้งานได้',
        text: <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>
    });
</script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
