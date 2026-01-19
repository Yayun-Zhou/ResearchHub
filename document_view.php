<?php
session_start();
require_once "includes/connect.php";

// Must be logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);
$role   = strtolower($_SESSION['Role'] ?? "");

// Must have document id
if (!isset($_GET['id'])) {
    die("Document ID missing.");
}
$docID = intval($_GET['id']);

/* ============================
   1. Fetch Document Basic Info
============================= */
$sqlDoc = "
    SELECT 
        D.DocumentID,
        D.Title,
        D.Abstract,
        D.Area,
        D.PublicationYear,
        D.SourceID,
        D.ISBN,
        D.LinkPath,
        D.ImportDate,
        D.ReviewStatus,
        S.SourceName,
        S.SourceType,
        S.Language
    FROM Document D
    LEFT JOIN Source S ON D.SourceID = S.SourceID
    WHERE D.DocumentID = ?
";
$stmt = $conn->prepare($sqlDoc);
$stmt->execute([$docID]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die("Document not found.");
}

// Check review status for showing interactions
$reviewStatus = $doc['ReviewStatus'] ?? 'Pending';
$canShowInteractions = ($reviewStatus === 'Approved');

/* ============================
   2. Fetch Tags
============================= */
$sqlTags = "
    SELECT T.TagName
    FROM DocumentTag DT
    JOIN Tag T ON DT.TagID = T.TagID
    WHERE DT.DocumentID = ?
    ORDER BY T.TagName
";
$stmt = $conn->prepare($sqlTags);
$stmt->execute([$docID]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ============================
   3. Fetch Authors
============================= */
$sqlAuthors = "
    SELECT 
        A.AuthorID,
        A.FirstName,
        A.LastName,
        A.AuthorArea,
        Af.AffiliationName
    FROM DocumentAuthor DA
    JOIN Author A ON DA.AuthorID = A.AuthorID
    LEFT JOIN Affiliation Af ON A.AffiliationID = Af.AffiliationID
    WHERE DA.DocumentID = ?
    ORDER BY A.LastName, A.FirstName
";
$stmt = $conn->prepare($sqlAuthors);
$stmt->execute([$docID]);
$authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$authorsInline = implode(', ', array_map(
    fn($a) => trim($a['FirstName'] . ' ' . $a['LastName']),
    $authors
)) ?: "Unknown";

/* ============================
   4. Citation Counts
============================= */
$stmt = $conn->prepare("SELECT COUNT(*) FROM Citation WHERE CitingDocumentID = ?");
$stmt->execute([$docID]);
$referenceCount = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM Citation WHERE CitedDocumentID = ?");
$stmt->execute([$docID]);
$citedByCount = (int)$stmt->fetchColumn();

/* ============================
   5. References (this cites others)
============================= */
$sqlRefs = "
    SELECT
        C.CitationID,
        C.ContextPage,
        D2.DocumentID,
        D2.Title,
        D2.PublicationYear,
        S2.SourceName,
        GROUP_CONCAT(DISTINCT CONCAT(A.FirstName,' ',A.LastName) SEPARATOR ', ') AS Authors
    FROM Citation C
    JOIN Document D2 ON C.CitedDocumentID = D2.DocumentID
    LEFT JOIN Source S2 ON D2.SourceID = S2.SourceID
    LEFT JOIN DocumentAuthor DA2 ON D2.DocumentID = DA2.DocumentID
    LEFT JOIN Author A ON DA2.AuthorID = A.AuthorID
    WHERE C.CitingDocumentID = ?
    GROUP BY C.CitationID, D2.DocumentID
    ORDER BY D2.PublicationYear DESC, D2.Title
";
$stmt = $conn->prepare($sqlRefs);
$stmt->execute([$docID]);
$references = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   6. Cited By (others cite this)
============================= */
$sqlCitedBy = "
    SELECT
        C.CitationID,
        D1.DocumentID,
        D1.Title,
        D1.PublicationYear,
        S1.SourceName,
        GROUP_CONCAT(DISTINCT CONCAT(A.FirstName,' ',A.LastName) SEPARATOR ', ') AS Authors
    FROM Citation C
    JOIN Document D1 ON C.CitingDocumentID = D1.DocumentID
    LEFT JOIN Source S1 ON D1.SourceID = S1.SourceID
    LEFT JOIN DocumentAuthor DA ON D1.DocumentID = DA.DocumentID
    LEFT JOIN Author A ON DA.AuthorID = A.AuthorID
    WHERE C.CitedDocumentID = ?
    GROUP BY C.CitationID, D1.DocumentID
    ORDER BY D1.PublicationYear DESC, D1.Title
";
$stmt = $conn->prepare($sqlCitedBy);
$stmt->execute([$docID]);
$citedBy = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   7. Notes
============================= */

// my notes
$sqlMyNotes = "
    SELECT NoteID, Content, PageNum, Visibility, CreatedTime, UserID
    FROM Notes
    WHERE DocumentID = ? AND UserID = ?
    ORDER BY CreatedTime DESC
";
$stmt = $conn->prepare($sqlMyNotes);
$stmt->execute([$docID, $userID]);
$myNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// public notes
$sqlPublicNotes = "
    SELECT 
        N.NoteID, N.Content, N.PageNum, N.Visibility, N.CreatedTime,
        N.UserID,
        U.UserName, U.Role, Af.AffiliationName
    FROM Notes N
    JOIN User U ON N.UserID = U.UserID
    LEFT JOIN Affiliation Af ON U.AffiliationID = Af.AffiliationID
    WHERE N.DocumentID = ? AND N.Visibility = 'Public' AND N.UserID <> ?
    ORDER BY N.CreatedTime DESC
";
$stmt = $conn->prepare($sqlPublicNotes);
$stmt->execute([$docID, $userID]);
$publicNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   8. Comments
============================= */
$sqlComments = "
    SELECT 
        C.CommentID, C.Context, C.CreatedAt,
        C.UserID,
        U.UserName, U.Role, Af.AffiliationName
    FROM Comment C
    JOIN User U ON C.UserID = U.UserID
    LEFT JOIN Affiliation Af ON U.AffiliationID = Af.AffiliationID
    WHERE C.DocumentID = ?
    ORDER BY C.CreatedAt DESC
";
$stmt = $conn->prepare($sqlComments);
$stmt->execute([$docID]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   9. Collections (for modal)
============================= */
$stmt = $conn->prepare("
    SELECT CollectionID, CollectionName
    FROM Collection
    WHERE UserID = ?
    ORDER BY UpdatedTime DESC
");
$stmt->execute([$userID]);
$userCollections = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($doc['Title']) ?></title>

    <link rel="stylesheet" href="assets/css/globals.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            background: #f8f9fc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: #1e293b;
            min-height: 100vh;
        }

        /* -------- Sidebar -------- */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            padding: 32px 24px;
            border-right: 1px solid #334155;
            min-height: 100vh;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .app-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .app-title .icon {
            font-size: 32px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .app-title .title {
            font-size: 22px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
        }

        .app-title .role {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .menu a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            text-decoration: none;
            font-size: 15px;
            color: #cbd5e1;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .menu a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
            transform: translateX(4px);
        }

        .menu a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .menu-heading {
            margin-top: 32px;
            margin-bottom: 12px;
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 1px;
            padding-left: 16px;
        }

        .logout {
            margin-top: 48px;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #f87171;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .logout:hover {
            background: rgba(248, 113, 113, 0.1);
            transform: translateX(4px);
        }

        /* -------- Main layout -------- */
        .main {
            flex: 1;
            padding: 48px 56px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            color: #667eea;
            transform: translateX(-4px);
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 32px;
            border: 1px solid #e2e8f0;
        }

        /* -------- Document header -------- */
        .doc-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #0f172a;
            line-height: 1.3;
        }

        .tag-badge {
            display: inline-block;
            background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
            color: #4c1d95;
            padding: 6px 14px;
            margin-right: 8px;
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 16px;
            align-items: center;
        }

        .meta-separator::before {
            content: "•";
            margin: 0 8px;
            color: #cbd5e1;
        }

        .citation-badge {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        .section-title {
            font-size: 20px;
            margin-top: 32px;
            margin-bottom: 16px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, #e2e8f0 0%, transparent 100%);
            margin: 32px 0;
        }

        /* -------- Authors -------- */
        .authors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }

        .author-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            transition: all 0.3s ease;
        }

        .author-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }

        .author-name {
            font-weight: 600;
            margin-bottom: 6px;
            color: #0f172a;
            font-size: 15px;
        }

        .author-meta {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 3px;
        }

        /* -------- Document info -------- */
        .doc-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            font-size: 14px;
        }

        .doc-info-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .doc-info-label {
            color: #64748b;
            font-weight: 600;
        }

        .doc-info-value {
            text-align: right;
            color: #0f172a;
            font-weight: 500;
        }

        /* -------- Buttons -------- */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-ghost {
            padding: 10px 20px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background: white;
            cursor: pointer;
            font-size: 14px;
            color: #475569;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-ghost:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-ghost.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* -------- Citations -------- */
        .tabs-header {
            display: inline-flex;
            padding: 6px;
            border-radius: 12px;
            background: #f1f5f9;
            gap: 6px;
            margin-bottom: 24px;
        }

        .citation-item {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 12px;
            background: #fafbfc;
            transition: all 0.3s ease;
        }

        .citation-item:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
            transform: translateX(4px);
        }

        .citation-title {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .citation-title a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .citation-title a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .citation-meta {
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
        }

        .citation-context {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
            font-style: italic;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 6px;
        }

        /* -------- Notes -------- */
        .note-new, .comment-new {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            padding: 20px;
            border: 2px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .note-textarea, .comment-textarea {
            width: 100%;
            min-height: 100px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 14px;
            font-size: 14px;
            resize: vertical;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .note-textarea:focus, .comment-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .note-input {
            padding: 10px 14px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .note-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .note-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .note-tag.public {
            background: #dbeafe;
            color: #1e40af;
        }

        .note-tag.private {
            background: #f3f4f6;
            color: #374151;
        }

        .note-card, .comment-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 12px;
            font-size: 14px;
            background: white;
            transition: all 0.3s ease;
            position: relative;
        }

        .note-card:hover, .comment-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
        }

        .note-card[style*="background:#f0f7ff"] {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
            border-color: #bfdbfe;
        }

        .note-meta, .comment-sub {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .comment-user {
            font-weight: 600;
            color: #0f172a;
            font-size: 15px;
        }

        .delete-link {
            position: absolute;
            bottom: 12px;
            right: 16px;
            color: #ef4444;
            font-size: 14px;
            text-decoration: none;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .note-card:hover .delete-link,
        .comment-card:hover .delete-link {
            opacity: 1;
        }

        .delete-link:hover {
            color: #dc2626;
        }

        /* -------- Modal -------- */
        .modal-bg {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 100;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-box {
            width: 480px;
            max-width: 90%;
            background: white;
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-close {
            float: right;
            cursor: pointer;
            font-size: 24px;
            color: #94a3b8;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            color: #ef4444;
            transform: rotate(90deg);
        }

        .modal-select {
            width: 100%;
            padding: 14px;
            margin-top: 12px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .modal-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
                padding: 24px 16px;
            }

            .main {
                padding: 32px 24px;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                padding: 24px 16px;
            }

            .card {
                padding: 24px;
            }

            .doc-title {
                font-size: 24px;
            }

            .authors-grid,
            .doc-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function openAddModal() {
            document.getElementById("addModal").style.display = "flex";
        }
        function closeAddModal() {
            document.getElementById("addModal").style.display = "none";
        }

        function showCitationTab(tab) {
            const refSection   = document.getElementById('citations-references');
            const citedBySection = document.getElementById('citations-citedby');
            const btnRef       = document.getElementById('btn-tab-references');
            const btnCitedBy   = document.getElementById('btn-tab-citedby');

            if (tab === 'references') {
                refSection.style.display   = 'block';
                citedBySection.style.display = 'none';
                btnRef.classList.add('active');
                btnCitedBy.classList.remove('active');
            } else {
                refSection.style.display   = 'none';
                citedBySection.style.display = 'block';
                btnRef.classList.remove('active');
                btnCitedBy.classList.add('active');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            showCitationTab('references');
        });
    </script>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="app-title">
        <span class="icon">🔖</span>
        <div>
            <div class="title">Research Hub</div>
            <div class="role"><?= htmlspecialchars($role) ?></div>
        </div>
    </div>

    <nav class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="advanced_search.php">Advanced Search</a>
        <a href="collections.php">Collections</a>
        <a href="notes.php">Notes</a>
        <a href="comments.php">Comments</a>
        <a href="user_account.php">User Account</a>
    </nav>

    <?php if ($role === "admin"): ?>
        <div class="menu-heading">Admin</div>
        <nav class="menu">
            <a href="user_list.php">User List</a>
            <a href="import_document.php">Import Document</a>
            <a href="review_documents.php">Review Documents</a>
        </nav>
    <?php endif; ?>

    <a class="logout" href="logout.php">Log Out</a>
</aside>

<!-- MAIN -->
<div class="main">

    <a class="back-link" href="javascript:history.back();">
        <span>←</span> <span>Back</span>
    </a>

    <!-- ========== BLOCK 1: Document Header & Info ========== -->
    <div class="card">
        <!-- Tags -->
        <?php if (!empty($tags)): ?>
            <div style="margin-bottom:16px;">
                <?php foreach ($tags as $tag): ?>
                    <span class="tag-badge"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Title -->
        <div class="doc-title">
            <?= htmlspecialchars($doc['Title']) ?>
        </div>

        <!-- Top meta: year • source • citation count -->
        <div class="meta-row">
            <span style="font-weight:600;"><?= htmlspecialchars($doc['PublicationYear']) ?></span>
            <?php if (!empty($doc['SourceName'])): ?>
                <span class="meta-separator"></span>
                <span><?= htmlspecialchars($doc['SourceName']) ?></span>
            <?php endif; ?>
            <span class="meta-separator"></span>
            <span class="citation-badge">
                <?= $referenceCount + $citedByCount ?> citations
            </span>
        </div>

        <!-- Authors inline -->
        <div style="font-size:15px; color:#64748b; margin-bottom:20px;">
            <strong style="color:#475569;">Authors:</strong> <?= htmlspecialchars($authorsInline) ?>
        </div>

        <!-- Abstract -->
        <div class="section-title">Abstract</div>
        <p style="line-height:1.8; color:#475569; font-size:15px;">
            <?= nl2br(htmlspecialchars($doc['Abstract'])) ?>
        </p>

        <div class="divider"></div>

        <!-- Authors detailed cards -->
        <div>
            <div class="section-title">Authors</div>
            <?php if (empty($authors)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👤</div>
                    <p>No author information available.</p>
                </div>
            <?php else: ?>
                <div class="authors-grid">
                    <?php foreach ($authors as $a): ?>
                        <div class="author-card">
                            <div class="author-name">
                                <?= htmlspecialchars(trim($a['FirstName'] . ' ' . $a['LastName'])) ?>
                            </div>
                            <?php if (!empty($a['AuthorArea'])): ?>
                                <div class="author-meta">📚 <?= htmlspecialchars($a['AuthorArea']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($a['AffiliationName'])): ?>
                                <div class="author-meta">🏛️ <?= htmlspecialchars($a['AffiliationName']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="divider"></div>

        <!-- Document Information -->
        <div>
            <div class="section-title">Document Information</div>
            <div class="doc-info-grid">
                <div class="doc-info-row">
                    <span class="doc-info-label">Area:</span>
                    <span class="doc-info-value"><?= htmlspecialchars($doc['Area'] ?? 'N/A') ?></span>
                </div>

                <div class="doc-info-row">
                    <span class="doc-info-label">Import Date:</span>
                    <span class="doc-info-value"><?= htmlspecialchars($doc['ImportDate']) ?></span>
                </div>

                <?php if (!empty($doc['ISBN'])): ?>
                    <div class="doc-info-row">
                        <span class="doc-info-label">ISBN:</span>
                        <span class="doc-info-value"><?= htmlspecialchars($doc['ISBN']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($doc['Language'])): ?>
                    <div class="doc-info-row">
                        <span class="doc-info-label">Language:</span>
                        <span class="doc-info-value"><?= htmlspecialchars($doc['Language']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($doc['SourceType'])): ?>
                    <div class="doc-info-row">
                        <span class="doc-info-label">Source Type:</span>
                        <span class="doc-info-value"><?= htmlspecialchars($doc['SourceType']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($doc['SourceName'])): ?>
                    <div class="doc-info-row">
                        <span class="doc-info-label">Source Name:</span>
                        <span class="doc-info-value"><?= htmlspecialchars($doc['SourceName']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($doc['LinkPath'])): ?>
                    <div class="doc-info-row">
                        <span class="doc-info-label">External Link:</span>
                        <span class="doc-info-value">
                            <a href="<?= htmlspecialchars($doc['LinkPath']) ?>" target="_blank" style="color:#667eea; text-decoration:none; font-weight:600;">
                                View Document →
                            </a>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add to collection -->
        <div style="margin-top:32px;">
            <button class="btn-primary" onclick="openAddModal()">
                <span>＋</span> Add to Collection
            </button>
        </div>
    </div>


    <!-- ========== BLOCK 2: Citations ========== -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:24px; margin:0; font-weight:700;">Citations</h2>
            <span style="font-size:13px; color:#94a3b8; font-weight:600;">
                <?= $referenceCount ?> References • <?= $citedByCount ?> Cited By
            </span>
        </div>

        <div class="tabs-header">
            <button id="btn-tab-references" class="btn-ghost" type="button"
                    onclick="showCitationTab('references')">
                References (<?= $referenceCount ?>)
            </button>
            <button id="btn-tab-citedby" class="btn-ghost" type="button"
                    onclick="showCitationTab('citedby')">
                Cited By (<?= $citedByCount ?>)
            </button>
        </div>

        <!-- References -->
        <div id="citations-references">
            <?php if (empty($references)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <p>No references recorded for this document.</p>
                </div>
            <?php else: ?>
                <?php foreach ($references as $ref): ?>
                    <div class="citation-item">
                        <div class="citation-title">
                            <a href="document_view.php?id=<?= $ref['DocumentID'] ?>">
                                <?= htmlspecialchars($ref['Title']) ?>
                            </a>
                        </div>
                        <div class="citation-meta">
                            <?= htmlspecialchars($ref['Authors'] ?: 'Unknown authors') ?>
                            <?php if (!empty($ref['PublicationYear'])): ?>
                                • <?= htmlspecialchars($ref['PublicationYear']) ?>
                            <?php endif; ?>
                            <?php if (!empty($ref['SourceName'])): ?>
                                • <?= htmlspecialchars($ref['SourceName']) ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($ref['ContextPage'])): ?>
                            <div class="citation-context">
                                📄 Cited at page: <?= htmlspecialchars($ref['ContextPage']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Cited By -->
        <div id="citations-citedby" style="display:none;">
            <?php if (empty($citedBy)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔗</div>
                    <p>No documents citing this one yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($citedBy as $cb): ?>
                    <div class="citation-item">
                        <div class="citation-title">
                            <a href="document_view.php?id=<?= $cb['DocumentID'] ?>">
                                <?= htmlspecialchars($cb['Title']) ?>
                            </a>
                        </div>

                        <div class="citation-meta">
                            <?= htmlspecialchars($cb['Authors'] ?: 'Unknown authors') ?>
                            <?php if (!empty($cb['PublicationYear'])): ?>
                                • <?= htmlspecialchars($cb['PublicationYear']) ?>
                            <?php endif; ?>
                            <?php if (!empty($cb['SourceName'])): ?>
                                • <?= htmlspecialchars($cb['SourceName']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
    
    <!-- ========== BLOCK 3: Notes ========== -->
    <?php if ($canShowInteractions): ?>
    <div class="card">
        <h2 style="font-size:24px; margin:0 0 24px 0; font-weight:700;">Notes</h2>

        <!-- Add New Note -->
        <div class="note-new">
            <form method="POST" action="controllers/add_note.php">
                <input type="hidden" name="document_id" value="<?= $docID ?>">

                <div style="margin-bottom:12px; font-weight:600; font-size:14px; color:#475569;">Add New Note</div>

                <textarea name="content" class="note-textarea"
                          placeholder="Write your note here..." required></textarea>

                <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:16px;">
                    <input 
                        type="number" 
                        name="page_num" 
                        class="note-input"
                        placeholder="Page reference (optional)"
                        min="1"
                        style="flex:1; min-width:200px;"
                    >

                    <select name="visibility" class="note-input" style="flex:1; min-width:150px;">
                        <option value="Private">🔒 Private</option>
                        <option value="Public">🌐 Public</option>
                    </select>

                    <button type="submit" class="btn-primary">
                        Add Note
                    </button>
                </div>
            </form>
        </div>

        <!-- Your Notes -->
        <?php if (!empty($myNotes)): ?>
            <div style="margin-top:24px; margin-bottom:12px; font-weight:600; font-size:15px; color:#0f172a;">
                Your Notes (<?= count($myNotes) ?>)
            </div>

            <?php foreach ($myNotes as $n): ?>
                <div class="note-card" style="background:#f0f7ff; position:relative;">
                    <?php if ($n['UserID'] == $userID || $role === 'admin'): ?>
                        <a href="controllers/delete_note.php?id=<?= $n['NoteID'] ?>&doc=<?= $docID ?>"
                            onclick="return confirm('Delete this note?');"
                            class="delete-link">
                            ✖ Delete
                        </a>
                    <?php endif; ?>
                    <div>
                        <span class="note-tag <?= strtolower($n['Visibility']) ?>">
                            <?= htmlspecialchars($n['Visibility']) ?>
                        </span>
                    </div>

                    <div style="margin-top:12px; line-height:1.7;">
                        <?= nl2br(htmlspecialchars($n['Content'])) ?>
                    </div>

                    <div class="note-meta">
                        <?php if (!empty($n['PageNum'])): ?>
                            📄 Page <?= htmlspecialchars($n['PageNum']) ?> •
                        <?php endif; ?>
                        🕐 <?= htmlspecialchars($n['CreatedTime']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Public Notes -->
        <?php if (!empty($publicNotes)): ?>
            <div style="margin-top:32px; margin-bottom:12px; font-weight:600; font-size:15px; color:#0f172a;">
                Public Notes (<?= count($publicNotes) ?>)
            </div>

            <?php foreach ($publicNotes as $n): ?>
                <div class="note-card" style="position:relative;">
                    <?php if ($role === 'admin'): ?>
                        <a href="controllers/delete_note.php?id=<?= $n['NoteID'] ?>&doc=<?= $docID ?>"
                            onclick="return confirm('Delete this public note?');"
                            class="delete-link">
                            ✖ Delete
                        </a>
                    <?php endif; ?>
                    <div>
                        <span class="note-tag public">Public</span>
                    </div>

                    <div style="margin-top:12px; line-height:1.7;">
                        <?= nl2br(htmlspecialchars($n['Content'])) ?>
                    </div>

                    <div class="note-meta">
                        <?php if (!empty($n['PageNum'])): ?>
                            📄 Page <?= htmlspecialchars($n['PageNum']) ?> •
                        <?php endif; ?>
                        👤 <?= htmlspecialchars($n['UserName']) ?>

                        <?php if (!empty($n['Role']) || !empty($n['AffiliationName'])): ?>
                            (<?= htmlspecialchars(trim($n['Role'] . ' ' . ($n['AffiliationName'] ?? ''))) ?>)
                        <?php endif; ?>

                        • 🕐 <?= htmlspecialchars($n['CreatedTime']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($myNotes) && empty($publicNotes)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <p>No notes yet. Be the first to add one!</p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>


    <!-- ========== BLOCK 4: Comments ========== -->
    <?php if ($canShowInteractions): ?>
    <div class="card">
        <h2 style="font-size:24px; margin:0 0 24px 0; font-weight:700;">Comments</h2>

        <!-- Add Comment -->
        <div class="comment-new">
            <form method="POST" action="controllers/add_comment.php">
                <input type="hidden" name="document_id" value="<?= $docID ?>">

                <div style="margin-bottom:12px; font-weight:600; font-size:14px; color:#475569;">Add Comment</div>

                <textarea name="content" class="comment-textarea"
                          placeholder="Share your thoughts about this document..." required></textarea>

                <div style="margin-top:16px;">
                    <button type="submit" class="btn-primary">
                        Post Comment
                    </button>
                </div>
            </form>
        </div>

        <!-- Comments List -->
        <?php if (empty($comments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">💬</div>
                <p>No comments yet. Be the first to comment!</p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="comment-card" style="position:relative;">
                    <?php if ($c['UserID'] == $userID || $role === 'admin'): ?>
                        <a href="controllers/delete_comment.php?id=<?= $c['CommentID'] ?>&doc=<?= $docID ?>"
                            onclick="return confirm('Delete this comment?');"
                            class="delete-link">
                            ✖ Delete
                        </a>
                    <?php endif; ?>
                    <div class="comment-header">
                        <div>
                            <div class="comment-user">👤 <?= htmlspecialchars($c['UserName']) ?></div>
                            <div class="comment-sub">
                                <?= htmlspecialchars(trim(($c['Role'] ?? '') . ' ' . ($c['AffiliationName'] ?? ''))) ?>
                            </div>
                        </div>
                        <div class="comment-sub">
                            🕐 <?= htmlspecialchars($c['CreatedAt']) ?>
                        </div>
                    </div>

                    <div style="margin-top:12px; line-height:1.7;">
                        <?= nl2br(htmlspecialchars($c['Context'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div> <!-- END .main -->


<!-- ============================== -->
<!-- Add To Collection MODAL -->
<!-- ============================== -->
<div id="addModal" class="modal-bg">
    <div class="modal-box">
        <span class="modal-close" onclick="closeAddModal()">✖</span>

        <h2 style="margin-top:0; margin-bottom:20px; font-size:24px; font-weight:700;">Add to Collection</h2>

        <form method="POST" action="controllers/add_to_collection.php">
            <input type="hidden" name="document_id" value="<?= $docID ?>">

            <label style="font-size:14px; font-weight:600; color:#475569; display:block; margin-bottom:8px;">
                Select Collection:
            </label>
            <select name="collection_id" required class="modal-select">
                <?php foreach ($userCollections as $c): ?>
                    <option value="<?= $c['CollectionID'] ?>">
                        <?= htmlspecialchars($c['CollectionName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
                <button type="button"
                        onclick="closeAddModal()"
                        class="btn-ghost">
                    Cancel
                </button>

                <button type="submit" class="btn-primary">
                    Add to Collection
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>