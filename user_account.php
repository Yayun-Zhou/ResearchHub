<?php
session_start();
require_once "includes/connect.php";

// Redirect if not logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];

// Fetch user profile
$stmt = $conn->prepare("
    SELECT U.UserName, U.Email, U.Role, U.AffiliationID, A.AffiliationName
    FROM User U
    LEFT JOIN Affiliation A ON U.AffiliationID = A.AffiliationID
    WHERE U.UserID = ?
");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// User statistics
$collectionCount = $conn->prepare("SELECT COUNT(*) FROM Collection WHERE UserID = ?");
$collectionCount->execute([$userID]);
$collectionCount = $collectionCount->fetchColumn();

$notesCount = $conn->prepare("SELECT COUNT(*) FROM Notes WHERE UserID = ?");
$notesCount->execute([$userID]);
$notesCount = $notesCount->fetchColumn();

$commentsCount = $conn->prepare("SELECT COUNT(*) FROM Comment WHERE UserID = ?");
$commentsCount->execute([$userID]);
$commentsCount = $commentsCount->fetchColumn();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Account - Research Hub</title>
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

        /* ===== MAIN ===== */
        .main {
            flex: 1;
            padding: 48px 56px;
            max-width: 1200px;
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

        .subtitle {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 32px;
        }

        /* ===== PROFILE BOX ===== */
        .profile-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #f1f5f9;
        }

        .profile-header-text h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .profile-header-text p {
            color: #64748b;
            font-size: 14px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
            margin-bottom: 24px;
        }

        .edit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
        }

        .info-item {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== STATS BOX ===== */
        .section-header {
            margin-top: 48px;
            margin-bottom: 24px;
        }

        .section-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .stats-box {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .stat-card {
            background: white;
            padding: 32px;
            text-align: center;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== DANGER ZONE ===== */
        .danger-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-top: 48px;
            border: 2px solid #fee2e2;
        }

        .danger-title {
            color: #dc2626;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .danger-desc {
            color: #64748b;
            margin-bottom: 24px;
            font-size: 15px;
        }

        .danger-btn {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .danger-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
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

            .profile-box {
                padding: 28px 24px;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .edit-btn {
                width: 100%;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .stats-box {
                grid-template-columns: 1fr;
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
        <a href="collections.php">Collections</a>
        <a href="notes.php">Notes</a>
        <a href="comments.php">Comments</a>
        <a href="user_account.php" class="active">User Account</a>
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

<!-- MAIN CONTENT -->
<div class="main">

    <div class="page-header">
        <h1>My Account</h1>
        <p class="subtitle">Manage your profile and view your activity</p>
    </div>

    <!-- Profile Box -->
    <div class="profile-box">

        <div class="profile-avatar">👤</div>

        <div class="profile-header">
            <div class="profile-header-text">
                <h2>Profile Information</h2>
                <p>Your personal information and credentials</p>
            </div>

            <a href="edit_profile.php" class="edit-btn">
                ✏️ Edit Profile
            </a>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Username</div>
                <div class="info-value"><?= htmlspecialchars($user['UserName']) ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Email Address</div>
                <div class="info-value"><?= htmlspecialchars($user['Email']) ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Account Role</div>
                <div class="info-value">
                    <span class="badge"><?= htmlspecialchars($user['Role']) ?></span>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Affiliation</div>
                <div class="info-value"><?= htmlspecialchars($user['AffiliationName']) ?></div>
            </div>
        </div>

    </div>


    <!-- STATS -->
    <div class="section-header">
        <h2>Activity Statistics</h2>
        <p class="subtitle">Your contributions to the platform</p>
    </div>

    <div class="stats-box">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?= $collectionCount ?></div>
            <div class="stat-label">Collections</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-number"><?= $notesCount ?></div>
            <div class="stat-label">Notes</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-number"><?= $commentsCount ?></div>
            <div class="stat-label">Comments</div>
        </div>
    </div>


    <!-- DANGER ZONE  -->
    <div class="danger-box">
        <h2 class="danger-title">
            ⚠️ Danger Zone
        </h2>
        <p class="danger-desc">
            Irreversible and destructive account actions. Please proceed with caution.
        </p>

        <form method="POST" action="controllers/delete_account.php"
              onsubmit="return confirm('⚠️ WARNING: Are you absolutely sure you want to delete your account?\n\nThis will permanently delete:\n• Your profile\n• All your collections\n• All your notes\n• All your comments\n\nThis action CANNOT be undone!')">
            <button class="danger-btn">🗑️ Delete Account</button>
        </form>
    </div>

</div>

</body>
</html>