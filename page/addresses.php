<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// check if user is admin
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';

if (!$is_admin) {
    // get user addresses
    $stm = $_db->prepare("
        SELECT * FROM user_address 
        WHERE UserID = ? 
        ORDER BY IsDefault DESC, CreatedDate DESC
    ");
    $stm->execute([$user_id]);
    $addresses = $stm->fetchAll();
} else {
    $addresses = [];
}

// Flash message
$info_message = temp('info');

$_title = 'My Addresses - Nº9 Perfume';
include '../_head.php';
?>

<style>
    .addresses-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .addresses-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .addresses-header h2 {
        font-size: 2rem;
        font-weight: 600;
        color: #1a1a1a;
        margin: 0;
    }

    .btn-add-address {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        background: linear-gradient(135deg, #000 0%, #333 100%);
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }

    .btn-add-address::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        background-size: 200% 100%;
        animation: shine 3s infinite;
    }

    .btn-add-address:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        background: linear-gradient(135deg, #222 0%, #444 100%);
    }

    .btn-add-address svg {
        animation: pulse 2s ease-in-out infinite;
    }

    .success-message {
        margin-bottom: 25px;
        padding: 16px 20px;
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border-radius: 10px;
        border-left: 4px solid #28a745;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    @keyframes shine {
        0% {
            background-position: -200% center;
        }
        100% {
            background-position: 200% center;
        }
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #999;
    }

    .empty-state svg {
        width: 120px;
        height: 120px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 1.1rem;
        margin-bottom: 30px;
    }

    .addresses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    @media (max-width: 768px) {
        .addresses-grid {
            grid-template-columns: 1fr;
        }
    }

    .address-card {
        background: #fff;
        border: 2px solid #e8e8e8;
        border-radius: 12px;
        padding: 25px;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
    }

    .address-card:nth-child(1) { animation-delay: 0.1s; }
    .address-card:nth-child(2) { animation-delay: 0.2s; }
    .address-card:nth-child(3) { animation-delay: 0.3s; }
    .address-card:nth-child(4) { animation-delay: 0.4s; }
    .address-card:nth-child(5) { animation-delay: 0.5s; }
    .address-card:nth-child(6) { animation-delay: 0.6s; }

    .address-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        border-color: #007bff;
    }

    .address-card:hover .address-label {
        color: #007bff;
    }

    .address-card:hover .address-details {
        color: #1a1a1a;
        font-weight: 500;
    }

    .address-card.default {
        border-color: #28a745;
        background: linear-gradient(135deg, #ffffff 0%, #f0fff4 100%);
    }

    .address-card.default:hover {
        border-color: #28a745;
    }

    .address-card.default:hover .address-label {
        color: #28a745;
    }

    .address-card::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, #007bff, #0056b3, #00d4ff, #0056b3, #007bff);
        background-size: 300% 300%;
        border-radius: 12px;
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
        animation: gradientMove 3s ease infinite;
    }

    .address-card.default::before {
        background: linear-gradient(45deg, #28a745, #20c997, #4ade80, #20c997, #28a745);
        background-size: 300% 300%;
    }

    .address-card:hover::before {
        opacity: 1;
    }

    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .default-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: #fff;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        animation: pulse 2s ease-in-out infinite;
    }

    .address-label {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1a1a1a;
        margin: 0 0 15px 0;
        padding-right: 100px;
    }

    .address-recipient {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .recipient-name {
        font-weight: 600;
        color: #333;
        font-size: 1rem;
    }

    .recipient-phone {
        color: #666;
        font-size: 0.95rem;
    }

    .address-details {
        line-height: 1.8;
        color: #555;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .address-actions {
        display: flex;
        gap: 10px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .btn-action {
        flex: 1;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        text-align: center;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .btn-edit {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: #fff;
    }

    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: #fff;
    }

    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    .icon {
        display: inline-block;
        width: 18px;
        height: 18px;
        vertical-align: middle;
    }

    .admin-notice {
        text-align: center;
        padding: 80px 20px;
        animation: fadeInUp 0.6s ease;
    }

    .admin-notice-icon {
        font-size: 5rem;
        margin-bottom: 20px;
        animation: float 3s ease-in-out infinite;
    }

    .admin-notice h3 {
        font-size: 2rem;
        color: #666;
        margin-bottom: 15px;
    }

    .admin-notice p {
        color: #999;
        font-size: 1.1rem;
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
        <div class="addresses-container">
            
            <?php if ($is_admin): ?>
                <div class="admin-notice">
                    <div class="admin-notice-icon">🔒</div>
                    <h3>Not Available for Admin</h3>
                    <p>Address management is only available for regular users.</p>
                </div>
            <?php else: ?>
            
            <div class="addresses-header">
                <h2>My Addresses</h2>
                <a href="manage_address.php?action=add" class="btn-add-address">
                    <svg class="icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Add New Address
                </a>
            </div>

            <?php if ($info_message): ?>
                <div class="success-message">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?= htmlspecialchars($info_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($addresses)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p>You have no saved addresses yet.</p>
                    <a href="manage_address.php?action=add" class="btn-add-address">
                        <svg class="icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Add Your First Address
                    </a>
                </div>
            <?php else: ?>
                <div class="addresses-grid">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="address-card <?= $addr->IsDefault ? 'default' : '' ?>">
                            <?php if ($addr->IsDefault): ?>
                                <span class="default-badge">
                                    ★ DEFAULT
                                </span>
                            <?php endif; ?>

                            <h4 class="address-label">
                                <?= htmlspecialchars($addr->AddressLabel) ?>
                            </h4>

                            <div class="address-recipient">
                                <svg class="icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                <span class="recipient-name"><?= htmlspecialchars($addr->RecipientName) ?></span>
                                <span style="color:#ddd;">|</span>
                                <span class="recipient-phone"><?= htmlspecialchars($addr->PhoneNumber) ?></span>
                            </div>

                            <div class="address-details">
                                <svg class="icon" style="float:left; margin-right:8px; margin-top:2px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                <div style="margin-left:26px;">
                                    <?= htmlspecialchars($addr->AddressLine1) ?><br>
                                    <?php if ($addr->AddressLine2): ?>
                                        <?= htmlspecialchars($addr->AddressLine2) ?><br>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($addr->PostalCode) ?> <?= htmlspecialchars($addr->City) ?>,
                                    <?= htmlspecialchars($addr->State) ?><br>
                                    <?= htmlspecialchars($addr->Country) ?>
                                </div>
                            </div>

                            <div class="address-actions">
                                <a href="manage_address.php?action=edit&id=<?= $addr->AddressID ?>" 
                                   class="btn-action btn-edit">
                                    Edit
                                </a>

                                <a href="manage_address.php?action=delete&id=<?= $addr->AddressID ?>"
                                   onclick="return confirm('Are you sure you want to delete this address?')"
                                   class="btn-action btn-delete">
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>