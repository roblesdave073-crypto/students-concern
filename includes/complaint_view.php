<?php
/** @var PDO $pdo Database connection initialized by the application bootstrap. */
global $pdo;

$complaintId = (int) ($_GET['id'] ?? 0);
$complaint = complaint_access($pdo, $complaintId);
$complaintReopeningEnabled = app_setting($pdo, 'complaint_reopening_enabled') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $user = current_user();

    if ($user['role'] === 'student') {
        if ($complaintReopeningEnabled && $complaint['status'] === 'Resolved') {
            $pdo->prepare(
                "UPDATE complaints SET status = 'Reopened' WHERE id = ?"
            )->execute([$complaintId]);

            $pdo->prepare(
                'INSERT INTO complaint_updates '
                . '(complaint_id, user_id, previous_status, new_status, message) '
                . 'VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $complaintId,
                $user['id'],
                'Resolved',
                'Reopened',
                'Student requested that this concern be reopened.',
            ]);

            $administrators = $pdo
                ->query("SELECT id FROM users WHERE role = 'admin'")
                ->fetchAll();

            foreach ($administrators as $administrator) {
                notify(
                    $pdo,
                    (int) $administrator['id'],
                    'Complaint reopened',
                    $complaint['tracking_no'] . ' has been reopened.',
                    $complaintId
                );
            }

            flash('Reopen request submitted.');
        }

        header('Location: ' . url('student/complaint_details.php?id=' . $complaintId));
        exit;
    }

    $message = trim($_POST['message'] ?? '');
    $newStatus = $_POST['status'] ?? $complaint['status'];
    $isInternal = $user['role'] === 'admin' && isset($_POST['internal']) ? 1 : 0;
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');
    $resolutionDetails = trim($_POST['resolution_details'] ?? '');

    $requiresReason = $newStatus === 'Rejected' && $rejectionReason === '';
    $requiresResolution = $newStatus === 'Resolved' && $resolutionDetails === '';

    if (!in_array($newStatus, statuses(), true) || $requiresReason || $requiresResolution) {
        flash(
            'A rejection reason or resolution detail is required for this status.',
            'error'
        );
    } else {
        $pdo->beginTransaction();

        $pdo->prepare(
            'UPDATE complaints '
            . 'SET status = ?, rejection_reason = ?, resolution_details = ? '
            . 'WHERE id = ?'
        )->execute([
            $newStatus,
            $newStatus === 'Rejected' ? $rejectionReason : $complaint['rejection_reason'],
            $newStatus === 'Resolved' ? $resolutionDetails : $complaint['resolution_details'],
            $complaintId,
        ]);

        if ($message !== '' || $newStatus !== $complaint['status']) {
            $pdo->prepare(
                'INSERT INTO complaint_updates '
                . '(complaint_id, user_id, previous_status, new_status, message, is_internal) '
                . 'VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $complaintId,
                $user['id'],
                $complaint['status'],
                $newStatus,
                $message !== '' ? $message : 'Status updated to ' . $newStatus . '.',
                $isInternal,
            ]);
        }

        notify(
            $pdo,
            (int) $complaint['student_id'],
            'Complaint update',
            $complaint['tracking_no'] . ' is now ' . $newStatus . '.',
            $complaintId
        );
        log_activity(
            $pdo,
            (int) $user['id'],
            'Updated complaint ' . $complaint['tracking_no'] . ' to ' . $newStatus,
            $complaintId
        );

        $pdo->commit();
        flash('Complaint updated.');

        header(
            'Location: ' . url($user['role'] . '/complaint_details.php?id=' . $complaintId)
        );
        exit;
    }
}

$updatesQuery = $pdo->prepare(
    'SELECT updates.*, users.full_name '
    . 'FROM complaint_updates AS updates '
    . 'JOIN users ON users.id = updates.user_id '
    . 'WHERE updates.complaint_id = ? AND (updates.is_internal = 0 OR ? = 1) '
    . 'ORDER BY updates.created_at DESC'
);
$updatesQuery->execute([
    $complaintId,
    current_user()['role'] === 'admin' ? 1 : 0,
]);
$updates = $updatesQuery->fetchAll();

require_once __DIR__ . '/layout.php';
page_start($complaint['tracking_no']);
?>

<div class="page-heading">
    <div>
        <h1><?= e($complaint['title']) ?></h1>
        <p class="muted mb-0">
            <?= e($complaint['tracking_no']) ?> ·
            <?= e($complaint['category']) ?> ·
            <?= e($complaint['department']) ?>
        </p>
    </div>
    <span class="badge-status"><?= e($complaint['status']) ?></span>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="card">
            <h2>Concern details</h2>
            <p><?= nl2br(e($complaint['description'])) ?></p>
            <p class="muted mb-0">
                Priority: <?= e($complaint['priority']) ?> ·
                Submitted <?= e($complaint['created_at']) ?>
            </p>

            <?php if ($complaint['status'] === 'Resolved'): ?>
                <hr>
                <h2>Resolution</h2>
                <p><?= nl2br(e($complaint['resolution_details'])) ?></p>
            <?php endif; ?>
        </section>

        <section class="card mt-4">
            <h2>History</h2>

            <?php foreach ($updates as $update): ?>
                <article class="py-3 border-bottom">
                    <strong><?= e($update['new_status'] ?: 'Update') ?></strong>
                    <span class="muted">
                        · <?= e($update['full_name']) ?> · <?= e($update['created_at']) ?>
                    </span>
                    <div><?= nl2br(e($update['message'])) ?></div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>

    <?php if (current_user()['role'] === 'student'): ?>
        <div class="col-lg-5">
            <?php if ($complaint['status'] === 'Resolved'): ?>
                <section class="card">
                    <h2>Next steps</h2>
                    <?php if ($complaintReopeningEnabled): ?><p>Still need help? Request to reopen this concern.</p><?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <?php if ($complaintReopeningEnabled): ?><button class="btn btn-navy" type="submit">Request reopening</button><?php endif; ?>
                        <a
                            class="btn btn-outline-navy"
                            href="<?= url('student/feedback.php?id=' . $complaintId) ?>"
                        >Leave feedback</a>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="col-lg-5">
            <section class="card">
                <h2>Update complaint</h2>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

                    <label class="form-label" for="status">Status</label>
                    <select class="form-select mb-3" id="status" name="status">
                        <?php foreach (statuses() as $status): ?>
                            <option <?= $status === $complaint['status'] ? 'selected' : '' ?>>
                                <?= e($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label" for="message">Response or note</label>
                    <textarea
                        class="form-control mb-3"
                        id="message"
                        name="message"
                        rows="4"
                    ></textarea>

                    <label class="form-label" for="rejection-reason">
                        Rejection reason (required if rejected)
                    </label>
                    <textarea
                        class="form-control mb-3"
                        id="rejection-reason"
                        name="rejection_reason"
                    ><?= e($complaint['rejection_reason']) ?></textarea>

                    <label class="form-label" for="resolution-details">
                        Resolution details (required if resolved)
                    </label>
                    <textarea
                        class="form-control mb-3"
                        id="resolution-details"
                        name="resolution_details"
                    ><?= e($complaint['resolution_details']) ?></textarea>

                    <?php if (current_user()['role'] === 'admin'): ?>
                        <label class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="internal">
                            <span class="form-check-label">Internal note</span>
                        </label>
                    <?php endif; ?>

                    <button class="btn btn-navy" type="submit">Save update</button>
                </form>
            </section>
        </div>
    <?php endif; ?>
</div>

<?php page_end(); ?>
