<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$currentUser = intval($_SESSION['UserID']);
$collectionID = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* ---------------------------
   Fetch Collection Info
--------------------------- */
$sql = "
    SELECT C.*, U.UserName
    FROM Collection C
    JOIN User U ON C.UserID = U.UserID
    WHERE C.CollectionID = ?
";
$stmt = $conn->prepare($sql);
$stmt->execute([$collectionID]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$collection) {
    die("Collection not found.");
}

$isOwner = ($currentUser === intval($collection['UserID']));

/* ---------------------------
   Fetch Documents with Tags + Citation Count
--------------------------- */
$docSQL = "
    SELECT 
        D.DocumentID,
        D.Title,
        D.Abstract,
        D.PublicationYear,
        S.SourceName,
        GROUP_CONCAT(DISTINCT CONCAT(A.FirstName, ' ', A.LastName) SEPARATOR ', ') AS Authors,
        GROUP_CONCAT(DISTINCT T.TagName SEPARATOR ', ') AS Tags,
        (SELECT COUNT(*) FROM Citation WHERE CitedDocumentID = D.DocumentID) AS CitationCount
    FROM CollectionDocument CD
    JOIN Document D ON CD.DocumentID = D.DocumentID
    LEFT JOIN Source S ON D.SourceID = S.SourceID
    LEFT JOIN DocumentAuthor DA ON D.DocumentID = DA.DocumentID
    LEFT JOIN Author A ON DA.AuthorID = A.AuthorID
    LEFT JOIN DocumentTag DT ON D.DocumentID = DT.DocumentID
    LEFT JOIN Tag T ON DT.TagID = T.TagID
    WHERE CD.CollectionID = ?
    GROUP BY D.DocumentID
";
$stmt = $conn->prepare($docSQL);
$stmt->execute([$collectionID]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($collection['CollectionName']) ?></title>

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
    min-height: 100vh;
}

/* -------- SIDEBAR -------- */
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

.title {
    font-size: 22px;
    font-weight: 700;
    color: white;
    letter-spacing: -0.5px;
}

.role {
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

/* -------- MAIN CONTENT -------- */
.main {
    flex: 1;
    padding: 48px 56px;
    max-width: 1400px;
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

/* -------- COLLECTION HEADER -------- */
.info-box {
    background: white;
    padding: 40px;
    border-radius: 20px;
    margin-bottom: 40px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
}

.info-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 24px;
}

.collection-title {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 12px 0;
    color: #0f172a;
    line-height: 1.3;
}

.collection-desc {
    color: #64748b;
    font-size: 16px;
    line-height: 1.7;
}

.action-buttons {
    display: flex;
    gap: 12px;
    flex-shrink: 0;
}

.btn-edit, .btn-danger {
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-edit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

.info-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.meta-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #94a3b8;
    letter-spacing: 0.5px;
}

.meta-value {
    font-size: 15px;
    color: #0f172a;
    font-weight: 600;
}

.visibility-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}

.visibility-badge.public {
    background: #d1fae5;
    color: #065f46;
}

.visibility-badge.private {
    background: #f3f4f6;
    color: #374151;
}

/* -------- DOCUMENT LIST -------- */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.section-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
}

.doc-count {
    background: #f1f5f9;
    color: #64748b;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.doc-list {
    background: white;
    padding: 32px;
    border-radius: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
}

.doc-item {
    padding: 28px 0;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.doc-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.doc-item:first-child {
    padding-top: 0;
}

.doc-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
    line-height: 1.4;
}

.tags-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.tag {
    background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
    color: #4c1d95;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.no-tags {
    color: #94a3b8;
    font-size: 13px;
    font-style: italic;
}

.abstract {
    margin: 12px 0;
    color: #64748b;
    line-height: 1.7;
    font-size: 15px;
}

.meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    color: #64748b;
    font-size: 14px;
    margin-top: 16px;
}

.meta-row strong {
    color: #475569;
}

.doc-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-view {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.remove-btn {
    background: white;
    color: #ef4444;
    padding: 10px 18px;
    border: 2px solid #fee2e2;
    border-radius: 10px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.remove-btn:hover {
    background: #fef2f2;
    border-color: #fecaca;
    transform: translateY(-1px);
}

.empty-state {
    text-align: center;
    padding: 80px 24px;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-text {
    color: #94a3b8;
    font-size: 16px;
    margin-bottom: 24px;
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

    .info-header {
        flex-direction: column;
    }

    .action-buttons {
        width: 100%;
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

    .collection-title {
        font-size: 24px;
    }

    .info-box {
        padding: 24px;
    }

    .doc-list {
        padding: 20px;
    }

    .doc-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-view,
    .remove-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>

<body>

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar">
    <div class="app-title">
        <span class="icon">🔖</span>
        <div>
            <div class="title">Research Hub</div>
            <div class="role"><?= strtolower($_SESSION['Role']) ?></div>
        </div>
    </div>

    <nav class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="advanced_search.php">Advanced Search</a>
        <a href="collections.php" class="active">Collections</a>
        <a href="notes.php">Notes</a>
        <a href="comments.php">Comments</a>
        <a href="user_account.php">User Account</a>
    </nav>

    <?php if (strtolower($_SESSION['Role']) === "admin"): ?>
        <div class="menu-heading">Admin</div>
        <nav class="menu">
            <a href="user_list.php">User List</a>
            <a href="import_document.php">Import Document</a>
            <a href="review_documents.php">Review Documents</a>
        </nav>
    <?php endif; ?>

    <a class="logout" href="logout.php">Log Out</a>
</aside>

<!-- ========== MAIN CONTENT ========== -->
<div class="main">

    <a class="back-link" href="collections.php">
        <span>←</span> Back to Collections
    </a>

    <!-- COLLECTION INFO -->
    <div class="info-box">
        <div class="info-header">
            <div style="flex: 1;">
                <div class="collection-title"><?= htmlspecialchars($collection['CollectionName']) ?></div>
                <?php if (!empty($collection['CollectionDescription'])): ?>
                    <div class="collection-desc"><?= nl2br(htmlspecialchars($collection['CollectionDescription'])) ?></div>
                <?php endif; ?>
            </div>

            <?php if ($isOwner): ?>
            <div class="action-buttons">
                <a href="edit_collection.php?id=<?= $collectionID ?>">
                    <button class="btn-edit">
                        <span>✏️</span> Edit
                    </button>
                </a>
                <form action="controllers/delete_collection.php" method="POST" style="display:inline;"
                      onsubmit="return confirm('Are you sure you want to delete this collection? This action cannot be undone.');">
                    <input type="hidden" name="id" value="<?= $collectionID ?>">
                    <button class="btn-danger">
                        <span>🗑️</span> Delete
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="info-meta">
            <div class="meta-item">
                <div class="meta-label">Visibility</div>
                <div class="meta-value">
                    <span class="visibility-badge <?= strtolower($collection['Visibility']) ?>">
                        <?= $collection['Visibility'] === 'Public' ? '🌐' : '🔒' ?> 
                        <?= htmlspecialchars($collection['Visibility']) ?>
                    </span>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Owner</div>
                <div class="meta-value">👤 <?= htmlspecialchars($collection['UserName']) ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Created</div>
                <div class="meta-value">📅 <?= htmlspecialchars($collection['CreatedTime']) ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Last Updated</div>
                <div class="meta-value">🕐 <?= htmlspecialchars($collection['UpdatedTime']) ?></div>
            </div>
        </div>
    </div>

    <!-- DOCUMENT LIST -->
    <div class="section-header">
        <h2>Documents in Collection</h2>
        <div class="doc-count"><?= count($documents) ?> document<?= count($documents) != 1 ? 's' : '' ?></div>
    </div>

    <div class="doc-list">

        <?php if (count($documents) === 0): ?>

            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <div class="empty-state-text">
                    No documents in this collection yet.<br>
                    Start adding papers from search results.
                </div>
                <a href="advanced_search.php"><button class="btn-edit">🔍 Search Documents</button></a>
            </div>

        <?php else: ?>

            <?php foreach ($documents as $d): ?>
            <div class="doc-item">

                <!-- Title -->
                <div class="doc-title"><?= htmlspecialchars($d['Title']) ?></div>

                <!-- Tags -->
                <div class="tags-row">
                    <?php if (!empty($d['Tags'])): ?>
                        <?php foreach (explode(", ", $d['Tags']) as $tag): ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="no-tags">No tags</span>
                    <?php endif; ?>
                </div>

                <!-- Abstract Preview -->
                <?php if (!empty($d['Abstract'])): ?>
                <div class="abstract">
                    <?= htmlspecialchars(substr($d['Abstract'], 0, 280)) . (strlen($d['Abstract']) > 280 ? '...' : '') ?>
                </div>
                <?php endif; ?>

                <!-- Meta Info -->
                <div class="meta-row">
                    <div><strong>Authors:</strong> <?= htmlspecialchars($d['Authors'] ?: "Unknown") ?></div>
                    <?php if (!empty($d['SourceName'])): ?>
                        <div><strong>Source:</strong> <?= htmlspecialchars($d['SourceName']) ?></div>
                    <?php endif; ?>
                    <div><strong>Year:</strong> <?= htmlspecialchars($d['PublicationYear']) ?></div>
                    <div><strong>Citations:</strong> <?= intval($d['CitationCount']) ?></div>
                </div>

                <!-- Action Buttons -->
                <div class="doc-actions">
                    <a href="document_view.php?id=<?= $d['DocumentID'] ?>" class="btn-view">
                        <span>📄</span> View Full Document
                    </a>

                    <?php if ($isOwner): ?>
                    <form action="controllers/remove_from_collection.php" method="POST"
                          onsubmit="return confirm('Remove this document from the collection?');">
                        <input type="hidden" name="collection_id" value="<?= $collectionID ?>">
                        <input type="hidden" name="document_id" value="<?= $d['DocumentID'] ?>">
                        <button class="remove-btn">✖ Remove</button>
                    </form>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</div>

</body>
</html>