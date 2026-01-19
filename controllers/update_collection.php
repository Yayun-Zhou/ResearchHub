<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);

$collectionID = intval($_POST['collection_id'] ?? 0);
$name = trim($_POST['name']);
$desc = trim($_POST['description']);
$visibility = $_POST['visibility'] ?? "Private";

/* Validation */
if ($collectionID <= 0 || $name === "") {
    die("Invalid form data.");
}

/* ---- Check ownership ---- */
$stmt = $conn->prepare("SELECT UserID FROM Collection WHERE CollectionID = ?");
$stmt->execute([$collectionID]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Collection not found.");
}
if ($row['UserID'] != $userID) {
    die("❌ You can only edit your own collections.");
}

try {
    // Set trigger session variable
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$userID]);

    // Update
    $sql = "UPDATE Collection
            SET CollectionName = ?, CollectionDescription = ?, Visibility = ?
            WHERE CollectionID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $desc, $visibility, $collectionID]);

    header("Location: ../collection_view.php?id=$collectionID&updated=1");
    exit;

} catch (Exception $e) {
    echo "<h2>SQL Error</h2><pre>" . $e->getMessage() . "</pre>";
}
?>