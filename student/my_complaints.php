<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('student');
require_once __DIR__ . '/../includes/layout.php';

$complaintQuery = $pdo->prepare(
'SELECT
c.id,
c.tracking_no,
c.title,
c.status,
c.created_at,
cat.name AS category
FROM complaints AS c
JOIN categories AS cat ON cat.id = c.category_id
WHERE c.student_id = ? AND c.archived_at IS NULL
ORDER BY c.created_at DESC'
);
$complaintQuery->execute([current_user()['id']]);
$rows = $complaintQuery->fetchAll();

page_start('My complaints', 'student/my_complaints.php');
?>

<div class="page-heading">
<h1>My complaints</h1>
<a class="btn btn-navy" href="<?= url('student/submit_complaint.php') ?>">
Submit a concern
</a>
</div>

<?php require __DIR__ . '/../includes/complaint_table.php'; ?>

<?php page_end(); ?>
