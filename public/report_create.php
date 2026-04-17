<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (!Auth::canAccessPublicReport()) {
    flash_set('error', 'กรุณายืนยันรหัสผ่านกลางก่อนเข้าใช้งานหน้ารายงาน');
    redirect('/public/report_access.php');
}

$pageTitle = 'รายงานความเสี่ยง';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$departments = fetch_all_departments();

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-6xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft lg:p-10">
        <div class="mb-8 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Incident Report Form</div>
                <h1 class="text-3xl font-bold text-slate-900">ฟอร์มรายงานความเสี่ยง</h1>
                <p class="mt-2 text-slate-600">สำหรับบุคลากรที่ต้องการรายงานเหตุการณ์ความเสี่ยงเข้าสู่ระบบ</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                สิทธิ์เข้าถึงหน้านี้มาจาก password กลางแบบ session ชั่วคราว
            </div>
        </div>

        <form action="<?= e(base_url('actions/report_store.php')) ?>" method="post" enctype="multipart/form-data" class="grid gap-5 lg:grid-cols-2">
            <?= csrf_field() ?>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อหัวข้อเหตุการณ์</label>
                <input name="incident_title" type="text" maxlength="255" value="<?= e((string) old('incident_title')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">ประเภทเหตุการณ์</label>
                <select name="incident_type" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
                    <option value="">เลือกประเภท</option>
                    <option value="CLINICAL" <?= old('incident_type') === 'CLINICAL' ? 'selected' : '' ?>>Clinical</option>
                    <option value="NON_CLINICAL" <?= old('incident_type') === 'NON_CLINICAL' ? 'selected' : '' ?>>Non-Clinical</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">วันที่และเวลาเกิดเหตุ</label>
                <input name="incident_datetime" type="datetime-local" value="<?= e((string) old('incident_datetime')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">หน่วยงานที่เกิดเหตุ</label>
                <select name="incident_department_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
                    <option value="">เลือกหน่วยงาน</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= e((string) $department['id']) ?>" <?= old('incident_department_id') == $department['id'] ? 'selected' : '' ?>>
                            <?= e((string) $department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">ระดับความรุนแรง</label>
                <select name="severity_code" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required>
                    <option value="">เลือกความรุนแรง</option>
                    <optgroup label="Clinical">
                        <?php foreach (['A','B','C','D','E','F','G','H','I'] as $code): ?>
                            <option value="<?= e($code) ?>" <?= old('severity_code') === $code ? 'selected' : '' ?>><?= e($code) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Non-Clinical">
                        <?php foreach (['1','2','3','4'] as $code): ?>
                            <option value="<?= e($code) ?>" <?= old('severity_code') === $code ? 'selected' : '' ?>><?= e($code) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">รายละเอียดเหตุการณ์</label>
                <textarea name="incident_detail" rows="6" maxlength="5000" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500" required><?= e((string) old('incident_detail')) ?></textarea>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">การแก้ไขเบื้องต้น</label>
                <textarea name="initial_action" rows="4" maxlength="3000" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500"><?= e((string) old('initial_action')) ?></textarea>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อผู้รายงาน (ถ้ามี)</label>
                <input name="reporter_name" type="text" maxlength="255" value="<?= e((string) old('reporter_name')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">เบอร์โทร (ถ้ามี)</label>
                <input name="reporter_phone" type="text" maxlength="50" value="<?= e((string) old('reporter_phone')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand-500">
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">ไฟล์แนบ (ถ้ามี)</label>
                <input name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none transition focus:border-brand-500">
                <p class="mt-2 text-xs text-slate-500">อนุญาตไฟล์ PDF, รูปภาพ, Word, Excel ขนาดไม่เกิน 5 MB</p>
            </div>

            <div class="lg:col-span-2 flex justify-end gap-3 pt-4">
                <a href="<?= e(base_url('/')) ?>" class="rounded-xl border border-slate-300 px-5 py-3 font-medium text-slate-700">กลับหน้าหลัก</a>
                <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white transition hover:bg-brand-700">บันทึกรายงาน</button>
            </div>
        </form>
    </section>
</main>

<?php if ($flashError): ?>
<script>
    Swal.fire({icon: 'error', title: 'บันทึกไม่สำเร็จ', text: <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>});
</script>
<?php endif; ?>

<?php if ($flashSuccess): ?>
<script>
    Swal.fire({icon: 'success', title: 'บันทึกสำเร็จ', text: <?= json_encode($flashSuccess, JSON_UNESCAPED_UNICODE) ?>});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
