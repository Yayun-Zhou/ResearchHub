<?php
session_start();
require_once "../includes/connect.php";

if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== 'admin') {
    die("Unauthorized");
}

$tagName = trim($_POST['tagname'] ?? '');
$tagDesc = trim($_POST['tagdesc'] ?? '');

if ($tagName === "") {
    die("Tag name is required.");
}

try {

    // make triggers work
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$_SESSION['UserID']]);

    $stmt = $conn->prepare("
        INSERT INTO Tag (TagName, TagDescription)
        VALUES (?, ?)
    ");
    $stmt->execute([$tagName, $tagDesc]);

    header("Location: ../import_document.php");
    exit;

} catch (Exception $e) {
    echo "<h3>Error adding tag</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}