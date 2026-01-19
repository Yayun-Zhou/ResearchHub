<?php
session_start();
require_once "../includes/connect.php";

// Admin only
if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== 'admin') {
    die("Unauthorized");
}

$currentUser = intval($_SESSION['UserID']);

// *** IMPORTANT: Set trigger user variable ***
$stmt = $conn->prepare("SET @current_user_id = ?");
$stmt->execute([$currentUser]);

// Clean input
function clean($str) {
    if (!isset($str)) return "";
    $s = mb_convert_encoding($str, 'UTF-8', 'auto');
    $s = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', $s);
    return trim($s);
}

$name = clean($_POST['name'] ?? "");
$type = clean($_POST['type'] ?? "");
$lang = clean($_POST['lang'] ?? "");

if ($name === "" || $type === "" || $lang === "") {
    die("Source Name, Source Type, and Language are required.");
}

try {
    $stmt = $conn->prepare("
        INSERT INTO Source (SourceType, SourceName, Language)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$type, $name, $lang]);

    header("Location: ../import_document.php");
    exit;

} catch (Exception $e) {
    echo "<h3>Error adding source</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}