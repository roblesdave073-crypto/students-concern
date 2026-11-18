<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('student');
require_once __DIR__ . '/../includes/layout.php';

$studentId = (int) current_user()['id'];

$countQuery = $pdo->prepare(
'SELECT status, COUNT(*) AS total
FROM complaints
WHERE student_id = ? AND archived_at IS NULL
GROUP BY status'
);
$countQuery->execute([$studentId]);
$counts = array_column($countQuery->fetchAll(), 'total', 'status');

$recentQuery = $pdo->prepare(
'SELECT id, tracking_no, title, status, created_at
FROM complaints
WHERE student_id = ? AND archived_at IS NULL
ORDER BY created_at DESC
LIMIT 5'
);
$recentQuery->execute([$studentId]);
$rows = $recentQuery->fetchAll();

page_start('Student dashboard', 'student/dashboard.php');
?>

<div class="page-heading">
<div>
<h1>Hello, <?= e(explode(' ', current_user()['full_name'])[0]) ?></h1>
<p class="muted mb-0">Your concerns and their progress, all in one place.</p>
</div>
<a class="btn btn-navy" href="<?= url('student/submit_complaint.php') ?>">
<i class="bi bi-plus-lg" aria-hidden="true">
</i>
Submit a concern
</a>
</div>

<div class="stat-grid">
<?php
$statistics = [
'Total' => array_sum($counts),
'Pending' => $counts['Pending'] ?? 0,
'In Progress' => $counts['In Progress'] ?? 0,
'Resolved' => $counts['Resolved'] ?? 0,
];
foreach ($statistics as $label => $value):
?>
<div class="card">
<div class="stat-label"><?= e($label) ?></div>
<div class="stat-value"><?= (int) $value ?></div>
</div>
<?php endforeach; ?>
</div>

<div class="page-heading">
<h2>Recent submissions</h2>
<a href="<?= url('student/my_complaints.php') ?>">View all</a>
</div>

<?php require __DIR__ . '/../includes/complaint_table.php'; ?>

<?php page_end(); ?>
