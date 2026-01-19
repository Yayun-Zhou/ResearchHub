<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== "admin") {
    header("Location: ../login.php");
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Invalid Document ID");
}

$docID = intval($_POST['id']);

try {
    // Set @current_user_id (triggers need this)
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$_SESSION['UserID']]);

    $conn->beginTransaction();

    // 1) Delete dependent rows FIRST
    $conn->prepare("DELETE FROM CollectionDocument WHERE DocumentID = ?")
         ->execute([$docID]);

    $conn->prepare("DELETE FROM Notes WHERE DocumentID = ?")
         ->execute([$docID]);

    $conn->prepare("DELETE FROM Comment WHERE DocumentID = ?")
         ->execute([$docID]);   // ⭐ YOU WERE MISSING THIS

    $conn->prepare("DELETE FROM DocumentTag WHERE DocumentID = ?")
         ->execute([$docID]);

    $conn->prepare("DELETE FROM DocumentAuthor WHERE DocumentID = ?")
         ->execute([$docID]);

    $conn->prepare("DELETE FROM Citation WHERE CitingDocumentID = ? OR CitedDocumentID = ?")
         ->execute([$docID, $docID]);

    // 2) Now delete Document
    $conn->prepare("DELETE FROM Document WHERE DocumentID = ?")
         ->execute([$docID]);

    $conn->commit();
    header("Location: ../review_documents.php?success=1");
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "<h2>Error Deleting Document</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}