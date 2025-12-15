<?php
require '../_base.php';
if (!isset($_SESSION['user_id'])) redirect('login.php');

$user_id = $_SESSION['user_id'];

$stm = $_db->prepare("SELECT Points FROM user WHERE userID = ?");
$stm->execute([$user_id]);
$points = $stm->fetchColumn() ?? 0;

$_title = 'My Points';
include '../_head.php';
?>

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content">
        <h2>My Points</h2>

        <div style="margin-top:30px; padding:30px; border:1px solid #eee; border-radius:10px;">
            <h1 style="font-size:3rem; margin:0;"><?= $points ?> pts</h1>
            <p style="color:#666;">Earn 1 point for every RM1 spent.</p>
        </div>

        <div style="margin-top:30px;">
            <h4>How to earn points?</h4>
            <ul>
                <li>RM1 spent = 1 point</li>
                <li>Redeem vouchers using points</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>
