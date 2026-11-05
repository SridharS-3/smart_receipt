<?php
// config/db.php - Database connection & session setup

require_once __DIR__ . '/env.php';

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host    = env('DB_HOST', '127.0.0.1');
$db_port    = env('DB_PORT', '3306');
$db_name    = env('DB_DATABASE', 'smart_receipt_db');
$db_user    = env('DB_USERNAME', 'root');
$db_pass    = env('DB_PASSWORD', '');
$db_charset = env('DB_CHARSET', 'utf8mb4');
$db_socket  = env('DB_SOCKET', '/opt/lampp/var/mysql/mysql.sock');

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    // Attempt fallback with unix socket if available
    if (!empty($db_socket) && file_exists($db_socket)) {
        try {
            $dsn_socket = "mysql:unix_socket={$db_socket};dbname={$db_name};charset={$db_charset}";
            $pdo = new PDO($dsn_socket, $db_user, $db_pass, $options);
        } catch (\PDOException $e2) {
            die("Database connection failed: " . htmlspecialchars($e2->getMessage()));
        }
    } else {
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    }
}

// Authentication Check Helpers
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_auth() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_inr($number) {
    $num = number_format((float)$number, 2, '.', ',');
    return $num;
}
