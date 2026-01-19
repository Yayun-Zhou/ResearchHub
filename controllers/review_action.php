<?php
session_start();
require_once "../includes/connect.php";

// Admin only
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== "Admin") {
    die("Unauthorized");
}

$managerID = intval($_SESSION['UserID']);
$docID     = intval($_POST['id'] ?? 0);
$decision  = strtolower(trim($_POST['decision'] ?? ""));

if ($docID <= 0) {
    die("Invalid document ID.");
}

if (!in_array($decision, ["approved", "rejected"])) {
    die("Invalid decision.");
}

try {
    // Set trigger variable for logging & permissions
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$managerID]);

    // Call stored procedure
    $stmt = $conn->prepare("CALL sp_review_document(?, ?, ?)");
    $stmt->execute([$managerID, $docID, $decision]);

    header("Location: ../review_documents.php");
    exit;

} catch (Exception $e) {
    echo "<h2>Error Reviewing Document</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}