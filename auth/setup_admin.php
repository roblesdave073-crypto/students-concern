<?php require_once __DIR__ . '/../includes/functions.php';
$exists = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
require_once __DIR__ . '/../includes/layout.php';
if ($exists){

    page_start('Administrator setup');
    ?><div class="card auth-card">
    <h1>Setup is complete</h1>
    <p>An administrator account already exists. Sign in to continue.</p>
    <a class="btn btn-navy" href="<?=url('auth/login.php')?>">Sign in</a>
    </div><?php page_end();
    exit;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw = $_POST['password'] ?? '';
    if ($name !== '' && filter_var($email,FILTER_VALIDATE_EMAIL) && strlen($pw) >= 12){
        $lock = $pdo->query("SELECT GET_LOCK('student_concerns_first_admin', 10)")->fetchColumn();
        if ((int) $lock !== 1) {
            flash('Administrator setup is busy. Please try again.','error');
            go('auth/setup_admin.php');
        }

        try {
            if ((int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() > 0) {
                flash('An administrator account already exists.','error');
                go('auth/setup_admin.php');
            }
            $s = $pdo->prepare("INSERT INTO users(full_name,email,password_hash,role) VALUES(?,?,?,'admin')");
            $s->execute([$name,$email,password_hash($pw,PASSWORD_DEFAULT)]);
            flash('Administrator account created. Sign in now.');
            go('auth/login.php');
        } catch (PDOException $e) {
            flash('Could not create the administrator account. Check the email and try again.','error');
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('student_concerns_first_admin')");
        }

    }

    flash('Check your name and email, and use a password of at least 12 characters.','error');

}

page_start('Create first administrator');
?><div class="card auth-card">
<h1>Create administrator</h1>
<p class="muted">Available only before the first administrator is created.</p>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<div class="mb-3">
<label class="form-label">Name</label>
<input class="form-control" name="name" required>
</div>
<div class="mb-3">
<label class="form-label">Email</label>
<input class="form-control" type="email" name="email" required>
</div>
<div class="mb-3">
<label class="form-label">Password (12+ characters)</label>
<input class="form-control" type="password" name="password" minlength="12" required>
</div>
<button class="btn btn-navy">Create admin</button>
</form>
</div><?php page_end();

