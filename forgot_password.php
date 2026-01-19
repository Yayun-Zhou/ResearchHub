<?php
session_start();
$error = isset($_GET['error']) ? $_GET['error'] : "";
$success = isset($_GET['success']) ? $_GET['success'] : "";
$reset_link = isset($_SESSION['reset_link']) ? $_SESSION['reset_link'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Research Hub</title>
    <link rel="stylesheet" href="assets/css/globals.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
        }

        .center {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            background: white;
            width: 100%;
            max-width: 440px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px 40px;
            animation: slideUp 0.5s ease-out;
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

        .title-box {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-box {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 16px;
            font-size: 36px;
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }

        .title-box h2 {
            font-size: 28px;
            color: #1a202c;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .title-box p {
            color: #718096;
            font-size: 15px;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            color: #2d3748;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f7fafc;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input::placeholder {
            color: #a0aec0;
        }

        button {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        button:active {
            transform: translateY(0);
        }

        .error, .success {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            background: #fff5f5;
            border-left: 4px solid #f56565;
            color: #c53030;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .success-content {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .success-icon {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .success-text {
            flex: 1;
            min-width: 0;
        }

        .link-container {
            margin-top: 12px;
            background: #f8fafc;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .link-text {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #64748b;
            word-break: break-all;
            line-height: 1.4;
            max-height: 80px;
            overflow-y: auto;
            margin-bottom: 8px;
            padding: 4px;
        }

        .link-text::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .link-text::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 2px;
        }

        .link-text::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        .link-text::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .link-actions {
            display: flex;
            gap: 8px;
        }

        .copy-btn, .open-btn {
            flex: 1;
            padding: 8px 12px;
            font-size: 13px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .copy-btn {
            background: #667eea;
            color: white;
        }

        .copy-btn:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }

        .copy-btn:active {
            transform: translateY(0);
        }

        .open-btn {
            background: #10b981;
            color: white;
        }

        .open-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .open-btn:active {
            transform: translateY(0);
        }

        .link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .bottom-text {
            text-align: center;
            font-size: 14px;
            color: #718096;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .bottom-text .link {
            margin-left: 4px;
        }

        .helper-text {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 12px;
            text-align: center;
            line-height: 1.5;
        }

        @media (max-width: 480px) {
            .card {
                padding: 36px 28px;
            }

            .title-box h2 {
                font-size: 24px;
            }

            .logo-box {
                width: 64px;
                height: 64px;
                font-size: 32px;
            }

            .link-text {
                font-size: 10px;
            }

            .copy-btn, .open-btn {
                font-size: 12px;
                padding: 7px 10px;
            }
        }
    </style>
</head>

<body>
<div class="center">
    <div class="card">

        <div class="title-box">
            <div class="logo-box">🔑</div>
            <h2>Reset Password</h2>
            <p>Enter your email to receive a password reset link</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <div class="success-content">
                    <span class="success-icon">✓</span>
                    <div class="success-text">
                        <div><?= htmlspecialchars($success) ?></div>
                        <?php if ($reset_link): ?>
                            <div class="link-container">
                                <div class="link-text" id="resetLink"><?= htmlspecialchars($reset_link) ?></div>
                                <div class="link-actions">
                                    <button class="copy-btn" onclick="copyLink(event)">📋 Copy Link</button>
                                    <a href="<?= htmlspecialchars($reset_link) ?>" class="open-btn">🔗 Open Link</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <script>
            function copyLink(event) {
                const link = document.getElementById('resetLink').textContent;
                navigator.clipboard.writeText(link).then(() => {
                    const btn = event.target;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '✓ Copied!';
                    btn.style.background = '#10b981';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = '#667eea';
                    }, 2000);
                }).catch(err => {
                    alert('Failed to copy link. Please copy manually.');
                    console.error('Copy failed:', err);
                });
            }
            </script>
        <?php endif; ?>

        <form method="POST" action="controllers/forgot_handler.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" 
                       id="email"
                       name="email" 
                       placeholder="your.email@university.edu" 
                       autocomplete="email"
                       required>
            </div>

            <button type="submit">
                Send Reset Link
            </button>
        </form>

        <div class="helper-text">
            We'll send you an email with instructions to reset your password.
        </div>

        <div class="bottom-text">
            Remember your password?  
            <a href="login.php" class="link">Back to Login</a>
        </div>

    </div>
</div>
</body>
</html>