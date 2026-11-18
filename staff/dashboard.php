<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('staff');
require_once __DIR__ . '/../includes/layout.php';

$staffId = (int) current_user()['id'];

$totalQuery = $pdo->prepare(
    'SELECT COUNT(*) FROM complaint_assignments WHERE staff_id = ?'
);
$totalQuery->execute([$staffId]);
$totalAssigned = (int) $totalQuery->fetchColumn();

$statusCounts = [];
foreach (['Assigned', 'In Progress', 'Resolved'] as $status) {
    $countQuery = $pdo->prepare(
        'SELECT COUNT(*) '
        . 'FROM complaints '
        . 'JOIN complaint_assignments '
        . 'ON complaint_assignments.complaint_id = complaints.id '
        . 'WHERE complaint_assignments.staff_id = ? AND complaints.status = ?'
    );
    $countQuery->execute([$staffId, $status]);
    $statusCounts[$status] = (int) $countQuery->fetchColumn();
}

$recentQuery = $pdo->prepare(
    'SELECT complaints.id, complaints.tracking_no, complaints.title, '
    . 'complaints.status, complaints.priority, complaints.created_at, '
    . 'categories.name AS category '
    . 'FROM complaints '
    . 'JOIN complaint_assignments '
    . 'ON complaint_assignments.complaint_id = complaints.id '
    . 'JOIN categories ON categories.id = complaints.category_id '
    . 'WHERE complaint_assignments.staff_id = ? '
    . 'AND complaints.archived_at IS NULL '
    . 'ORDER BY complaints.updated_at DESC LIMIT 10'
);
$recentQuery->execute([$staffId]);
$rows = $recentQuery->fetchAll();

page_start('Staff dashboard', 'staff/dashboard.php');
?>

<div class="page-heading">
    <div>
        <h1>Staff dashboard</h1>
        <p class="muted mb-0">Your assigned cases</p>
    </div>
</div>

<div class="stat-grid">
    <div class="card dashboard-stat-featured">
        <div class="stat-label">Total assigned</div>
        <div class="stat-value"><?= $totalAssigned ?></div>
    </div>

    <?php
    $statistics = [
        'Pending assignments' => $statusCounts['Assigned'],
        'In progress' => $statusCounts['In Progress'],
        'Resolved' => $statusCounts['Resolved'],
    ];
    ?>
    <?php foreach ($statistics as $label => $value): ?>
        <div class="card">
            <div class="stat-label"><?= e($label) ?></div>
            <div class="stat-value"><?= $value ?></div>
        </div>
    <?php endforeach; ?>
</div>

<section class="card dashboard-panel">
    <div class="page-heading">
        <h2>Recently assigned complaints</h2>
        <a href="<?= url('staff/assigned_complaints.php') ?>">View all</a>
    </div>

    <?php require __DIR__ . '/../includes/complaint_table.php'; ?>
</section>

<?php page_end(); ?>
