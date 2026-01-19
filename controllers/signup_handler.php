<?php
session_start();
require_once "../includes/connect.php";  // This file creates $conn (PDO)

// Read form input safely
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password']; 
$affiliationID = $_POST['affiliation'];
$role = $_POST['role'];

$errors = [];

// Validation
if (!$username) $errors[] = "Username is required";
if (!$email) $errors[] = "Email is required";
if (!$password) $errors[] = "Password is required";
if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
if (!$affiliationID) $errors[] = "Affiliation is required";

// If validation fails → redirect back
if (!empty($errors)) {
    $query = implode('|', $errors);
    header("Location: ../signup.php?error=$query");
    exit;
}

// 1. Check if email already exists
$checkSql = "SELECT UserID FROM User WHERE Email = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->execute([$email]);

if ($checkStmt->rowCount() > 0) {
    header("Location: ../signup.php?error=Email already exists");
    exit;
}

// 2. Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 3. Insert user
$sql = "INSERT INTO User (UserName, Email, Password, AffiliationID, Role)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$success = $stmt->execute([$username, $email, $hashedPassword, $affiliationID, $role]);

if ($success) {
    // Store user session
    $_SESSION['UserID'] = $conn->lastInsertId();
    $_SESSION['UserName'] = $username;
    $_SESSION['Role'] = $role;

    header("Location: ../dashboard.php");
    exit;
}

// Unknown error
header("Location: ../signup.php?error=Signup failed");
exit;
?>