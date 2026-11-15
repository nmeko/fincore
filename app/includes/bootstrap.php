<?php
// Loaded first by every page: config, hardened session, security headers,
// CSRF protection for all POST requests, and shared helpers.

$configFile = __DIR__ . '/../config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('FinCore is not configured yet: copy app/config.example.php to app/config.php and fill it in.');
}
$config = require $configFile;

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/** HTML-escape for element content and attribute values. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** A value as a JS literal that is safe inside an HTML attribute such as onclick="f(<?= js($x) ?>)". */
function js($value): string
{
    return e(json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE));
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

// Every state-changing request must carry the session's token.
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    exit('Your session expired. Go back, reload the page and try again.');
}

function require_login(): void
{
    if (empty($_SESSION['logged_in'])) {
        header('Location: login.php');
        exit();
    }
}

function db(): mysqli
{
    global $config;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name'], (int)($config['db_port'] ?? 3306));
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (mysqli_sql_exception $ex) {
        error_log('FinCore DB connection failed: ' . $ex->getMessage());
        http_response_code(500);
        exit('The database is unavailable. Check app/config.php and that MySQL is running.');
    }
}

/** Returns [message, type] left by a redirect, or ['', ''] when there is none. */
function take_flash(): array
{
    $flash = $_SESSION['flash'] ?? ['', ''];
    unset($_SESSION['flash']);
    return $flash;
}

// A query that throws during a form submit (PHP 8.1+ mysqli throws by default) returns the
// visitor to the same page with a readable message instead of a fatal error.
set_exception_handler(function (Throwable $ex) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !headers_sent()) {
        $_SESSION['flash'] = [db_error($ex), 'error'];
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
    error_log('FinCore error: ' . $ex);
    http_response_code(500);
    echo 'Something went wrong. Please try again.';
});

/** A user-safe message for a failed query; details go to the server log only. */
function db_error(Throwable $ex): string
{
    $code = $ex instanceof mysqli_sql_exception ? $ex->getCode() : 0;
    // MySQL 8 sends 1217/1216 instead of 1451/1452 to users without table-level grants.
    if ($code === 1451 || $code === 1217) return 'Cannot delete: other records still refer to this one.';
    if ($code === 1452 || $code === 1216) return 'That linked record does not exist.';
    if ($code === 1644) return $ex->getMessage(); // raised on purpose by a trigger's SIGNAL
    error_log('FinCore query failed: ' . $ex->getMessage());
    return 'Something went wrong while saving. Please check the values and try again.';
}
