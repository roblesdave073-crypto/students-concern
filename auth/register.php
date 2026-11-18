<?php
require_once __DIR__ . '/../includes/functions.php';

if (current_user())go(current_user()['role'] . '/dashboard.php');

$error = null;
$selectedRole = $_POST['role'] ?? 'student';
$studentRegistrationOpen = app_setting($pdo, 'student_registration_open') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (!in_array($role,['student','staff'],true))$error = 'Choose a valid account role.';

    elseif ($role === 'student' && !$studentRegistrationOpen)$error = 'Student registration is currently closed. Please contact the administrator.';

    elseif (strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<10 || $password !== $confirm)$error = 'Check your details. Passwords must match and be at least 10 characters.';

    else{

        try{

            $pdo->beginTransaction();
            $active = $role === 'staff'?0:1;
            $s = $pdo->prepare('INSERT INTO users(full_name,email,password_hash,role,is_active) VALUES(?,?,?,?,?)');
            $s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$active]);
            $id = (int)$pdo->lastInsertId();

            if ($role === 'student')$pdo->prepare('INSERT INTO students(user_id,student_number,course,year_level) VALUES(?,?,?,?)')->execute([$id,trim($_POST['student_number'] ?? '')?:null,trim($_POST['course'] ?? '')?:null,trim($_POST['year_level'] ?? '')?:null]);

            if ($role === 'staff'){

                $pdo->prepare('INSERT INTO staff(user_id,department_id,position) VALUES(?,?,?)')->execute([$id,(int)($_POST['department_id'] ?? 0)?:null,trim($_POST['position'] ?? '')?:null]);
                foreach ($pdo->query("SELECT id FROM users WHERE role='admin' AND is_active=1")->fetchAll() as $admin)notify($pdo,(int)$admin['id'],'Staff account awaiting approval',$name . ' requested a staff account.');

            }

            log_activity($pdo,$id,'Registered ' . $role . ' account');
            $pdo->commit();

            if ($role === 'staff'){

                flash('Registration received. Your staff account will be available after an administrator approves it.');
                go('auth/login.php');

            }

            flash('Account created. Please sign in.');
            go('auth/login.php');

        }

        catch (PDOException $e){

            if ($pdo->inTransaction())$pdo->rollBack();
            $error = 'That email or student ID may already be registered.';

        }

    }

}

$departments = $pdo->query('SELECT id,name FROM departments ORDER BY name')->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create account | Student Concerns</title>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=url('assets/css/app.css')?>" rel="stylesheet">
<link href="<?=url('assets/css/design.css')?>" rel="stylesheet">
</head>
<body class="register-screen">
<main class="register-layout">
<aside class="register-side">
<a class="landing-brand" href="<?=url()?>">
<span class="brand-mark" style="background:white;color:var(--navy)">
<i class="bi bi-chat-left-text">
</i>
</span>
<span>Student Concerns<small>Southern leyte State University</small>
</span>
</a>
<div class="auth-intro">
<h1>Join the support system</h1>
<p>Create an account to submit concerns, follow progress, or help resolve student cases.</p>
<div class="auth-points">
<span>
<i class="bi bi-check-circle">
</i>Track concerns and updates</span>
<span>
<i class="bi bi-check-circle">
</i>Choose the account role that fits you</span>
<span>
<i class="bi bi-check-circle">
</i>Secure, role-based access</span>
</div>
</div>
<footer>Student Complaint and Concerns Management System</footer>
</aside>
<section class="register-main">
<div class="register-main-inner">
<main class="register-card">
<h1>Create an account</h1>
<p class="muted">Choose your role and enter your details to get started.</p><?php if ($error):?><div class="alert alert-navy"><?=e($error)?></div><?php endif;
?><form method="post" id="register-form">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<div class="mb-3">
<label class="form-label" for="role">Account role</label>
<select class="form-select" name="role" id="role" required>
<option value="student" <?=$selectedRole === 'student'?'selected':''?> <?=$studentRegistrationOpen?'':'disabled'?>>Student<?=$studentRegistrationOpen?'':' (registration closed)'?></option>
<option value="staff" <?=$selectedRole === 'staff'?'selected':''?>>Staff</option>
</select>
<small class="muted" id="role-help"><?=$studentRegistrationOpen?'Students can sign in after registration.': 'Student registration is currently closed.'?></small>
</div>
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Full name</label>
<input class="form-control" name="full_name" value="<?=e($_POST['full_name'] ?? '')?>" required>
</div>
<div class="col-md-6">
<label class="form-label">Email</label>
<input class="form-control" type="email" name="email" value="<?=e($_POST['email'] ?? '')?>" required>
</div>
<div class="col-md-6 student-field">
<label class="form-label">Student ID</label>
<input class="form-control" name="student_number" value="<?=e($_POST['student_number'] ?? '')?>">
</div>
<div class="col-md-6 student-field">
<label class="form-label">Course</label>
<input class="form-control" name="course" value="<?=e($_POST['course'] ?? '')?>">
</div>
<div class="col-md-6 student-field">
<label class="form-label">Year level</label>
<input class="form-control" name="year_level" value="<?=e($_POST['year_level'] ?? '')?>">
</div>
<div class="col-md-6 staff-field">
<label class="form-label">Department</label>
<select class="form-select" name="department_id">
<option value="">Select department</option><?php foreach ($departments as $d):?><option value="<?=$d['id']?>" <?=($_POST['department_id'] ?? '') == $d['id']?'selected':''?>><?=e($d['name'])?></option><?php endforeach;
?></select>
</div>
<div class="col-md-6 staff-field">
<label class="form-label">Position</label>
<input class="form-control" name="position" value="<?=e($_POST['position'] ?? '')?>">
</div>
<div class="col-md-6">
<label class="form-label">Password</label>
<input class="form-control" type="password" name="password" minlength="10" required>
</div>
<div class="col-md-6">
<label class="form-label">Confirm password</label>
<input class="form-control" type="password" name="confirm_password" minlength="10" required>
</div>
<div class="col-12">
<button class="btn btn-navy w-100">Create account</button>
</div>
</div>
</form>
<p class="muted mt-3 mb-0">Staff accounts require administrator approval. The first administrator is created through the setup page.</p>
<p class="register-home">Already registered? <a href="<?=url('auth/login.php')?>">Log in</a>
<br>
<a href="<?=url()?>">Back to home</a>
</p>
</main>
</div>
</section>
</main>
<script>
const role=document.getElementById('role');function updateRoleFields(){const r=role.value;document.querySelectorAll('.student-field').forEach(x=>x.hidden=r!=='student');document.querySelectorAll('.staff-field').forEach(x=>x.hidden=r!=='staff');document.getElementById('role-help').textContent=r==='staff'?'Staff accounts remain pending until an administrator approves them.':'Students can sign in after registration.';}role.addEventListener('change',updateRoleFields);updateRoleFields();
</script>
</body>
</html>
