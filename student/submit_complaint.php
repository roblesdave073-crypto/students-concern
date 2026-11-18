<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('student');
require_once __DIR__ . '/../includes/layout.php';
$anonymousReportsEnabled = app_setting($pdo, 'anonymous_reports_enabled') === '1';
$defaultPriority = app_setting($pdo, 'default_complaint_priority', 'Medium');
if (!in_array($defaultPriority, ['Low', 'Medium', 'High', 'Urgent'], true)) {
    $defaultPriority = 'Medium';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $departmentId = (int) ($_POST['department_id'] ?? 0);

    $categoryCheck = $pdo->prepare(
    'SELECT 1 FROM categories WHERE id = ? AND is_active = 1'
    );
    $categoryCheck->execute([$categoryId]);
    $categoryExists = (bool) $categoryCheck->fetchColumn();

    $departmentCheck = $pdo->prepare(
    'SELECT 1 FROM departments WHERE id = ?'
    );
    $departmentCheck->execute([$departmentId]);
    $departmentExists = (bool) $departmentCheck->fetchColumn();

    if (strlen($title) < 4 || strlen($description) < 15 || !$categoryExists || !$departmentExists){

        flash('Complete all required fields. Description must be at least 15 characters.', 'error');
    }
    else{

        try{

            $pdo->beginTransaction();

            $insertComplaint = $pdo->prepare(
            'INSERT INTO complaints
            (student_id, category_id, department_id, title, description, priority, is_anonymous)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $insertComplaint->execute([
            current_user()['id'],
            $categoryId,
            $departmentId,
            $title,
            $description,
            $defaultPriority,
            $anonymousReportsEnabled && isset($_POST['anonymous']) ? 1 : 0,
            ]);

            $complaintId = (int) $pdo->lastInsertId();
            $trackingNumber = 'CCS-' . date('Y') . '-' . str_pad((string) $complaintId, 4, '0', STR_PAD_LEFT);

            $pdo->prepare('UPDATE complaints SET tracking_no = ? WHERE id = ?')
            ->execute([$trackingNumber, $complaintId]);

            $pdo->prepare(
            'INSERT INTO complaint_updates (complaint_id, user_id, new_status, message)
            VALUES (?, ?, ?, ?)'
            )->execute([
            $complaintId,
            current_user()['id'],
            'Pending',
            'Complaint submitted.',
            ]);

            $admins = $pdo->query(
            "SELECT id FROM users WHERE role = 'admin' AND is_active = 1"
            )->fetchAll();

            foreach ($admins as $admin){

                notify(
                $pdo,
                (int) $admin['id'],
                'New complaint submitted',
                $trackingNumber . ' requires review.',
                $complaintId
                );
            }

            notify(
            $pdo,
            (int) current_user()['id'],
            'Complaint submitted',
            'Your tracking number is ' . $trackingNumber . '.',
            $complaintId
            );
            log_activity(
            $pdo,
            (int) current_user()['id'],
            'Submitted complaint ' . $trackingNumber,
            $complaintId
            );

            $pdo->commit();
            flash('Concern submitted. Tracking number: ' . $trackingNumber);
            go('student/my_complaints.php');
        }
        catch (Throwable $exception){

            if ($pdo->inTransaction()){

                $pdo->rollBack();
            }

            throw $exception;
        }

    }

}

$categories = $pdo->query(
'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name'
)->fetchAll();
$departments = $pdo->query(
'SELECT id, name FROM departments ORDER BY name'
)->fetchAll();

page_start('Submit a concern', 'student/submit_complaint.php');
?>

<div class="page-heading">
<div>
<h1>Submit a concern</h1>
<p class="muted mb-0">Share the details so the right team can help.</p>
</div>
</div>

<div class="card">
<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

<div class="row g-3">
<div class="col-md-6">
<label class="form-label" for="category">Category</label>
<select class="form-select" id="category" name="category_id" required>
<option value="">Choose a category</option>
<?php foreach ($categories as $category): ?>
<option value="<?= (int) $category['id'] ?>">
<?= e($category['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6">
<label class="form-label" for="department">Department</label>
<select class="form-select" id="department" name="department_id" required>
<option value="">Choose a department</option>
<?php foreach ($departments as $department): ?>
<option value="<?= (int) $department['id'] ?>">
<?= e($department['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-12">
<label class="form-label" for="title">Title</label>
<input class="form-control" id="title" name="title" maxlength="180" required>
</div>

<div class="col-12">
<label class="form-label" for="description">What happened?</label>
<textarea
class="form-control"
id="description"
name="description"
rows="6"
minlength="15"
required
>
</textarea>
</div>

<?php if ($anonymousReportsEnabled): ?><div class="col-12">
<label class="form-check">
<input class="form-check-input" type="checkbox" name="anonymous">
<span class="form-check-label">
Submit anonymously (identity remains available to authorized administrators)
</span>
</label>
</div><?php endif; ?>

<div class="col-12">
<button class="btn btn-navy" type="submit">Submit concern</button>
</div>
</div>
</form>
</div>

<?php page_end(); ?>
