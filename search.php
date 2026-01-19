<?php
session_start();
require_once "includes/connect.php";

// must be logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];
$role   = $_SESSION['Role'] ?? '';

// must have a search query
if (!isset($_GET['q'])) {
    header("Location: dashboard.php");
    exit;
}

$q = trim($_GET['q']);
$qLike = "%$q%";

/* ===========================================
   Search Approved Documents + Tags + Citation
=========================================== */
$sql = "
    SELECT 
        D.DocumentID,
        D.Title,
        D.Abstract,
        D.Area,
        D.PublicationYear,
        S.SourceName,

        -- Authors
        GROUP_CONCAT(DISTINCT CONCAT(A.FirstName, ' ', A.LastName) SEPARATOR ', ') AS Authors,

        -- Tags
        GROUP_CONCAT(DISTINCT T.TagName SEPARATOR ', ') AS Tags,

        -- Citation Count
        COUNT(DISTINCT C.CitationID) AS CitationCount

    FROM Document D
    LEFT JOIN Source S ON D.SourceID = S.SourceID
    LEFT JOIN DocumentAuthor DA ON D.DocumentID = DA.DocumentID
    LEFT JOIN Author A ON DA.AuthorID = A.AuthorID
    LEFT JOIN DocumentTag DT ON D.DocumentID = DT.DocumentID
    LEFT JOIN Tag T ON DT.TagID = T.TagID
    LEFT JOIN Citation C ON D.DocumentID = C.CitedDocumentID

    WHERE D.ReviewStatus = 'Approved'
      AND (
          D.Title      LIKE ? OR
          D.Abstract   LIKE ? OR
          D.Area       LIKE ? OR
          A.FirstName  LIKE ? OR
          A.LastName   LIKE ? OR
          T.TagName    LIKE ? OR
          S.SourceName LIKE ?
      )

    GROUP BY D.DocumentID
";

$stmt = $conn->prepare($sql);
$stmt->execute([$qLike,$qLike,$qLike,$qLike,$qLike,$qLike,$qLike]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================================
   Current User's Collections
=========================================== */
$stmt = $conn->prepare("
    SELECT CollectionID, CollectionName 
    FROM Collection
    WHERE UserID = ?
    ORDER BY UpdatedTime DESC
");
$stmt->execute([$userID]);
$userCollections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Research Hub</title>
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

        /* ===== SIDEBAR ===== */
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

        /* ===== MAIN ===== */
        .main {
            flex: 1;
            padding: 48px 56px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            margin-bottom: 40px;
        }

        h1 {
            font-size: 36px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .search-query {
            color: #667eea;
            font-weight: 700;
        }

        .results-count {
            color: #64748b;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .results-badge {
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 15px;
            line-height: 1.5;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%);
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* ===== RESULT CARD ===== */
        .result-card {
            background: white;
            padding: 32px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .result-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .result-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #0f172a;
            line-height: 1.3;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
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
            letter-spacing: 0.5px;
            color: #64748b;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .tags-section {
            margin-bottom: 20px;
        }

        .tags-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tag {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: #4f46e5;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #c7d2fe;
        }

        .abstract {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 24px;
            font-size: 15px;
            padding: 16px;
            background: #fafbfc;
            border-left: 3px solid #667eea;
            border-radius: 8px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-add {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        /* ===== MODAL ===== */
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
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-box {
            background: white;
            padding: 32px;
            border-radius: 20px;
            width: 520px;
            max-width: 90%;
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

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .modal-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .modal-close {
            cursor: pointer;
            font-size: 24px;
            color: #94a3b8;
            background: none;
            border: none;
            padding: 4px;
            transition: color 0.2s ease;
        }

        .modal-close:hover {
            color: #475569;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
        }

        .required {
            color: #ef4444;
            margin-left: 4px;
        }

        .optional-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 8px;
        }

        .input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            background: #f8fafc;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.input {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }

        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            background: #f8fafc;
            transition: all 0.3s ease;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 44px;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn-cancel {
            padding: 12px 24px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: #cbd5e1;
        }

        .btn-secondary {
            padding: 12px 24px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-submit {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            background: white;
            padding: 80px 40px;
            border-radius: 16px;
            border: 2px dashed #e2e8f0;
            text-align: center;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .empty-state-text {
            color: #64748b;
            font-size: 15px;
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

            h1 {
                font-size: 28px;
            }

            .result-card {
                padding: 24px;
            }

            .meta {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .modal-actions {
                flex-direction: column;
            }

            .btn-cancel,
            .btn-secondary,
            .btn-submit {
                width: 100%;
            }
        }
    </style>

    <script>
        function openAddModal(docID) {
            // 设置两个表单中的 document_id
            document.getElementById("modal_document_id").value = docID;
            document.getElementById("modal_document_id_create").value = docID;
            
            document.getElementById("addModal").style.display = "flex";
            // 确保显示选择收藏夹的界面
            document.getElementById("select_collection_view").style.display = "block";
            document.getElementById("create_collection_view").style.display = "none";
        }
        
        function closeAddModal() {
            document.getElementById("addModal").style.display = "none";
        }
        
        function showCreateCollection() {
            document.getElementById("select_collection_view").style.display = "none";
            document.getElementById("create_collection_view").style.display = "block";
        }
        
        function showSelectCollection() {
            document.getElementById("create_collection_view").style.display = "none";
            document.getElementById("select_collection_view").style.display = "block";
        }
    </script>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="app-title">
        <span class="icon">🔖</span>
        <div>
            <div class="title">Research Hub</div>
            <div class="role"><?= htmlspecialchars(strtolower($role)) ?></div>
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

    <?php if (strtolower($role) === "admin"): ?>
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
    <div class="page-header">
        <h1>
            Search Results for: <span class="search-query">"<?= htmlspecialchars($q) ?>"</span>
        </h1>
        <div class="results-count">
            <span class="results-badge"><?= count($results) ?> result<?= count($results) != 1 ? 's' : '' ?></span>
            <span>found</span>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <span>✓</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (count($results) === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <div class="empty-state-title">No Results Found</div>
            <div class="empty-state-text">
                No documents matched your search query. Try different keywords or filters.
            </div>
        </div>

    <?php else: ?>
        <?php foreach ($results as $doc): ?>
            <div class="result-card">
                
                <div class="result-title"><?= htmlspecialchars($doc['Title']) ?></div>

                <div class="meta">
                    <div class="meta-item">
                        <div class="meta-label">Authors</div>
                        <div class="meta-value"><?= htmlspecialchars($doc['Authors'] ?: "Unknown") ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Area</div>
                        <div class="meta-value"><?= htmlspecialchars($doc['Area']) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Source</div>
                        <div class="meta-value"><?= htmlspecialchars($doc['SourceName']) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Year</div>
                        <div class="meta-value"><?= htmlspecialchars($doc['PublicationYear']) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Citations</div>
                        <div class="meta-value"><?= intval($doc['CitationCount']) ?></div>
                    </div>
                </div>

                <div class="tags-section">
                    <div class="tags-label">Tags</div>
                    <div class="tags">
                        <?php if ($doc['Tags']): ?>
                            <?php foreach (explode(", ", $doc['Tags']) as $tag): ?>
                                <span class="tag"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color:#94a3b8; font-size:14px;">No tags</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="abstract">
                    <?= nl2br(htmlspecialchars(substr($doc['Abstract'], 0, 300))) ?><?= strlen($doc['Abstract']) > 300 ? '...' : '' ?>
                </div>

                <div class="actions">
                    <a href="document_view.php?id=<?= $doc['DocumentID'] ?>" class="btn btn-view">
                        📄 View Full Document
                    </a>
                    <button class="btn btn-add" onclick="openAddModal(<?= $doc['DocumentID'] ?>)">
                        ＋ Add to Collection
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ADD TO COLLECTION MODAL -->
<div id="addModal" class="modal-bg">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add to Collection</h2>
            <button class="modal-close" onclick="closeAddModal()">✖</button>
        </div>

        <!-- VIEW 1: Select Existing Collection -->
        <div id="select_collection_view">
            <form method="POST" action="controllers/add_to_collection.php">
                <input type="hidden" id="modal_document_id" name="document_id">

                <div class="form-group">
                    <label for="collection_select">Select a Collection</label>
                    <select id="collection_select" name="collection_id" <?= count($userCollections) > 0 ? 'required' : '' ?>>
                        <?php if (count($userCollections) === 0): ?>
                            <option value="" disabled selected>No collections found</option>
                        <?php else: ?>
                            <option value="">-- Choose Collection --</option>
                            <?php foreach ($userCollections as $c): ?>
                                <option value="<?= $c['CollectionID'] ?>">
                                    <?= htmlspecialchars($c['CollectionName']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">
                        Cancel
                    </button>
                    <button type="button" class="btn-secondary" onclick="showCreateCollection()">
                        ＋ New Collection
                    </button>
                    <button type="submit" class="btn-submit" <?= count($userCollections) === 0 ? 'disabled' : '' ?>>
                        Add to Collection
                    </button>
                </div>
            </form>
        </div>

        <!-- VIEW 2: Create New Collection -->
        <div id="create_collection_view" style="display: none;">
            <form method="POST" action="controllers/create_and_add_to_collection.php">
                <input type="hidden" name="document_id" id="modal_document_id_create">

                <div class="form-group">
                    <label for="new_collection_name">
                        Collection Name<span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="new_collection_name"
                           name="collection_name" 
                           class="input" 
                           required
                           placeholder="e.g., Machine Learning Papers">
                </div>

                <div class="form-group">
                    <label for="new_collection_desc">
                        Description<span class="optional-badge">Optional</span>
                    </label>
                    <textarea id="new_collection_desc"
                              name="collection_description" 
                              class="input" 
                              placeholder="Brief description of this collection..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="showSelectCollection()">
                        ← Back
                    </button>
                    <button type="submit" class="btn-submit">
                        Create & Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>