<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

$userID = intval($_POST['user_id'] ?? 0);

if ($userID <= 0) {
    header("Location: ../user_list.php?error=invalid_user");
    exit;
}

// Admin cannot delete themselves
if ($userID == $_SESSION['UserID']) {
    header("Location: ../user_list.php?error=cannot_delete_self");
    exit;
}

try {
    // Set trigger context
    $conn->prepare("SET @current_user_id = ?")->execute([$_SESSION['UserID']]);

    $conn->beginTransaction();

    // 1. Delete user notes
    $conn->prepare("DELETE FROM Notes WHERE UserID = ?")->execute([$userID]);

    // 2. Delete user comments
    $conn->prepare("DELETE FROM Comment WHERE UserID = ?")->execute([$userID]);

    // 3. Delete CollectionDocument through user-owned Collections
    $conn->prepare("
        DELETE CD FROM CollectionDocument CD
        JOIN Collection C ON CD.CollectionID = C.CollectionID
        WHERE C.UserID = ?
    ")->execute([$userID]);

    // 4. Delete user collections
    $conn->prepare("DELETE FROM Collection WHERE UserID = ?")->execute([$userID]);

    // 5. Delete action_logs for this user (optional but clean)
    $conn->prepare("DELETE FROM action_log WHERE UserID = ?")->execute([$userID]);

    // 6. Finally delete the user
    $conn->prepare("DELETE FROM User WHERE UserID = ?")->execute([$userID]);

    $conn->commit();

    header("Location: ../user_list.php?success=deleted");
    exit;

} catch (Exception $e) {

    if ($conn->inTransaction()) $conn->rollBack();

    error_log("Admin delete user error: " . $e->getMessage());
    header("Location: ../user_list.php?error=" . urlencode($e->getMessage()));
    exit;
}