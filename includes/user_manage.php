<?php
$role = basename($_SERVER['SCRIPT_NAME']) === 'manage_staff.php'?'staff':'student';
$label = ucfirst($role);

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();

    if (isset($_POST['approve']) && $role === 'staff'){

        $id = (int)$_POST['approve'];
        $pdo->prepare("UPDATE users SET is_active=1 WHERE id=? AND role='staff'")->execute([$id]);
        notify($pdo,$id,'Staff account approved','Your account is approved. You can now sign in.');
        log_activity($pdo,(int)current_user()['id'],'Approved staff account ' . $id);
        flash('Staff account approved.');

    }

    elseif (isset($_POST['deactivate'])){

        $pdo->prepare('UPDATE users SET is_active=0 WHERE id=? AND role=?')->execute([(int)$_POST['deactivate'],$role]);
        flash($label . ' account deactivated.');

    }

    else{

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pw = $_POST['password'] ?? '';
        if ($name !== '' && filter_var($email,FILTER_VALIDATE_EMAIL) && strlen($pw) >= 10){

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users(full_name,email,password_hash,role) VALUES(?,?,?,?)');
            $stmt->execute([$name,$email,password_hash($pw,PASSWORD_DEFAULT),$role]);
            $id = (int)$pdo->lastInsertId();
            if ($role === 'student')$pdo->prepare('INSERT INTO students(user_id,student_number,course,year_level) VALUES(?,?,?,?)')->execute([$id,trim($_POST['student_number'] ?? '')?:null,trim($_POST['course'] ?? '')?:null,trim($_POST['year_level'] ?? '')?:null]);
            else $pdo->prepare('INSERT INTO staff(user_id,department_id,position) VALUES(?,?,?)')->execute([$id,(int)($_POST['department_id'] ?? 0)?:null,trim($_POST['position'] ?? '')?:null]);
            $pdo->commit();
            flash($label . ' account created.');

        }

        else flash('Enter a valid name, email, and password of at least 10 characters.','error');

    }

    header('Location: ' . url('admin/manage_' . $role . 's.php'));
    exit;

}

$s = $pdo->prepare("SELECT u.*,s.student_number,s.course,s.year_level,st.position FROM users u LEFT JOIN students s ON s.user_id=u.id LEFT JOIN staff st ON st.user_id=u.id WHERE u.role=? ORDER BY u.created_at DESC");
$s->execute([$role]);
$rows = $s->fetchAll();
require_once __DIR__ . '/layout.php';
page_start('Manage ' . $label . 's','admin/manage_' . $role . 's.php');

?><div class="page-heading">
<h1>Manage <?=e($label)?>s</h1>
</div>
<div class="card mb-4">
<h2>Add <?=e($label)?></h2>
<form method="post" class="row g-3">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<div class="col-md-3">
<input class="form-control" name="name" placeholder="Full name" required>
</div>
<div class="col-md-3">
<input class="form-control" type="email" name="email" placeholder="Email" required>
</div>
<div class="col-md-3">
<input class="form-control" type="password" name="password" placeholder="Temporary password" minlength="10" required>
</div><?php if ($role === 'student'):?><div class="col-md-3">
<input class="form-control" name="student_number" placeholder="Student number">
</div>
<div class="col-md-3">
<input class="form-control" name="course" placeholder="Course">
</div>
<div class="col-md-3">
<input class="form-control" name="year_level" placeholder="Year level">
</div><?php else:?><div class="col-md-3">
<input class="form-control" name="position" placeholder="Position">
</div>
<div class="col-md-3">
<select class="form-select" name="department_id">
<option value="">Department</option><?php foreach ($pdo->query('SELECT * FROM departments') as $d):?><option value="<?=$d['id']?>"><?=e($d['name'])?></option><?php endforeach;
?></select>
</div><?php endif;
?><div class="col-12">
<button class="btn btn-navy">Create account</button>
</div>
</form>
</div>
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Additional info</th>
<th>State</th>
<th>Action</th>
</tr>
</thead>
<tbody><?php foreach ($rows as $r):?><tr>
<td><?=e($r['full_name'])?></td>
<td><?=e($r['email'])?></td>
<td><?= e($r['student_number'] ?? $r['position'] ?? '—') ?></td>
<td><?=$r['is_active']?'Active':($role === 'staff'?'Pending approval':'Inactive')?></td>
<td><?php if (!$r['is_active'] && $role === 'staff'):?><form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<button class="btn btn-navy btn-sm" name="approve" value="<?=$r['id']?>">Approve</button>
</form><?php elseif ($r['is_active']):?><form method="post" data-confirm="Deactivate this account?">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<button class="btn btn-outline-navy btn-sm" name="deactivate" value="<?=$r['id']?>">Deactivate</button>
</form><?php endif;
?></td>
</tr><?php endforeach;
?></tbody>
</table>
</div><?php page_end();

