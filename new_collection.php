<?php
session_start();
require_once "includes/connect.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Collection - Research Hub</title>
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
            margin-bottom: 24px;
            color: #667eea;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s ease;
            padding: 8px 12px;
            border-radius: 8px;
        }

        .back-link:hover {
            background: #f1f5f9;
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
            display: flex;
            align-items: center;
            gap: 12px;
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

        .form-section {
            margin-bottom: 32px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #475569;
            font-size: 14px;
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

        input[type="text"], 
        textarea, 
        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            background: #f8fafc;
            font-family: inherit;
            transition: all 0.3s ease;
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
            min-height: 120px;
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

        .helper-text {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
        }

        .visibility-options {
            display: grid;
            gap: 12px;
            margin-top: 12px;
        }

        .visibility-option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .visibility-option:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .visibility-option input[type="radio"] {
            margin-top: 2px;
            cursor: pointer;
        }

        .visibility-option input[type="radio"]:checked ~ .visibility-info {
            color: #0f172a;
        }

        .visibility-option input[type="radio"]:checked {
            accent-color: #667eea;
        }

        .visibility-option:has(input:checked) {
            border-color: #667eea;
            background: #f5f7ff;
        }

        .visibility-info {
            flex: 1;
        }

        .visibility-label {
            font-weight: 600;
            font-size: 15px;
            color: #0f172a;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .visibility-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px solid #f1f5f9;
        }

        .btn-create {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 28px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            padding: 16px 28px;
            border-radius: 12px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: #cbd5e1;
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

            .btn-create,
            .btn-cancel {
                width: 100%;
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

    <a href="collections.php" class="back-link">
        <span>←</span> Back to Collections
    </a>

    <div class="page-header">
        <h1>
            <span>📚</span> Create New Collection
        </h1>
        <p class="subtitle">Organize your research papers into a new collection</p>
    </div>

    <div class="form-card">
        <form action="controllers/create_collection.php" method="POST">

            <div class="form-section">
                <div class="form-section-title">
                    <span>📝</span> Basic Information
                </div>

                <div class="form-group">
                    <label>
                        Collection Name<span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           required 
                           placeholder="e.g., Deep Learning Papers"
                           maxlength="255">
                    <div class="helper-text">Choose a descriptive name for your collection</div>
                </div>

                <div class="form-group">
                    <label>
                        Description<span class="optional-badge">Optional</span>
                    </label>
                    <textarea name="description" 
                              placeholder="Provide a brief description of this collection and what papers it contains..."></textarea>
                    <div class="helper-text">Help others understand what this collection is about</div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <span>🔒</span> Privacy Settings
                </div>

                <div class="form-group">
                    <label>
                        Visibility<span class="required">*</span>
                    </label>
                    
                    <div class="visibility-options">
                        <label class="visibility-option">
                            <input type="radio" 
                                   name="visibility" 
                                   value="Private" 
                                   required 
                                   checked>
                            <div class="visibility-info">
                                <div class="visibility-label">
                                    🔒 Private
                                </div>
                                <div class="visibility-desc">
                                    Only you can view and access this collection
                                </div>
                            </div>
                        </label>

                        <label class="visibility-option">
                            <input type="radio" 
                                   name="visibility" 
                                   value="Public" 
                                   required>
                            <div class="visibility-info">
                                <div class="visibility-label">
                                    🌐 Public
                                </div>
                                <div class="visibility-desc">
                                    Everyone can view this collection and its contents
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="collections.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-create">
                    <span>✓</span> Create Collection
                </button>
            </div>

        </form>
    </div>

</div>

</body>
</html>