<?php
require '../_base.php';

$id = req('id');

// 1. 检查 Token 是否存在且未过期
$stm = $_db->prepare('SELECT * FROM token WHERE token_id = ? AND expire > NOW()');
$stm->execute([$id]);
$t = $stm->fetch();

if (!$t) {
    temp('info', 'Link expired or invalid. Please login manually.');
    redirect('login.php');
}

// 2. 获取用户信息
$stm = $_db->prepare('SELECT * FROM user WHERE userID = ?');
$stm->execute([$t->userID]);
$user = $stm->fetch();

// verify_register.php

if ($user) {
    // 核心修改：将用户状态从 'Pending' 改为 'Activated'
    $stm = $_db->prepare("UPDATE user SET status = 'Activated' WHERE userID = ?");
    $stm->execute([$user->userID]);

    // 设置 Session 并自动登录
    $_SESSION['user_id']       = $user->userID;
    $_SESSION['user_name']     = $user->name;
    $_SESSION['email']         = $user->email;
    $_SESSION['phone']         = $user->phone_number;
    $_SESSION['user_role']     = $user->role;
    $_SESSION['Profile_Photo'] = $user->Profile_Photo;

    // 销毁 Token 并跳转
    $stm = $_db->prepare('DELETE FROM token WHERE token_id = ?');
    $stm->execute([$id]);

    temp('info', "Email verified! Your account is now active.");
    redirect('profile.php'); 
}