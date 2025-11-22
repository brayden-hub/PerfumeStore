<?php
session_start();
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stm = $_db->prepare("SELECT * FROM user WHERE remember_token = ?");
    $stm->execute([$token]);
    $user = $stm->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;
    } else {
        // 如果 token 无效 → 清掉 cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

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
            // 设置 session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_role'] = $user->role;

            temp('info', 'Login successful!');

            // 如果勾选 Remember Me → 生成 token 并存 cookie
            if (req('remember') == '1') {
                // 生成 64 位随机 token
                $token = bin2hex(random_bytes(32));

                // 存到数据库
                $stm = $_db->prepare("UPDATE user SET remember_token = ? WHERE id = ?");
                $stm->execute([$token, $user->id]);

                // 存到 cookie，有效期 30 天
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            }

            redirect('/'); 
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

        <input type="password" name="password" placeholder="Password" 
               style="width:100%; padding:10px; margin:10px 0;">
        <?= err('password') ?>
        <input type="checkbox" name="remember" value="1"> Remember me


        <button type="submit" 
                style="width:100%; padding:10px; background:#000; color:#fff; border:none;">
            Login
        </button>
    </form>
</div>

<?php include '../_foot.php'; ?>
