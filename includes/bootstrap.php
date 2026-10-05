<?php
/**
 * Loaded at the top of every page: DB, session, and shared helpers.
 */
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/validation.php';

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Never show raw exceptions to users; log them instead.
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    abort(500, 'Something went wrong. Please try again.');
});

// ---------------------------------------------------------------------------
// Output / navigation helpers
// ---------------------------------------------------------------------------
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function abort(int $status, string $message): void
{
    http_response_code($status);
    $pageTitle = (string) $status;
    require __DIR__ . '/header.php';
    echo '<div class="card"><h1>' . $status . '</h1><p>' . e($message) . '</p>'
        . '<p><a href="index.php">Back to feed</a></p></div>';
    require __DIR__ . '/footer.php';
    exit;
}

function is_post_request(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function format_datetime(string $timestamp): string
{
    return date('M j, Y \a\t g:i A', strtotime($timestamp));
}

/** Read a positive integer id from an input array, or null if invalid. */
function int_param(array $source, string $key): ?int
{
    $id = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

// ---------------------------------------------------------------------------
// Flash messages (survive exactly one redirect)
// ---------------------------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---------------------------------------------------------------------------
// CSRF protection for every state-changing form
// ---------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        abort(403,'Your session expired. Please go back, refresh the page, and try again.');
    }
}

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user_name(): string
{
    return $_SESSION['user_name'] ?? '';
}

function is_authenticated(): bool
{
    return current_user_id() !== null;
}

/** Authenticated-only pages: send guests to the login page. */
function require_auth(): int
{
    if (!is_authenticated()) {
        flash('info', 'Please log in to continue.');
        redirect('login.php');
    }
    return current_user_id();
}

/** Guest-only pages (login/register): send signed-in users to the feed. */
function require_guest(): void
{
    if (is_authenticated()) {
        redirect('index.php');
    }
}

function login_user(int $id, string $name): void
{
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id']   = $id;
    $_SESSION['user_name'] = $name;
    unset($_SESSION['csrf_token']);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
