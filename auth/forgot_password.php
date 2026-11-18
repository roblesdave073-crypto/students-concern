<?php require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
page_start('Password recovery');
?><div class="card auth-card">
<h1>Password recovery</h1>
<p class="muted mt-3">Ask your school administrator to reset your password. This installation does not have an email delivery service configured.</p>
<a class="btn btn-navy" href="<?=url('auth/login.php')?>">Return to sign in</a>
</div><?php page_end();
?>
