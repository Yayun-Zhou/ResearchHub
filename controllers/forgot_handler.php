<?php
session_start();
require_once "../includes/connect.php";

// Secret key for signing tokens (store this in config or env file in production)
define('SECRET_KEY', 'your-super-secret-key-change-this-in-production-12345');

$email = trim($_POST['email'] ?? '');

// Validate email
if (empty($email)) {
    header("Location: ../forgot_password.php?error=" . urlencode("Email is required"));
    exit;
}

try {
    // 1. Check if email exists
    $stmt = $conn->prepare("SELECT UserID, UserName FROM User WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Security: Don't reveal if email exists or not
        header("Location: ../forgot_password.php?success=" . urlencode("If the email exists, a reset link has been sent"));
        exit;
    }

    // 2. Create token payload with expiration (15 minutes from now)
    $payload = [
        'user_id' => $user['UserID'],
        'email' => $email,
        'exp' => time() + (15 * 60), // Expires in 15 minutes
        'random' => bin2hex(random_bytes(8)) // Add randomness to prevent token reuse
    ];

    // 3. Encode payload and create signature
    $payloadJson = json_encode($payload);
    $payloadBase64 = base64_encode($payloadJson);
    $signature = hash_hmac('sha256', $payloadBase64, SECRET_KEY);
    
    // 4. Combine payload and signature
    $token = $payloadBase64 . '.' . $signature;

    // 5. Generate reset link
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $resetLink = $protocol . "://" . $host . $basePath . "/reset_password.php?token=" . urlencode($token);

    // Alternative: Hardcoded ngrok URL
    // $ngrokBase = "https://hypernormal-nontopographical-nathaniel.ngrok-free.dev/ResearchHub";
    // $resetLink = $ngrokBase . "/reset_password.php?token=" . urlencode($token);

    // Store the reset link in session (改这里！)
    $_SESSION['reset_link'] = $resetLink;
    
    // Redirect with only the success message (改这里！)
    header("Location: ../forgot_password.php?success=" . urlencode("Reset link generated successfully (valid for 15 minutes)"));
    exit;

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    header("Location: ../forgot_password.php?error=" . urlencode("An error occurred. Please try again."));
    exit;
}
?>