<?php $u = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();
    if (isset($_POST['read_all']))$pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$u['id']]);
    else $pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([(int)$_POST['id'],$u['id']]);
    header('Location: ' . url($u['role'] . '/notifications.php'));
    exit;

}

$s = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100');
$s->execute([$u['id']]);
$items = $s->fetchAll();
require_once __DIR__ . '/layout.php';
page_start('Notifications',$u['role'] . '/notifications.php');
?><div class="page-heading">
<h1>Notifications</h1>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<button class="btn btn-outline-navy" name="read_all">Mark all as read</button>
</form>
</div><?php foreach ($items as $n):?><div class="card mb-3">
<div class="d-flex justify-content-between gap-3">
<div>
<strong><?=e($n['title'])?></strong> <?=empty($n['is_read'])?'<span class="badge-status">New</span>':''?><p class="mb-1 mt-2"><?=e($n['message'])?></p>
<small class="muted"><?=e($n['created_at'])?></small>
</div><?php if ($n['complaint_id']):?><a class="btn btn-outline-navy btn-sm" href="<?=url($u['role'] . '/complaint_details.php?id=' . $n['complaint_id'])?>">Open</a><?php endif;
?></div><?php if (empty($n['is_read'])):?><form method="post" class="mt-2">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<input type="hidden" name="id" value="<?=$n['id']?>">
<button class="btn btn-outline-navy btn-sm">Mark read</button>
</form><?php endif;
?></div><?php endforeach;
?><?php if (!$items):?><div class="card empty">No notifications.</div><?php endif;
?><?php page_end();

