<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('TEAM_LEAD');

$pageTitle = 'งานของทีมนำ';
$flashError = flash_get('error');
$flashSuccess = flash_get('success');
$user = Auth::user();
$teamId = (int) ($user['team_id'] ?? 0);
$reports = [];

if ($teamId <= 0) {
    flash_set('error', 'บัญชีนี้ยังไม่ได้ผูกกับทีมนำ');
    redirect('/dashboard.php');
}

try {
    $sql = <<<SQL
        SELECT
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
            d.department_name
        FROM report_assignments ra
        INNER JOIN incident_reports ir ON ir.id = ra.report_id
        INNER JOIN incident_types it ON it.id = ir.incident_type_id
        INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
        INNER JOIN departments d ON d.id = ir.incident_department_id
        WHERE ra.target_team_id = :team_id
        ORDER BY ra.id DESC
    SQL;

    $stmt = Database::connection()->prepare($sql);
    $stmt->execute(['team_id' => $teamId]);
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
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Team Lead Queue</div>
                <h1 class="text-3xl font-bold text-slate-900">งานของทีมนำ</h1>
                <p class="mt-2 text-slate-600">รายการรายงานที่ถูกส่งต่อมาให้ทีมนำของคุณพิจารณา</p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">
            <table id="teamReportsTable" class="display w-full text-sm">
                <thead>
                    <tr>
                        <th>เลข Assignment</th>
                        <th>เลขรายงาน</th>
                        <th>หัวข้อ</th>
                        <th>ประเภท</th>
                        <th>ระดับ</th>
                        <th>หน่วยงาน</th>
                        <th>สถานะงาน</th>
                        <th>วันที่ส่งต่อ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= e((string) $report['assignment_no']) ?></td>
                            <td><?= e((string) ($report['report_no'] ?: ('IR-' . $report['report_id']))) ?></td>
                            <td><?= e((string) $report['incident_title']) ?></td>
                            <td><?= e((string) $report['type_name']) ?></td>
                            <td><?= e((string) $report['level_code']) ?></td>
                            <td><?= e((string) $report['department_name']) ?></td>
                            <td><?= e((string) $report['assignment_status']) ?></td>
                            <td><?= e((string) $report['assigned_at']) ?></td>
                            <td>
                                <a href="<?= e(base_url('team/report_detail.php?assignment_id=' . $report['assignment_id'])) ?>" class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">
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
        $('#teamReportsTable').DataTable({
            pageLength: 10,
            order: [[7, 'desc']],
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
