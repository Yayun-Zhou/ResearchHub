<?php
session_start();
require_once "../includes/connect.php";

// Must be logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);

// update MySQL user variable for triggers
try {
    $setVar = $conn->prepare("SET @current_user_id := ?");
    $setVar->execute([$userID]);
} catch (Exception $e) {
    die("Failed to set @current_user_id: " . $e->getMessage());
}

// Validate POST
if (!isset($_POST['document_id']) || !isset($_POST['content']) || !isset($_POST['visibility'])) {
    die("Missing required fields.");
}

$docID = intval($_POST['document_id']);
$content = trim($_POST['content']);
$visibility = ($_POST['visibility'] === "Public") ? "Public" : "Private"; // sanitize
$pageNum = (isset($_POST['page_num']) && $_POST['page_num'] !== "") ? trim($_POST['page_num']) : null;

// Validate note content
if ($content === "") {
    die("Note content cannot be empty.");
}

try {
    // Insert note
    $sql = "
        INSERT INTO Notes (UserID, DocumentID, Content, PageNum, Visibility)
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$userID, $docID, $content, $pageNum, $visibility]);

    // Redirect back
    header("Location: ../document_view.php?id=" . $docID);
    exit;

} catch (Exception $e) {

    // Always show SQL error for debugging
    echo "ERROR: " . $e->getMessage();
}
?>