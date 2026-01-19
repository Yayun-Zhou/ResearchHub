<?php
session_start();
require_once "includes/connect.php";

// Must be admin
if (!isset($_SESSION['UserID']) || strtolower($_SESSION['Role']) !== "admin") {
    header("Location: login.php");
    exit;
}

// Search + Pagination
$usersPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $usersPerPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$whereSQL = "";
$params = [];

if ($search !== "") {
    $whereSQL = "WHERE UserName LIKE ? OR Email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Count total users
$countSQL = "SELECT COUNT(*) FROM User $whereSQL";
$stmt = $conn->prepare($countSQL);
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $usersPerPage);

// Get user rows
$userSQL = "
    SELECT U.UserID, U.UserName, U.Email, U.Role, A.AffiliationName
    FROM User U
    LEFT JOIN Affiliation A ON U.AffiliationID = A.AffiliationID
    $whereSQL
    ORDER BY U.UserID ASC
    LIMIT $usersPerPage OFFSET $offset
";

$stmt = $conn->prepare($userSQL);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List - Research Hub</title>
    <link rel="stylesheet" href="assets/css/globals.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fc;
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
            max-width: 1600px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }

        /* ===== SEARCH ===== */
        .search-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .search-bar {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .search-input-wrapper {
            flex: 1;
            background: #f8fafc;
            padding: 14px 18px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
        }

        .search-input-wrapper:focus-within {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            font-size: 18px;
            color: #94a3b8;
        }

        .search-input {
            border: none;
            outline: none;
            background: none;
            width: 100%;
            font-size: 15px;
            color: #0f172a;
        }

        .search-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* ===== TABLE ===== */
        .table-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 16px 20px;
            font-size: 13px;
            color: #64748b;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px 20px;
            font-size: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .role-badge {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
        }

        /* ===== BUTTONS ===== */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-primary,
        .btn-danger {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* ===== PAGINATION ===== */
        .pagination {
            margin-top: 24px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .page-btn {
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            color: #475569;
            background: white;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .page-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state-text {
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

            .search-bar {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
            }

            .table-card {
                overflow-x: auto;
            }

            table {
                min-width: 700px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-primary,
            .btn-danger {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <script>
        function confirmPromote(userName) {
            return confirm('Are you sure you want to PROMOTE "' + userName + '" to Admin?\n\nThis will grant full administrative privileges to this user.');
        }

        function confirmDelete(userName) {
            return confirm('⚠️ WARNING: Are you sure you want to DELETE user "' + userName + '"?\n\nThis will permanently remove:\n• Their account\n• All their collections\n• All their notes\n• All their comments\n\nThis action CANNOT be undone!');
        }
    </script>
</head>

<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="app-title">
        <span class="icon">🔖</span>
        <div>
            <div class="title">Research Hub</div>
            <div class="role"><?= strtolower(htmlspecialchars($_SESSION['Role'])) ?></div>
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
        <a href="user_list.php" class="active">User List</a>
        <a href="import_document.php">Import Document</a>
        <a href="review_documents.php">Review Documents</a>
    </nav>

    <a href="logout.php" class="logout">Log Out</a>
</aside>

<!-- Main Content -->
<div class="main">

    <div class="page-header">
        <h1>User Management</h1>
        <div class="subtitle">
            <span>View and manage all users in the system</span>
            <span class="user-count"><?= $totalUsers ?> user<?= $totalUsers != 1 ? 's' : '' ?></span>
        </div>
    </div>

    <!-- Search -->
    <div class="search-container">
        <form method="GET" class="search-bar">
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" class="search-input"
                       placeholder="Search by username or email..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <button class="search-button">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="table-card">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <div class="empty-state-text">No users found</div>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Affiliation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['UserName']) ?></strong></td>
                    <td><?= htmlspecialchars($u['Email']) ?></td>

                    <td>
                        <?php if (strtolower($u['Role']) === "admin"): ?>
                            <span class="badge admin">Admin</span>
                        <?php else: ?>
                            <span class="badge role-badge"><?= htmlspecialchars($u['Role']) ?></span>
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($u['AffiliationName'] ?: '—') ?></td>

                    <td>
                        <div class="action-buttons">
                            <?php if (strtolower($u['Role']) !== "admin"): ?>
                            <!-- Promote -->
                            <form method="POST" 
                                  action="controllers/promote_handler.php" 
                                  style="display:inline;"
                                  onsubmit="return confirmPromote('<?= addslashes(htmlspecialchars($u['UserName'])) ?>')">
                                <input type="hidden" name="user_id" value="<?= $u['UserID'] ?>">
                                <button class="btn-primary">⬆️ Promote</button>
                            </form>
                            <?php endif; ?>

                            <!-- Delete -->
                            <form method="POST" 
                                  action="controllers/delete_user_handler.php"
                                  style="display:inline;"
                                  onsubmit="return confirmDelete('<?= addslashes(htmlspecialchars($u['UserName'])) ?>')">
                                <input type="hidden" name="user_id" value="<?= $u['UserID'] ?>">
                                <button class="btn-danger">🗑️ Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="page-btn <?= $i == $page ? 'active' : '' ?>"
               href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>