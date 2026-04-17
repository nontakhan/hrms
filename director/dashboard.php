<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

Auth::requireRole('DIRECTOR');

$pageTitle = 'Dashboard ผู้อำนวยการ';
$stats = [
    'total_reports' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
];
$severitySummary = [];
$teamSummary = [];
$recentReports = [];

try {
    $pdo = Database::connection();

    $countStmt = $pdo->query(
        "SELECT
            COUNT(*) AS total_reports,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
         FROM incident_reports"
    );
    $stats = array_merge($stats, $countStmt->fetch() ?: []);

    $severitySummary = $pdo->query(
        "SELECT sl.level_code, COUNT(*) AS total
         FROM incident_reports ir
         INNER JOIN severity_levels sl ON sl.id = ir.current_severity_id
         GROUP BY sl.level_code
         ORDER BY total DESC, sl.level_code ASC"
    )->fetchAll();

    $teamSummary = $pdo->query(
        "SELECT t.team_code, COUNT(*) AS total
         FROM report_assignments ra
         INNER JOIN teams t ON t.id = ra.target_team_id
         GROUP BY t.team_code
         ORDER BY total DESC, t.team_code ASC"
    )->fetchAll();

    $recentReports = $pdo->query(
        "SELECT ir.report_no, ir.incident_title, ir.status, ir.reported_at, d.department_name
         FROM incident_reports ir
         INNER JOIN departments d ON d.id = ir.incident_department_id
         ORDER BY ir.id DESC
         LIMIT 10"
    )->fetchAll();
} catch (Throwable) {
    // ให้ dashboard เปิดได้แม้ฐานข้อมูลยังไม่พร้อมทั้งหมด
}

$severityLabels = array_map(static fn(array $row): string => (string) $row['level_code'], $severitySummary);
$severityData = array_map(static fn(array $row): int => (int) $row['total'], $severitySummary);
$teamLabels = array_map(static fn(array $row): string => (string) $row['team_code'], $teamSummary);
$teamData = array_map(static fn(array $row): int => (int) $row['total'], $teamSummary);

require __DIR__ . '/../partials/layout_top.php';
?>
<main class="mx-auto max-w-7xl px-6 py-8 lg:py-12">
    <section class="rounded-[2rem] bg-white p-8 shadow-soft">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">Director Overview</div>
                <h1 class="text-3xl font-bold text-slate-900">Dashboard ผู้อำนวยการ</h1>
                <p class="mt-2 text-slate-600">ภาพรวมความเสี่ยงขององค์กรสำหรับติดตามสถานะและแนวโน้มสำคัญ</p>
            </div>
            <a href="<?= e(base_url('dashboard.php')) ?>" class="rounded-xl border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
                กลับ Dashboard
            </a>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">รายงานทั้งหมด</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['total_reports']) ?></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">รอรับเรื่อง</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['pending']) ?></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">กำลังดำเนินการ</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['in_progress']) ?></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">เสร็จสิ้น</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $stats['completed']) ?></div>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">สรุประดับความรุนแรง</h2>
                <canvas id="severityChart" class="mt-4 h-80 w-full"></canvas>
            </div>
            <div class="rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900">สรุปจำนวนงานตามทีมนำ</h2>
                <canvas id="teamChart" class="mt-4 h-80 w-full"></canvas>
            </div>
        </div>

        <div class="mt-8 rounded-2xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">รายงานล่าสุด</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="px-3 py-3">เลขรายงาน</th>
                            <th class="px-3 py-3">หัวข้อ</th>
                            <th class="px-3 py-3">หน่วยงาน</th>
                            <th class="px-3 py-3">สถานะ</th>
                            <th class="px-3 py-3">วันที่รายงาน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReports as $report): ?>
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-3"><?= e((string) ($report['report_no'] ?: '-')) ?></td>
                                <td class="px-3 py-3"><?= e((string) $report['incident_title']) ?></td>
                                <td class="px-3 py-3"><?= e((string) $report['department_name']) ?></td>
                                <td class="px-3 py-3"><?= e((string) $report['status']) ?></td>
                                <td class="px-3 py-3"><?= e((string) $report['reported_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const severityCtx = document.getElementById('severityChart');
    if (severityCtx) {
        new Chart(severityCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($severityLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'จำนวนรายงาน',
                    data: <?= json_encode($severityData, JSON_UNESCAPED_UNICODE) ?>,
                    backgroundColor: '#1d7f5f'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const teamCtx = document.getElementById('teamChart');
    if (teamCtx) {
        new Chart(teamCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($teamLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode($teamData, JSON_UNESCAPED_UNICODE) ?>,
                    backgroundColor: ['#1d7f5f', '#f4b942', '#0f172a', '#38bdf8', '#ef4444', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
</script>

<?php require __DIR__ . '/../partials/layout_bottom.php'; ?>
