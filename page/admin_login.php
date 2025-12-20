<?php
require '../_base.php';


if (isset($_SESSION['user_id'])) {
    redirect('profile.php');
}

$_err = [];

if (is_post()) {
    $email = req('email');
    $password = req('password');

    // Validation
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
        else if (!password_verify($password, $user->password)) {
            $_err['password'] = 'Wrong password';
        } 
        
        else if ($user->role !== 'Admin') {
            $_err['email'] = 'Only administrators can access this page.';
        }
        
        else if ($user->status !== 'Activated') {
            $_err['email'] = 'This admin account is not active.';
        }
        else {
            
            $_SESSION['user_id']       = $user->userID;
            $_SESSION['user_name']     = $user->name;
            $_SESSION['email']         = $user->email;
            $_SESSION['phone']         = $user->phone_number ?? '';
            $_SESSION['user_role']     = $user->role;
            $_SESSION['Profile_Photo'] = $user->Profile_Photo ?? 'default1.jpg';

            temp('info', 'Welcome back, Administrator.');

            
            redirect('profile.php'); 
        }
    }
}

$info_message = temp('info');
$_title = 'Admin Login';
include '../_head.php';
?>

<div class="auth-container">
    <h2 class="auth-title">Admin Portal</h2>
    
    <?php if ($info_message): ?>
        <div class="alert-info">
            ✓ <?= htmlspecialchars($info_message) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="auth-input-group">
            <input type="email" name="email" class="auth-input" placeholder="Admin Email" value="<?= encode(req('email')) ?>">
            <?= err('email') ?>
        </div>

        <div class="auth-input-group" style="margin-bottom: 25px;">
            <div class="auth-pass-wrapper">
                <input type="password" id="login_password" name="password" class="auth-input" placeholder="Password">
                <button type="button" class="show-pass auth-btn-show" data-target="#login_password">
                    👁️
                </button>
            </div>
            <?= err('password') ?>
        </div>

        <button type="submit" class="auth-btn-login">
            Login as Admin
        </button>

        <div class="auth-footer">
            <a href="login.php" class="auth-link-forgot">
                ← Back to Member Login
            </a>
        </div>
    </form>
</div>

<?php include '../_foot.php'; ?>