<?php
session_start();

$_title = 'Login';
include '../_head.php';
?>

<div class="container" style="max-width:400px; margin:100px auto;">
    <form method="post">
        <input type="email" name="email" placeholder="Email" required style="width:100%; padding:10px; margin:10px 0;">
        <input type="password" name="password" placeholder="Password" required style="width:100%; padding:10px; margin:10px 0;">
        <button type="submit" style="width:100%; padding:10px; background:#000; color:#fff; border:none;">Login</button>
    </form>
</div>

<?php include '../_foot.php'; ?>