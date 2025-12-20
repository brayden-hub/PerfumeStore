<?php $page = basename($_SERVER['PHP_SELF']); ?>

<style>
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
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

@keyframes shimmer {
    0% {
        background-position: -200% center;
    }
    100% {
        background-position: 200% center;
    }
}

.account-wrapper {
    display: flex;
    gap: 40px;
    margin-top: 2rem;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
}

.account-sidebar {
    width: 280px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 30px 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    position: sticky;
    top: 20px;
    height: fit-content;
    animation: slideInLeft 0.6s ease;
    border: 2px solid #f0f0f0;
}

.sidebar-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e8e8e8;
}

.sidebar-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    display: block;
    animation: pulse 2s ease-in-out infinite;
}

.account-sidebar h4 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
    color: #1a1a1a;
    background: linear-gradient(135deg, #000 0%, #333 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.account-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-sidebar li {
    margin-bottom: 8px;
}

.account-sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    color: #555;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    font-weight: 500;
}

.account-sidebar a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: linear-gradient(135deg, #000 0%, #333 100%);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.account-sidebar a:hover::before {
    transform: scaleY(1);
}

.account-sidebar a:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #000;
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.account-sidebar a.active {
    background: linear-gradient(135deg, #000 0%, #333 100%);
    color: #fff;
    font-weight: 600;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    position: relative;
}

.account-sidebar a.active::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,0.2),
        transparent
    );
    background-size: 200% 100%;
    animation: shimmer 3s infinite;
}

.account-sidebar a.active .menu-icon {
    animation: pulse 2s ease-in-out infinite;
}

.menu-icon {
    font-size: 1.3rem;
    width: 24px;
    text-align: center;
}

.menu-text {
    flex: 1;
}

.badge {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.sidebar-footer {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #e8e8e8;
    text-align: center;
}

.sidebar-tip {
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
    border: 2px solid #D4AF37;
    border-radius: 12px;
    padding: 15px;
    font-size: 0.85rem;
    color: #666;
    line-height: 1.6;
}

.sidebar-tip-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
    display: block;
}

.account-content {
    flex: 1;
    min-width: 0;
}

@media (max-width: 968px) {
    .account-wrapper {
        flex-direction: column;
        gap: 20px;
    }

    .account-sidebar {
        width: 100%;
        position: static;
    }
}
</style>

<div class="account-sidebar">
    <div class="sidebar-header">
        <span class="sidebar-icon">👤</span>
        <h4>My Account</h4>
    </div>
    
    <ul>
        <li>
            <a href="profile.php" class="<?= $page=='profile.php'?'active':'' ?>">
                <span class="menu-icon">📝</span>
                <span class="menu-text">Profile</span>
            </a>
        </li>
        <li>
            <a href="addresses.php" class="<?= $page=='addresses.php'?'active':'' ?>">
                <span class="menu-icon">📍</span>
                <span class="menu-text">Addresses</span>
            </a>
        </li>
        <li>
            <a href="favorites.php" class="<?= $page=='favorites.php'?'active':'' ?>">
                <span class="menu-icon">❤️</span>
                <span class="menu-text">My Favorites</span>
            </a>
        </li>
        <li>
            <a href="my_vouncher.php" class="<?= $page=='my_vouncher.php'?'active':'' ?>">
                <span class="menu-icon">🎟️</span>
                <span class="menu-text">My Voucher</span>
            </a>
        </li>
        <li>
            <a href="change_password.php" class="<?= $page=='change_password.php'?'active':'' ?>">
                <span class="menu-icon">🔒</span>
                <span class="menu-text">Change Password</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="sidebar-tip">
            <span class="sidebar-tip-icon">💡</span>
            <strong>Tip:</strong> Keep your profile updated to receive personalized offers!
        </div>
    </div>
</div>