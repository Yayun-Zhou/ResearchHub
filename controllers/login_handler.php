<?php
session_start();
require_once "../includes/connect.php"; // PDO connection

// Read form input
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header("Location: ../login.php?error=Missing email or password");
    exit;
}

try {
    // Prepare SQL query (PDO)
    $sql = "SELECT * FROM `User` WHERE Email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user exists and password matches (PASSWORD is hashed)
    if ($user && password_verify($password, $user['Password'])) {

        // Store user session
        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['UserName'] = $user['UserName'];
        $_SESSION['Role'] = $user['Role'];

        header("Location: ../dashboard.php");
        exit;
    }

    // Invalid login
    header("Location: ../login.php?error=Invalid email or password");
    exit;

} catch (Exception $e) {
    // Debug purpose: uncomment if needed
    // die("DEBUG ERROR: " . $e->getMessage()); 
    header("Location: ../login.php?error=Server error");
    exit;
}
?>