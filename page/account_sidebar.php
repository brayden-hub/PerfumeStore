<?php $page = basename($_SERVER['PHP_SELF']); ?>

<style>
.account-wrapper {
    display: flex;
    gap: 30px;
    margin-top: 2rem;
}

.account-sidebar {
    width: 220px;
    border-right: 1px solid #eee;
    padding-right: 20px;
}

.account-sidebar h4 {
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: #333;
}

.account-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-sidebar li {
    margin-bottom: 10px;
}

.account-sidebar a {
    display: block;
    padding: 10px 14px;
    border-radius: 6px;
    color: #333;
    text-decoration: none;
}

.account-sidebar a:hover {
    background: #f5f5f5;
    color: #ee4d2d;
}

.account-sidebar a.active {
    background: #ee4d2d;
    color: #fff;
    font-weight: 500;
}

.account-content {
    flex: 1;
}
</style>

<div class="account-sidebar">
    <h4>My Account</h4>
    <ul>
        <li><a href="profile.php" class="<?= $page=='profile.php'?'active':'' ?>">Profile</a></li>
        <li><a href="addresses.php" class="<?= $page=='addresses.php'?'active':'' ?>">Addresses</a></li>
        <li><a href="favorites.php" class="<?= $page=='favorites.php'?'active':'' ?>">My Favorites</a></li>
        <li><a href="points.php" class="<?= $page=='points.php'?'active':'' ?>">My Points</a></li>
        <li><a href="change_password.php" class="<?= $page=='change_password.php'?'active':'' ?>">Change Password</a></li>
    </ul>
</div>
