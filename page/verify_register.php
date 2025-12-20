<?php
require '../_base.php';

$id = req('id');


$stm = $_db->prepare('SELECT * FROM token WHERE token_id = ? AND expire > NOW()');
$stm->execute([$id]);
$t = $stm->fetch();

if (!$t) {
    temp('info', 'Link expired or invalid. Please login manually.');
    redirect('login.php');
}


$stm = $_db->prepare('SELECT * FROM user WHERE userID = ?');
$stm->execute([$t->userID]);
$user = $stm->fetch();



if ($user) {
    
    $stm = $_db->prepare("UPDATE user SET status = 'Activated' WHERE userID = ?");
    $stm->execute([$user->userID]);

    
    $_SESSION['user_id']       = $user->userID;
    $_SESSION['user_name']     = $user->name;
    $_SESSION['email']         = $user->email;
    $_SESSION['phone']         = $user->phone_number;
    $_SESSION['user_role']     = $user->role;
    $_SESSION['Profile_Photo'] = $user->Profile_Photo;

    
    $stm = $_db->prepare('DELETE FROM token WHERE token_id = ?');
    $stm->execute([$id]);

    temp('info', "Email verified! Your account is now active.");
    redirect('profile.php'); 
}