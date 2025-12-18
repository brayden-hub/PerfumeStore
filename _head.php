<?php
// Ensure session is started if not already (usually done in _base.php, but good practice here if standalone)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get cart count for logged-in users
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $_db->prepare("SELECT SUM(Quantity) FROM cart WHERE UserID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn() ?: 0;
}

// Get current User Role safely
$user_role = $_SESSION['user_role'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $_title ?? 'N°9 Perfume' ?></title>
    <link rel="shortcut icon" href="/public/images/logo.jpg">  
    <link rel="stylesheet" href="/public/css/perfume.css">  
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/public/js/app.js"></script> 
</head>
<body>

<header class="logo">
    <h1>N°9 Perfume</h1>
    <a href="/">
        <img src="/public/images/logo.jpg" width="100" height="70" alt="Logo"/>
    </a>
    <nav>
        <ul>
            <li><a href="/" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Home</a></li>

            <?php if ($user_role === 'Admin'): ?>
                <li><a href="/page/productList.php" class="<?= basename($_SERVER['PHP_SELF']) === 'productList.php' ? 'active' : '' ?>">Product</a></li>
                <li><a href="/page/user.php" class="<?= basename($_SERVER['PHP_SELF']) === 'user.php' ? 'active' : '' ?>">User</a></li>
                <li><a href="/page/order.php" class="<?= basename($_SERVER['PHP_SELF']) === 'order.php' ? 'active' : '' ?>">Order</a></li>
                <li><a href="/page/report.php" class="<?= basename($_SERVER['PHP_SELF']) === 'report.php' ? 'active' : '' ?>">Report</a></li>
                
                <?php if (isset($_SESSION['user_id'])): 
                    // Admin 登录后也需要头像菜单
                    $avatar_path = $_SESSION['Profile_Photo'] ?? 'default1.jpg';
                ?>
                    <li class="profile-dropdown-li">
                        <img src="/images/avatars/<?= htmlspecialchars($avatar_path) ?>" 
                             alt="Profile Avatar" 
                             class="nav-avatar" 
                             id="profile-menu-toggle">
                        
                        <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                            <p class="dropdown-username"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></p>
                            <hr>
                            <a href="/page/profile.php" class="dropdown-item">
                                Manage Your Profile
                            </a>
                            <a href="/logout.php" class="dropdown-item logout-link-btn">
                                Logout
                            </a>
                        </div>
                    </li>
                <?php endif; ?>

            <?php else: // Member or Guest ?>
                <li><a href="/page/product.php" class="<?= basename($_SERVER['PHP_SELF']) === 'product.php' ? 'active' : '' ?>">Product</a></li>

                <?php if (isset($_SESSION['user_id'])): // Logged-in Member Links ?>
                    <li><a href="/page/order_history.php" class="<?= basename($_SERVER['PHP_SELF']) === 'order_history.php' ? 'active' : '' ?>">My Orders</a></li>
                <?php else: // Guest Links ?>
                    <li><a href="/page/login.php" class="<?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>">Login</a></li>
                    <li><a href="/page/register.php" class="<?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : '' ?>">Register</a></li>
                <?php endif; ?>

                <li class="cart-li">
                    <a href="/page/cart.php" class="cart-link <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>">
                        Cart
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <span id="cart-count"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            
                <?php if (isset($_SESSION['user_id'])): 
                    $avatar_path = $_SESSION['Profile_Photo'] ?? 'default1.jpg';
                ?>
                    <li class="profile-dropdown-li">
                        <img src="/images/avatars/<?= htmlspecialchars($avatar_path) ?>" 
                             alt="Profile Avatar" 
                             class="nav-avatar" 
                             id="profile-menu-toggle">
                        
                        <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                            <p class="dropdown-username"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></p>
                            <hr>
                            <a href="/page/profile.php" class="dropdown-item">
                                Manage Your Profile
                            </a>
                            <a href="/logout.php" class="dropdown-item logout-link-btn">
                                Logout
                            </a>
                        </div>
                    </li>
                <?php endif; ?>

            <?php endif; ?>
        </ul>
    </nav>
</header>

<main style="margin-top: 0px;">