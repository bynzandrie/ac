<?php
// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value, "'\" \t\n\r\0\x0B");
    }
}

// Database configuration from environment variables
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_DATABASE') ?: 'canteen_portal';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

// Set character set to UTF-8
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create PDO instance for more secure database operations
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // For backward compatibility, maintain the mysqli connection
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    if ($mysqli->connect_errno) {
        throw new Exception('MySQLi connection failed: ' . $mysqli->connect_error);
    }
    
    // Set character set for mysqli
    $mysqli->set_charset('utf8mb4');
    
} catch (PDOException $e) {
    // Log the error (don't show detailed errors in production)
    error_log('Database connection failed: ' . $e->getMessage());
    die('Could not connect to the database. Please try again later.');
} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    die('A database error occurred. Please try again later.');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax'
    ]);
}

// Set timezone
date_default_timezone_set('Asia/Manila');  // Change to your timezone