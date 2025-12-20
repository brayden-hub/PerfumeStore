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
        max-width: 100% !important;
        width: 100% !important;
        padding: 2rem;
    }

    .profile-header {
        text-align: center;
        margin-bottom: 3rem;
        animation: fadeInDown 0.6s ease;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .profile-header h2 {
        font-size: 2.2rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
    }

    .profile-header p {
        color: #666;
        font-size: 1rem;
    }

    .info-message {
        padding: 1.2rem 1.5rem;
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border-radius: 12px;
        margin-bottom: 2rem;
        border-left: 4px solid #28a745;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideIn 0.5s ease;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .profile-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        animation: fadeInUp 0.6s ease;
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .profile-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 8px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899, #f59e0b);
        background-size: 200% 100%;
        animation: gradientMove 3s linear infinite;
    }

    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .profile-content-wrapper {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 3rem;
        align-items: start;
        width: 100%;
    }

    .profile-avatar-section {
        text-align: center;
        background: white;
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }

    .profile-avatar-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .avatar-container {
        position: relative;
        display: inline-block;
        margin-bottom: 1.5rem;
    }

    .profile-avatar-section img {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        object-fit: cover;
        border: 6px solid #fff;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    }

    .avatar-container:hover img {
        transform: scale(1.05);
        box-shadow: 0 12px 32px rgba(0,0,0,0.2);
    }

    .avatar-badge {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        border: 4px solid white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }

    .change-avatar-btn {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .change-avatar-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    }

    .profile-info-section {
        background: white;
        padding: 2.5rem;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        width: 100%;
    }

    .profile-info-section h3 {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }

    .profile-info {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .info-item {
        padding: 1.5rem 2rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        border-left: 4px solid #6366f1;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: flex !important;
        flex-direction: row !important;
        align-items: center;
        gap: 2rem;
        width: 100%;
    }

    .info-item::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, transparent 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .info-item:hover::before {
        opacity: 1;
    }

    .info-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.15);
        border-left-color: #8b5cf6;
    }

    .info-label {
        display: flex !important;
        flex-direction: row !important;
        align-items: center;
        gap: 0.5rem;
        color: #666;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        min-width: 120px;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .info-value {
        font-size: 1.2rem;
        color: #1a1a1a;
        font-weight: 600;
        flex: 1;
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        overflow: visible;
    }

    .profile-actions {
        padding-top: 2rem;
        border-top: 2px solid #f0f0f0;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .action-btn {
        flex: 1;
        min-width: 180px;
        padding: 1rem 1.5rem;
        text-align: center;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .action-btn.primary {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        color: #1a1a1a !important;
        border: 2px solid #e0e0e0;
        font-weight: 600;
    }

    .action-btn.primary:hover {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
        border-color: transparent;
    }

    .action-btn.danger {
        background: white;
        color: #dc2626 !important;
        border: 2px solid #fecaca;
        font-weight: 600;
    }

    .action-btn.danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(220, 38, 38, 0.3);
        border-color: transparent;
    }

    /* 響應式設計 */
    @media (max-width: 1024px) {
        .profile-content-wrapper {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .profile-info {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .account-content {
            padding: 1rem;
        }

        .profile-card {
            padding: 2rem 1.5rem;
        }

        .profile-header h2 {
            font-size: 1.8rem;
        }

        .action-btn {
            min-width: 100%;
        }
    }

    /* 新增：美化滾動效果 */
    .profile-card {
        animation: fadeInUp 0.6s ease;
    }

    .info-item:nth-child(1) { animation-delay: 0.1s; }
    .info-item:nth-child(2) { animation-delay: 0.2s; }
    .info-item:nth-child(3) { animation-delay: 0.3s; }
    .info-item:nth-child(4) { animation-delay: 0.4s; }
</style>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content">
        <div class="profile-header">
            <h2>👋 Welcome back, <?= htmlspecialchars($username) ?>!</h2>
            <p>Manage your profile and account settings</p>
        </div>

        <?php if ($info_message): ?>
            <div class="info-message">
                <span style="font-size: 1.5rem;">✓</span>
                <span><?= htmlspecialchars($info_message) ?></span>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-content-wrapper">
                <!-- Avatar Section -->
                <div class="profile-avatar-section">
                    <div class="avatar-container">
                        <img src="../images/avatars/<?= htmlspecialchars($avatar) ?>" alt="Profile Avatar">
                        <div class="avatar-badge">✨</div>
                    </div>
                    <a href="upload_avatar.php" class="change-avatar-btn">
                        📸 Change Avatar
                    </a>
                </div>

                <!-- Info Section -->
                <div class="profile-info-section">
                    <h3>📋 Account Information</h3>
                    
                    <div class="profile-info">
                        <div class="info-item">
                            <div class="info-label">
                                <span>👤</span>
                                <span>Name</span>
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($_SESSION['user_name']) ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <span>📧</span>
                                <span>Email</span>
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($_SESSION['email']) ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <span>📱</span>
                                <span>Phone</span>
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($_SESSION['phone'] ?: 'Not provided') ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <span>🎫</span>
                                <span>Member ID</span>
                            </div>
                            <div class="info-value">
                                #<?= $_SESSION['user_id'] ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <a href="edit_profile.php" class="action-btn primary">
                            ✏️ Edit Profile
                        </a>
                        <a href="change_password.php" class="action-btn primary">
                            🔒 Change Password
                        </a>
                        <a href="../logout.php" class="action-btn danger">
                            🚪 Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>