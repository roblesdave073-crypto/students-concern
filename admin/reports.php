<?php require_once __DIR__ . '/../includes/functions.php';
require_login('admin');
$where = ['c.archived_at IS NULL'];
$params = [];
foreach (['status','category_id','department_id'] as $k)if (!empty($_GET[$k])){

    $where[] = 'c.' . $k . '=?';
    $params[] = $_GET[$k];

}

if (!empty($_GET['from'])){

    $where[] = 'DATE(c.created_at)>=?';
    $params[] = $_GET['from'];

}

if (!empty($_GET['to'])){

    $where[] = 'DATE(c.created_at)<=?';
    $params[] = $_GET['to'];

}

$sql = 'SELECT c.tracking_no,c.title,cat.name category,d.name department,c.status,c.priority,c.created_at FROM complaints c JOIN categories cat ON cat.id=c.category_id JOIN departments d ON d.id=c.department_id WHERE ' . implode(' AND ',$where) . ' ORDER BY c.created_at DESC';
$s = $pdo->prepare($sql);
$s->execute($params);
$rows = $s->fetchAll();
if (isset($_GET['export'])){

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="complaint-report.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Tracking','Title','Category','Department','Status','Priority','Date']);
    foreach ($rows as $r)fputcsv($out,$r);
    fclose($out);
    exit;

}

require_once __DIR__ . '/../includes/layout.php';
page_start('Reports','admin/reports.php');
?><div class="page-heading">
<h1>Complaint reports</h1>
<button class="btn btn-outline-navy" onclick="window.print()">
<i class="bi bi-printer">
</i> Print</button>
</div>
<div class="card mb-4">
<form class="row g-3">
<div class="col-md-2">
<label class="form-label">Status</label>
<select class="form-select" name="status">
<option value="">All</option><?php foreach (statuses() as $st):?><option value="<?=e($st)?>" <?=($_GET['status'] ?? '') === $st?'selected':''?>><?=e($st)?></option><?php endforeach;
?></select>
</div><?php foreach (['category_id' => 'categories','department_id' => 'departments'] as $key => $table):?><div class="col-md-3">
<label class="form-label"><?=e(ucfirst(str_replace('_id','',$key)))?></label>
<select class="form-select" name="<?=$key?>">
<option value="">All</option><?php foreach ($pdo->query("SELECT id,name FROM {$table
}

") as $x):?><option value="<?=$x['id']?>" <?=($_GET[$key] ?? '') == $x['id']?'selected':''?>><?=e($x['name'])?></option><?php endforeach;
?></select>
</div><?php endforeach;
?><div class="col-md-2">
<label class="form-label">From</label>
<input class="form-control" type="date" name="from" value="<?=e($_GET['from'] ?? '')?>">
</div>
<div class="col-md-2">
<label class="form-label">To</label>
<input class="form-control" type="date" name="to" value="<?=e($_GET['to'] ?? '')?>">
</div>
<div class="col-12">
<button class="btn btn-navy">Filter</button> <button class="btn btn-outline-navy" name="export" value="1">Export CSV</button>
</div>
</form>
</div>
<p><?=count($rows)?> complaints</p>
<div class="table-wrap">
<table class="table">
<thead>
<tr><?php foreach (['Tracking','Title','Category','Department','Status','Priority','Date'] as $h):?><th><?=e($h)?></th><?php endforeach;
?></tr>
</thead>
<tbody><?php foreach ($rows as $r):?><tr><?php foreach ($r as $v):?><td><?=e($v)?></td><?php endforeach;
?></tr><?php endforeach;
?></tbody>
</table>
</div><?php page_end();

