<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../includes/connect.php";

$currentUser = intval($_SESSION['UserID'] ?? 0);
$collectionID = intval($_POST['collection_id'] ?? 0);
$documentID   = intval($_POST['document_id'] ?? 0);

if ($currentUser === 0 || $collectionID === 0 || $documentID === 0) {
    die("Missing required inputs.");
}

/* Verify owner */
$sql = "SELECT UserID FROM Collection WHERE CollectionID = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$collectionID]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$collection) { die("Collection not found"); }
if ($collection['UserID'] != $currentUser) { die("Permission denied"); }

/* ---- IMPORTANT: set trigger variable ---- */
$stmt = $conn->prepare("SET @current_user_id = ?");
$stmt->execute([$currentUser]);

/* Remove document */
$deleteSQL = "
    DELETE FROM CollectionDocument
    WHERE CollectionID = ? AND DocumentID = ?
";
$stmt = $conn->prepare($deleteSQL);
$stmt->execute([$collectionID, $documentID]);

/* Update collection timestamp */
$updateSQL = "UPDATE Collection SET UpdatedTime = NOW() WHERE CollectionID = ?";
$stmt = $conn->prepare($updateSQL);
$stmt->execute([$collectionID]);

header("Location: ../collection_view.php?id=$collectionID");
exit;