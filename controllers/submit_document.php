<?php
session_start();
require_once "../includes/connect.php";

// Admin only (your DB stores "Admin" with uppercase A)
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== "Admin") {
    header("Location: ../login.php");
    exit;
}

$currentUser = intval($_SESSION['UserID']);

// --------- Clean POST data ---------
function clean($x) {
    return isset($x) ? trim($x) : "";
}

$title     = clean($_POST['title'] ?? "");
$abstract  = clean($_POST['abstract'] ?? "");
$year      = ($_POST['year'] ?? "") !== "" ? intval($_POST['year']) : null;
$area      = clean($_POST['area'] ?? "");
$isbn_raw  = clean($_POST['isbn'] ?? "");
$linkpath  = clean($_POST['linkpath'] ?? "");
$sourceID  = ($_POST['source'] ?? "") !== "" ? intval($_POST['source']) : null;

$tagIDs            = $_POST['tags']               ?? [];
$authorIDs         = $_POST['authors']            ?? [];
$citationIDs       = $_POST['citations_ids']      ?? [];
$citationContexts  = $_POST['citations_contexts'] ?? [];

// ISBN: empty string -> NULL (avoid UNIQUE '' conflict)
$isbn = ($isbn_raw === "") ? null : $isbn_raw;

// Title is required
if ($title === "") {
    die("Title is required.");
}

try {
    // Set trigger variable for permission system
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->execute([$currentUser]);

    $conn->beginTransaction();

    // --------- Insert Document ---------
    // IMPORTANT CHANGE:
    //   - Do NOT set ReviewStatus here, let DB default 'Pending' 
    $sql = "
        INSERT INTO Document
        (Title, Abstract, PublicationYear, SourceID, Area, ISBN, LinkPath, ImportDate)
        VALUES (:title, :abstract, :year, :source, :area, :isbn, :linkpath, CURDATE())
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":title", $title);
    $stmt->bindValue(":abstract", $abstract);

    // year
    if ($year === null) {
        $stmt->bindValue(":year", null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(":year", $year, PDO::PARAM_INT);
    }

    // source
    if ($sourceID === null) {
        $stmt->bindValue(":source", null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(":source", $sourceID, PDO::PARAM_INT);
    }

    // area
    $stmt->bindValue(":area", $area);

    // ISBN (NULL-safe)
    if ($isbn === null) {
        $stmt->bindValue(":isbn", null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(":isbn", $isbn);
    }

    $stmt->bindValue(":linkpath", $linkpath);

    $stmt->execute();

    $docID = intval($conn->lastInsertId());

    // --------- Insert Tags ---------
    if (!empty($tagIDs)) {
        $sqlTag = "INSERT IGNORE INTO DocumentTag (DocumentID, TagID) VALUES (:doc, :tag)";
        $stTag = $conn->prepare($sqlTag);
        foreach ($tagIDs as $tid) {
            $tid = intval($tid);
            if ($tid > 0) {
                $stTag->execute([":doc" => $docID, ":tag" => $tid]);
            }
        }
    }

    // --------- Insert Authors ---------
    if (!empty($authorIDs)) {
        $sqlAuth = "INSERT IGNORE INTO DocumentAuthor (DocumentID, AuthorID) VALUES (:doc, :auth)";
        $stAuth = $conn->prepare($sqlAuth);
        foreach ($authorIDs as $aid) {
            $aid = intval($aid);
            if ($aid > 0) {
                $stAuth->execute([":doc" => $docID, ":auth" => $aid]);
            }
        }
    }

    // --------- Insert Citations ---------
    if (!empty($citationIDs)) {
        $sqlCit = "
            INSERT IGNORE INTO Citation (CitingDocumentID, CitedDocumentID, ContextPage)
            VALUES (:citing, :cited, :ctx)
        ";
        $stCit = $conn->prepare($sqlCit);

        foreach ($citationIDs as $idx => $cid) {
            $cid = intval($cid);
            if ($cid <= 0) continue;

            $ctx = clean($citationContexts[$idx] ?? "");

            $stCit->execute([
                ":citing" => $docID,
                ":cited"  => $cid,
                ":ctx"    => ($ctx === "" ? null : $ctx)
            ]);
        }
    }

    $conn->commit();

    // After import → go to document view (status should now be 'Pending')
    header("Location: ../document_view.php?id=" . $docID);
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo "<h2>ERROR inserting document</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}