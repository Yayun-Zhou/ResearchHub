<?php
session_start();
require_once "../includes/connect.php";

// ===============================
//  Admin only
// ===============================
if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== "admin") {
    die("Unauthorized");
}

$currentUser = intval($_SESSION['UserID']);

// ===============================
//  Important: Set trigger variable
// ===============================
$stmt = $conn->prepare("SET @current_user_id = ?");
$stmt->execute([$currentUser]);

// ===============================
//  Helper: Clean Input
// ===============================
function clean($str) {
    if (!isset($str)) return "";
    // convert to UTF-8
    $s = mb_convert_encoding($str, 'UTF-8', 'auto');
    // remove invisible chars
    $s = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', $s);
    return trim($s);
}

$fname = clean($_POST['fname'] ?? "");
$lname = clean($_POST['lname'] ?? "");
$area  = clean($_POST['area']  ?? "");
$aff   = clean($_POST['aff']   ?? "");  // may be empty

// ===============================
//  Validation
// ===============================
if ($fname === "" || $lname === "") {
    die("Author first and last name are required.");
}

// Convert affiliation (empty → NULL)
$affID = ($aff === "") ? null : intval($aff);

try {

    // ===============================
    //  Insert Author
    // ===============================
    $sql = "INSERT INTO Author (FirstName, LastName, AffiliationID, AuthorArea)
            VALUES (:fn, :ln, :aff, :area)";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(":fn", $fname);
    $stmt->bindValue(":ln", $lname);

    if ($affID === null) {
        $stmt->bindValue(":aff", null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(":aff", $affID, PDO::PARAM_INT);
    }

    $stmt->bindValue(":area", $area);
    $stmt->execute();

    // Redirect back
    header("Location: ../import_document.php");
    exit;

} catch (Exception $e) {
    echo "<h2>Error adding author</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}