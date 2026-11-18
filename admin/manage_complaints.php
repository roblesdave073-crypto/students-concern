<?php require_once __DIR__ . '/../includes/functions.php';
require_login('admin');
require_once __DIR__ . '/../includes/layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();
    $id = (int)$_POST['id'];
    $staff = (int)$_POST['staff_id'];
    $s = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='staff' AND is_active=1");
    $s->execute([$staff]);
    if ($s->fetchColumn()){

        $pdo->prepare('INSERT IGNORE INTO complaint_assignments(complaint_id,staff_id,assigned_by) VALUES(?,?,?)')->execute([$id,$staff,current_user()['id']]);
        $pdo->prepare("UPDATE complaints SET status='Assigned' WHERE id=? AND status IN ('Pending','Under Review')")->execute([$id]);
        notify($pdo,$staff,'Complaint assigned','A complaint has been assigned to you.',$id);
        $c = $pdo->prepare('SELECT student_id,tracking_no FROM complaints WHERE id=?');
        $c->execute([$id]);
        $x = $c->fetch();
        notify($pdo,(int)$x['student_id'],'Complaint assigned',$x['tracking_no'] . ' has been assigned for handling.',$id);
        flash('Complaint assigned.');

    }

    go('admin/manage_complaints.php');

}

$rows = $pdo->query('SELECT c.id,c.tracking_no,c.title,c.status,c.created_at,cat.name category FROM complaints c JOIN categories cat ON cat.id=c.category_id WHERE c.archived_at IS NULL ORDER BY c.created_at DESC')->fetchAll();
$staff = $pdo->query("SELECT id,full_name FROM users WHERE role='staff' AND is_active=1 ORDER BY full_name")->fetchAll();
page_start('Manage complaints','admin/manage_complaints.php');
?><div class="page-heading">
<h1>Complaints</h1>
</div>
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>Tracking</th>
<th>Title</th>
<th>Category</th>
<th>Status</th>
<th>Submitted</th>
<th>Assign staff</th>
<th>
</th>
</tr>
</thead>
<tbody><?php foreach ($rows as $r):?><tr>
<td><?=e($r['tracking_no'])?></td>
<td><?=e($r['title'])?></td>
<td><?=e($r['category'])?></td>
<td>
<span class="badge-status"><?=e($r['status'])?></span>
</td>
<td><?=e($r['created_at'])?></td>
<td>
<form method="post" class="d-flex gap-1">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<input type="hidden" name="id" value="<?=$r['id']?>">
<select name="staff_id" class="form-select form-select-sm" required>
<option value="">Staff</option><?php foreach ($staff as $s):?><option value="<?=$s['id']?>"><?=e($s['full_name'])?></option><?php endforeach;
?></select>
<button class="btn btn-navy btn-sm">Assign</button>
</form>
</td>
<td>
<a class="btn btn-outline-navy btn-sm" href="<?=url('admin/complaint_details.php?id=' . $r['id'])?>">Open</a>
</td>
</tr><?php endforeach;
?></tbody>
</table>
</div><?php page_end();

