<?php require_once __DIR__ . '/../includes/functions.php';
require_login('admin');
require_once __DIR__ . '/../includes/layout.php';
$rows = $pdo->query('SELECT l.*,u.full_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 200')->fetchAll();
page_start('Activity logs','admin/activity_logs.php');
?><h1 class="mb-4">Activity logs</h1>
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>Time</th>
<th>User</th>
<th>Action</th>
<th>IP</th>
</tr>
</thead>
<tbody><?php foreach ($rows as $r):?><tr>
<td><?=e($r['created_at'])?></td>
<td><?=e($r['full_name'] ?? 'Deleted account')?></td>
<td><?=e($r['action'])?></td>
<td><?=e($r['ip_address'])?></td>
</tr><?php endforeach;
?></tbody>
</table>
</div><?php page_end();

