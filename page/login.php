<?php

require '../_base.php';
if (isset($_SESSION['user_id'])) {
    // 如果用户已登录，将他们发送到 profile.php，避免再次看到登录页面
    redirect('profile.php');
}

$_err = [];

if (is_post()) {
    $email = req('email');
    $password = req('password');

    // Validate input
    if ($email == '') {
        $_err['email'] = 'Email required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email format';
    }

    if ($password == '') {
        $_err['password'] = 'Password required';
    }

    // If no validate error → check DB
    if (!$_err) {
        $stm = $_db->prepare("SELECT * FROM user WHERE email = ?");
        $stm->execute([$email]);
        $user = $stm->fetch();

        if (!$user) {
            $_err['email'] = 'Email not registered';
        } 
        // === 新增：检查是否处于锁定时间 (5分钟锁定) ===
        else if ($user->login_attempts >= 3 && (time() - strtotime($user->attempt_time)) < 300) {
            $remaining = 300 - (time() - strtotime($user->attempt_time));
            $_err['email'] = "Too many failed attempts. Please try again in " . ceil($remaining/60) . " minute(s).";
        }
        else if (!password_verify($password, $user->password)) {
            // === 新增：密码错误时增加计数器并记录时间 ===
            $new_attempts = $user->login_attempts + 1;
            $stm = $_db->prepare("UPDATE user SET login_attempts = ?, attempt_time = NOW() WHERE userID = ?");
            $stm->execute([$new_attempts, $user->userID]);
            
            $_err['password'] = "Wrong password. Attempts left: " . (3 - $new_attempts);
            if ($new_attempts >= 3) {
                $_err['email'] = "Your account is locked for 5 minutes due to too many failed attempts.";
            }
        } 
        else if ($user->role !== 'Member') {
            $_err['email'] = 'Only member can in';
        }
        else if ($user->status === 'Deactivated') { 
            $_err['email'] = 'Your account has been deactivated. Please contact support.';
        }
        else if ($user->status === 'Pending') {
            $_err['email'] = 'Your account is not active yet. Please check your email.';
        }
        else {
            // === 登录成功：重置计数器和时间 ===
            $stm = $_db->prepare("UPDATE user SET login_attempts = 0, attempt_time = NULL WHERE userID = ?");
            $stm->execute([$user->userID]);
        
            $_SESSION['user_id']   = $user->userID;

            $_SESSION['user_name']  = $user->name;
            $_SESSION['email']     = $user->email;
            $_SESSION['phone']     = $user->phone_number ?? '';
            $_SESSION['user_role']      = $user->role;
            $_SESSION['Profile_Photo'] = $user->Profile_Photo ?? 'default1.jpg';

            temp('info', 'Login successful!');

            // Remember Me functionality
            if (req('remember') == '1' && $user->role === 'Member') {
                $token = bin2hex(random_bytes(32));
                $stm = $_db->prepare("UPDATE user SET remember_token = ? WHERE userID = ?");
                $stm->execute([$token, $user->userID]);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            } else if (req('remember') == '1' && $user->role !== 'Member') {
                $_err['remember'] = 'Only Members can use Remember Me';
            }

            redirect('profile.php');
        }
    }
}

// Get temp message ONCE and store it
$info_message = temp('info');

// Page view
$_title = 'Login';
include '../_head.php';
?>

<div class="auth-container">
    <h2 class="auth-title">Login</h2>
    
    <?php if ($info_message): ?>
        <div class="alert-info">
            ✓ <?= htmlspecialchars($info_message) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="auth-input-group">
            <input type="email" 
                   name="email" 
                   class="auth-input"
                   placeholder="Email" 
                   value="<?= encode(req('email')) ?>">
            <?= err('email') ?>
        </div>

        <div class="auth-input-group">
            <div class="auth-pass-wrapper">
                <input type="password" 
                       id="login_password" 
                       name="password"
                       class="auth-input"
                       placeholder="Password">
                <button type="button" 
                        class="show-pass auth-btn-show" 
                        data-target="#login_password">
                    👁️
                </button>
            </div>
            <?= err('password') ?>
        </div>

        <div class="auth-options">
            <label class="auth-remember">
                <input type="checkbox" name="remember" value="1"> 
                <span>Remember me</span>
            </label>
            <a href="forgot_password.php" class="auth-link-forgot">
                Forgot Password?
            </a>
        </div>
        <?= err('remember') ?>

        <button type="submit" class="auth-btn-login">
            Login
        </button>

        <div class="auth-footer">
            <span style="color:#666;">Don't have an account? </span>
            <a href="register.php" class="auth-link-reg">Register</a>
        </div>
    </form>
</div>

<?php include '../_foot.php'; ?>