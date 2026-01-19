<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['UserID'];

// Read form values
$username = $_POST['username'];
$email = $_POST['email'];
$affiliationID = $_POST['affiliation'];
$newPassword = $_POST['new_password'];

// Base SQL
$sql = "UPDATE User SET UserName = ?, Email = ?, AffiliationID = ? WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$username, $email, $affiliationID, $userID]);

// If new password provided → hash & update
if (!empty($newPassword)) {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE User SET Password = ? WHERE UserID = ?");
    $stmt->execute([$hashed, $userID]);
}

// Update session username so UI updates instantly
$_SESSION['UserName'] = $username;

// Redirect back to User Account page
header("Location: ../user_account.php?success=Profile updated successfully");
exit;
?>