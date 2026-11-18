<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $basePath = getenv('APP_BASE_PATH');

    if ($basePath === false) {
        $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $scriptDirectory = rtrim($scriptDirectory, '/');

        if (preg_match('~/(admin|auth|staff|student)$~', $scriptDirectory)) {
            $scriptDirectory = dirname($scriptDirectory);
        }

        $basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : $scriptDirectory;
    }

    $basePath = '/' . trim((string) $basePath, '/');
    $basePath = rtrim($basePath, '/');

    return $basePath . '/' . ltrim($path, '/');
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    if (!hash_equals(csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Invalid form token. Please go back and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(?string $role = null): void
{
    $user = current_user();

    if (!$user) {
        header('Location: ' . url('auth/login.php'));
        exit;
    }

    if ($role !== null && $user['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function go(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function log_activity(PDO $pdo, int $userId, string $action, ?int $complaintId = null): void
{
    $statement = $pdo->prepare(
        'INSERT INTO activity_logs (user_id, action, complaint_id, ip_address) '
        . 'VALUES (?, ?, ?, ?)'
    );
    $statement->execute([
        $userId,
        $action,
        $complaintId,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function notify(
    PDO $pdo,
    int $userId,
    string $title,
    string $body,
    ?int $complaintId = null
): void {
    $statement = $pdo->prepare(
        'INSERT INTO notifications (user_id, complaint_id, title, message) '
        . 'VALUES (?, ?, ?, ?)'
    );
    $statement->execute([$userId, $complaintId, $title, $body]);
}

function statuses(): array
{
    return [
        'Pending',
        'Under Review',
        'Assigned',
        'In Progress',
        'Resolved',
        'Rejected',
        'Reopened',
    ];
}

function app_setting(PDO $pdo, string $key, string $default = '1'): string
{
    try {
        $statement = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $statement->execute([$key]);
        $value = $statement->fetchColumn();

        return $value === false ? $default : (string) $value;
    } catch (PDOException) {
        return $default;
    }
}

function complaint_access(PDO $pdo, int $complaintId): array
{
    $statement = $pdo->prepare(
        'SELECT complaints.*, categories.name AS category, '
        . 'departments.name AS department, users.full_name AS student_name '
        . 'FROM complaints '
        . 'JOIN categories ON categories.id = complaints.category_id '
        . 'JOIN departments ON departments.id = complaints.department_id '
        . 'JOIN users ON users.id = complaints.student_id '
        . 'WHERE complaints.id = ? AND complaints.archived_at IS NULL'
    );
    $statement->execute([$complaintId]);

    $complaint = $statement->fetch();
    $user = current_user();

    $isUnauthorizedStudent = $complaint
        && $user
        && $user['role'] === 'student'
        && (int) $complaint['student_id'] !== (int) $user['id'];
    $isUnassignedStaff = $complaint
        && $user
        && $user['role'] === 'staff'
        && !is_assigned($pdo, $complaintId, (int) $user['id']);

    if (!$complaint || !$user || $isUnauthorizedStudent || $isUnassignedStaff) {
        http_response_code(404);
        exit('Complaint not found.');
    }

    return $complaint;
}

function is_assigned(PDO $pdo, int $complaintId, int $staffId): bool
{
    $statement = $pdo->prepare(
        'SELECT 1 FROM complaint_assignments WHERE complaint_id = ? AND staff_id = ?'
    );
    $statement->execute([$complaintId, $staffId]);

    return (bool) $statement->fetchColumn();
}
