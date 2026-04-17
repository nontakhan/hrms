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
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Department Head Queue</div>
                <h1 class="text-3xl font-bold text-slate-900">งานของหัวหน้ากลุ่มงาน/หัวหน้างาน</h1>
                <p class="mt-2 text-slate-600">รายการรายงานที่ทีมนำส่งต่อมาโดยตรง และรายการที่ได้รับสิทธิ์มองเห็นเพิ่มเติมจากการตั้งค่า workflow</p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
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
                            <td><?= e((string) $report['level_code']) ?></td>
                            <td><?= e((string) $report['department_name']) ?></td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $report['access_type'] === 'direct' ? 'bg-brand-100 text-brand-700' : 'bg-amber-100 text-amber-700' ?>">
                                    <?= $report['access_type'] === 'direct' ? 'รับตรง' : 'เห็นเพิ่ม' ?>
                                </span>
                            </td>
                            <td><?= e((string) $report['assignment_status']) ?></td>
                            <td><?= e((string) $report['assigned_at']) ?></td>
                            <td>
                                <a href="<?= e(base_url('head/report_detail.php?assignment_id=' . $report['assignment_id'])) ?>" class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">
                                    เปิดงาน
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
