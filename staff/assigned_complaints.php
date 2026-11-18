<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('staff');
require_once __DIR__ . '/../includes/layout.php';

$statement = $pdo->prepare(
    'SELECT complaints.id, complaints.tracking_no, complaints.title, '
    . 'complaints.status, complaints.created_at, categories.name AS category '
    . 'FROM complaints '
    . 'JOIN complaint_assignments '
    . 'ON complaint_assignments.complaint_id = complaints.id '
    . 'JOIN categories ON categories.id = complaints.category_id '
    . 'WHERE complaint_assignments.staff_id = ? '
    . 'AND complaints.archived_at IS NULL '
    . 'ORDER BY complaints.updated_at DESC'
);
$statement->execute([current_user()['id']]);
$rows = $statement->fetchAll();

page_start('Assigned complaints', 'staff/assigned_complaints.php');
?>

<h1 class="mb-4">Assigned complaints</h1>

<?php require __DIR__ . '/../includes/complaint_table.php'; ?>

<?php page_end(); ?>
