<?php
session_start();
require_once "../includes/connect.php";

// Secret key (same as above)
define('SECRET_KEY', 'your-super-secret-key-change-this-in-production-12345');

$token = trim($_POST['token'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Function to verify token (same as in reset_password.php)
function verifyToken($token) {
    if (empty($token)) {
        return ['valid' => false, 'error' => 'No token provided'];
    }

    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return ['valid' => false, 'error' => 'Invalid token format'];
    }

    list($payloadBase64, $providedSignature) = $parts;

    $expectedSignature = hash_hmac('sha256', $payloadBase64, SECRET_KEY);
    if (!hash_equals($expectedSignature, $providedSignature)) {
        return ['valid' => false, 'error' => 'Invalid token signature'];
    }

    $payloadJson = base64_decode($payloadBase64);
    $payload = json_decode($payloadJson, true);

    if (!$payload) {
        return ['valid' => false, 'error' => 'Invalid token data'];
    }

    if (!isset($payload['exp']) || $payload['exp'] < time()) {
        return ['valid' => false, 'error' => 'Token has expired'];
    }

    if (!isset($payload['user_id']) || !isset($payload['email'])) {
        return ['valid' => false, 'error' => 'Invalid token data'];
    }

    return [
        'valid' => true,
        'user_id' => $payload['user_id'],
        'email' => $payload['email']
    ];
}

// Validate inputs
if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
    header("Location: ../reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("All fields are required"));
    exit;
}

if ($newPassword !== $confirmPassword) {
    header("Location: ../reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("Passwords do not match"));
    exit;
}

if (strlen($newPassword) < 6) {
    header("Location: ../reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("Password must be at least 6 characters"));
    exit;
}

try {
    // Verify token
    $verification = verifyToken($token);
    
    if (!$verification['valid']) {
        throw new Exception($verification['error']);
    }

    $userID = $verification['user_id'];

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update user's password
    $stmt = $conn->prepare("UPDATE User SET Password = ? WHERE UserID = ?");
    $stmt->execute([$hashedPassword, $userID]);

    // Check if update was successful
    if ($stmt->rowCount() === 0) {
        throw new Exception("User not found or password unchanged");
    }

    // Redirect to login with success message
    header("Location: ../login.php?success=" . urlencode("Password reset successfully. Please login with your new password."));
    exit;

} catch (Exception $e) {
    error_log("Password reset handler error: " . $e->getMessage());
    header("Location: ../reset_password.php?token=" . urlencode($token) . "&error=" . urlencode($e->getMessage()));
    exit;
}
?>