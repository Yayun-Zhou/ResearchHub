<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    die("Not logged in.");
}

if (!isset($_POST['note_id']) || !isset($_POST['content']) || !isset($_POST['document_id'])) {
    die("Missing fields.");
}

$userID = intval($_SESSION['UserID']);
$role   = strtolower($_SESSION['Role'] ?? "");
$noteID = intval($_POST['note_id']);
$docID  = intval($_POST['document_id']);

$content = trim($_POST['content']);
$pageNum = isset($_POST['page_num']) && $_POST['page_num'] !== "" ? intval($_POST['page_num']) : null;
$visibility = ($_POST['visibility'] === "Public" ? "Public" : "Private");

/* set MySQL session variable for triggers */
$setUser = $conn->prepare("SET @current_user_id := ?");
$setUser->execute([$userID]);

/* check ownership */
$stmt = $conn->prepare("SELECT UserID FROM Notes WHERE NoteID = ?");
$stmt->execute([$noteID]);
$ownerID = intval($stmt->fetchColumn());

if ($ownerID === 0) {
    die("Note not found.");
}

if ($role !== "admin" && $ownerID !== $userID) {
    die("Permission denied.");
}

/* update */
$sql = "
    UPDATE Notes
    SET Content = ?, PageNum = ?, Visibility = ?, UpdatedTime = NOW()
    WHERE NoteID = ?
";
$stmt = $conn->prepare($sql);
$stmt->execute([$content, $pageNum, $visibility, $noteID]);

header("Location: ../document_view.php?id=" . $docID);
exit;