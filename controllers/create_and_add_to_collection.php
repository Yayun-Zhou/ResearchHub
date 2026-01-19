<?php
// controllers/create_and_add_to_collection.php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['UserID'];
$documentID = intval($_POST['document_id'] ?? 0);
$collectionName = trim($_POST['collection_name'] ?? '');
$collectionDesc = trim($_POST['collection_description'] ?? '');

// Validate inputs
if (empty($collectionName) || $documentID <= 0) {
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../dashboard.php') . "?error=Invalid input");
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Set current user for triggers
    $conn->exec("SET @current_user_id = " . intval($userID));

    // 1. Create new collection - 使用正确的字段名
    $stmt = $conn->prepare("
        INSERT INTO Collection (UserID, CollectionName, CollectionDescription, CreatedTime, UpdatedTime, Visibility)
        VALUES (?, ?, ?, NOW(), NOW(), 'Private')
    ");
    
    if (!$stmt->execute([$userID, $collectionName, $collectionDesc])) {
        throw new Exception("Failed to create collection");
    }
    
    $newCollectionID = $conn->lastInsertId();

    if (!$newCollectionID) {
        throw new Exception("Failed to get new collection ID");
    }

    // 2. Add document to the new collection
    $stmt = $conn->prepare("
        INSERT INTO CollectionDocument (CollectionID, DocumentID)
        VALUES (?, ?)
    ");
    
    if (!$stmt->execute([$newCollectionID, $documentID])) {
        throw new Exception("Failed to add document to collection");
    }

    // Commit transaction
    $conn->commit();

    // Redirect back with success message
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        $separator = strpos($referer, '?') !== false ? '&' : '?';
        header("Location: " . $referer . $separator . "success=" . urlencode("Document added to new collection '$collectionName'"));
    } else {
        header("Location: ../collections.php?success=" . urlencode("Collection created and document added"));
    }
    exit;

} catch (PDOException $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the actual error for debugging
    error_log("PDO Error in create_and_add_to_collection.php: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    
    // Check for specific errors
    if ($e->getCode() == 23000) {
        $errorMsg = "Document already in this collection or collection name already exists";
    } else {
        $errorMsg = "Database error: " . $e->getMessage();
    }
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../dashboard.php') . "?error=" . urlencode($errorMsg));
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("General Error in create_and_add_to_collection.php: " . $e->getMessage());
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../dashboard.php') . "?error=" . urlencode($e->getMessage()));
    exit;
}
?>