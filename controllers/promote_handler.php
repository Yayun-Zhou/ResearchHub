<?php
session_start();
require_once "../includes/connect.php";

// Must be admin
if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

$userID = $_POST['user_id'] ?? null;

if (!$userID) {
    header("Location: ../user_list.php?error=Invalid user ID");
    exit;
}

// Promote to admin
$stmt = $conn->prepare("UPDATE User SET Role = 'Admin' WHERE UserID = ?");
$stmt->execute([$userID]);

header("Location: ../user_list.php?success=promoted");
exit;
?>