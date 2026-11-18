<?php require_once __DIR__ . '/../includes/functions.php';
require_login('admin');
require_once __DIR__ . '/../includes/layout.php';

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS app_settings ('
    . 'setting_key VARCHAR(100) PRIMARY KEY, '
    . 'setting_value TEXT NOT NULL, '
    . 'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP '
    . 'ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $settings = [
        'student_registration_open' => isset($_POST['student_registration_open']) ? '1' : '0',
        'anonymous_reports_enabled' => isset($_POST['anonymous_reports_enabled']) ? '1' : '0',
        'complaint_reopening_enabled' => isset($_POST['complaint_reopening_enabled']) ? '1' : '0',
        'default_complaint_priority' => in_array($_POST['default_complaint_priority'] ?? '', ['Low', 'Medium', 'High', 'Urgent'], true)
            ? $_POST['default_complaint_priority']
            : 'Medium',
    ];
    $save = $pdo->prepare(
        'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) '
        . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($settings as $key => $value) {
        $save->execute([$key, $value]);
    }
    log_activity($pdo, (int) current_user()['id'], 'Updated system settings');
    flash('System settings saved.');
    go('admin/settings.php');
}

$studentRegistrationOpen = app_setting($pdo, 'student_registration_open') === '1';
$anonymousReportsEnabled = app_setting($pdo, 'anonymous_reports_enabled') === '1';
$complaintReopeningEnabled = app_setting($pdo, 'complaint_reopening_enabled') === '1';
$defaultComplaintPriority = app_setting($pdo, 'default_complaint_priority', 'Medium');
if (!in_array($defaultComplaintPriority, ['Low', 'Medium', 'High', 'Urgent'], true)) {
    $defaultComplaintPriority = 'Medium';
}
page_start('Settings','admin/settings.php');
?><div class="page-heading">
<div><h1>Settings</h1><p class="muted mb-0">Manage accessibility, account access, and complaint workflow.</p></div>
</div>
<form method="post" class="settings-form">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<section class="card settings-group">
<div class="settings-group-heading"><span class="settings-icon"><i class="bi bi-universal-access"></i></span><div><h2>Accessibility</h2><p class="muted mb-0">Personal display preference for this browser.</p></div></div>
<div class="settings-row"><div><strong>Text size</strong><p class="muted mb-0">Adjust text throughout the admin portal.</p></div><label for="text-size" class="settings-control"><span class="visually-hidden">Text size</span><select class="form-select" id="text-size"><option value="normal">Default</option><option value="large">Large</option><option value="larger">Extra large</option></select></label></div>
</section>
<section class="card settings-group">
<div class="settings-group-heading"><span class="settings-icon"><i class="bi bi-shield-lock"></i></span><div><h2>Accounts and privacy</h2><p class="muted mb-0">Choose how students access the system and submit concerns.</p></div></div>
<div class="settings-row"><div><strong>Student registration</strong><p class="muted mb-0">Allow students to create accounts from the registration page.</p></div><label class="settings-toggle"><input type="checkbox" name="student_registration_open" value="1" <?=$studentRegistrationOpen?'checked':''?>><span class="settings-switch" aria-hidden="true"></span><span class="visually-hidden">Allow student self-registration</span></label></div>
<div class="settings-row"><div><strong>Anonymous complaints</strong><p class="muted mb-0">Let students hide their identity when submitting a concern.</p></div><label class="settings-toggle"><input type="checkbox" name="anonymous_reports_enabled" value="1" <?=$anonymousReportsEnabled?'checked':''?>><span class="settings-switch" aria-hidden="true"></span><span class="visually-hidden">Allow anonymous complaints</span></label></div>
</section>
<section class="card settings-group">
<div class="settings-group-heading"><span class="settings-icon"><i class="bi bi-clipboard-check"></i></span><div><h2>Complaint workflow</h2><p class="muted mb-0">Set the default handling options for new and resolved concerns.</p></div></div>
<div class="settings-row"><div><strong>Default priority</strong><p class="muted mb-0">Priority assigned automatically to new complaints.</p></div><label for="default-priority" class="settings-control"><span class="visually-hidden">Default complaint priority</span><select class="form-select" id="default-priority" name="default_complaint_priority"><?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $priority): ?><option value="<?=e($priority)?>" <?=$defaultComplaintPriority === $priority ? 'selected' : ''?>><?=e($priority)?></option><?php endforeach; ?></select></label></div>
<div class="settings-row"><div><strong>Complaint reopening</strong><p class="muted mb-0">Allow students to request reopening a resolved complaint.</p></div><label class="settings-toggle"><input type="checkbox" name="complaint_reopening_enabled" value="1" <?=$complaintReopeningEnabled?'checked':''?>><span class="settings-switch" aria-hidden="true"></span><span class="visually-hidden">Allow complaint reopening</span></label></div>
</section>
<div class="settings-actions"><button class="btn btn-navy" type="submit"><i class="bi bi-check2"></i> Save settings</button><a class="btn btn-outline-navy" href="<?=url('admin/dashboard.php')?>">Cancel</a></div>
</form>
<?php page_end();

