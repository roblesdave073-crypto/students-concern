<?php $kind = basename($_SERVER['SCRIPT_NAME'],'.php');
$defs = ['manage_categories' => ['categories',
'Category',
'name'],
'manage_departments' => ['departments',
'Department',
'name']];
[$table,
$label,
$field] = $defs[$kind];
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    verify_csrf();
    if (isset($_POST['delete'])){

        if ($table === 'categories')$pdo->prepare('UPDATE categories SET is_active=0 WHERE id=?')->execute([(int)$_POST['delete']]);
        else $pdo->prepare('DELETE FROM departments WHERE id=?')->execute([(int)$_POST['delete']]);
        flash($label . ' removed.');

    }

    else{

        $v = trim($_POST['name'] ?? '');
        if ($v !== ''){

            if (!empty($_POST['id']))$pdo->prepare("UPDATE {$table
            }

            SET {$field
            }

            =? WHERE id=?")->execute([$v,(int)$_POST['id']]);
            else $pdo->prepare("INSERT INTO {$table
            }

            ({$field
            }

            ) VALUES(?)")->execute([$v]);
            flash($label . ' saved.');

        }

    }

    header('Location: ' . url('admin/' . $kind . '.php'));
    exit;

}

$rows = $pdo->query("SELECT * FROM {$table
}

ORDER BY {$field
}

")->fetchAll();
require_once __DIR__ . '/layout.php';
page_start('Manage ' . $label . 's','admin/' . $kind . '.php');
?><div class="page-heading">
<h1>Manage <?=e($label)?>s</h1>
</div>
<div class="row g-4">
<div class="col-lg-5">
<div class="card">
<h2>Add <?=e($label)?></h2>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<input class="form-control mb-3" name="name" required maxlength="120" placeholder="<?=e($label)?> name">
<button class="btn btn-navy">Save</button>
</form>
</div>
</div>
<div class="col-lg-7">
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>Name</th>
<th>Action</th>
</tr>
</thead>
<tbody><?php foreach ($rows as $r):?><tr>
<td><?=e($r[$field])?></td>
<td>
<form method="post" data-confirm="Remove this <?=e($label)?>?">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<button class="btn btn-outline-navy btn-sm" name="delete" value="<?=$r['id']?>">Remove</button>
</form>
</td>
</tr><?php endforeach;
?></tbody>
</table>
</div>
</div>
</div><?php page_end();

