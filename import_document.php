<?php
session_start();
require_once "includes/connect.php";

// only admin can access
if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== "admin") {
    header("Location: dashboard.php");
    exit;
}

$userID   = $_SESSION['UserID'];
$userName = $_SESSION['UserName'];
$role     = $_SESSION['Role'];

/* ---------- Fetch dropdown data ---------- */

// Source list
$src = $conn->query("
    SELECT SourceID, SourceName 
    FROM Source 
    ORDER BY SourceName
")->fetchAll(PDO::FETCH_ASSOC);

// Tag list
$tags = $conn->query("
    SELECT TagID, TagName 
    FROM Tag 
    ORDER BY TagName
")->fetchAll(PDO::FETCH_ASSOC);

// Authors list
$authors = $conn->query("
    SELECT AuthorID, FirstName, LastName 
    FROM Author 
    ORDER BY LastName, FirstName
")->fetchAll(PDO::FETCH_ASSOC);

// Affiliation list (for new author modal)
$affs = $conn->query("
    SELECT AffiliationID, AffiliationName 
    FROM Affiliation 
    ORDER BY AffiliationName
")->fetchAll(PDO::FETCH_ASSOC);

// Approved documents for citation selection
$approvedDocs = $conn->query("
    SELECT DocumentID, Title, PublicationYear
    FROM Document
    WHERE ReviewStatus = 'Approved'
    ORDER BY Title
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Document - Research Hub</title>
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
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            margin-bottom: 32px;
        }

        h1 {
            font-size: 36px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 16px;
        }

        /* ===== CARD ===== */
        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 32px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #475569;
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
            background: #f8fafc;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.input {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        select.input {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 44px;
        }

        .flex-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        /* ===== CHIPS ===== */
        .chips-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            min-height: 40px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px dashed #cbd5e1;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: #4338ca;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #c7d2fe;
        }

        .chip span {
            margin-right: 8px;
        }

        .chip .close {
            cursor: pointer;
            font-weight: bold;
            color: #6366f1;
            margin-left: 4px;
            transition: color 0.2s ease;
        }

        .chip .close:hover {
            color: #4338ca;
        }

        .helper-text {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
        }

        /* ===== MODALS ===== */
        .modal-bg {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            display: none;
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

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .modal-header h3 {
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

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .divider {
            margin: 32px 0;
            border: 0;
            border-top: 2px solid #f1f5f9;
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

            .card {
                padding: 28px 24px;
            }

            .flex-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = "flex";
        }
        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        // ---------- Tags ----------
        function addTag() {
            const select = document.getElementById("tag_select");
            const tagId = select.value;
            if (!tagId) return;

            const tagName = select.options[select.selectedIndex].text;
            const existing = document.getElementById("tag_chip_" + tagId);
            if (existing) return; // prevent duplicate

            const box = document.getElementById("tags_box");
            const chip = document.createElement("div");
            chip.className = "chip";
            chip.id = "tag_chip_" + tagId;
            chip.innerHTML = `
                <span>${tagName}</span>
                <input type="hidden" name="tags[]" value="${tagId}">
                <div class="close" onclick="this.parentNode.remove()">✖</div>
            `;
            box.appendChild(chip);
        }

        // ---------- Authors ----------
        function addAuthor() {
            const select = document.getElementById("author_select");
            const authorId = select.value;
            if (!authorId) return;

            const label = select.options[select.selectedIndex].text;
            const existing = document.getElementById("author_chip_" + authorId);
            if (existing) return;

            const box = document.getElementById("authors_box");
            const chip = document.createElement("div");
            chip.className = "chip";
            chip.id = "author_chip_" + authorId;
            chip.innerHTML = `
                <span>${label}</span>
                <input type="hidden" name="authors[]" value="${authorId}">
                <div class="close" onclick="this.parentNode.remove()">✖</div>
            `;
            box.appendChild(chip);
        }

        // ---------- Citations (with ContextPage) ----------
        function addCitation() {
            const select = document.getElementById("citation_select");
            const docId = select.value;
            if (!docId) return;

            const label = select.options[select.selectedIndex].text;
            const ctxInput = document.getElementById("citation_context");
            const ctx = ctxInput.value.trim();

            const box = document.getElementById("citations_box");

            const chip = document.createElement("div");
            chip.className = "chip";
            chip.id = "citation_chip_" + docId + "_" + (ctx || "noctx");

            const text = ctx ? `${label} – ${ctx}` : label;
            const safeCtx = ctx.replace(/"/g, '&quot;');

            chip.innerHTML = `
                <span>${text}</span>
                <input type="hidden" name="citations_ids[]" value="${docId}">
                <input type="hidden" name="citations_contexts[]" value="${safeCtx}">
                <div class="close" onclick="this.parentNode.remove()">✖</div>
            `;
            box.appendChild(chip);

            ctxInput.value = "";
            select.value = "";
        }
    </script>
</head>

<body>

<!-- ========== SIDEBAR ========== -->
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
        <a href="advanced_search.php">Advanced Search</a>
        <a href="collections.php">Collections</a>
        <a href="notes.php">Notes</a>
        <a href="comments.php">Comments</a>
        <a href="user_account.php">User Account</a>
    </nav>

    <div class="menu-heading">Admin</div>
    <nav class="menu">
        <a href="user_list.php">User List</a>
        <a href="import_document.php" class="active">Import Document</a>
        <a href="review_documents.php">Review Documents</a>
    </nav>

    <a href="logout.php" class="logout">Log Out</a>
</aside>

<!-- ========== MAIN ========== -->
<div class="main">
    <div class="page-header">
        <h1>Import New Document</h1>
        <p class="subtitle">Manually add a new research paper into the system</p>
    </div>

    <div class="card">
        <form action="controllers/submit_document.php" method="POST">

            <!-- BASIC INFO -->
            <div class="section-title">📄 Basic Information</div>

            <div class="form-group">
                <label>Title<span class="required">*</span></label>
                <input type="text" name="title" class="input" required placeholder="Enter document title">
                <div class="helper-text">The full title of the research paper</div>
            </div>

            <div class="form-group">
                <label>Abstract<span class="required">*</span></label>
                <textarea name="abstract" class="input" required placeholder="Enter abstract or summary"></textarea>
                <div class="helper-text">A brief summary of the document's content</div>
            </div>

            <div class="form-group">
                <label>Publication Year<span class="required">*</span></label>
                <input type="number" name="year" class="input" required min="1800" max="2100" placeholder="e.g., 2024">
            </div>

            <div class="form-group">
                <label>Area<span class="required">*</span></label>
                <input type="text" name="area" class="input" required placeholder="e.g., Machine Learning, Biology">
                <div class="helper-text">Research field or subject area</div>
            </div>

            <div class="form-group">
                <label>ISBN<span class="optional-badge">Optional</span></label>
                <input type="text" name="isbn" class="input" placeholder="e.g., 978-3-16-148410-0">
            </div>

            <div class="form-group">
                <label>Link Path<span class="required">*</span></label>
                <input type="text" name="linkpath" class="input" required placeholder="https://example.com/paper.pdf">
                <div class="helper-text">URL to the document or PDF file</div>
            </div>

            <!-- SOURCE -->
            <div class="section-title">📚 Source</div>

            <div class="form-group">
                <label>Source<span class="required">*</span></label>
                <div class="flex-row">
                    <select name="source" class="input" style="flex:1;" required>
                        <option value="">-- Select Source --</option>
                        <?php foreach ($src as $s): ?>
                            <option value="<?= $s['SourceID'] ?>">
                                <?= htmlspecialchars($s['SourceName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline" onclick="openModal('modal_source')">
                        ＋ New Source
                    </button>
                </div>
            </div>

            <!-- TAGS -->
            <div class="section-title">🏷️ Tags</div>

            <div class="form-group">
                <label>Tags<span class="required">*</span></label>
                <div class="flex-row">
                    <select id="tag_select" class="input" style="flex:1;">
                        <option value="">-- Select Tag --</option>
                        <?php foreach ($tags as $t): ?>
                            <option value="<?= $t['TagID'] ?>">
                                <?= htmlspecialchars($t['TagName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline" onclick="addTag()">Add</button>
                    <button type="button" class="btn btn-outline" onclick="openModal('modal_tag')">
                        ＋ New Tag
                    </button>
                </div>
                <div class="helper-text">Add at least one tag to categorize this document</div>
                <div id="tags_box" class="chips-container"></div>
            </div>

            <!-- AUTHORS -->
            <div class="section-title">👥 Authors</div>

            <div class="form-group">
                <label>Authors<span class="required">*</span></label>
                <div class="flex-row">
                    <select id="author_select" class="input" style="flex:1;">
                        <option value="">-- Select Author --</option>
                        <?php foreach ($authors as $a): ?>
                            <option value="<?= $a['AuthorID'] ?>">
                                <?= htmlspecialchars($a['LastName'] . ", " . $a['FirstName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline" onclick="addAuthor()">Add</button>
                    <button type="button" class="btn btn-outline" onclick="openModal('modal_author')">
                        ＋ New Author
                    </button>
                </div>
                <div class="helper-text">Add at least one author</div>
                <div id="authors_box" class="chips-container"></div>
            </div>

            <!-- CITATIONS -->
            <div class="section-title">📖 Citations <span class="optional-badge">Optional</span></div>

            <div class="form-group">
                <label>Referenced Documents</label>
                <div class="flex-row">
                    <select id="citation_select" class="input" style="flex:1;">
                        <option value="">-- Select Approved Document --</option>
                        <?php foreach ($approvedDocs as $d): ?>
                            <option value="<?= $d['DocumentID'] ?>">
                                <?= htmlspecialchars($d['Title']) ?>
                                (<?= htmlspecialchars($d['PublicationYear']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="helper-text">Link this document to papers it cites</div>
            </div>

            <div class="form-group">
                <label>Citation Context (Optional)</label>
                <div class="flex-row">
                    <input
                        type="text"
                        id="citation_context"
                        class="input"
                        style="flex:1;"
                        placeholder="e.g., p.12, Section 3.1"
                    >
                    <button type="button" class="btn btn-outline" onclick="addCitation()">
                        Add Citation
                    </button>
                </div>
                <div class="helper-text">Specify page or section where the citation appears</div>
                <div id="citations_box" class="chips-container"></div>
            </div>

            <hr class="divider">

            <button type="submit" class="btn btn-primary">
                📤 Submit Document
            </button>
        </form>
    </div>
</div>

<!-- ========== MODAL: NEW TAG ========== -->
<div class="modal-bg" id="modal_tag">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Create New Tag</h3>
            <button class="modal-close" onclick="closeModal('modal_tag')">✖</button>
        </div>
        <form action="controllers/add_tag.php" method="POST">
            <div class="form-group">
                <label>Tag Name<span class="required">*</span></label>
                <input type="text" name="tagname" class="input" required placeholder="e.g., Machine Learning">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="tagdesc" class="input" style="height:100px;" placeholder="Optional description"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal_tag')">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Add Tag
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL: NEW SOURCE ========== -->
<div class="modal-bg" id="modal_source">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Create New Source</h3>
            <button class="modal-close" onclick="closeModal('modal_source')">✖</button>
        </div>

        <form action="controllers/add_source.php" method="POST">
            
            <div class="form-group">
                <label>Source Name<span class="required">*</span></label>
                <input type="text" name="name" class="input" required placeholder="e.g., Nature, IEEE">
            </div>

            <div class="form-group">
                <label>Source Type<span class="required">*</span></label>
                <select name="type" class="input" required>
                    <option value="">-- Select Type --</option>
                    <option value="Journal">Journal</option>
                    <option value="Book">Book</option>
                    <option value="Conference">Conference</option>
                    <option value="Report">Report</option>
                    <option value="Website">Website</option>
                    <option value="Thesis">Thesis</option>
                    <option value="Newspaper">Newspaper</option>
                </select>
            </div>

            <div class="form-group">
                <label>Language<span class="required">*</span></label>
                <select name="lang" class="input" required>
                    <option value="">-- Select Language --</option>
                    <option value="English">English</option>
                    <option value="Chinese">Chinese</option>
                    <option value="Japanese">Japanese</option>
                    <option value="French">French</option>
                    <option value="Spanish">Spanish</option>
                    <option value="German">German</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal_source')">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Add Source
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ========== MODAL: NEW AUTHOR ========== -->
<div class="modal-bg" id="modal_author">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Create New Author</h3>
            <button class="modal-close" onclick="closeModal('modal_author')">✖</button>
        </div>
        <form action="controllers/add_author.php" method="POST">
            <div class="form-group">
                <label>First Name<span class="required">*</span></label>
                <input type="text" name="fname" class="input" required placeholder="e.g., John">
            </div>

            <div class="form-group">
                <label>Last Name<span class="required">*</span></label>
                <input type="text" name="lname" class="input" required placeholder="e.g., Smith">
            </div>

            <div class="form-group">
                <label>Affiliation<span class="optional-badge">Optional</span></label>
                <select name="aff" class="input">
                    <option value="">-- Select Affiliation --</option>
                    <?php foreach ($affs as $af): ?>
                        <option value="<?= $af['AffiliationID'] ?>">
                            <?= htmlspecialchars($af['AffiliationName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Area<span class="optional-badge">Optional</span></label>
                <input type="text" name="area" class="input" placeholder="e.g., Computer Science">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal_author')">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Add Author
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>