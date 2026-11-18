<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('student');
require_once __DIR__ . '/../includes/layout.php';

$complaintId = (int) ($_GET['id'] ?? $_POST['complaint_id'] ?? 0);
$complaint = complaint_access($pdo, $complaintId);

if ($complaint['status'] !== 'Resolved'){

    exit('Feedback is available after resolution.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();

    $rating = (int) ($_POST['rating'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');

    if ($rating < 1 || $rating > 5){

        flash('Choose a rating from 1 to 5.', 'error');
    }
    else{

        $statement = $pdo->prepare(
        'INSERT INTO feedback (complaint_id, student_id, rating, comments)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), comments = VALUES(comments)'
        );
        $statement->execute([
        $complaintId,
        current_user()['id'],
        $rating,
        $comments,
        ]);

        flash('Thank you for your feedback.');
        go('student/complaint_details.php?id=' . $complaintId);
    }

}

page_start('Complaint feedback');
?>

<div class="card feedback-card">
<h1>Feedback</h1>
<p class="muted mt-2">
<?= e($complaint['tracking_no']) ?>
<span aria-hidden="true">&middot;</span>
<?= e($complaint['title']) ?>
</p>

<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
<input type="hidden" name="complaint_id" value="<?= $complaintId ?>">

<div class="mb-3">
<label class="form-label" for="rating">How was this concern handled?</label>
<select class="form-select" id="rating" name="rating" required>
<option value="">Choose a rating</option>
<?php for ($score = 1; $score <= 5; $score++): ?>
<option value="<?= $score ?>" <?= (int) ($_POST['rating'] ?? 0) === $score ? 'selected' : '' ?>>
<?= $score ?>
</option>
<?php endfor; ?>
</select>
</div>

<div class="mb-3">
<label class="form-label" for="comments">Comments</label>
<textarea class="form-control" id="comments" name="comments" rows="4"><?= e($_POST['comments'] ?? '') ?></textarea>
</div>

<button class="btn btn-navy" type="submit">Save feedback</button>
</form>

<p class="feedback-home">
<a href="<?= url('student/my_complaints.php') ?>">Back to my complaints</a>
</p>
</div>

<?php page_end(); ?>
