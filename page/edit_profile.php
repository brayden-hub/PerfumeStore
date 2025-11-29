<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$info    = '';

// 当使用者按更新按钮
if (is_post()) {
    $name  = trim(req('name'));
    $phone = trim(req('phone'));

    // 验证
    if ($name === '') {
        $err_name = 'Name cannot be empty';
    }
    if ($phone !== '' && !preg_match('/^\d{8,15}$/', $phone)) {
        $err_phone = 'Please enter a valid phone number';
    }

    // 没错误才更新
    if (!isset($err_name) && !isset($err_phone)) {
        $stm = $_db->prepare("UPDATE user SET name = ?, phone_number = ? WHERE userID = ?");
        $stm->execute([$name, $phone, $user_id]);

        // 更新 session（只用 phone 这个 key）
        $_SESSION['username'] = $name;
        $_SESSION['phone']    = $phone;

        $info = 'Profile updated successfully!';
    }
}

// 抓目前资料显示在表单
$stm = $_db->prepare("SELECT name, phone_number FROM user WHERE userID = ?");
$stm->execute([$user_id]);
$user = $stm->fetch();

$_title = 'Edit Profile - N°9 Perfume';
include '../_head.php';
?>

<div style="padding:2rem; max-width: 600px; margin: 0 auto;">
    <h2>Edit Profile</h2>

    <?php if ($info): ?>
        <p style="color:green; font-weight:bold; padding:10px 0;">✓ <?= $info ?></p>
    <?php endif; ?>

    <form method="post" style="margin-top:2rem;">
        <div style="margin-bottom:1rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333;">Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user->name ?? '') ?>"
                   style="width:100%; padding:10px; font-size:1rem;">
            <?php if (isset($err_name)) echo '<small style="color:red;">'.$err_name.'</small>' ?>
        </div>

        <div style="margin-bottom:1.5rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333;">Phone (optional)</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($user->phone_number ?? '') ?>"
                   placeholder="e.g. 91234567"
                   style="width:100%; padding:10px; font-size:1rem;">
            <?php if (isset($err_phone)) echo '<small style="color:red;">'.$err_phone.'</small>' ?>
        </div>

        <button type="submit"
                style="padding:12px 30px; background:#000; color:#fff; border:none; cursor:pointer;">
            Update Profile
        </button>

        <a href="profile.php" style="margin-left:20px; color:#666; text-decoration:underline;">
            ← Back to Profile
        </a>
    </form>
</div>

<?php include '../_foot.php'; ?>