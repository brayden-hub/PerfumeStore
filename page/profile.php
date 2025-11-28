<?php
require '../_base.php';

// =============== Check if logged in ===============
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user info from session
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Guest';
$email    = $_SESSION['email'] ?? '';
$avatar   = $_SESSION['avatar'] ?? 'default.jpg';   // fallback avatar

$_title = 'My Profile - N°9 Perfume';
include '../_head.php';
?>

<div style="padding:2rem; max-width: 800px; margin: 0 auto;">
    <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>

    <div style="display:flex; gap:2.5rem; align-items:flex-start; margin-top:2rem;">
        <!-- Avatar -->
        <div>
            <img src="../images/avatars/<?php echo htmlspecialchars($avatar); ?>"
                 alt="Profile Picture"
                 style="width:150px; height:150px; border-radius:50%; object-fit:cover; border:5px solid #f0f0f0;">
            <p style="text-align:center; margin-top:10px; color:#888; font-size:0.9rem;">
                Avatar upload<br><small>(coming in next step)</small>
            </p>
        </div>

        <!-- User info -->
        <div style="flex:1;">
            <table style="font-size:1.05rem; line-height:2.2;">
                <tr><td style="color:#666; width:130px;">Name</td>
                <td><?php echo htmlspecialchars($_SESSION['username']); ?></td></tr>
                <tr><td style="color:#666;">Email</td>
                <td><?php echo htmlspecialchars($_SESSION['email']); ?></td></tr>
                <tr><td style="color:#666;">Phone</td>
                <td><?php echo htmlspecialchars($_SESSION['phone'] ?: 'Not provided'); ?></td></tr>
                <tr><td style="color:#666;">Member ID</td>
                <td>#<?php echo $_SESSION['user_id']; ?></td></tr>
            </table>

            <div style="margin-top:2.5rem; font-size:1rem;">
                <a href="edit_profile.php" style="color:#333; text-decoration:underline;">✏️ Edit Profile</a>
                <span style="margin:0 1rem; color:#aaa;">|</span>
                <a href="change_password.php" style="color:#333; text-decoration:underline;">🔑 Change Password</a>
                <span style="margin:0 1rem; color:#aaa;">|</span>
                <a href="logout.php" style="color:#c33;">🚪 Logout</a>
            </div>
        </div>
    </div>

    <hr style="margin:3.5rem 0; border:none; border-top:1px dashed #ddd;">

    <p style="color:#999; font-size:0.95rem;">
        More features (avatar upload, preferences, etc.) are under development.<br>
        We’ll add them one by one — stay tuned! 
    </p>
</div>

<?php include '../_foot.php'; ?>