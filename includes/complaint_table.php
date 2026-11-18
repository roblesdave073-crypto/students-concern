<div class="table-wrap">
<table class="table align-middle">
<thead>
<tr>
<th>Tracking no.</th>
<th>Title</th>
<th>Category</th>
<th>Status</th>
<th>Submitted</th>
<th>
</th>
</tr>
</thead>
<tbody><?php if (empty($rows)):?><tr>
<td colspan="6" class="empty">No complaints yet.</td>
</tr><?php else:foreach ($rows as $r):?><tr>
<td><?=e($r['tracking_no'])?></td>
<td><?=e($r['title'])?></td>
<td><?= e($r['category'] ?? '—') ?></td>
<td>
<span class="badge-status"><?=e($r['status'])?></span>
</td>
<td><?=e(date('M j, Y',strtotime($r['created_at'])))?></td>
<td>
<a class="btn btn-outline-navy btn-sm" href="<?=url((current_user()['role'] === 'admin'?'admin':current_user()['role']) . '/complaint_details.php?id=' . $r['id'])?>">View</a>
</td>
</tr><?php endforeach;
endif;
?></tbody>
</table>
</div>
