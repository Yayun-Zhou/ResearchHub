<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    die("Not logged in.");
}

// -------- update @current_user_id for trigger --------
$userID = intval($_SESSION['UserID']);
try {
    $setVar = $conn->prepare("SET @current_user_id := ?");
    $setVar->execute([$userID]);
} catch (Exception $e) {
    die("Failed to set current user in DB: " . $e->getMessage());
}
// -----------------------------------------------------------

if (!isset($_POST['document_id']) || !isset($_POST['content'])) {
    die("Missing POST fields.");
}

$docID   = intval($_POST['document_id']);
$content = trim($_POST['content']);

if ($content === "") {
    die("Comment cannot be empty.");
}

try {
    $sql = "INSERT INTO Comment (UserID, DocumentID, Context)
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userID, $docID, $content]);

    header("Location: ../document_view.php?id=" . $docID);
    exit;

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}