<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'Guest';
$email    = $_SESSION['email'] ?? '';

// 强制从数据库抓最新头像（永不漏）
$stm = $_db->prepare("SELECT Profile_Photo FROM user WHERE userID = ?");
$stm->execute([$user_id]);
$avatar = $stm->fetchColumn() ?: 'default1.jpg';
$_SESSION['Profile_Photo'] = $avatar;

// Get temp message ONCE
$info_message = temp('info');

$_title = 'My Profile - Nº9 Perfume';
include '../_head.php';
?>

<div style="padding:2rem; max-width: 800px; margin: 0 auto;">
    <h2>Welcome back, <?= htmlspecialchars($username) ?>!</h2>

    <?php if ($info_message): ?>
        <div style="padding:12px; background:#d4edda; color:#155724; border-radius:5px; margin:15px 0; border-left: 4px solid #28a745;">
            ✓ <?= htmlspecialchars($info_message) ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap:2.5rem; align-items:flex-start; margin-top:2rem;">
        <!-- Avatar -->
        <div>
            <img src="../images/avatars/<?= htmlspecialchars($avatar) ?>"
                 alt="Profile Picture"
                 style="width:150px; height:150px; border-radius:50%; object-fit:cover; border:5px solid #f0f0f0;">
            <p style="text-align:center; margin-top:10px;">
                <a href="upload_avatar.php" style="color:#333; text-decoration:underline;">
                    Change Avatar
                </a>
            </p>
        </div>

        <!-- User info -->
        <div style="flex:1;">
            <table style="font-size:1.05rem; line-height:2.2;">
                <tr><td style="color:#666; width:130px;">Name</td>
                    <td><?= htmlspecialchars($_SESSION['user_name']) ?></td></tr>
                <tr><td style="color:#666;">Email</td>
                    <td><?= htmlspecialchars($_SESSION['email']) ?></td></tr>
                <tr><td style="color:#666;">Phone</td>
                    <td><?= htmlspecialchars($_SESSION['phone'] ?: 'Not provided') ?></td></tr>
                <tr><td style="color:#666;">Member ID</td>
                    <td>#<?= $_SESSION['user_id'] ?></td></tr>
            </table>

            <div style="margin-top:2.5rem;">
                <a href="edit_profile.php">Edit Profile</a>
                <span style="margin:0 1rem; color:#aaa;">|</span>
                <a href="change_password.php">Change Password</a>
                <span style="margin:0 1rem; color:#aaa;">|</span>
                <a href="../logout.php" style="color:#c33;">Logout</a>
            </div>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>