<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('DEPARTMENT_HEAD');

$pageTitle = 'งานของหัวหน้ากลุ่มงาน/หัวหน้างาน';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$user = Auth::user();
$userId = (int) ($user['id'] ?? 0);
$reports = [];

if ($userId <= 0) {
    flash_set('error', 'ไม่พบข้อมูลผู้ใช้งาน');
    redirect('/dashboard.php');
}

try {
    $sql = <<<SQL
        SELECT DISTINCT
            ra.id AS assignment_id,
            ra.assignment_no,
            ra.assignment_status,
            ra.sent_reason,
            ra.assigned_at,
            ir.id AS report_id,
            ir.report_no,
            ir.incident_title,
            ir.reported_at,
            ir.status AS report_status,
            it.type_name,
            sl.level_code,
            d.department_name,
            t.team_code,
            t.team_name,
            CASE
                WHEN ra.target_head_user_id = :user_id THEN 'direct'
                ELSE COALESCE(tdv.visibility_type, 'supervisor')
            END AS access_type
        FROM report_assignments ra
        INNER JOIN incident_reports ir ON ir.id = ra.report_id
        INNER JOIN incident_types it ON it.id = ir.incident_type_id
        INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
        INNER JOIN departments d ON d.id = ir.incident_department_id
        INNER JOIN teams t ON t.id = ra.target_team_id
        LEFT JOIN team_department_visibility tdv
            ON tdv.team_id = ra.target_team_id
           AND tdv.department_id = ir.incident_department_id
           AND tdv.viewer_user_id = :user_id
           AND tdv.is_active = 1
        WHERE ra.target_head_user_id = :user_id
           OR tdv.id IS NOT NULL
        ORDER BY ra.id DESC
    SQL;

    $stmt = Database::connection()->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $reports = $stmt->fetchAll();
} catch (Throwable) {
    $reports = [];
}

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Department Head Queue</div>
                <h1 class="text-3xl font-bold text-slate-900">งานของหัวหน้ากลุ่มงาน/หัวหน้างาน</h1>
                <p class="mt-2 max-w-3xl text-slate-600">รวมทั้งเคสที่ทีมนำส่งตรงมาถึงคุณ และเคสที่คุณได้รับสิทธิ์มองเห็นเพิ่มเติมจากการตั้งค่า workflow เพื่อให้ติดตามงานและเปิดเคสได้จากหน้าเดียว</p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700 transition hover:bg-slate-50">กลับ Dashboard</a>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm text-slate-500">Assignment ทั้งหมด</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) count($reports)) ?></div>
            </div>
            <div class="rounded-2xl border border-brand-200 bg-brand-50/70 p-5">
                <div class="text-sm text-brand-700">รับตรง</div>
                <div class="mt-2 text-3xl font-bold text-brand-900"><?= e((string) count(array_filter($reports, static fn(array $report): bool => (string) $report['access_type'] === 'direct'))) ?></div>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="text-sm text-amber-700">เห็นเพิ่มจาก visibility</div>
                <div class="mt-2 text-3xl font-bold text-amber-900"><?= e((string) count(array_filter($reports, static fn(array $report): bool => (string) $report['access_type'] !== 'direct'))) ?></div>
            </div>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
                <div class="text-sm text-violet-700">รอเปิดดำเนินการ</div>
                <div class="mt-2 text-3xl font-bold text-violet-900"><?= e((string) count(array_filter($reports, static fn(array $report): bool => (string) $report['assignment_status'] === 'sent'))) ?></div>
            </div>
        </div>

        <div class="mt-8 rounded-2xl border border-slate-200 p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">คิวงานของหัวหน้า</h2>
                    <p class="mt-1 text-sm text-slate-500">สแกนบทบาทการเข้าถึง สถานะงาน และกดเปิดเคสเพื่อบันทึกแนวทางแก้ไขได้จากตารางนี้</p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-600"><?= e((string) count($reports)) ?> รายการ</div>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
                <table id="headReportsTable" class="display w-full text-sm">
                    <thead>
                        <tr>
                            <th>เลข Assignment</th>
                            <th>ทีมนำ</th>
                            <th>เลขรายงาน</th>
                            <th>หัวข้อ</th>
                            <th>ประเภท</th>
                            <th>ระดับ</th>
                            <th>หน่วยงาน</th>
                            <th>การเข้าถึง</th>
                            <th>สถานะงาน</th>
                            <th>วันที่ส่งต่อ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= e((string) $report['assignment_no']) ?></td>
                                <td><?= e((string) $report['team_code']) ?></td>
                                <td><?= e((string) ($report['report_no'] ?: ('IR-' . $report['report_id']))) ?></td>
                                <td><?= e((string) $report['incident_title']) ?></td>
                                <td><?= e((string) $report['type_name']) ?></td>
                                <td><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"><?= e((string) $report['level_code']) ?></span></td>
                                <td><?= e((string) $report['department_name']) ?></td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $report['access_type'] === 'direct' ? 'bg-brand-100 text-brand-700' : 'bg-amber-100 text-amber-700' ?>">
                                        <?= $report['access_type'] === 'direct' ? 'รับตรง' : 'เห็นเพิ่ม' ?>
                                    </span>
                                </td>
                                <td><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"><?= e((string) $report['assignment_status']) ?></span></td>
                                <td><?= e((string) $report['assigned_at']) ?></td>
                                <td>
                                    <a href="<?= e(base_url('head/report_detail.php?assignment_id=' . $report['assignment_id'])) ?>" class="inline-flex rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">เปิดงาน</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

<script>
    $(function () {
        $('#headReportsTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[9, 'desc']],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' },
                zeroRecords: 'ไม่พบข้อมูล'
            }
        });
    });
</script>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
