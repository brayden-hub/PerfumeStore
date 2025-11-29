<?php

require '../_base.php'; // 确保 $_db 定义






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
        // 查询 user table 是否有这个 email
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
            // 登入成功 → 把所有资料存进 session
            $_SESSION['user_id']   = $user->userID;
            $_SESSION['username']  = $user->name;
            $_SESSION['email']     = $user->email;
            $_SESSION['phone']     = $user->phone_number ?? '';
            $_SESSION['role']      = $user->role;
            $_SESSION['user_name'] = $user->name;  
            $_SESSION['Profile_Photo'] = $user->Profile_Photo ?? 'default1.jpg';

            temp('info', 'Login successful!');

            // Remember Me 功能保持不变（你写得很好）
            if (req('remember') == '1' && $user->role === 'Member') {
                $token = bin2hex(random_bytes(32));
                $stm = $_db->prepare("UPDATE user SET remember_token = ? WHERE userID = ?");
                $stm->execute([$token, $user->userID]);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            } else if (req('remember') == '1' && $user->role !== 'Member') {
                $_err['remember'] = 'Only Members can use Remember Me';
            }

            redirect('profile.php');  // 改这里！直接跳 profile
        }

    }
}

// Page view
$_title = 'Login';
include '../_head.php';
?>

<div class="container" style="max-width:400px; margin:100px auto;">
    <form method="post">
        
        <input type="email" name="email" placeholder="Email" 
               value="<?= encode(req('email')) ?>" 
               style="width:100%; padding:10px; margin:10px 0;">
        <?= err('email') ?>

        <div style="display: flex; align-items: center; gap: 5px; margin:10px 0;">
            <input type="password" id="login_password" name="password"
                placeholder="Password"
                style="width:100%; padding:10px;">
            <button type="button" class="show-pass" data-target="#login_password" 
                    style="cursor:pointer; padding:10px;">👁️</button>
        </div>
        <?= err('password') ?>

        <input type="checkbox" name="remember" value="1" user_role='Member' > Remember me
        <?= err('remember') ?>


        <button type="submit" 
                style="width:100%; padding:10px; background:#000; color:#fff; border:none;">
            Login
        </button>
    </form>
</div>

<?php include '../_foot.php'; ?>
