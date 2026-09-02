<?php
/**
 * Global Session + Database Connector
 * Shared by all pages and controllers.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Make session cookie available for the whole site
    session_set_cookie_params([
        'lifetime' => 0,      // until browser closes
        'path'     => '/',    // important: share sessions across subdirs
        'secure'   => false,  // set true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ========================
// DATABASE CONNECTION
// ========================

// Credentials and the token-signing key live in config.php, which is NOT in git.
// __DIR__ makes this resolve relative to THIS file, so it works both from the
// site root and from controllers/ (which sit one directory deeper).
require_once __DIR__ . '/config.php';

$host   = DB_HOST;
$dbName = DB_NAME;

$sessionRole = $_SESSION['Role'] ?? 'app_user';  // default as app_user

if ($sessionRole === 'Admin') {
    $dbUser = DB_ADMIN_USER;
    $dbPass = DB_ADMIN_PASS;
} else {
    $dbUser = DB_APP_USER;
    $dbPass = DB_APP_PASS;
}

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // *** CRITICAL FIX: make connection collation match table collation ***
    // All your tables use utf8mb4_general_ci, so we force the connection to use it too.
    $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->exec("SET collation_connection = utf8mb4_general_ci");

} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}