<?php
session_start();
require_once "../includes/connect.php";

// Must login
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$currentUser = intval($_SESSION['UserID']);

$collectionID = intval($_POST['collection_id']);
$documentID = intval($_POST['document_id']);

// Validate
if ($collectionID <= 0 || $documentID <= 0) {
    die("Invalid data.");
}

try {
    // Set trigger variable
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$currentUser]);

    // Insert (triggers ensure only owner can add)
    $sql = "INSERT IGNORE INTO CollectionDocument (CollectionID, DocumentID)
            VALUES (?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$collectionID, $documentID]);

    // Redirect back to the collection page
    header("Location: ../collection_view.php?id=" . $collectionID);
    exit;

} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo $e->getMessage();
    echo "<h4>Debug:</h4>";
    var_dump($_POST);
}