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

$noteID = intval($_GET['id']);
$docID  = intval($_GET['doc']);

// Set MySQL user variable for triggers
$conn->exec("SET @current_user_id = $userID");

try {
    // Admin can delete anyone's notes
    if ($role === "admin") {
        $sql = "DELETE FROM Notes WHERE NoteID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$noteID]);

    } else {
        // Normal user: can only delete their own notes
        $sql = "DELETE FROM Notes WHERE NoteID = ? AND UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$noteID, $userID]);
    }

    header("Location: ../document_view.php?id=" . $docID);
    exit;

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}