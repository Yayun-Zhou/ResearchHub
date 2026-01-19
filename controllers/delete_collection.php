<?php
session_start();
require_once "../includes/connect.php";

// Must be logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$currentUser = intval($_SESSION['UserID']);
$collectionID = intval($_POST['id'] ?? 0);

if ($collectionID <= 0) {
    die("Invalid Collection ID.");
}

try {
    // Start transaction
    $conn->beginTransaction();

    // 1. Set MySQL session variable for triggers
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$currentUser]);

    // 2. Delete from CollectionDocument first
    $stmt = $conn->prepare("DELETE FROM CollectionDocument WHERE CollectionID = ?");
    $stmt->execute([$collectionID]);

    // 3. Delete Collection
    $stmt = $conn->prepare("DELETE FROM Collection WHERE CollectionID = ?");
    $stmt->execute([$collectionID]);

    // Commit
    $conn->commit();

    // Redirect
    header("Location: ../collections.php?deleted=1");
    exit;

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo "<h2>SQL Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";

    echo "<h3>DEBUG</h3>";
    var_dump([
        "currentUser" => $currentUser,
        "collectionID" => $collectionID
    ]);
}