<?php
@session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

function get($key, $default = null) { return $_GET[$key] ?? $default; }
function post($key, $default = null) { return $_POST[$key] ?? $default; }

$cart_count = 0;
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
            <li><a href="/page/about.php" class="<?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : '' ?>">About us</a></li>
            <li><a href="/page/product.php" class="<?= basename($_SERVER['PHP_SELF']) === 'product.php' ? 'active' : '' ?>">Product</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="/page/profile.php">Profile</a></li>
                <li><a href="/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="/page/login.php">Login</a></li>
                <li><a href="/page/register.php">Register</a></li>
            <?php endif; ?>
            <li class="cart-li">
                <a href="/page/cart.php" class="cart-link">
                    Cart
                    <span id="cart-count"><?= $cart_count ?></span>
                </a>
            </li>
        </ul>
    </nav>
</header>

<main style="margin-top: 15px;">
    <h1><?= $_title ?? 'Untitled' ?></h1>