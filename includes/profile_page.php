<?php
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (strlen($name) > 1 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $statement = $pdo->prepare(
                'UPDATE users SET full_name = ?, email = ? WHERE id = ?'
            );
            $statement->execute([$name, $email, $user['id']]);

            $_SESSION['user']['full_name'] = $name;
            $_SESSION['user']['email'] = $email;

            flash('Profile updated.');
        } catch (PDOException $exception) {
            flash('Email is already in use.', 'error');
        }
    }

    header('Location: ' . url($user['role'] . '/profile.php'));
    exit;
}

require_once __DIR__ . '/layout.php';
page_start('Profile', $user['role'] . '/profile.php');
?>

<div class="card profile-card">
    <h1 class="mb-4">Profile</h1>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

        <div class="mb-3">
            <label class="form-label" for="full-name">Name</label>
            <input
                class="form-control"
                id="full-name"
                name="full_name"
                value="<?= e($user['full_name']) ?>"
                autocomplete="name"
                required
            >
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-control"
                id="email"
                type="email"
                name="email"
                value="<?= e($user['email']) ?>"
                autocomplete="email"
                required
            >
        </div>

        <button class="btn btn-navy" type="submit">Save profile</button>
    </form>
</div>

<?php page_end(); ?>
