<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$info = '';
$errors = [];

// Check for success redirect
if (isset($_GET['success'])) {
    $info = 'Password changed successfully!';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    $current = trim($current);
    $new     = trim($new);
    $confirm = trim($confirm);

    if ($current === '' || $new === '' || $confirm === '') {
        $errors['form'] = 'Please fill in all fields';
    } else {
        $stm = $_db->prepare("SELECT password FROM user WHERE userID = ?");
        $stm->execute([$user_id]);
        $user = $stm->fetch();

        if (!$user) {
            $errors['form'] = 'User not found in database';
        }
        elseif (!password_verify($current, $user->password)) {
            $errors['current_password'] = 'Current password is incorrect';
        }
        elseif (strlen($new) < 6) {
            $errors['new_password'] = 'New password must be at least 6 characters';
        }
        elseif ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (empty($errors)) {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $stm = $_db->prepare("UPDATE user SET password = ? WHERE userID = ?");
            $stm->execute([$new_hash, $user_id]);
            
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
            exit;
        }
    }
}

$_title = 'Change Password - Nº9 Perfume';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?></title>
    <link rel="shortcut icon" href="/public/images/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow-x: hidden;
}

/* Animated Background Particles */
.particles {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 0;
    pointer-events: none;
}

.particle {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 15s infinite ease-in-out;
}

.particle:nth-child(1) {
    width: 80px;
    height: 80px;
    left: 10%;
    animation-delay: 0s;
    animation-duration: 12s;
}

.particle:nth-child(2) {
    width: 60px;
    height: 60px;
    left: 70%;
    animation-delay: 2s;
    animation-duration: 18s;
}

.particle:nth-child(3) {
    width: 100px;
    height: 100px;
    left: 40%;
    animation-delay: 4s;
    animation-duration: 15s;
}

.particle:nth-child(4) {
    width: 50px;
    height: 50px;
    left: 80%;
    animation-delay: 1s;
    animation-duration: 20s;
}

.particle:nth-child(5) {
    width: 70px;
    height: 70px;
    left: 20%;
    animation-delay: 3s;
    animation-duration: 14s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(100vh) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 0.5;
    }
    90% {
        opacity: 0.5;
    }
    50% {
        transform: translateY(-100px) rotate(360deg);
        opacity: 1;
    }
}

/* Header */
.header {
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    padding: 1.2rem 2rem;
    position: relative;
    z-index: 10;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.header a {
    color: #fff;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.header a:hover {
    transform: translateX(-5px);
    color: #ffd700;
}

.header a::before {
    content: '←';
    font-size: 1.5rem;
}

/* Main Content */
main {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    z-index: 1;
}

.account-wrapper {
    display: flex;
    gap: 2rem;
    max-width: 1100px;
    width: 100%;
    animation: slideUp 0.6s ease;
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

/* Password Form Container */
.password-form-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 3rem;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    flex: 1;
    position: relative;
    overflow: hidden;
}

.password-form-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
    background-size: 200% 100%;
    animation: gradientMove 3s ease infinite;
}

@keyframes gradientMove {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.password-form-container h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
}

.password-form-subtitle {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 2rem;
}

/* Success Message */
.success-message {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: #fff;
    padding: 1.2rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    font-weight: 500;
    animation: successPop 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 0 10px 30px rgba(17, 153, 142, 0.3);
}

@keyframes successPop {
    0% {
        opacity: 0;
        transform: scale(0.8) translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.success-message::before {
    content: '✓';
    font-size: 1.5rem;
    font-weight: bold;
    background: rgba(255, 255, 255, 0.3);
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

/* Error Message */
.general-error {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: #fff;
    padding: 1.2rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    animation: errorShake 0.5s ease;
    box-shadow: 0 10px 30px rgba(245, 87, 108, 0.3);
}

@keyframes errorShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.general-error::before {
    content: '✕';
    font-weight: bold;
    font-size: 1.5rem;
    background: rgba(255, 255, 255, 0.3);
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.8rem;
    position: relative;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.6rem;
    color: #333;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.password-input-wrapper {
    position: relative;
}

.form-group input[type="password"],
.form-group input[type="text"] {
    width: 100%;
    padding: 1rem 3.5rem 1rem 1.2rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
    background: #fff;
}

.toggle-password {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 1.3rem;
    color: #999;
    transition: all 0.3s ease;
    user-select: none;
    padding: 0.3rem;
    border-radius: 8px;
}

.toggle-password:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.toggle-password:active {
    transform: translateY(-50%) scale(0.95);
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.form-group input.error {
    border-color: #f5576c;
    animation: inputShake 0.4s ease;
}

@keyframes inputShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-8px); }
    75% { transform: translateX(8px); }
}

.error-message {
    color: #f5576c;
    font-size: 0.875rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    animation: fadeIn 0.3s ease;
    font-weight: 500;
}

@keyframes fadeIn {
    from { 
        opacity: 0;
        transform: translateY(-5px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.error-message::before {
    content: '⚠';
    font-size: 1.1rem;
}

/* Password Requirements */
.password-requirements {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-left: 4px solid #667eea;
    padding: 1.2rem;
    margin-top: 0.8rem;
    border-radius: 12px;
    font-size: 0.875rem;
    color: #555;
    animation: fadeIn 0.5s ease;
}

.password-requirements strong {
    color: #333;
    display: block;
    margin-bottom: 0.5rem;
}

.password-requirements ul {
    margin: 0.5rem 0 0 1.5rem;
    padding: 0;
}

.password-requirements li {
    margin: 0.4rem 0;
    position: relative;
}

.password-requirements li::marker {
    color: #667eea;
}

/* Submit Button */
.btn-change-password {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 1.1rem 2.5rem;
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    width: 100%;
    margin-top: 1.5rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    position: relative;
    overflow: hidden;
}

.btn-change-password::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-change-password:hover::before {
    width: 300px;
    height: 300px;
}

.btn-change-password:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
}

.btn-change-password:active {
    transform: translateY(-1px);
}

/* Footer */
.footer {
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    padding: 1.5rem 2rem;
    text-align: center;
    position: relative;
    z-index: 10;
    color: #fff;
}

.footer p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 768px) {
    .account-wrapper {
        flex-direction: column;
        padding: 1rem;
    }
    
    .password-form-container {
        padding: 2rem 1.5rem;
    }
    
    .password-form-container h2 {
        font-size: 1.6rem;
    }
}

/* Sidebar Styles */
.account-sidebar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 2rem;
    border-radius: 24px;
    min-width: 260px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

@media (max-width: 768px) {
    .account-sidebar {
        min-width: auto;
    }
}
    </style>
</head>
<body>

<!-- Animated Background Particles -->
<div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<!-- Header -->
<header class="header">
    <a href="/">Back to N°9 Perfume</a>
</header>

<!-- Main Content -->
<main>
    <div class="account-wrapper">
        <?php include 'account_sidebar.php'; ?>

        <div class="password-form-container">
            <h2>Change Password</h2>
            <p class="password-form-subtitle">Update your password to keep your account secure</p>

            <?php if ($info): ?>
                <div class="success-message"><?= htmlspecialchars($info) ?></div>
            <?php endif; ?>

            <?php if (isset($errors['form'])): ?>
                <div class="general-error"><?= htmlspecialchars($errors['form']) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            class="<?= isset($errors['current_password']) ? 'error' : '' ?>"
                        >
                        <span class="toggle-password" onclick="togglePassword('current_password')">👁️</span>
                    </div>
                    <?php if (isset($errors['current_password'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['current_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="new_password"
                            name="new_password"
                            autocomplete="new-password"
                            class="<?= isset($errors['new_password']) ? 'error' : '' ?>"
                        >
                        <span class="toggle-password" onclick="togglePassword('new_password')">👁️</span>
                    </div>
                    <?php if (isset($errors['new_password'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['new_password']) ?></div>
                    <?php endif; ?>
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 6 characters long</li>
                            <li>Different from your current password</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password"
                            name="confirm_password"
                            autocomplete="new-password"
                            class="<?= isset($errors['confirm_password']) ? 'error' : '' ?>"
                        >
                        <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-change-password">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="footer">
    <p>Copyright © <?= date('Y') ?> N°9 Perfume. All Rights Reserved.</p>
</footer>

<script>
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const toggle = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.textContent = '🙈';
        toggle.style.color = '#667eea';
    } else {
        input.type = 'password';
        toggle.textContent = '👁️';
        toggle.style.color = '#999';
    }
}
</script>

</body>
</html>