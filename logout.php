<?php

require '_base.php'; // 如果你的 _base.php 在上一层，请改为 ../_base.php

// 如果用户已登录并且有 remember_token → 从数据库清空
if (isset($_SESSION['user_id'])) {

    // 清空数据库 token
    $stm = $_db->prepare("UPDATE user SET remember_token = NULL WHERE UserID = ?");
    $stm->execute([$_SESSION['user_id']]);
}

// 1. 清除 session
$_SESSION = [];
session_destroy();

// 2. 删除 cookie remember_token
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// 3. 返回首页
temp('info', 'Logout successful!');
redirect('/');
