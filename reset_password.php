<?php
session_start();
require_once "includes/connect.php";

// Secret key (same as in forgot_password_handler.php)
define('SECRET_KEY', 'your-super-secret-key-change-this-in-production-12345');

$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Function to verify token
function verifyToken($token) {
    if (empty($token)) {
        return ['valid' => false, 'error' => 'No token provided'];
    }

    // Split token into payload and signature
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return ['valid' => false, 'error' => 'Invalid token format'];
    }

    list($payloadBase64, $providedSignature) = $parts;

    // Verify signature
    $expectedSignature = hash_hmac('sha256', $payloadBase64, SECRET_KEY);
    if (!hash_equals($expectedSignature, $providedSignature)) {
        return ['valid' => false, 'error' => 'Invalid token signature'];
    }

    // Decode payload
    $payloadJson = base64_decode($payloadBase64);
    $payload = json_decode($payloadJson, true);

    if (!$payload) {
        return ['valid' => false, 'error' => 'Invalid token data'];
    }

    // Check expiration
    if (!isset($payload['exp']) || $payload['exp'] < time()) {
        return ['valid' => false, 'error' => 'Token has expired'];
    }

    // Check required fields
    if (!isset($payload['user_id']) || !isset($payload['email'])) {
        return ['valid' => false, 'error' => 'Invalid token data'];
    }

    return [
        'valid' => true,
        'user_id' => $payload['user_id'],
        'email' => $payload['email']
    ];
}

// Verify token if provided
$tokenValid = false;
$userID = null;

if ($token) {
    $verification = verifyToken($token);
    if ($verification['valid']) {
        $tokenValid = true;
        $userID = $verification['user_id'];
    } else {
        $error = $verification['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Research Hub</title>
    <link rel="stylesheet" href="assets/css/globals.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            color: #64748b;
            text-align: center;
            margin-bottom: 32px;
            font-size: 15px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-error {
            background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%);
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
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

        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .back-link {
            text-align: center;
            margin-top: 24px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="logo">
        <div class="logo-icon">🔖</div>
        <div class="logo-text">Research Hub</div>
    </div>

    <h1>Reset Your Password</h1>
    <p class="subtitle">Enter your new password below</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($tokenValid): ?>
        <form action="controllers/reset_password_handler.php" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" 
                       id="new_password" 
                       name="new_password" 
                       required 
                       minlength="6"
                       placeholder="Enter new password">
                <div class="password-requirements">
                    Password must be at least 6 characters long
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required 
                       minlength="6"
                       placeholder="Confirm new password">
            </div>

            <button type="submit" class="btn-submit">Reset Password</button>
        </form>
    <?php endif; ?>

    <div class="back-link">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

</body>
</html>