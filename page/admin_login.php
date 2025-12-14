<?php

require '../_base.php';

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
        else if (!password_verify($password, $user->password)) {
            $_err['password'] = 'Wrong password';
        } 
        else {
            // Login successful
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

<div class="container" style="max-width:450px; margin:100px auto; padding:30px; background:#f9f9f9; border-radius:10px;">
    <h2 style="text-align:center; margin-bottom:20px;">Login</h2>
    
    <?php if ($info_message): ?>
        <div style="padding:12px; background:#d4edda; color:#155724; border-radius:5px; margin-bottom:15px; border-left: 4px solid #28a745;">
            ✓ <?= htmlspecialchars($info_message) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div style="margin-bottom:15px;">
            <input type="email" 
                   name="email" 
                   placeholder="Email" 
                   value="<?= encode(req('email')) ?>" 
                   style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">
            <?= err('email') ?>
        </div>

        <div style="margin-bottom:15px;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <input type="password" 
                       id="login_password" 
                       name="password"
                       placeholder="Password"
                       style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">
                <button type="button" 
                        class="show-pass" 
                        data-target="#login_password" 
                        style="cursor:pointer; padding:12px; background:#f0f0f0; border:1px solid #ddd; border-radius:5px;">
                    👁️
                </button>
            </div>
            <?= err('password') ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:20px;">
            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                <input type="checkbox" name="remember" value="1"> 
                <span>Remember me</span>
            </label>
            <a href="forgot_password.php" style="color:#666; text-decoration:none; font-size:14px;">
                Forgot Password?
            </a>
        </div>
        <?= err('remember') ?>

        <button type="submit" 
                style="width:100%; padding:12px; background:#000; color:#fff; border:none; border-radius:5px; cursor:pointer; font-size:16px; margin-bottom:15px;">
            Login
        </button>

        <div style="text-align:center;">
            <span style="color:#666;">Don't have an account? </span>
            <a href="register.php" style="color:#000; text-decoration:none; font-weight:bold;">Register</a>
        </div>
    </form>
</div>

<?php include '../_foot.php'; ?>