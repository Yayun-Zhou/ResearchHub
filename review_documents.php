<?php
session_start();
require_once "includes/connect.php";

/* -------------------------------
   Helper: Safe HTML output
--------------------------------*/
function h($str) {
    return htmlspecialchars($str ?? "", ENT_QUOTES, 'UTF-8');
}

/* -------------------------------
   Admin-only access
--------------------------------*/
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== "Admin") {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];
$userName = $_SESSION['UserName'];
$role = $_SESSION['Role'];

/* -------------------------------
   Fetch Documents
--------------------------------*/

// Pending
$pendingDocs = $conn->query("
    SELECT DocumentID, Title, PublicationYear, Area, LinkPath
    FROM Document
    WHERE ReviewStatus = 'Pending'
    ORDER BY ImportDate DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Approved
$approvedDocs = $conn->query("
    SELECT DocumentID, Title, PublicationYear, Area, LinkPath
    FROM Document
    WHERE ReviewStatus = 'Approved'
    ORDER BY Title ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Rejected
$rejectedDocs = $conn->query("
    SELECT DocumentID, Title, PublicationYear, Area, LinkPath
    FROM Document
    WHERE ReviewStatus = 'Rejected'
    ORDER BY Title ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Documents - Research Hub</title>
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
            padding: 48px 56px;
            flex: 1;
            max-width: 1600px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* ===== TABLE ===== */
        .table-wrapper {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        td {
            font-size: 14px;
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

        .doc-title {
            font-weight: 600;
            color: #0f172a;
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .external-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .external-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* ===== BUTTONS ===== */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 13px;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-approve:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-reject:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .btn-check {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-check:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(185, 28, 28, 0.3);
        }

        .btn-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(185, 28, 28, 0.4);
        }

        /* ===== MODAL ===== */
        .modal-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-box {
            background: white;
            padding: 32px;
            width: 540px;
            max-width: 90%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-box h3 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
        }

        #dup_results {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .dup-item {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .dup-item:last-child {
            margin-bottom: 0;
        }

        .dup-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .dup-year {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .dup-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .dup-link:hover {
            text-decoration: underline;
        }

        .modal-close {
            width: 100%;
            padding: 12px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #cbd5e1;
        }

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

            table {
                font-size: 13px;
            }

            th, td {
                padding: 12px 16px;
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

            .table-wrapper {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-sm {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = "flex";
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        // 确认审批操作
        function confirmAction(action, title) {
            const messages = {
                'approved': 'Are you sure you want to APPROVE this document?\n\n"' + title + '"',
                'rejected': 'Are you sure you want to REJECT this document?\n\n"' + title + '"\n\nThis document will be moved to the rejected list.'
            };
            return confirm(messages[action] || 'Confirm this action?');
        }

        // 确认删除操作
        function confirmDelete(title, type) {
            let message = '';
            if (type === 'approved') {
                message = 'Are you sure you want to DELETE this approved document?\n\n"' + title + '"\n\n⚠️ This action CANNOT be undone!';
            } else if (type === 'rejected') {
                message = 'Are you sure you want to PERMANENTLY DELETE this rejected document?\n\n"' + title + '"\n\n⚠️ This action CANNOT be undone!';
            }
            return confirm(message);
        }

        // AJAX Check for duplicates
        function checkDuplicate(title) {
            fetch("controllers/check_duplicate.php?title=" + encodeURIComponent(title))
                .then(res => res.json())
                .then(data => {
                    const box = document.getElementById("dup_results");
                    box.innerHTML = "";

                    if (data.length === 0) {
                        box.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">✓</div>
                                <div class="empty-state-text">No similar approved documents found.</div>
                            </div>`;
                    } else {
                        data.forEach(doc => {
                            box.innerHTML += `
                                <div class="dup-item">
                                    <div class="dup-title">${doc.Title}</div>
                                    <div class="dup-year">${doc.PublicationYear}</div>
                                    <a href="document_view.php?id=${doc.DocumentID}" 
                                       class="dup-link" 
                                       target="_blank">
                                        View Document →
                                    </a>
                                </div>`;
                        });
                    }

                    openModal('modal_dup');
                });
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
            <div class="role"><?= strtolower(h($role)) ?></div>
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
        <a href="user_list.php">User List</a>
        <a href="import_document.php">Import Document</a>
        <a href="review_documents.php" class="active">Review Documents</a>
    </nav>

    <a href="logout.php" class="logout">Log Out</a>
</aside>

<!-- Main Content -->
<div class="main">

    <div class="page-header">
        <h1>Review Documents</h1>
        <p class="subtitle">Manage and moderate research paper submissions</p>
    </div>

    <!-- Pending -->
    <div class="section-header">
        <h2>⏳ Pending Review</h2>
        <div class="section-count"><?= count($pendingDocs) ?> document<?= count($pendingDocs) != 1 ? 's' : '' ?></div>
    </div>

    <div class="table-wrapper">
        <?php if (empty($pendingDocs)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✓</div>
                <div class="empty-state-text">No pending documents to review</div>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Year</th>
                    <th>Area</th>
                    <th>Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingDocs as $d): ?>
                <tr>
                    <td><div class="doc-title"><?= h($d['Title']) ?></div></td>
                    <td><?= h($d['PublicationYear']) ?></td>
                    <td><?= h($d['Area']) ?></td>
                    <td>
                        <?php if (!empty($d['LinkPath'])): ?>
                            <a href="<?= h($d['LinkPath']) ?>" target="_blank" class="external-link">Open →</a>
                        <?php else: ?>
                            <span style="color: #cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <form method="POST" 
                                  action="controllers/review_action.php" 
                                  style="display:inline;"
                                  onsubmit="return confirmAction('approved', '<?= addslashes(h($d['Title'])) ?>')">
                                <input type="hidden" name="id" value="<?= $d['DocumentID'] ?>">
                                <input type="hidden" name="decision" value="approved">
                                <button class="btn-sm btn-approve">✓ Approve</button>
                            </form>

                            <form method="POST" 
                                  action="controllers/review_action.php" 
                                  style="display:inline;"
                                  onsubmit="return confirmAction('rejected', '<?= addslashes(h($d['Title'])) ?>')">
                                <input type="hidden" name="id" value="<?= $d['DocumentID'] ?>">
                                <input type="hidden" name="decision" value="rejected">
                                <button class="btn-sm btn-reject">✕ Reject</button>
                            </form>

                            <button class="btn-sm btn-check"
                                onclick="checkDuplicate('<?= addslashes(h($d['Title'])) ?>')">
                                🔍 Check
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Approved -->
    <div class="section-header">
        <h2>✓ Approved Documents</h2>
        <div class="section-count"><?= count($approvedDocs) ?> document<?= count($approvedDocs) != 1 ? 's' : '' ?></div>
    </div>

    <div class="table-wrapper">
        <?php if (empty($approvedDocs)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-text">No approved documents yet</div>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Year</th>
                    <th>Area</th>
                    <th>Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($approvedDocs as $d): ?>
                <tr>
                    <td><div class="doc-title"><?= h($d['Title']) ?></div></td>
                    <td><?= h($d['PublicationYear']) ?></td>
                    <td><?= h($d['Area']) ?></td>
                    <td>
                        <?php if (!empty($d['LinkPath'])): ?>
                            <a href="<?= h($d['LinkPath']) ?>" target="_blank" class="external-link">Open →</a>
                        <?php else: ?>
                            <span style="color: #cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" 
                              action="controllers/delete_document.php"
                              onsubmit="return confirmDelete('<?= addslashes(h($d['Title'])) ?>', 'approved')">
                            <input type="hidden" name="id" value="<?= $d['DocumentID'] ?>">
                            <button class="btn-sm btn-delete">🗑️ Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Rejected -->
    <div class="section-header">
        <h2>✕ Rejected Documents</h2>
        <div class="section-count"><?= count($rejectedDocs) ?> document<?= count($rejectedDocs) != 1 ? 's' : '' ?></div>
    </div>

    <div class="table-wrapper">
        <?php if (empty($rejectedDocs)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-text">No rejected documents</div>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Year</th>
                    <th>Area</th>
                    <th>Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rejectedDocs as $d): ?>
                <tr>
                    <td><div class="doc-title"><?= h($d['Title']) ?></div></td>
                    <td><?= h($d['PublicationYear']) ?></td>
                    <td><?= h($d['Area']) ?></td>
                    <td>
                        <?php if (!empty($d['LinkPath'])): ?>
                            <a href="<?= h($d['LinkPath']) ?>" target="_blank" class="external-link">Open →</a>
                        <?php else: ?>
                            <span style="color: #cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <form method="POST" 
                                  action="controllers/review_action.php" 
                                  style="display:inline;"
                                  onsubmit="return confirmAction('approved', '<?= addslashes(h($d['Title'])) ?>')">
                                <input type="hidden" name="id" value="<?= $d['DocumentID'] ?>">
                                <input type="hidden" name="decision" value="approved">
                                <button class="btn-sm btn-approve">✓ Re-Approve</button>
                            </form>

                            <form method="POST" 
                                  action="controllers/delete_document.php"
                                  style="display:inline;"
                                  onsubmit="return confirmDelete('<?= addslashes(h($d['Title'])) ?>', 'rejected')">
                                <input type="hidden" name="id" value="<?= $d['DocumentID'] ?>">
                                <button class="btn-sm btn-delete">🗑️ Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- Duplicate Modal -->
<div class="modal-bg" id="modal_dup">
    <div class="modal-box">
        <h3>Duplicate Check Results</h3>
        <div id="dup_results"></div>
        <button class="modal-close" onclick="closeModal('modal_dup')">Close</button>
    </div>
</div>

</body>
</html>