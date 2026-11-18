<?php
function page_start(string $title, string $active = ''): void
{
    global $pdo;
    $user = current_user();
    $notificationItems = [];
    $unreadNotificationCount = 0;
    if ($user) {
        $notificationQuery = $pdo->prepare(
            'SELECT id, complaint_id, title, message, is_read, created_at '
            . 'FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100'
        );
        $notificationQuery->execute([$user['id']]);
        $notificationItems = $notificationQuery->fetchAll();
        foreach ($notificationItems as $notificationItem) {
            if (empty($notificationItem['is_read'])) {
                $unreadNotificationCount++;
            }
        }
    }
    $flashMessage = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | Student Concerns</title>
        <link
            href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        >
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
            rel="stylesheet"
        >
        <link href="<?= url('assets/css/app.css?v=3') ?>" rel="stylesheet">
        <link href="<?= url('assets/css/design.css') ?>" rel="stylesheet">
        <?php if ($user && $user['role'] === 'admin'): ?>
            <link href="<?= url('assets/css/admin.css?v=2') ?>" rel="stylesheet">
        <?php endif; ?>
    </head>
    <body>
    <?php if ($user): ?>
        <aside class="sidebar">
            <a class="brand" href="<?= url() ?>">
                <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                <span>
                    Student Concerns
                    <small class="sidebar-school">Southern leyte State University</small>
                </span>
            </a>
            <button class="sidebar-close" type="button" aria-label="Close navigation">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>

            <div class="user-label">
                <?= e($user['full_name']) ?>
                <small><?= e(ucfirst($user['role'])) ?></small>
            </div>

            <nav id="main-navigation" aria-label="Main navigation">
                <?php
                $links = match ($user['role']) {
                    'admin' => [
                        ['admin/dashboard.php', 'Dashboard', 'grid'],
                        ['admin/manage_complaints.php', 'Complaints', 'file-earmark-text'],
                        ['admin/manage_students.php', 'Students', 'people'],
                        ['admin/manage_staff.php', 'Staff', 'person-check'],
                        ['admin/manage_departments.php', 'Departments', 'building'],
                        ['admin/manage_categories.php', 'Categories', 'grid'],
                        ['admin/reports.php', 'Reports', 'bar-chart'],
                        ['admin/activity_logs.php', 'Activity logs', 'clock-history'],
                        ['admin/settings.php', 'Settings', 'gear'],
                    ],
                    'staff' => [
                        ['staff/dashboard.php', 'Dashboard', 'grid'],
                        ['staff/assigned_complaints.php', 'Assigned complaints', 'inbox'],
                        ['staff/profile.php', 'Profile', 'person'],
                    ],
                    default => [
                        ['student/dashboard.php', 'Dashboard', 'grid'],
                        ['student/submit_complaint.php', 'Submit a concern', 'plus-lg'],
                        ['student/my_complaints.php', 'My complaints', 'chat-left-text'],
                        ['student/profile.php', 'Profile', 'person'],
                    ],
                };
                ?>

                <?php foreach ($links as [$href, $label, $icon]): ?>
                    <a
                        class="nav-item <?= $active === $href ? 'active' : '' ?>"
                        href="<?= url($href) ?>"
                    >
                        <i class="bi bi-<?= e($icon) ?>" aria-hidden="true"></i>
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>

                <a class="nav-item logout" href="<?= url('auth/logout.php') ?>">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    Log out
                </a>
            </nav>
        </aside>

        <div class="sidebar-backdrop" aria-hidden="true"></div>
        <main class="main-shell">
            <header class="topbar">
                <button
                    class="menu-toggle"
                    type="button"
                    aria-label="Open navigation"
                    aria-expanded="false"
                    aria-controls="main-navigation"
                >
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <span class="mobile-brand">Student Concerns</span>
                <span class="dashboard-head">
                    <?= e($title) ?>
                    <small><?= e(ucfirst($user['role'])) ?></small>
                </span>
                <div class="notification-menu">
                    <button
                        class="notification-toggle"
                        type="button"
                        aria-label="Notifications<?= $unreadNotificationCount ? ', ' . $unreadNotificationCount . ' unread' : '' ?>"
                        aria-expanded="false"
                        aria-controls="notification-dropdown"
                    >
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <?php if ($unreadNotificationCount): ?>
                            <span class="notification-count"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span>
                        <?php endif; ?>
                    </button>
                    <section class="notification-dropdown" id="notification-dropdown" aria-label="Recent notifications" hidden>
                        <div class="notification-dropdown-heading">
                            <strong>Notifications</strong>
                            <?php if ($unreadNotificationCount): ?>
                                <span><?= $unreadNotificationCount ?> new</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-list">
                            <?php if (!$notificationItems): ?>
                                <p class="notification-empty">No notifications yet.</p>
                            <?php else: ?>
                                <?php foreach ($notificationItems as $notificationItem): ?>
                                    <?php $notificationHref = !empty($notificationItem['complaint_id'])
                                        ? url($user['role'] . '/complaint_details.php?id=' . (int) $notificationItem['complaint_id'])
                                        : url($user['role'] . '/notifications.php'); ?>
                                    <a class="notification-entry <?= empty($notificationItem['is_read']) ? 'unread' : '' ?>" href="<?= e($notificationHref) ?>">
                                        <span class="notification-entry-title">
                                            <?= e($notificationItem['title']) ?>
                                            <?php if (empty($notificationItem['is_read'])): ?><span class="notification-unread-dot" aria-label="Unread"></span><?php endif; ?>
                                        </span>
                                        <span class="notification-entry-message"><?= e($notificationItem['message']) ?></span>
                                        <time datetime="<?= e(date(DATE_ATOM, strtotime($notificationItem['created_at']))) ?>"><?= e($notificationItem['created_at']) ?></time>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a class="notification-view-all" href="<?= url($user['role'] . '/notifications.php') ?>">View all notifications</a>
                    </section>
                </div>
            </header>
            <section class="content">
    <?php else: ?>
        <main class="public-shell">
            <header class="public-nav">
                <a class="brand" href="<?= url() ?>">Student Concerns</a>
                <span class="muted">Southern leyte State University</span>
                <a class="btn btn-outline-navy" href="<?= url('auth/login.php') ?>">Sign in</a>
            </header>
            <section class="public-content">
    <?php endif; ?>

    <?php if ($flashMessage): ?>
        <div class="alert alert-navy" role="status">
            <?= e($flashMessage['message']) ?>
        </div>
    <?php endif; ?>
    <?php
}

function page_end(): void
{
    ?>
            </section>
            <footer class="footer">
                Student Complaint and Concerns Management System ·
                Southern leyte State University
            </footer>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= url('assets/js/app.js?v=3') ?>"></script>
    </body>
    </html>
    <?php
}
