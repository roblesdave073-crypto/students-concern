<?php require_once __DIR__ . '/../includes/functions.php';
if (current_user())go(current_user()['role'] . '/dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();
    $s = $pdo->prepare('SELECT * FROM users WHERE email=? AND is_active=1');
    $s->execute([strtolower(trim($_POST['email'] ?? ''))]);
    $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '',$u['password_hash'])){

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'],
        'full_name' => $u['full_name'],
        'email' => $u['email'],
        'role' => $u['role']];
        log_activity($pdo,(int)$u['id'],'Signed in');
        go($u['role'] . '/dashboard.php');

    }

    flash('Email or password was not recognized.','error');

}

$flash = $_SESSION['flash']['message'] ?? null;
unset($_SESSION['flash']);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log in | Student Concerns</title>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=url('assets/css/app.css')?>" rel="stylesheet">
<link href="<?=url('assets/css/design.css')?>" rel="stylesheet">
</head>
<body class="auth-page">
<main class="auth-screen">
<aside class="auth-panel">
<a class="landing-brand" href="<?=url()?>">
<span class="brand-mark" style="background:white;color:var(--navy)">
<i class="bi bi-chat-left-text">
</i>
</span>
<span>Student Concerns<small>Southern leyte State University</small>
</span>
</a>
<div class="auth-intro">
<h1>Welcome back</h1>
<p>Log in to submit a concern, check the status of your complaints, and read responses from staff.</p>
<div class="auth-points">
<span>
<i class="bi bi-check-circle">
</i>Track every complaint by number</span>
<span>
<i class="bi bi-check-circle">
</i>Get notified on every update</span>
<span>
<i class="bi bi-check-circle">
</i>Report anonymously when needed</span>
</div>
</div>
<footer>Student Complaint and Concerns Management System</footer>
</aside>
<section class="auth-form-side">
<div class="auth-form-wrap">
<h2>Log in</h2>
<p class="lead">Students, faculty, staff, and administrators sign in here.</p><?php if ($flash):?><div class="alert alert-navy"><?=e($flash)?></div><?php endif;
?><form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<div class="mb-4">
<label class="form-label">Email or student ID</label>
<input class="form-control" type="email" name="email" placeholder="name@school.edu" autocomplete="username" required>
</div>
<div class="mb-3">
<label class="form-label">Password</label>
<input class="form-control" type="password" name="password" placeholder="Password" autocomplete="current-password" required>
</div>
<div class="d-flex justify-content-between mb-4">
<label>
<input type="checkbox"> Remember me</label>
<a href="<?=url('auth/forgot_password.php')?>">Forgot password?</a>
</div>
<button class="btn btn-navy w-100">Log in</button>
</form>
<div class="auth-bottom">New student? <a href="<?=url('auth/register.php')?>">Create an account</a>
<p class="mt-4">
<a href="<?=url()?>">Back to home</a>
</p>
</div>
</div>
</section>
</main>
</body>
</html>
