<?php
/**
 * Database configuration and shared PDO instance.
 *
 * Import with:  require_once __DIR__ . '/db.php';
 * After that, $pdo is available in the including file.
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

$dbConfig = [
    'host'    => '127.0.0.1',
    'port'    => 3306,
    'name'    => 'blog_site',
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4',
];

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['name'],
    $dbConfig['charset']
);

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // fail loudly, never silently
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                  // real server-side prepared statements
    ]);
    // Keep MySQL timestamps in the same timezone PHP formats them in.
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please try again later.');
}

unset($dbConfig, $dsn);
