<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];

$collectionID = $_GET['id'] ?? null;
if (!$collectionID) {
    header("Location: collections.php");
    exit;
}

/* ---- Fetch collection info ---- */
$stmt = $conn->prepare("
    SELECT * FROM Collection WHERE CollectionID = ?
");
$stmt->execute([$collectionID]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$collection) {
    die("Collection not found.");
}

/* ---- Permission: only owner can edit ---- */
if ($collection['UserID'] != $userID) {
    die("❌ Permission denied. You can only edit your own collection.");
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Collection - Research Hub</title>
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
            max-width: 900px;
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

        /* ===== FORM CARD ===== */
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 28px;
        }

        .form-group:last-of-type {
            margin-bottom: 32px;
        }

        label {
            font-weight: 600;
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
            display: block;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            color: #0f172a;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            min-height: 140px;
            resize: vertical;
            line-height: 1.6;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 44px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            padding-top: 8px;
        }

        .btn-save,
        .btn-cancel {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-save:active {
            transform: translateY(0);
        }

        .btn-cancel {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .helper-text {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
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

            .form-card {
                padding: 28px 24px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-save,
            .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
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


<!-- MAIN -->
<div class="main">

    <a href="collection_view.php?id=<?= $collectionID ?>" class="back-link">
        <span>←</span> Back to Collection
    </a>

    <div class="page-header">
        <h1>Edit Collection</h1>
        <p class="subtitle">Update details for "<?= htmlspecialchars($collection['CollectionName']) ?>"</p>
    </div>

    <div class="form-card">
        <form action="controllers/update_collection.php" method="POST">

            <input type="hidden" name="collection_id" value="<?= $collectionID ?>">

            <div class="form-group">
                <label for="name">Collection Name</label>
                <input type="text" 
                       id="name"
                       name="name" 
                       required 
                       placeholder="e.g., Machine Learning Papers"
                       value="<?= htmlspecialchars($collection['CollectionName']) ?>">
                <div class="helper-text">Choose a descriptive name for your collection</div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description"
                          name="description"
                          placeholder="Describe what this collection is about..."><?= htmlspecialchars($collection['CollectionDescription']) ?></textarea>
                <div class="helper-text">Optional: Add details about the collection's purpose or contents</div>
            </div>

            <div class="form-group">
                <label for="visibility">Visibility</label>
                <select id="visibility" name="visibility">
                    <option value="Private" <?= $collection['Visibility'] === 'Private' ? 'selected' : '' ?>>
                        🔒 Private (only you can view)
                    </option>
                    <option value="Public" <?= $collection['Visibility'] === 'Public' ? 'selected' : '' ?>>
                        🌐 Public (everyone can view)
                    </option>
                </select>
                <div class="helper-text">Control who can see this collection</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <span>💾</span> Save Changes
                </button>
                <a href="collection_view.php?id=<?= $collectionID ?>" class="btn-cancel">
                    Cancel
                </a>
            </div>

        </form>
    </div>

</div>

</body>
</html>