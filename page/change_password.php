<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$info = '';
$errors = [];

if (is_post()) {
    $current = req('current_password');
    $new     = req('new_password');
    $confirm = req('confirm_password');

    // 抓目前资料库的密码
    $stm = $_db->prepare("SELECT password FROM user WHERE userID = ?");
    $stm->execute([$user_id]);
    $user = $stm->fetch();

    // 验证目前密码
    if (!password_verify($current, $user->password)) {
        $errors['current_password'] = 'Current password is incorrect';
    }
    // 验证新密码长度
    elseif (strlen($new) < 6) {
        $errors['new_password'] = 'New password must be at least 6 characters';
    }
    // 确认两次输入一样
    elseif ($new !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // 全部正确 → 更新密码
    if (!$errors) {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stm = $_db->prepare("UPDATE user SET password = ? WHERE userID = ?");
        $stm->execute([$new_hash, $user_id]);

        $info = 'Password changed successfully!';
    }
}

$_title = 'Change Password - N°9 Perfume';
include '../_head.php';
?>

<div style="padding:2rem; max-width: 500px; margin: 0 auto;">
    <h2>Change Password</h2>

    <?php if ($info): ?>
        <p style="color:green; font-weight:bold; padding:10px 0;">✓ <?= $info ?></p>
    <?php endif; ?>

    <form method="post" style="margin-top:2rem;">
        <div style="margin-bottom:1rem;">
            <label>Current Password</label>
            <input type="password" name="current_password" required
                   style="width:100%; padding:10px; font-size:1rem;">
            <?php if (isset($errors['current_password'])) 
                echo '<small style="color:red;">'.$errors['current_password'].'</small>' ?>
        </div>

        <div style="margin-bottom:1rem;">
            <label>New Password</label>
            <input type="password" name="new_password" required
                   style="width:100%; padding:10px; font-size:1rem;">
            <?php if (isset($errors['new_password'])) 
                echo '<small style="color:red;">'.$errors['new_password'].'</small>' ?>
        </div>

        <div style="margin-bottom:1.5rem;">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required
                   style="width:100%; padding:10px; font-size:1rem;">
            <?php if (isset($errors['confirm_password'])) 
                echo '<small style="color:red;">'.$errors['confirm_password'].'</small>' ?>
        </div>

        <button type="submit"
                style="padding:12px 30px; background:#000; color:#fff; border:none; cursor:pointer;">
            Change Password
        </button>

        <a href="profile.php" style="margin-left:20px; color:#666; text-decoration:underline;">
            ← Back to Profile
        </a>
    </form>
</div>

<?php include '../_foot.php'; ?>