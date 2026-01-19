<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['UserID'];

try {

    $conn->beginTransaction();

    // REQUIRED for triggers
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$userID]);

    // 1. Delete Notes
    $stmt = $conn->prepare("DELETE FROM Notes WHERE UserID = ?");
    $stmt->execute([$userID]);

    // 2. Delete Comments
    $stmt = $conn->prepare("DELETE FROM Comment WHERE UserID = ?");
    $stmt->execute([$userID]);

    // 3. Delete CollectionDocument (because Collection has FK)
    $stmt = $conn->prepare("
        DELETE cd FROM CollectionDocument cd
        INNER JOIN Collection c ON cd.CollectionID = c.CollectionID
        WHERE c.UserID = ?");
    $stmt->execute([$userID]);

    // 4. Delete Collections
    $stmt = $conn->prepare("DELETE FROM Collection WHERE UserID = ?");
    $stmt->execute([$userID]);

    // 5. Finally delete the user
    $stmt = $conn->prepare("DELETE FROM User WHERE UserID = ?");
    $stmt->execute([$userID]);

    $conn->commit();

    session_destroy();
    header("Location: ../login.php?success=Account+deleted");
    exit;

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo "<h2>Error deleting account</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}