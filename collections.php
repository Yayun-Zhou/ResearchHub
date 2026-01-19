<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];
$role = $_SESSION['Role'];

/* ---- Fetch My Collections ---- */
$stmt = $conn->prepare("
    SELECT C.*, 
        (SELECT COUNT(*) FROM CollectionDocument CD WHERE CD.CollectionID = C.CollectionID) AS DocCount,
        U.UserName AS CreatorName
    FROM Collection C
    JOIN User U ON C.UserID = U.UserID
    WHERE C.UserID = ?
    ORDER BY C.UpdatedTime DESC
");
$stmt->execute([$userID]);
$myCollections = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---- Fetch Public Collections (others) ---- */
$stmt = $conn->prepare("
    SELECT C.*, 
        (SELECT COUNT(*) FROM CollectionDocument CD WHERE CD.CollectionID = C.CollectionID) AS DocCount,
        U.UserName AS CreatorName
    FROM Collection C
    JOIN User U ON C.UserID = U.UserID
    WHERE C.Visibility = 'Public' AND C.UserID != ?
    ORDER BY C.UpdatedTime DESC
");
$stmt->execute([$userID]);
$publicCollections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections - Research Hub</title>
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

        /* ---------------- SIDEBAR ---------------- */
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

        /* ---------------- MAIN ---------------- */
        .main {
            flex: 1;
            padding: 48px 56px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .new-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .new-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 48px 0 24px 0;
        }

        .section-header:first-of-type {
            margin-top: 0;
        }

        h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .section-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* -------- GRID LAYOUT -------- */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        /* -------- COLLECTION CARD -------- */
        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card-link:hover .card {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
            flex: 1;
        }

        /* ---- BADGE: PUBLIC / PRIVATE ---- */
        .badge-public,
        .badge-private {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        .badge-public {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .badge-private {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #374151;
        }

        .desc {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 20px;
            line-height: 1.6;
            flex-grow: 1;
        }

        .card-footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 16px;
        }

        .meta {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .meta:last-child {
            margin-bottom: 0;
        }

        .creator {
            color: #475569;
            font-size: 14px;
            margin-top: 12px;
            font-weight: 500;
        }

        .empty-state {
            padding: 60px 40px;
            background: white;
            border-radius: 16px;
            border: 2px dashed #e2e8f0;
            text-align: center;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state-text {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .empty-state-text b {
            color: #0f172a;
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

            .grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .new-btn {
                width: 100%;
                justify-content: center;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                margin: 32px 0 20px 0;
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
        <div>
            <h1>Collections</h1>
            <p class="subtitle">Organize your research papers into collections</p>
        </div>
        <a href="new_collection.php" class="new-btn">
            <span>＋</span> New Collection
        </a>
    </div>

    <!-- ================= My Collections ================= -->
    <div class="section-header">
        <h2>My Collections</h2>
        <div class="section-count"><?= count($myCollections) ?> collection<?= count($myCollections) != 1 ? 's' : '' ?></div>
    </div>

    <?php if (count($myCollections) === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <div class="empty-state-text">
                You haven't created any collections yet.<br>
                Click <b>"New Collection"</b> to get started organizing your research!
            </div>
            <a href="new_collection.php" class="new-btn">
                <span>＋</span> Create Your First Collection
            </a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($myCollections as $c): ?>
                <a href="collection_view.php?id=<?= $c['CollectionID'] ?>" class="card-link">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <?= htmlspecialchars($c['CollectionName']) ?>
                            </div>

                            <?php if ($c['Visibility'] === 'Public'): ?>
                                <span class="badge-public">🌐 Public</span>
                            <?php else: ?>
                                <span class="badge-private">🔒 Private</span>
                            <?php endif; ?>
                        </div>

                        <div class="desc">
                            <?= htmlspecialchars($c['CollectionDescription']) ?>
                        </div>

                        <div class="card-footer">
                            <div class="meta">📄 <?= $c['DocCount'] ?> document<?= $c['DocCount'] != 1 ? 's' : '' ?></div>
                            <div class="meta">🕐 Updated <?= $c['UpdatedTime'] ?></div>
                            <div class="creator">👤 <?= htmlspecialchars($c['CreatorName']) ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ================= Public Collections ================= -->
    <div class="section-header">
        <h2>Public Collections</h2>
        <div class="section-count"><?= count($publicCollections) ?> collection<?= count($publicCollections) != 1 ? 's' : '' ?></div>
    </div>

    <?php if (count($publicCollections) === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🌐</div>
            <div class="empty-state-text">
                No public collections available from other users yet.
            </div>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($publicCollections as $c): ?>
                <a href="collection_view.php?id=<?= $c['CollectionID'] ?>" class="card-link">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <?= htmlspecialchars($c['CollectionName']) ?>
                            </div>
                            <span class="badge-public">🌐 Public</span>
                        </div>

                        <div class="desc">
                            <?= htmlspecialchars($c['CollectionDescription']) ?>
                        </div>

                        <div class="card-footer">
                            <div class="meta">📄 <?= $c['DocCount'] ?> document<?= $c['DocCount'] != 1 ? 's' : '' ?></div>
                            <div class="meta">🕐 Updated <?= $c['UpdatedTime'] ?></div>
                            <div class="creator">👤 <?= htmlspecialchars($c['CreatorName']) ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>