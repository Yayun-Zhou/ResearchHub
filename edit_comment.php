<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);
$role   = strtolower($_SESSION['Role'] ?? "");

/* ===== Validate input ===== */
if (!isset($_GET['id']) || !isset($_GET['doc'])) {
    die("Missing parameters.");
}

$commentID = intval($_GET['id']);
$docID     = intval($_GET['doc']);

/* ===== Fetch comment ===== */
$sql = "
    SELECT 
        C.CommentID, C.Context, C.DocumentID, C.UserID AS OwnerID,
        D.Title AS DocTitle
    FROM Comment C
    JOIN Document D ON C.DocumentID = D.DocumentID
    WHERE C.CommentID = ?
";
$stmt = $conn->prepare($sql);
$stmt->execute([$commentID]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    die("Comment not found.");
}

/* ===== Permission Check ===== */
if ($comment['OwnerID'] != $userID && $role !== "admin") {
    die("Permission denied.");
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Comment - Research Hub</title>
    <link rel="stylesheet" href="assets/css/globals.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fc;
            display: flex;
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

        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .doc-reference {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 32px;
            border: 1px solid #e2e8f0;
        }

        .doc-reference-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .doc-reference-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 28px;
        }

        label {
            font-weight: 600;
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
            display: block;
        }

        textarea {
            width: 100%;
            min-height: 180px;
            resize: vertical;
            font-size: 15px;
            padding: 16px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-family: inherit;
            line-height: 1.7;
            transition: all 0.3s ease;
            color: #0f172a;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .helper-text {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 8px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            padding-top: 8px;
        }

        .btn-primary,
        .btn-secondary {
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
            text-decoration: none;
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

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
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

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
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
            <div class="role"><?= htmlspecialchars($role) ?></div>
        </div>
    </div>

    <nav class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="advanced_search.php">Advanced Search</a>
        <a href="collections.php">Collections</a>
        <a href="notes.php">Notes</a>
        <a href="comments.php" class="active">Comments</a>
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

    <a href="logout.php" class="logout">Log Out</a>
</aside>

<!-- MAIN -->
<div class="main">

    <a href="comments.php" class="back-link">
        <span>←</span> Back to Comments
    </a>

    <div class="page-header">
        <h1>Edit Comment</h1>
        <p class="subtitle">Update your thoughts on this research paper</p>
    </div>

    <div class="card">

        <!-- Document Reference -->
        <div class="doc-reference">
            <div class="doc-reference-label">Commenting On</div>
            <div class="doc-reference-title">
                <span>📄</span>
                <?= htmlspecialchars($comment['DocTitle']) ?>
            </div>
        </div>

        <!-- Edit Form -->
        <form method="POST" action="controllers/update_comment.php">

            <input type="hidden" name="comment_id" value="<?= $commentID ?>">
            <input type="hidden" name="document_id" value="<?= $docID ?>">

            <div class="form-group">
                <label for="content">Comment Content</label>
                <textarea 
                    id="content"
                    name="content" 
                    required
                    placeholder="Share your insights, analysis, or thoughts about this paper..."><?= htmlspecialchars($comment['Context']) ?></textarea>
                <div class="helper-text">Express your thoughts clearly and constructively</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <span>💾</span> Update Comment
                </button>
                <a href="comments.php" class="btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

</body>
</html>