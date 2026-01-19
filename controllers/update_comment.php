<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    die("Not logged in.");
}

$userID = intval($_SESSION['UserID']);
$role   = strtolower($_SESSION['Role'] ?? "");

if (!isset($_POST['comment_id'], $_POST['content'], $_POST['document_id'])) {
    die("Missing fields.");
}

$commentID = intval($_POST['comment_id']);
$docID     = intval($_POST['document_id']);
$content   = trim($_POST['content']);

if ($content === "") {
    die("Comment content cannot be empty.");
}

$stmt = $conn->prepare("SELECT UserID FROM Comment WHERE CommentID = ?");
$stmt->execute([$commentID]);
$ownerID = intval($stmt->fetchColumn());

if ($ownerID === 0) {
    die("Comment not found.");
}

if ($role !== "admin" && $ownerID !== $userID) {
    die("Permission denied.");
}

// Set the variable so triggers work
$conn->exec("SET @current_user_id = {$userID}");

try {
    $sql = "UPDATE Comment SET Context = ? WHERE CommentID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$content, $commentID]);

    header("Location: ../document_view.php?id=" . $docID);
    exit;

} catch (Exception $e) {
    echo "ERROR updating comment: " . $e->getMessage();
    exit;
}