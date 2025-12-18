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

    $stm = $_db->prepare("SELECT password FROM user WHERE userID = ?");
    $stm->execute([$user_id]);
    $user = $stm->fetch();

    if (!password_verify($current, $user->password)) {
        $errors['current_password'] = 'Current password is incorrect';
    }
    elseif (strlen($new) < 6) {
        $errors['new_password'] = 'New password must be at least 6 characters';
    }
    elseif ($new !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (!$errors) {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stm = $_db->prepare("UPDATE user SET password = ? WHERE userID = ?");
        $stm->execute([$new_hash, $user_id]);

        $info = 'Password changed successfully!';
    }
}

$_title = 'Change Password - Nº9 Perfume';
include '../_head.php';
?>

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content" style="max-width:500px;">
        <h2>Change Password</h2>

        <?php if ($info): ?>
            <p style="color:green; font-weight:bold;">✓ <?= $info ?></p>
        <?php endif; ?>

        <form method="post" style="margin-top:2rem;">
            <div style="margin-bottom:1rem;">
                <label>Current Password</label>
                <input type="password" name="current_password" required style="width:100%; padding:10px;">
                <?= $errors['current_password'] ?? '' ?>
            </div>

            <div style="margin-bottom:1rem;">
                <label>New Password</label>
                <input type="password" name="new_password" required style="width:100%; padding:10px;">
                <?= $errors['new_password'] ?? '' ?>
            </div>

            <div style="margin-bottom:1.5rem;">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required style="width:100%; padding:10px;">
                <?= $errors['confirm_password'] ?? '' ?>
            </div>

            <button type="submit" style="padding:12px 30px; background:#000; color:#fff;">
                Change Password
            </button>
        </form>
    </div>
</div>

<?php include '../_foot.php'; ?>
