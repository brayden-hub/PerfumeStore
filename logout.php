<?php

require '_base.php'; 

// If the user is logged in and has a remember_token → clear from the database
if (isset($_SESSION['user_id'])) {

    // Clear database token
    $stm = $_db->prepare("UPDATE user SET remember_token = NULL WHERE UserID = ?");
    $stm->execute([$_SESSION['user_id']]);
}

// 1. Clear session
$_SESSION = [];
session_destroy();

// 2. Delete cookie remember_token
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// 3. Return to homepage
temp('info', 'Logout successful!');
redirect('/');
