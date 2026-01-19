<?php
session_start();
require_once "../includes/connect.php";

// Only Admin may use duplicate checking
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== "Admin") {
    echo json_encode([]);
    exit;
}

$title = trim($_GET['title'] ?? "");

if ($title === "") {
    echo json_encode([]);
    exit;
}

// Search similar approved documents
$stmt = $conn->prepare("
    SELECT DocumentID, Title, PublicationYear
    FROM Document
    WHERE ReviewStatus = 'Approved'
      AND Title LIKE CONCAT('%', ?, '%')
    ORDER BY Title ASC
");
$stmt->execute([$title]);

$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/json");
echo json_encode($docs);