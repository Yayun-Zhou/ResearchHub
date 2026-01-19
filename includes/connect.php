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

$host   = "localhost";
$dbName = "projectDB3";
// $dbUser = "root";
// $dbPass = ""; // XAMPP default


$sessionRole = $_SESSION['Role'] ?? 'app_user';  // default as app_user

if ($sessionRole === 'Admin') {
    $dbUser = "admin";
    $dbPass = "admin_password";
} else {
    $dbUser = "app_user";
    $dbPass = "app_user_password";
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