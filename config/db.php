<?php
/**
 * ModStore — Database Configuration
 * Edit DB_USER / DB_PASS to match your XAMPP setup.
 * Default XAMPP credentials: root / (empty password)
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'modstore');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST, DB_NAME, DB_CHARSET
);

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
} catch (PDOException $e) {
    // In production, log this and show a friendly error page.
    http_response_code(503);
    die('<div style="font-family:sans-serif;padding:2rem;color:#fff;background:#0f1115;">
         <h2>⚠ Database Unavailable</h2>
         <p>Could not connect to the database. Please check your XAMPP MySQL service and <code>config/db.php</code> credentials.</p>
         </div>');
}
