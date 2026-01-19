<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);
$role   = strtolower($_SESSION['Role'] ?? "");

/* Fetch comments by this user */
$sql = "
    SELECT 
        C.CommentID, C.Context, C.CreatedAt,
        C.DocumentID, C.UserID AS CommentOwner,
        D.Title AS DocTitle,
        D.PublicationYear,
        GROUP_CONCAT(CONCAT(A.FirstName,' ',A.LastName) SEPARATOR ', ') AS Authors
    FROM Comment C
    JOIN Document D ON C.DocumentID = D.DocumentID
    LEFT JOIN DocumentAuthor DA ON D.DocumentID = DA.DocumentID
    LEFT JOIN Author A ON DA.AuthorID = A.AuthorID
    WHERE C.UserID = ?
    GROUP BY C.CommentID, D.DocumentID
    ORDER BY C.CreatedAt DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$userID]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Comments - Research Hub</title>
    <link rel="stylesheet" href="assets/css/globals.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fc;
            display: flex;
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

        /* ===== MAIN CONTENT ===== */
        .main {
            flex: 1;
            padding: 48px 56px;
            max-width: 1200px;
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

        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            color: #64748b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 12px;
        }

        /* ===== COMMENT CARD ===== */
        .comment-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 20px;
            background: white;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .comment-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .doc-info {
            flex: 1;
        }

        .doc-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
            line-height: 1.4;
            transition: color 0.2s ease;
            display: inline-block;
            margin-bottom: 6px;
        }

        .doc-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .doc-meta {
            font-size: 14px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .meta-separator {
            color: #cbd5e1;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-icon {
            padding: 8px 12px;
            border-radius: 8px;
            background: white;
            border: 2px solid #e2e8f0;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .edit-btn {
            color: #667eea;
            border-color: #e0e7ff;
        }

        .edit-btn:hover {
            background: #eff6ff;
            border-color: #c7d2fe;
            transform: translateY(-1px);
        }

        .delete-btn {
            color: #ef4444;
            border-color: #fee2e2;
        }

        .delete-btn:hover {
            background: #fef2f2;
            border-color: #fecaca;
            transform: translateY(-1px);
        }

        .comment-content {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 15px;
            color: #0f172a;
            line-height: 1.7;
            border: 1px solid #e2e8f0;
        }

        .comment-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }

        .submeta {
            font-size: 13px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            background: white;
            padding: 80px 32px;
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
            line-height: 1.6;
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

            .comment-card {
                padding: 20px;
            }

            .comment-header {
                flex-direction: column;
            }

            .action-buttons {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
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


<!-- ===== MAIN CONTENT ===== -->
<div class="main">

    <div class="page-header">
        <h1>My Comments</h1>
        <p class="subtitle">All your comments on research papers</p>
        <div class="stats-badge">
            <span>💬</span>
            <span><?= count($comments) ?> comment<?= count($comments) != 1 ? 's' : '' ?></span>
        </div>
    </div>

    <?php if (empty($comments)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">💬</div>
            <div class="empty-state-title">No Comments Yet</div>
            <div class="empty-state-text">
                You haven't made any comments yet.<br>
                Comments can be added from document pages to share your thoughts and insights.
            </div>
        </div>

    <?php else: ?>

        <?php foreach ($comments as $c): ?>
            <div class="comment-card">

                <div class="comment-header">
                    <div class="doc-info">
                        <a href="document_view.php?id=<?= $c['DocumentID'] ?>" class="doc-link">
                            <?= htmlspecialchars($c['DocTitle']) ?>
                        </a>

                        <div class="doc-meta">
                            <span>👤 <?= htmlspecialchars($c['Authors'] ?: "Unknown authors") ?></span>
                            <?php if (!empty($c['PublicationYear'])): ?>
                                <span class="meta-separator">•</span>
                                <span>📅 <?= htmlspecialchars($c['PublicationYear']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($role === "admin" || $c['CommentOwner'] == $userID): ?>
                        <div class="action-buttons">
                            <a class="edit-btn btn-icon"
                               href="edit_comment.php?id=<?= $c['CommentID'] ?>&doc=<?= $c['DocumentID'] ?>"
                               title="Edit comment">
                                ✏️
                            </a>

                            <a class="delete-btn btn-icon"
                               href="controllers/delete_comment.php?id=<?= $c['CommentID'] ?>&doc=<?= $c['DocumentID'] ?>"
                               onclick="return confirm('Are you sure you want to delete this comment?');"
                               title="Delete comment">
                                🗑️
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="comment-content">
                    <?= nl2br(htmlspecialchars($c['Context'])) ?>
                </div>

                <div class="comment-footer">
                    <div class="submeta">
                        <span>🕐</span>
                        <span>Posted on <?= htmlspecialchars($c['CreatedAt']) ?></span>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>