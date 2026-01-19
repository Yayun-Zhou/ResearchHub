<?php
session_start();
require_once "../includes/connect.php";

// 1. Must be logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$visibility = $_POST['visibility'] ?? 'Private';

// 2. Validation
if ($name === '') {
    header("Location: ../new_collection.php?error=Missing+Name");
    exit;
}

try {
    // 3. Set MySQL session variable correctly (exec, not prepare!)
    $conn->exec("SET @current_user_id = {$userID}");

    // 4. Insert collection
    $sql = "INSERT INTO Collection 
            (UserID, CollectionName, CollectionDescription, Visibility)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$userID, $name, $description, $visibility]);

    header("Location: ../collections.php?success=1");
    exit;

} catch (Exception $e) {

    echo "<h2>SQL Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";

    echo "<h3>DEBUG Session</h3>";
    var_dump($_SESSION);

    echo "<h3>DEBUG SQL DATA</h3>";
    var_dump([$userID, $name, $description, $visibility]);
}