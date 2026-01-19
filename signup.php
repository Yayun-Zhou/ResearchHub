<?php
session_start();
require_once "includes/connect.php"; 

// Fetch all affiliations from database
$affQuery = $conn->query("SELECT AffiliationID, AffiliationName FROM Affiliation ORDER BY AffiliationName ASC");
$affiliations = $affQuery->fetchAll(PDO::FETCH_ASSOC);

// Error message from signup_handler
$error = isset($_GET['error']) ? $_GET['error'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Research Hub</title>
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
            max-width: 540px;
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

        .error-box {
            background: #fff5f5;
            border-left: 4px solid #f56565;
            color: #c53030;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            align-items: center;
            gap: 10px;
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

        input, select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f7fafc;
            font-family: inherit;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input::placeholder {
            color: #a0aec0;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 44px;
        }

        .input-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 20px;
            user-select: none;
            opacity: 0.6;
            transition: opacity 0.2s ease;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        .password-hint {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
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

        /* Progress indicator */
        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .password-strength-bar.weak {
            width: 33%;
            background: #ef4444;
        }

        .password-strength-bar.medium {
            width: 66%;
            background: #f59e0b;
        }

        .password-strength-bar.strong {
            width: 100%;
            background: #10b981;
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
            <h2>Create Account</h2>
            <p>Join Research Hub to manage your research literature</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="controllers/signup_handler.php">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" 
                       id="username"
                       name="username" 
                       placeholder="Your name" 
                       required
                       autocomplete="name">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" 
                       id="email"
                       name="email" 
                       placeholder="your.email@university.edu" 
                       required
                       autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="password"
                           name="password" 
                           placeholder="Create a strong password" 
                           required
                           minlength="6"
                           oninput="checkPasswordStrength()">
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-hint">At least 6 characters</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="confirm_password"
                           name="confirm_password" 
                           placeholder="Re-enter your password" 
                           required
                           minlength="6">
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
            </div>

            <!-- Dynamic Affiliation Dropdown -->
            <div class="form-group">
                <label for="affiliation">Affiliation</label>
                <select id="affiliation" name="affiliation" required>
                    <option value="">-- Select Your Institution --</option>
                    <?php foreach ($affiliations as $aff): ?>
                        <option value="<?= $aff['AffiliationID'] ?>">
                            <?= htmlspecialchars($aff['AffiliationName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Role Selector -->
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="">-- Select Your Role --</option>
                    <option value="Student">🎓 Student</option>
                    <option value="Researcher">🔬 Researcher</option>
                    <option value="Professor">👨‍🏫 Professor</option>
                </select>
            </div>

            <button type="submit">Create Account</button>

        </form>

        <div class="bottom-text">
            Already have an account?
            <a href="login.php" class="link">Sign In</a>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.parentElement.querySelector('.toggle-password');
    
    if (field.type === "password") {
        field.type = "text";
        icon.textContent = "🙈";
    } else {
        field.type = "password";
        icon.textContent = "👁️";
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthBar = document.getElementById('strengthBar');
    
    // Remove all classes
    strengthBar.className = 'password-strength-bar';
    
    if (password.length === 0) {
        return;
    }
    
    let strength = 0;
    
    // Length check
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    
    // Has numbers
    if (/\d/.test(password)) strength++;
    
    // Has uppercase and lowercase
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    
    // Has special characters
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    // Apply strength class
    if (strength <= 2) {
        strengthBar.classList.add('weak');
    } else if (strength <= 4) {
        strengthBar.classList.add('medium');
    } else {
        strengthBar.classList.add('strong');
    }
}
</script>

</body>
</html>