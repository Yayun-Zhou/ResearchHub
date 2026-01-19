<?php
// ----- DEBUG -----
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "includes/connect.php";

// ----- CHECK LOGIN -----
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID   = $_SESSION['UserID'];
$userName = $_SESSION['UserName'];
$role     = $_SESSION['Role'];

// -------------------------------
//       PROCESS SEARCH INPUT
// -------------------------------

$q_title   = $_GET['title']   ?? "";
$q_author  = $_GET['author']  ?? "";
$q_tag     = $_GET['tag']     ?? "";
$year_from = $_GET['year_from'] ?? "";
$year_to   = $_GET['year_to']   ?? "";
$sort      = $_GET['sort']      ?? "relevance";

// Check if any search has been performed
$hasSearched = isset($_GET['title']) || isset($_GET['author']) || isset($_GET['tag']) || isset($_GET['year_from']) || isset($_GET['year_to']);

$results = [];

// Only perform search if user has submitted the form
if ($hasSearched) {
    // Set default year range if not provided
    if ($year_from === "") {
        $year_from = 1900;
    }
    if ($year_to === "") {
        $year_to = date('Y');
    }

    // -------------------------------
    //         BUILD SQL QUERY
    // -------------------------------

    $sql = "
        SELECT 
            D.DocumentID,
            D.Title,
            D.Abstract,
            D.PublicationYear,
            GROUP_CONCAT(DISTINCT CONCAT(A.FirstName,' ',A.LastName) SEPARATOR ', ') AS Authors,
            GROUP_CONCAT(DISTINCT T.TagName SEPARATOR ', ') AS Tags,
            (
                (D.Title LIKE :title_exact) * 3 +
                (D.Abstract LIKE :title_exact) * 2 +
                (CONCAT(A.FirstName,' ',A.LastName) LIKE :author_exact) * 2 +
                (T.TagName LIKE :tag_exact) * 1
            ) AS relevance_score
        FROM Document D
        LEFT JOIN DocumentAuthor DA ON D.DocumentID = DA.DocumentID
        LEFT JOIN Author A ON DA.AuthorID = A.AuthorID
        LEFT JOIN DocumentTag DT ON D.DocumentID = DT.DocumentID
        LEFT JOIN Tag T ON DT.TagID = T.TagID
        WHERE 1=1
            AND D.ReviewStatus = 'approved'
    ";

    if ($q_title !== "") {
        $sql .= " AND (D.Title LIKE :title OR D.Abstract LIKE :title)";
    }
    if ($q_author !== "") {
        $sql .= " AND CONCAT(A.FirstName,' ',A.LastName) LIKE :author";
    }
    if ($q_tag !== "") {
        $sql .= " AND T.TagName LIKE :tag";
    }

    $sql .= " AND (D.PublicationYear BETWEEN :year_from AND :year_to)";
    $sql .= " GROUP BY D.DocumentID ";

    if ($sort === "newest") {
        $sql .= " ORDER BY D.PublicationYear DESC";
    } elseif ($sort === "title") {
        $sql .= " ORDER BY D.Title ASC";
    } else {
        $sql .= " ORDER BY relevance_score DESC";
    }

    $stmt = $conn->prepare($sql);

    if ($q_title !== "") {
        $stmt->bindValue(':title', "%$q_title%");
    }
    if ($q_author !== "") {
        $stmt->bindValue(':author', "%$q_author%");
    }
    if ($q_tag !== "") {
        $stmt->bindValue(':tag', "%$q_tag%");
    }
    $stmt->bindValue(':year_from', $year_from, PDO::PARAM_INT);
    $stmt->bindValue(':year_to', $year_to, PDO::PARAM_INT);
    $stmt->bindValue(':title_exact', "%$q_title%");
    $stmt->bindValue(':author_exact', "%$q_author%");
    $stmt->bindValue(':tag_exact', "%$q_tag%");

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===== Load current user's collections ===== */
$stmt2 = $conn->prepare("
    SELECT CollectionID, CollectionName
    FROM Collection
    WHERE UserID = ?
    ORDER BY UpdatedTime DESC
");
$stmt2->execute([$userID]);
$userCollections = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Get success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - Research Hub</title>
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

/* ------ SIDEBAR ------ */
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

/* ------ MAIN ------ */
.main {
    flex: 1;
    padding: 48px 56px;
    max-width: 1600px;
    margin: 0 auto;
    width: 100%;
}

h1 {
    font-size: 36px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}

.subtitle {
    color: #64748b;
    margin-bottom: 32px;
    font-size: 16px;
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

.search-layout {
    display: flex;
    gap: 32px;
    align-items: flex-start;
}

/* ------ FILTERS BOX ------ */
.filters-box {
    width: 360px;
    background: white;
    padding: 32px;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    position: sticky;
    top: 32px;
}

.filters-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 24px;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group {
    margin-bottom: 20px;
}

.filter-label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #475569;
    display: block;
}

.input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    background: #f8fafc;
    font-size: 15px;
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

.year-inputs {
    display: flex;
    gap: 12px;
}

.year-inputs .input {
    flex: 1;
}

.search-btn {
    margin-top: 24px;
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.search-btn:active {
    transform: translateY(0);
}

/* ------- RESULTS BOX ------- */
.results-box {
    flex: 1;
    min-width: 0;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.results-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
}

.results-count {
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
    background: #f1f5f9;
    padding: 6px 14px;
    border-radius: 20px;
}

.result-card {
    background: white;
    padding: 32px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.result-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: #cbd5e1;
    transform: translateY(-2px);
}

.result-title {
    font-size: 20px;
    margin-bottom: 12px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.4;
}

.result-abstract {
    color: #64748b;
    margin-bottom: 16px;
    line-height: 1.7;
    font-size: 15px;
}

.result-meta {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.meta-separator {
    color: #cbd5e1;
}

.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
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

.result-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* Buttons */
.btn {
    padding: 12px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    text-decoration: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-add {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-outline {
    background: white;
    border: 2px solid #e2e8f0;
    color: #475569;
}

.btn-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.empty-state {
    text-align: center;
    padding: 80px 24px;
    background: white;
    border-radius: 16px;
    border: 2px dashed #e2e8f0;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-text {
    color: #94a3b8;
    font-size: 16px;
    line-height: 1.6;
}

.initial-state {
    text-align: center;
    padding: 100px 24px;
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.initial-state-icon {
    font-size: 80px;
    margin-bottom: 24px;
    opacity: 0.6;
}

.initial-state-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
}

.initial-state-text {
    color: #64748b;
    font-size: 16px;
    line-height: 1.6;
    max-width: 500px;
    margin: 0 auto;
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

/* Responsive */
@media (max-width: 1024px) {
    .sidebar {
        width: 240px;
        padding: 24px 16px;
    }

    .main {
        padding: 32px 24px;
    }

    .search-layout {
        flex-direction: column;
    }

    .filters-box {
        width: 100%;
        position: relative;
        top: 0;
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

    .result-actions {
        flex-direction: column;
        align-items: stretch;
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

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">
    <div class="app-title">
        <span class="icon">🔖</span>
        <div>
            <div class="title">Research Hub</div>
            <div class="role"><?= strtolower(htmlspecialchars($role)) ?></div>
        </div>
    </div>

    <nav class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="advanced_search.php" class="active">Advanced Search</a>
        <a href="collections.php">Collections</a>
        <a href="notes.php">Notes</a>
        <a href="comments.php">Comments</a>
        <a href="user_account.php">User Account</a>
    </nav>

    <?php if (strtolower($role)==="admin"): ?>
    <div class="menu-heading">Admin</div>
    <nav class="menu">
        <a href="user_list.php">User List</a>
        <a href="import_document.php">Import Document</a>
        <a href="review_documents.php">Review Documents</a>
    </nav>
    <?php endif; ?>

    <a href="logout.php" class="logout">Log Out</a>
</aside>

<!-- ===== MAIN ===== -->
<div class="main">

<h1>Advanced Search</h1>
<p class="subtitle">Find research papers with advanced filters and precise criteria</p>

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

<div class="search-layout">

<!-- FILTERS -->
<div class="filters-box">
    <div class="filters-title">
        <span>🔍</span> Search Filters
    </div>

    <form method="GET">

        <div class="filter-group">
            <label class="filter-label">Title or Keywords</label>
            <input class="input" type="text" name="title" 
                   placeholder="Enter title or keywords..."
                   value="<?= htmlspecialchars($q_title) ?>">
        </div>

        <div class="filter-group">
            <label class="filter-label">Authors</label>
            <input class="input" type="text" name="author" 
                   placeholder="Author name..."
                   value="<?= htmlspecialchars($q_author) ?>">
        </div>

        <div class="filter-group">
            <label class="filter-label">Tags</label>
            <input class="input" type="text" name="tag" 
                   placeholder="Search by tag..."
                   value="<?= htmlspecialchars($q_tag) ?>">
        </div>

        <div class="filter-group">
            <label class="filter-label">Publication Year Range</label>
            <div class="year-inputs">
                <input class="input" type="number" name="year_from" 
                       placeholder="From"
                       value="<?= htmlspecialchars($year_from) ?>" min="1900" max="2025">
                <input class="input" type="number" name="year_to" 
                       placeholder="To"
                       value="<?= htmlspecialchars($year_to) ?>" min="1900" max="2025">
            </div>
        </div>

        <div class="filter-group">
            <label class="filter-label">Sort By</label>
            <select name="sort" class="input">
                <option value="relevance" <?= $sort=="relevance"?"selected":"" ?>>Relevance</option>
                <option value="newest" <?= $sort=="newest"?"selected":"" ?>>Newest First</option>
                <option value="title" <?= $sort=="title"?"selected":"" ?>>Title (A–Z)</option>
            </select>
        </div>

        <button type="submit" class="search-btn">
            🔍 Search Documents
        </button>

    </form>
</div>

<!-- RESULTS -->
<div class="results-box">

    <?php if (!$hasSearched): ?>
        <!-- Initial state - no search performed yet -->
        <div class="initial-state">
            <div class="initial-state-icon">🔍</div>
            <div class="initial-state-title">Ready to Search</div>
            <div class="initial-state-text">
                Use the filters on the left to search for research papers.<br>
                You can search by title, author, tags, or publication year.
            </div>
        </div>

    <?php else: ?>
        <!-- Search has been performed -->
        <div class="results-header">
            <h2>Search Results</h2>
            <div class="results-count">
                <?= count($results) ?> document<?= count($results) != 1 ? 's' : '' ?> found
            </div>
        </div>

        <?php if (empty($results)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-text">
                    No documents found matching your criteria.<br>
                    Try adjusting your search filters.
                </div>
            </div>
        <?php else: ?>

            <?php foreach ($results as $d): ?>
            <div class="result-card">

                <h3 class="result-title">
                    <?= htmlspecialchars($d['Title']) ?>
                </h3>

                <p class="result-abstract">
                    <?= htmlspecialchars(substr($d['Abstract'], 0, 200)) . (strlen($d['Abstract']) > 200 ? "..." : "") ?>
                </p>

                <div class="result-meta">
                    <span>👤 <?= htmlspecialchars($d['Authors'] ?: 'Unknown authors') ?></span>
                    <span class="meta-separator">•</span>
                    <span>📅 <?= htmlspecialchars($d['PublicationYear']) ?></span>
                </div>

                <?php if (!empty($d['Tags'])): ?>
                <div class="tags-container">
                    <?php foreach (explode(",", $d['Tags']) as $tg): ?>
                        <?php if (trim($tg) !== ""): ?>
                            <span class="tag"><?= htmlspecialchars(trim($tg)) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="result-actions">
                    <!-- View Details -->
                    <a href="document_view.php?id=<?= $d['DocumentID'] ?>" class="btn btn-primary">
                        <span>📄</span> View Details
                    </a>

                    <!-- Add to Collection -->
                    <button class="btn btn-add" onclick="openAddModal(<?= $d['DocumentID'] ?>)">
                        ＋ Add to Collection
                    </button>
                </div>

            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    <?php endif; ?>

</div>
</div>

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