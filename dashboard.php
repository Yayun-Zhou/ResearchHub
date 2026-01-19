<?php
// ----- DEBUG: Show PHP errors -----
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

// ----- FETCH STATISTICS -----

// Collections Count
$stmt = $conn->prepare("SELECT COUNT(*) FROM Collection WHERE UserID = ?");
$stmt->execute([$userID]);
$collectionCount = $stmt->fetchColumn();

// Notes Count
$stmt = $conn->prepare("SELECT COUNT(*) FROM Notes WHERE UserID = ?");
$stmt->execute([$userID]);
$notesCount = $stmt->fetchColumn();

// Comments Count
$stmt = $conn->prepare("SELECT COUNT(*) FROM Comment WHERE UserID = ?");
$stmt->execute([$userID]);
$commentsCount = $stmt->fetchColumn();

// Interacted Documents Count
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT DocumentID)
    FROM (
        SELECT DocumentID FROM Notes   WHERE UserID = ?
        UNION
        SELECT DocumentID FROM Comment WHERE UserID = ?
    ) AS t
");
$stmt->execute([$userID, $userID]);
$interactedDocs = $stmt->fetchColumn();

// ----- RECENT ACTIVITY -----
$activitySql = "
    SELECT 'note' AS Type,
           N.CreatedTime AS CreatedAt,
           N.DocumentID,
           D.Title AS DocTitle,
           NULL AS CollectionID,
           NULL AS CollectionName
    FROM Notes N
    JOIN Document D ON N.DocumentID = D.DocumentID
    WHERE N.UserID = :uid1

    UNION ALL

    SELECT 'comment' AS Type,
           C.CreatedAt AS CreatedAt,
           C.DocumentID,
           D.Title AS DocTitle,
           NULL AS CollectionID,
           NULL AS CollectionName
    FROM Comment C
    JOIN Document D ON C.DocumentID = D.DocumentID
    WHERE C.UserID = :uid2

    UNION ALL

    SELECT 'collection' AS Type,
           C.CreatedTime AS CreatedAt,
           NULL AS DocumentID,
           NULL AS DocTitle,
           C.CollectionID,
           C.CollectionName
    FROM Collection C
    WHERE C.UserID = :uid3

    ORDER BY CreatedAt DESC
    LIMIT 5
";

$stmt = $conn->prepare($activitySql);
$stmt->execute([
    ':uid1' => $userID,
    ':uid2' => $userID,
    ':uid3' => $userID
]);
$recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Research Hub</title>

    <!-- Global site CSS -->
    <link rel="stylesheet" href="assets/css/globals.css">

    <!-- Dashboard-specific layout styling -->
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

        /* Sidebar */
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

        /* Main content */
        .main {
            flex: 1;
            padding: 48px 56px;
            max-width: 1400px;
        }

        h1 { 
            font-size: 36px;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 700;
        }

        .subtitle { 
            color: #64748b;
            margin-bottom: 32px;
            font-size: 16px;
        }

        /* Search Bar */
        .search-container {
            margin: 32px 0 40px 0;
            padding: 32px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .search-bar {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .search-input-wrapper {
            flex: 1;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .search-input-wrapper:focus-within {
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            font-size: 20px;
            margin-right: 12px;
            opacity: 0.6;
        }

        .search-input {
            flex: 1;
            border: none;
            font-size: 16px;
            outline: none;
            background: none;
            color: #1e293b;
            font-weight: 500;
        }

        .search-input::placeholder {
            color: #94a3b8;
        }

        .search-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .search-button:active {
            transform: translateY(0);
        }

        /* Stats grid */
        .grid {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            margin-bottom: 40px;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card-title { 
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .card-number { 
            font-size: 40px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        /* Recent activity */
        .activity-box {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .activity-box h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .activity-box .subtitle {
            margin-bottom: 28px;
        }

        .activity-item {
            border-bottom: 1px solid #f1f5f9;
            padding: 18px 0;
            font-size: 15px;
            color: #475569;
            line-height: 1.6;
            transition: background 0.2s ease;
        }

        .activity-item:hover {
            background: #f8fafc;
            margin: 0 -16px;
            padding: 18px 16px;
            border-radius: 8px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-time {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 6px;
            font-weight: 500;
        }

        .activity-type-pill {
            display: inline-block;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 6px;
            margin-right: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .activity-type-pill[data-type="note"] {
            background: #dbeafe;
            color: #1e40af;
        }

        .activity-type-pill[data-type="comment"] {
            background: #fce7f3;
            color: #9f1239;
        }

        .activity-type-pill[data-type="collection"] {
            background: #d1fae5;
            color: #065f46;
        }

        .activity-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .activity-link:hover {
            color: #764ba2;
            text-decoration: underline;
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

            h1 {
                font-size: 28px;
            }

            .search-bar {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
            }

            .grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 16px;
            }
        }
    </style>
</head>

<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="app-title">
        <span class="icon">🔖</span>
        <div>
            <div class="title">Research Hub</div>
            <div class="role"><?= strtolower(htmlspecialchars($role)) ?></div>
        </div>
    </div>

    <nav class="menu">
        <a href="dashboard.php" class="active">Dashboard</a>
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

    <a href="logout.php" class="logout">Log Out</a>
</aside>

<!-- Main content -->
<div class="main">

    <h1>Dashboard</h1>
    <p class="subtitle">Welcome back, <?= htmlspecialchars($userName) ?>!</p>

    <!-- Search Bar -->
    <div class="search-container">
        <form action="search.php" method="GET" class="search-bar">
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input 
                    type="text" 
                    name="q" 
                    placeholder="Quick search for papers, authors, or topics..."
                    class="search-input"
                >
            </div>
            <button type="submit" class="search-button">Search</button>
        </form>
    </div>

    <!-- Statistics -->
    <div class="grid">
        <div class="card">
            <div class="card-title">Interacted Documents</div>
            <div class="card-number"><?= $interactedDocs ?></div>
        </div>

        <div class="card">
            <div class="card-title">Collections</div>
            <div class="card-number"><?= $collectionCount ?></div>
        </div>

        <div class="card">
            <div class="card-title">Notes</div>
            <div class="card-number"><?= $notesCount ?></div>
        </div>

        <div class="card">
            <div class="card-title">Comments</div>
            <div class="card-number"><?= $commentsCount ?></div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="activity-box">
        <h2>Recent Activity</h2>
        <p class="subtitle">Your latest interactions on the platform</p>

        <?php if (empty($recentActivities)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>No recent activity yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($recentActivities as $act): ?>
                <div class="activity-item">
                    <?php
                        $type = $act['Type'];
                        $time = $act['CreatedAt'];
                        $docTitle = $act['DocTitle'] ?? null;
                        $docId = $act['DocumentID'] ?? null;
                        $colId = $act['CollectionID'] ?? null;
                        $colName = $act['CollectionName'] ?? null;

                        if ($type === 'note') {
                            echo '<span class="activity-type-pill" data-type="note">Note</span>';
                            echo 'You added a note on ';
                            if ($docId && $docTitle) {
                                echo '<a class="activity-link" href="document_view.php?id='
                                     . htmlspecialchars($docId) . '">'
                                     . htmlspecialchars($docTitle) . '</a>.';
                            } else {
                                echo 'a document.';
                            }
                        } elseif ($type === 'comment') {
                            echo '<span class="activity-type-pill" data-type="comment">Comment</span>';
                            echo 'You commented on ';
                            if ($docId && $docTitle) {
                                echo '<a class="activity-link" href="document_view.php?id='
                                     . htmlspecialchars($docId) . '">'
                                     . htmlspecialchars($docTitle) . '</a>.';
                            } else {
                                echo 'a document.';
                            }
                        } elseif ($type === 'collection') {
                            echo '<span class="activity-type-pill" data-type="collection">Collection</span>';
                            echo 'You created collection ';
                            if ($colId && $colName) {
                                echo '<a class="activity-link" href="collection_view.php?id='
                                     . htmlspecialchars($colId) . '">'
                                     . htmlspecialchars($colName) . '</a>.';
                            } else {
                                echo 'a collection.';
                            }
                        }
                    ?>
                    <div class="activity-time">
                        <?= htmlspecialchars($time) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>