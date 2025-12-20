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

<style>
    .account-content {
        max-width: 1400px !important; /* 增加最大寬度 */
        width: 100% !important;
    }

    .profile-card {
        min-height: 400px;
        padding: 3rem 4rem;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        width: 100%; /* 佔滿整個容器 */
    }

    .profile-card h2 {
        margin-bottom: 2.5rem;
        font-size: 1.8rem;
        color: #333;
    }

    .profile-content-wrapper {
        display: grid;
        grid-template-columns: 220px 1fr; /* Avatar固定寬度，內容自適應 */
        gap: 4rem;
        align-items: start;
        margin-top: 2rem;
    }

    .profile-avatar {
        text-align: center;
    }

    .profile-avatar img {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f5f5f5;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .profile-avatar a {
        display: inline-block;
        margin-top: 1.5rem;
        color: #6366f1;
        text-decoration: none;
        font-size: 0.95rem;
        transition: color 0.3s;
    }

    .profile-avatar a:hover {
        color: #4f46e5;
        text-decoration: underline;
    }

    .profile-info-section {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .profile-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* 2列佈局，讓信息橫向排列 */
        gap: 2rem 3rem;
        margin-bottom: 2.5rem;
    }

    .profile-info .row {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        padding: 1.5rem;
        background: #fafafa;
        border-radius: 8px;
        border-left: 3px solid #6366f1;
        transition: all 0.3s;
    }

    .profile-info .row:hover {
        background: #f0f0ff;
        border-left-color: #4f46e5;
        transform: translateX(5px);
    }

    .profile-info .row span {
        color: #666;
        font-size: 0.85rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .profile-info .row strong {
        font-size: 1.2rem;
        color: #333;
        font-weight: 600;
    }

    .profile-actions {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid #f5f5f5;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .profile-actions a {
        padding: 0.9rem 2.2rem;
        background: #f8f9fa;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .profile-actions a:hover {
        background: #6366f1;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .profile-actions a.danger {
        background: #fff;
        color: #dc2626;
        border: 2px solid #fee;
    }

    .profile-actions a.danger:hover {
        background: #dc2626;
        color: white;
        border-color: #dc2626;
    }

    .info-message {
        padding: 1rem 1.5rem;
        background: #d4edda;
        color: #155724;
        border-radius: 8px;
        margin: 1.5rem 0 2rem 0;
        border-left: 4px solid #28a745;
        font-weight: 500;
    }

    /* 響應式設計 */
    @media (max-width: 1200px) {
        .profile-info {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .profile-content-wrapper {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        .account-content {
            max-width: 100% !important;
        }
    }
</style>

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
                <div class="info-message">
                    ✓ <?= htmlspecialchars($info_message) ?>
                </div>
            <?php endif; ?>

            <div class="profile-content-wrapper">
                <!-- Avatar -->
                <div class="profile-avatar">
                    <img src="../images/avatars/<?= htmlspecialchars($avatar) ?>" alt="Profile Avatar">
                    <a href="upload_avatar.php">Change Avatar</a>
                </div>

                <!-- Info -->
                <div class="profile-info-section">
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
</div>

<?php include '../_foot.php'; ?>