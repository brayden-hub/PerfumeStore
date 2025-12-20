<?php
require '../_base.php';
if (isset($_SESSION['user_id'])) {
    redirect('profile.php');
}

$_err = [];

if (is_post()) {
    $email = req('email');
    $password = req('password');

    if ($email == '') {
        $_err['email'] = 'Email required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email format';
    }

    if ($password == '') {
        $_err['password'] = 'Password required';
    }

    if (!$_err) {
        $stm = $_db->prepare("SELECT * FROM user WHERE email = ?");
        $stm->execute([$email]);
        $user = $stm->fetch();

        if (!$user) {
            $_err['email'] = 'Email not registered';
        } 
        
        else if ($user->login_attempts >= 3 && (time() - strtotime($user->attempt_time)) < 300) {
            $remaining = 300 - (time() - strtotime($user->attempt_time));
            $_err['email'] = "Account locked. Please try again in " . ceil($remaining/60) . " minute(s).";
        }
        
        else if (!password_verify($password, $user->password)) {
            
            $is_lock_expired = (time() - strtotime($user->attempt_time)) >= 300;
            
            if ($is_lock_expired && $user->login_attempts >= 3) {
                
                $new_attempts = 1;
            } else {
                
                $new_attempts = $user->login_attempts + 1;
            }
            
            $update = $_db->prepare("UPDATE user SET login_attempts = ?, attempt_time = NOW() WHERE userID = ?");
            $update->execute([$new_attempts, $user->userID]);
            
            $left = max(0, 3 - $new_attempts);
            
            if ($new_attempts >= 3) {
                $_err['email'] = "Too many failed attempts. Your account is now locked for 5 minutes.";
            } else {
                $_err['password'] = "Wrong password. Attempts left: $left";
            }
        }
        else if ($user->role !== 'Member') {
            $_err['email'] = 'Invalid email';
        }
        else if ($user->status === 'Deactivated') { 
            $_err['email'] = 'Your account has been deactivated. Please contact support.';
        }
        else if ($user->status === 'Pending') {
            $_err['email'] = 'Your account is not active yet. Please check your email.';
        }
        else {
            
            $stm = $_db->prepare("UPDATE user SET login_attempts = 0, attempt_time = NULL WHERE userID = ?");
            $stm->execute([$user->userID]);
        
            $_SESSION['user_id']       = $user->userID;
            $_SESSION['user_name']     = $user->name;
            $_SESSION['email']         = $user->email;
            $_SESSION['phone']         = $user->phone_number ?? '';
            $_SESSION['user_role']     = $user->role;
            $_SESSION['Profile_Photo'] = $user->Profile_Photo ?? 'default1.jpg';
            
            
            auto_assign_vouchers($user->userID);

            temp('info', 'Login successful!');

            if (req('remember') == '1' && $user->role === 'Member') {
                $token = bin2hex(random_bytes(32));
                $stm = $_db->prepare("UPDATE user SET remember_token = ? WHERE userID = ?");
                $stm->execute([$token, $user->userID]);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            }

            redirect('profile.php');
        }
    }
}

$info_message = temp('info');
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