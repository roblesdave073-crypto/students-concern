<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');
require_once __DIR__ . '/../includes/layout.php';

$statusQuery = $pdo->query(
    'SELECT status, COUNT(*) AS total '
    . 'FROM complaints '
    . 'WHERE archived_at IS NULL '
    . 'GROUP BY status'
);
$statusCounts = array_fill_keys(statuses(), 0);

foreach ($statusQuery->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

$totalComplaints = array_sum($statusCounts);
$activeStudents = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1"
)->fetchColumn();
$activeStaff = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'staff' AND is_active = 1"
)->fetchColumn();

$categoryQuery = $pdo->query(
    'SELECT categories.name, COUNT(complaints.id) AS total '
    . 'FROM categories '
    . 'LEFT JOIN complaints '
    . 'ON complaints.category_id = categories.id '
    . 'AND complaints.archived_at IS NULL '
    . 'GROUP BY categories.id, categories.name '
    . 'ORDER BY total DESC, categories.name ASC LIMIT 8'
);
$categories = $categoryQuery->fetchAll();

$trendQuery = $pdo->query(
    'SELECT DATE(created_at) AS activity_date, COUNT(*) AS total '
    . 'FROM complaints '
    . 'WHERE archived_at IS NULL '
    . 'AND created_at >= CURRENT_DATE - INTERVAL 6 DAY '
    . 'GROUP BY DATE(created_at)'
);
$submittedByDate = array_column($trendQuery->fetchAll(), 'total', 'activity_date');

$resolvedQuery = $pdo->query(
    "SELECT DATE(created_at) AS activity_date, COUNT(DISTINCT complaint_id) AS total "
    . "FROM complaint_updates "
    . "WHERE new_status = 'Resolved' "
    . 'AND created_at >= CURRENT_DATE - INTERVAL 6 DAY '
    . 'GROUP BY DATE(created_at)'
);
$resolvedByDate = array_column($resolvedQuery->fetchAll(), 'total', 'activity_date');

$trendLabels = [];
$submittedTrend = [];
$resolvedTrend = [];

for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
    $date = new DateTimeImmutable("-$daysAgo days");
    $dateKey = $date->format('Y-m-d');

    $trendLabels[] = $date->format('M j');
    $submittedTrend[] = (int) ($submittedByDate[$dateKey] ?? 0);
    $resolvedTrend[] = (int) ($resolvedByDate[$dateKey] ?? 0);
}

$recentQuery = $pdo->query(
    'SELECT complaints.id, complaints.tracking_no, complaints.title, '
    . 'complaints.status, complaints.priority, complaints.created_at, '
    . 'complaints.is_anonymous, categories.name AS category, '
    . 'CASE WHEN complaints.is_anonymous = 1 THEN \'Anonymous\' '
    . 'ELSE students_user.full_name END AS student_name, '
    . '(SELECT GROUP_CONCAT(DISTINCT assigned_user.full_name SEPARATOR \', \') '
    . 'FROM complaint_assignments '
    . 'JOIN users AS assigned_user ON assigned_user.id = complaint_assignments.staff_id '
    . 'WHERE complaint_assignments.complaint_id = complaints.id) AS assigned_staff '
    . 'FROM complaints '
    . 'JOIN categories ON categories.id = complaints.category_id '
    . 'JOIN users AS students_user ON students_user.id = complaints.student_id '
    . 'WHERE complaints.archived_at IS NULL '
    . 'ORDER BY complaints.created_at DESC LIMIT 6'
);
$recentComplaints = $recentQuery->fetchAll();

$urgentUnassigned = (int) $pdo->query(
    "SELECT COUNT(*) FROM complaints "
    . "WHERE priority = 'Urgent' AND status NOT IN ('Resolved', 'Rejected') "
    . 'AND archived_at IS NULL '
    . 'AND NOT EXISTS ('
    . 'SELECT 1 FROM complaint_assignments '
    . 'WHERE complaint_assignments.complaint_id = complaints.id)'
)->fetchColumn();

$overdueComplaints = (int) $pdo->query(
    "SELECT COUNT(*) FROM complaints "
    . "WHERE status NOT IN ('Resolved', 'Rejected') "
    . 'AND archived_at IS NULL '
    . 'AND created_at < NOW() - INTERVAL 7 DAY'
)->fetchColumn();

$stalePending = (int) $pdo->query(
    "SELECT COUNT(*) FROM complaints "
    . "WHERE status = 'Pending' AND archived_at IS NULL "
    . 'AND created_at < NOW() - INTERVAL 3 DAY'
)->fetchColumn();

$activityQuery = $pdo->query(
    'SELECT activity_logs.action, activity_logs.created_at, users.full_name '
    . 'FROM activity_logs '
    . 'LEFT JOIN users ON users.id = activity_logs.user_id '
    . 'ORDER BY activity_logs.created_at DESC LIMIT 4'
);
$activities = $activityQuery->fetchAll();

page_start('Admin dashboard', 'admin/dashboard.php');
?>

<div class="admin-dashboard">
    <header class="admin-dashboard-heading">
        <div>
            <div class="dashboard-breadcrumb">
                <a href="<?= url() ?>">Home</a>
                <span aria-hidden="true">/</span>
                <strong>Dashboard</strong>
            </div>
            <h1>Admin dashboard</h1>
            <p class="muted mb-0">Monitor concerns and track resolution progress.</p>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-outline-navy" href="<?= url('admin/manage_students.php') ?>">
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                Add student
            </a>
            <a class="btn btn-navy" href="<?= url('admin/reports.php') ?>">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export report
            </a>
        </div>
    </header>

    <section class="dashboard-metrics" aria-label="Complaint summary">
        <article class="dashboard-metric dashboard-metric-primary">
            <span class="dashboard-metric-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
            <div>
                <p>Total complaints</p>
                <strong><?= $totalComplaints ?></strong>
            </div>
        </article>
        <article class="dashboard-metric">
            <span class="dashboard-metric-icon dashboard-icon-pending"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            <div>
                <p>Pending</p>
                <strong><?= $statusCounts['Pending'] ?></strong>
            </div>
        </article>
        <article class="dashboard-metric">
            <span class="dashboard-metric-icon dashboard-icon-progress"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span>
            <div>
                <p>In progress</p>
                <strong><?= $statusCounts['In Progress'] ?></strong>
            </div>
        </article>
        <article class="dashboard-metric">
            <span class="dashboard-metric-icon dashboard-icon-resolved"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <div>
                <p>Resolved</p>
                <strong><?= $statusCounts['Resolved'] ?></strong>
            </div>
        </article>
    </section>

    <div class="dashboard-community-summary">
        <span><i class="bi bi-mortarboard" aria-hidden="true"></i> <?= $activeStudents ?> active students</span>
        <span><i class="bi bi-people" aria-hidden="true"></i> <?= $activeStaff ?> active staff</span>
    </div>

    <section class="dashboard-analytics-grid" aria-label="Complaint analytics">
        <article class="card dashboard-panel dashboard-trend-panel">
            <div class="dashboard-panel-heading">
                <div>
                    <h2>Complaints over time</h2>
                    <p>Submitted and resolved in the last 7 days</p>
                </div>
                <?php if ($totalComplaints > 0): ?>
                    <div class="dashboard-chart-legend">
                        <span><i class="legend-submitted"></i>Submitted</span>
                        <span><i class="legend-resolved"></i>Resolved</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($totalComplaints > 0): ?>
                <div class="dashboard-trend-chart">
                    <canvas id="complaintsTrendChart" aria-label="Complaints submitted and resolved over the last seven days"></canvas>
                </div>
            <?php else: ?>
                <div class="dashboard-chart-empty">
                    <span class="dashboard-empty-icon"><i class="bi bi-graph-up" aria-hidden="true"></i></span>
                    <strong>No complaint activity yet</strong>
                    <p>Submitted and resolved trends will appear here when cases are recorded.</p>
                </div>
            <?php endif; ?>
        </article>

        <article class="card dashboard-panel dashboard-category-panel">
            <div class="dashboard-panel-heading">
                <div>
                    <h2>Complaints by category</h2>
                    <p>Most reported concern types</p>
                </div>
            </div>

            <?php $largestCategory = max(1, ...array_map('intval', array_column($categories, 'total'))); ?>
            <?php if ($totalComplaints > 0 && $categories): ?>
                <div class="dashboard-category-list">
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryTotal = (int) $category['total']; ?>
                        <div class="dashboard-category-item">
                            <div class="dashboard-category-label">
                                <span><?= e($category['name']) ?></span>
                                <strong><?= $categoryTotal ?></strong>
                            </div>
                            <div class="dashboard-category-track">
                                <span style="width: <?= round($categoryTotal / $largestCategory * 100) ?>%"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dashboard-chart-empty dashboard-category-empty">
                    <span class="dashboard-empty-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
                    <strong>Category totals will show here</strong>
                    <p>No complaints have been submitted yet.</p>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="dashboard-content-grid">
        <article class="card dashboard-panel dashboard-recent-panel">
            <div class="dashboard-panel-heading">
                <div>
                    <h2>Recent complaints</h2>
                    <p>Latest submissions across all departments</p>
                </div>
                <a href="<?= url('admin/manage_complaints.php') ?>">View all</a>
            </div>

            <?php if ($recentComplaints): ?>
                <div class="table-wrap dashboard-table-wrap">
                    <table class="table dashboard-complaints-table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Tracking no.</th>
                                <th scope="col">Student</th>
                                <th scope="col">Category</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Status</th>
                                <th scope="col">Assigned to</th>
                                <th scope="col"><span class="visually-hidden">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComplaints as $complaint): ?>
                                <tr>
                                    <td class="dashboard-tracking-number">
                                        <?= e($complaint['tracking_no'] ?: 'SCC-' . $complaint['id']) ?>
                                    </td>
                                    <td><?= e($complaint['student_name']) ?></td>
                                    <td><?= e($complaint['category']) ?></td>
                                    <td>
                                        <span class="priority-pill priority-<?= strtolower(e($complaint['priority'])) ?>">
                                            <?= e($complaint['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-pill status-<?= strtolower(str_replace(' ', '-', e($complaint['status']))) ?>">
                                            <?= e($complaint['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($complaint['assigned_staff'] ?: 'Unassigned') ?></td>
                                    <td>
                                        <a href="<?= url('admin/complaint_details.php?id=' . (int) $complaint['id']) ?>">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="dashboard-table-empty">
                    <i class="bi bi-inbox" aria-hidden="true"></i>
                    <strong>No complaints yet</strong>
                    <span>New student submissions will appear here.</span>
                </div>
            <?php endif; ?>
        </article>

        <aside class="dashboard-side-panels">
            <section class="card dashboard-panel dashboard-attention-panel">
                <div class="dashboard-panel-heading">
                    <div>
                        <h2><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Needs attention</h2>
                        <p>Cases that may need an administrator</p>
                    </div>
                </div>

                <?php if ($urgentUnassigned + $overdueComplaints + $stalePending > 0): ?>
                    <ul class="dashboard-attention-list">
                        <?php if ($urgentUnassigned > 0): ?>
                            <li class="attention-urgent">
                                <div><strong><?= $urgentUnassigned ?> urgent complaint<?= $urgentUnassigned === 1 ? '' : 's' ?> unassigned</strong><span>Needs staff assignment</span></div>
                                <a href="<?= url('admin/manage_complaints.php') ?>">Review</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($overdueComplaints > 0): ?>
                            <li class="attention-overdue">
                                <div><strong><?= $overdueComplaints ?> overdue complaint<?= $overdueComplaints === 1 ? '' : 's' ?></strong><span>Open for more than 7 days</span></div>
                                <a href="<?= url('admin/manage_complaints.php') ?>">Review</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($stalePending > 0): ?>
                            <li class="attention-pending">
                                <div><strong><?= $stalePending ?> pending over 3 days</strong><span>Waiting for a first response</span></div>
                                <a href="<?= url('admin/manage_complaints.php') ?>">Review</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php else: ?>
                    <div class="dashboard-attention-clear">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <span>Everything is up to date.</span>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card dashboard-panel dashboard-activity-panel">
                <div class="dashboard-panel-heading">
                    <div>
                        <h2>Recent activity</h2>
                        <p>Latest updates across the system</p>
                    </div>
                    <a href="<?= url('admin/activity_logs.php') ?>">Audit log</a>
                </div>

                <?php if ($activities): ?>
                    <ul class="dashboard-activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <li>
                                <span class="dashboard-activity-dot" aria-hidden="true"></span>
                                <div>
                                    <p><?= e($activity['action']) ?></p>
                                    <small><?= e($activity['full_name'] ?? 'System') ?> · <?= e($activity['created_at']) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="dashboard-attention-clear">
                        <span>No recent activity yet.</span>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </section>
</div>

<?php if ($totalComplaints > 0): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const trendLabels = <?= json_encode($trendLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const submittedTrend = <?= json_encode($submittedTrend) ?>;
    const resolvedTrend = <?= json_encode($resolvedTrend) ?>;

    Chart.defaults.font.family = "'Figtree', sans-serif";
    Chart.defaults.color = '#64748B';

    new Chart(document.getElementById('complaintsTrendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label: 'Submitted',
                    data: submittedTrend,
                    borderColor: '#0B1F3A',
                    backgroundColor: 'rgba(11, 31, 58, .08)',
                    fill: true,
                    tension: .3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                },
                {
                    label: 'Resolved',
                    data: resolvedTrend,
                    borderColor: '#25845B',
                    backgroundColor: 'transparent',
                    borderDash: [6, 4],
                    tension: .3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, stepSize: 1 },
                    grid: { color: 'rgba(11, 31, 58, .08)' },
                    border: { display: false },
                },
            },
        },
    });
    </script>
<?php endif; ?>

<?php page_end(); ?>
