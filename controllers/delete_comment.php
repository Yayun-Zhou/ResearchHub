<?php
session_start();
require_once "../includes/connect.php";

// Must be logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);
$role   = strtolower($_SESSION['Role'] ?? "");

// Validate GET
if (!isset($_GET['id']) || !isset($_GET['doc'])) {
    die("Missing parameters.");
}

$commentID = intval($_GET['id']);
$docID     = intval($_GET['doc']);

// Set MySQL user variable for triggers
$conn->exec("SET @current_user_id = $userID");

try {
    // Admin deletes any comment
    if ($role === "admin") {
        $sql = "DELETE FROM Comment WHERE CommentID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$commentID]);

    } else {
        // Normal user: can only delete their own comments
        $sql = "DELETE FROM Comment WHERE CommentID = ? AND UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$commentID, $userID]);
    }

    header("Location: ../document_view.php?id=" . $docID);
    exit;

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}