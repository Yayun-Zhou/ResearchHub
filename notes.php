<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = intval($_SESSION['UserID']);
$role   = strtolower($_SESSION['Role'] ?? "");

/* ========= Fetch notes of this user ========= */
$sql = "
    SELECT 
        N.NoteID, N.Content, N.PageNum, N.Visibility, N.CreatedTime,
        N.DocumentID, N.UserID,
        D.Title AS DocTitle
    FROM Notes N
    JOIN Document D ON N.DocumentID = D.DocumentID
    WHERE N.UserID = ?
    ORDER BY N.CreatedTime DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$userID]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notes - Research Hub</title>
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
            color: #0f172a;
            min-height: 100vh;
        }

        /* ---------- SIDEBAR ---------- */
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

        /* ---------- MAIN ---------- */
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

        /* ---------- NOTE CARD ---------- */
        .note-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 20px;
            background: white;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .note-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .note-meta {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: fit-content;
        }

        .badge-public {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        .badge-private {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #374151;
        }

        .doc-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
            transition: color 0.2s ease;
            display: inline-block;
        }

        .doc-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-ghost {
            padding: 8px 12px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background: white;
            cursor: pointer;
            color: #64748b;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-ghost.edit:hover {
            background: #eff6ff;
            border-color: #c7d2fe;
            color: #667eea;
        }

        .btn-ghost.delete:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #ef4444;
        }

        .note-content {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 15px;
            color: #0f172a;
            line-height: 1.7;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
        }

        .note-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            flex-wrap: wrap;
        }

        .page-ref {
            font-size: 14px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .timestamp {
            font-size: 13px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ---------- EMPTY STATE ---------- */
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

            .note-card {
                padding: 20px;
            }

            .note-header {
                flex-direction: column;
            }

            .action-buttons {
                width: 100%;
                justify-content: flex-end;
            }

            .note-footer {
                flex-direction: column;
                align-items: flex-start;
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
        <a href="notes.php" class="active">Notes</a>
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
    <div class="page-header">
        <h1>My Notes</h1>
        <p class="subtitle">All your notes on research papers</p>
        <div class="stats-badge">
            <span>📝</span>
            <span><?= count($notes) ?> note<?= count($notes) != 1 ? 's' : '' ?></span>
        </div>
    </div>

    <?php if (empty($notes)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📝</div>
            <div class="empty-state-title">No Notes Yet</div>
            <div class="empty-state-text">
                You haven't created any notes yet.<br>
                Notes can be added from document pages to capture your insights and ideas.
            </div>
        </div>
    <?php else: ?>

        <?php foreach ($notes as $n): ?>
            <div class="note-card">

                <div class="note-header">
                    <div class="note-meta">
                        <span class="badge <?= strtolower($n['Visibility']) === 'public' ? 'badge-public' : 'badge-private' ?>">
                            <?= strtolower($n['Visibility']) === 'public' ? '🌐' : '🔒' ?> 
                            <?= htmlspecialchars($n['Visibility']) ?>
                        </span>

                        <a href="document_view.php?id=<?= $n['DocumentID'] ?>" class="doc-link">
                            <?= htmlspecialchars($n['DocTitle']) ?>
                        </a>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="action-buttons">
                        <a href="edit_note.php?id=<?= $n['NoteID'] ?>"
                           class="btn-ghost edit" 
                           title="Edit this note">✏️</a>

                        <a href="controllers/delete_note.php?id=<?= $n['NoteID'] ?>&doc=<?= $n['DocumentID'] ?>"
                           class="btn-ghost delete"
                           onclick="return confirm('Are you sure you want to delete this note?');"
                           title="Delete this note">
                            🗑️
                        </a>
                    </div>
                </div>

                <!-- CONTENT -->
                <div class="note-content">
                    <?= nl2br(htmlspecialchars($n['Content'])) ?>
                </div>

                <!-- FOOTER -->
                <div class="note-footer">
                    <?php if (!empty($n['PageNum'])): ?>
                        <div class="page-ref">
                            <span>📄</span>
                            <span>Page <?= htmlspecialchars($n['PageNum']) ?></span>
                        </div>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <div class="timestamp">
                        <span>🕐</span>
                        <span><?= htmlspecialchars($n['CreatedTime']) ?></span>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>