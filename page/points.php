<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stm = $_db->prepare("SELECT Points FROM user WHERE userID = ?");
$stm->execute([$_SESSION['user_id']]);
$points = $stm->fetchColumn() ?? 0;

$_title = 'My Points - Nº9 Perfume';
include '../_head.php';
?>

<div style="max-width:600px; margin:3rem auto; padding:2rem;">
    <h2>My Points</h2>

    <div style="margin-top:2rem; padding:2rem; border-radius:10px;
                background:linear-gradient(135deg,#000,#333); color:#fff;">
        <p style="font-size:1.2rem;">Available Points</p>
        <h1 style="font-size:3rem; margin:0;">
            <?= (int)$points ?>
        </h1>
    </div>

    <p style="margin-top:2rem; color:#666;">
        Earn points for every purchase and redeem vouchers!
    </p>
</div>

<?php include '../_foot.php'; ?>
