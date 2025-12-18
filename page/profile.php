<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'Guest';

// 强制抓最新头像
$stm = $_db->prepare("SELECT Profile_Photo FROM user WHERE userID = ?");
$stm->execute([$user_id]);
$avatar = $stm->fetchColumn() ?: 'default1.jpg';
$_SESSION['Profile_Photo'] = $avatar;

// Flash message
$info_message = temp('info');

$_title = 'My Profile - Nº9 Perfume';
include '../_head.php';
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content">
        <div class="profile-card">

            <h2>Welcome back, <?= htmlspecialchars($username) ?>!</h2>

        <?php if ($info_message): ?>
            <div style="padding:12px; background:#d4edda; color:#155724; border-radius:5px; margin:15px 0;">
                ✓ <?= htmlspecialchars($info_message) ?>
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:2.5rem; align-items:flex-start; margin-top:2rem;">
            <!-- Avatar -->
            <div class="profile-avatar">
                <img src="../images/avatars/<?= htmlspecialchars($avatar) ?>">
                <a href="upload_avatar.php">Change Avatar</a>
            </div>

            <!-- Info -->
            <div style="flex:1;">
                <div class="profile-info">
                    <div class="row">
                        <span>Name</span>
                        <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                    </div>

            <div class="row">
                <span>Email</span>
                <strong><?= htmlspecialchars($_SESSION['email']) ?></strong>
            </div>

            <div class="row">
                <span>Phone</span>
                <strong><?= htmlspecialchars($_SESSION['phone'] ?: '-') ?></strong>
            </div>

            <div class="row">
                <span>Member ID</span>
                <strong>#<?= $_SESSION['user_id'] ?></strong>
            </div>
        </div>

                <div class="profile-actions">
                    <a href="edit_profile.php">Edit Profile</a>
                    <a href="change_password.php">Change Password</a>
                    <a href="../logout.php" class="danger">Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>
