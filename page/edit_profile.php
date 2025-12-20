<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 獲取當前用戶資料
$stm = $_db->prepare("SELECT * FROM user WHERE userID = ?");
$stm->execute([$user_id]);
$user = $stm->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $errors = [];
    
    // 驗證名字
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters";
    } elseif (strlen($name) > 50) {
        $errors[] = "Name must not exceed 50 characters";
    }
    
    // 驗證電話（可選）
    if (!empty($phone)) {
        // 移除空格和破折號
        $phone_clean = preg_replace('/[\s\-]/', '', $phone);
        
        if (!preg_match('/^[0-9]{10,15}$/', $phone_clean)) {
            $errors[] = "Phone number must be 10-15 digits";
        }
        $phone = $phone_clean;
    }
    
    if (empty($errors)) {
        // 更新資料庫
        $update = $_db->prepare("UPDATE user SET name = ?, phone_number = ? WHERE userID = ?");
        $update->execute([$name, $phone, $user_id]);
        
        // 更新 Session
        $_SESSION['user_name'] = $name;
        $_SESSION['phone'] = $phone;
        
        temp('info', 'Profile updated successfully! 🎉');
        header('Location: profile.php');
        exit();
    }
}

$_title = 'Edit Profile - Nº9 Perfume';
include '../_head.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .edit-profile-wrapper {
        max-width: 900px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #6366f1;
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 2rem;
        transition: all 0.3s;
    }

    .back-link:hover {
        gap: 0.75rem;
        color: #4f46e5;
    }

    .edit-profile-card {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 3rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 15s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .profile-avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid rgba(255,255,255,0.3);
        object-fit: cover;
        margin: 0 auto 1.5rem;
        display: block;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        position: relative;
        z-index: 1;
    }

    .card-header h1 {
        color: white;
        font-size: 2rem;
        margin: 0 0 0.5rem 0;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .card-header p {
        color: rgba(255,255,255,0.9);
        margin: 0;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }

    .card-body {
        padding: 3rem;
    }

    .info-banner {
        background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
        border-left: 4px solid #3b82f6;
        padding: 1.25rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 2.5rem;
        display: flex;
        align-items: start;
        gap: 1rem;
    }

    .info-banner-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .info-banner-text {
        color: #1e40af;
        line-height: 1.6;
    }

    .form-section {
        margin-bottom: 3rem;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-title::before {
        content: '✏️';
        font-size: 1.3rem;
    }

    .form-group {
        margin-bottom: 2rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .form-group label .required {
        color: #ef4444;
        margin-left: 0.25rem;
    }

    .form-group label .optional {
        color: #9ca3af;
        font-size: 0.85rem;
        font-weight: 400;
        margin-left: 0.5rem;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.2rem;
        color: #9ca3af;
        pointer-events: none;
    }

    .form-group input {
        width: 100%;
        padding: 1.1rem 1.25rem 1.1rem 3.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s;
        background: #fafafa;
    }

    .form-group input:focus {
        outline: none;
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .form-group input:hover:not(:focus) {
        border-color: #d1d5db;
        background: #fff;
    }

    .input-help {
        margin-top: 0.5rem;
        font-size: 0.85rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .input-help::before {
        content: 'ℹ️';
        font-size: 1rem;
    }

    .readonly-field {
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        color: #6b7280;
        padding: 1.1rem 1.25rem 1.1rem 3.5rem;
        border-radius: 12px;
        font-size: 1rem;
        display: flex;
        align-items: center;
    }

    .readonly-field::before {
        content: '🔒';
        position: absolute;
        left: 1.25rem;
        font-size: 1.2rem;
    }

    .error-messages {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border-left: 4px solid #ef4444;
        padding: 1.25rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
    }

    .error-messages ul {
        margin: 0;
        padding-left: 1.5rem;
        color: #991b1b;
    }

    .error-messages ul li {
        margin: 0.5rem 0;
        font-weight: 500;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 3rem;
        padding-top: 2.5rem;
        border-top: 2px solid #f3f4f6;
    }

    .btn {
        padding: 1.1rem 2.5rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        flex: 1;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .btn-secondary {
        background: #f3f4f6;
        color: #6b7280;
    }

    .btn-secondary:hover {
        background: #e5e7eb;
        color: #374151;
    }

    @media (max-width: 640px) {
        .form-actions {
            flex-direction: column;
        }
        
        .card-body {
            padding: 2rem 1.5rem;
        }
    }
</style>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
        
        // 表單驗證動畫
        $('form').on('submit', function() {
            $('.btn-primary').html('⏳ Updating...').prop('disabled', true);
        });
        
        // 輸入框動畫
        $('input').on('focus', function() {
            $(this).parent().find('.input-icon').css('color', '#6366f1');
        }).on('blur', function() {
            $(this).parent().find('.input-icon').css('color', '#9ca3af');
        });
    });
</script>

<div class="edit-profile-wrapper">
    <a href="profile.php" class="back-link">
        ← Back to Profile
    </a>
    
    <div class="edit-profile-card">
        <div class="card-header">
            <img src="../images/avatars/<?= htmlspecialchars($user->Profile_Photo ?: 'default1.jpg') ?>" 
                 alt="Profile" 
                 class="profile-avatar-large">
            <h1>Edit Your Profile</h1>
            <p>Update your personal information</p>
        </div>

        <div class="card-body">
            
            <div class="info-banner">
                <span class="info-banner-icon">📧</span>
                <div class="info-banner-text">
                    <strong>Note:</strong> Your email address cannot be changed here. 
                    If you need to update it, please contact our support team.
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                
                <div class="form-section">
                    <div class="section-title">Editable Information</div>
                    
                    <div class="form-group">
                        <label for="name">
                            Full Name
                            <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?= htmlspecialchars($user->name ?? '') ?>" 
                                required
                                placeholder="Enter your full name"
                                maxlength="50"
                            >
                        </div>
                        <div class="input-help">
                            This is how your name will appear on your profile
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            Phone Number
                            <span class="optional">(optional)</span>
                        </label>
                        <div class="input-wrapper">
                            <span class="input-icon">📱</span>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?= htmlspecialchars($user->phone_number ?? '') ?>"
                                placeholder="0123456789"
                                pattern="[0-9\s\-]{10,15}"
                            >
                        </div>
                        <div class="input-help">
                            We'll use this for order updates and important notifications
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Account Information</div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <div class="readonly-field">
                                <?= htmlspecialchars($user->email) ?>
                            </div>
                        </div>
                        <div class="input-help">
                            Email address cannot be changed for security reasons
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Save Changes
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>