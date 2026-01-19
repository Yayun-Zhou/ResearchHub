<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];

$stmt = $conn->prepare("
    SELECT UserName, Email, Role, AffiliationID
    FROM User
    WHERE UserID = ?
");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$role = $user['Role'];
$userName = $user['UserName'];

// Load affiliations
$affQuery = $conn->query("SELECT AffiliationID, AffiliationName FROM Affiliation ORDER BY AffiliationName ASC");
$affiliations = $affQuery->fetchAll(PDO::FETCH_ASSOC);

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Research Hub</title>
    <link rel="stylesheet" href="assets/css/globals.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            max-width: 900px;
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

        /* ===== ALERTS ===== */
        .error, .success {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 15px;
            line-height: 1.5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error {
            background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%);
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* ===== SECTION CARD ===== */
        .section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            font-weight: 600;
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
            display: block;
        }

        input, select {
            width: 100%;
            padding: 14px 16px;
            margin-top: 8px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            color: #0f172a;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 44px;
        }

        input::placeholder {
            color: #94a3b8;
        }

        .helper-text {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
        }

        button {
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            margin-top: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 24px;
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }

        .info-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 32px;
            border: 1px solid #e2e8f0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
        }

        .info-value {
            color: #0f172a;
            font-weight: 600;
            font-size: 14px;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-badge.admin {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .role-badge.researcher {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
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

            .section {
                padding: 28px 24px;
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
        <a href="dashboard.php">Dashboard</a>
        <a href="advanced_search.php">Advanced Search</a>
        <a href="collections.php">Collections</a>
        <a href="notes.php">Notes</a>
        <a href="comments.php">Comments</a>
        <a href="user_account.php" class="active">User Account</a>
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

    <div class="page-header">
        <h1>Account Settings</h1>
        <p class="subtitle">Manage your personal information and preferences</p>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <span>✓</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <!-- Current Profile Info -->
    <div class="section">
        <div class="profile-icon">👤</div>
        
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Current Username</span>
                <span class="info-value"><?= htmlspecialchars($user['UserName']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email Address</span>
                <span class="info-value"><?= htmlspecialchars($user['Email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Account Role</span>
                <span class="info-value">
                    <span class="role-badge <?= strtolower($role) ?>">
                        <?= htmlspecialchars($role) ?>
                    </span>
                </span>
            </div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="section">
        <div class="section-title">
            <span>✏️</span> Edit Profile Information
        </div>

        <form method="POST" action="controllers/update_profile.php">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" 
                       id="username"
                       name="username"
                       value="<?= htmlspecialchars($user['UserName']) ?>" 
                       required
                       placeholder="Enter your username">
                <div class="helper-text">This is how you'll be identified on the platform</div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" 
                       id="email"
                       name="email"
                       value="<?= htmlspecialchars($user['Email']) ?>" 
                       required
                       placeholder="your.email@example.com">
                <div class="helper-text">Used for account recovery and notifications</div>
            </div>

            <div class="form-group">
                <label for="affiliation">Affiliation</label>
                <select id="affiliation" name="affiliation" required>
                    <?php foreach ($affiliations as $aff): ?>
                        <option value="<?= $aff['AffiliationID'] ?>"
                            <?= $aff['AffiliationID'] == $user['AffiliationID'] ? "selected" : "" ?>>
                            <?= htmlspecialchars($aff['AffiliationName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="helper-text">Your academic or research institution</div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password (Optional)</label>
                <input type="password" 
                       id="new_password"
                       name="new_password" 
                       placeholder="Leave empty to keep current password">
                <div class="helper-text">Only fill this if you want to change your password</div>
            </div>

            <button type="submit">
                <span>💾</span> Save Changes
            </button>
        </form>

    </div>

</div>

</body>
</html>