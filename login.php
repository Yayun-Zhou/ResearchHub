<?php
session_start();
$error = isset($_GET['error']) ? $_GET['error'] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Hub - Login</title>
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
            margin-bottom: 20px;
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

        .error-box {
            background: #fff5f5;
            border-left: 4px solid #f56565;
            color: #c53030;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }

        .forgot-password {
            text-align: right;
            margin: 12px 0 24px 0;
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
        }
    </style>
</head>

<body>
<div class="center">
    <div class="card">

        <div class="title-box">
            <div class="logo-box">🔖</div>
            <h2>Research Hub</h2>
            <p>Sign in to access the platform</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="controllers/login_handler.php">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="your.email@university.edu" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" autocomplete="off" required>
            </div>

            <div class="forgot-password">
                <a href="forgot_password.php" class="link">Forgot Password?</a>
            </div>

            <button type="submit">Sign In</button>
        </form>

        <div class="bottom-text">
            Don't have an account?
            <a href="signup.php" class="link">Sign Up</a>
        </div>
    </div>
</div>
</body>
</html>