<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'Guest';
$email    = $_SESSION['email'] ?? '';

// 强制从数据库抓最新头像
$stm = $_db->prepare("SELECT Profile_Photo FROM user WHERE userID = ?");
$stm->execute([$user_id]);
$avatar = $stm->fetchColumn() ?: 'default1.jpg';
$_SESSION['Profile_Photo'] = $avatar;

// 抓用户的所有地址
$stm = $_db->prepare("SELECT * FROM user_address WHERE UserID = ? ORDER BY IsDefault DESC, CreatedDate DESC");
$stm->execute([$user_id]);
$addresses = $stm->fetchAll();

// Get temp message
$info_message = temp('info');

$_title = 'My Profile - Nº9 Perfume';
include '../_head.php';
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<div style="padding:2rem; max-width: 1000px; margin: 0 auto;">
    <h2>Welcome back, <?= htmlspecialchars($username) ?>!</h2>

    <?php if ($info_message): ?>
        <div style="padding:12px; background:#d4edda; color:#155724; border-radius:5px; margin:15px 0; border-left: 4px solid #28a745;">
            ✓ <?= htmlspecialchars($info_message) ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap:2.5rem; align-items:flex-start; margin-top:2rem;">
        <!-- Avatar -->
        <div>
            <img src="../images/avatars/<?= htmlspecialchars($avatar) ?>"
                 alt="Profile Picture"
                 style="width:150px; height:150px; border-radius:50%; object-fit:cover; border:5px solid #f0f0f0;">
            <p style="text-align:center; margin-top:10px;">
                <a href="upload_avatar.php" style="color:#333; text-decoration:underline;">
                    Change Avatar
                </a>
            </p>
        </div>

        <!-- User info -->
        <div style="flex:1;">
            <table style="font-size:1.05rem; line-height:2.2;">
                <tr><td style="color:#666; width:130px;">Name</td>
                    <td><?= htmlspecialchars($_SESSION['user_name']) ?></td></tr>
                <tr><td style="color:#666;">Email</td>
                    <td><?= htmlspecialchars($_SESSION['email']) ?></td></tr>
                <tr><td style="color:#666;">Phone</td>
                    <td><?= htmlspecialchars($_SESSION['phone'] ?: 'Not provided') ?></td></tr>
                <tr><td style="color:#666;">Member ID</td>
                    <td>#<?= $_SESSION['user_id'] ?></td></tr>
            </table>

            <div style="margin-top:2.5rem;">
                <a href="edit_profile.php">Edit Profile</a>
                <span style="margin:0 1rem; color:#aaa;">|</span>
                <a href="change_password.php">Change Password</a>
                <span style="margin:0 1rem; color:#aaa;">|</span>
                <a href="../logout.php" style="color:#c33;">Logout</a>
            </div>
        </div>
    </div>

    <!-- ============= 地址管理区域 ============= -->
    <div style="margin-top:4rem; padding-top:2rem; border-top:2px solid #eee;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0;">My Addresses</h3>
            <a href="manage_address.php?action=add" 
               style="padding:10px 20px; background:#000; color:#fff; text-decoration:none; border-radius:5px;">
                + Add New Address
            </a>
        </div>

        <?php if (empty($addresses)): ?>
            <p style="color:#999; text-align:center; padding:3rem 0;">
                No addresses yet. Click "Add New Address" to create one.
            </p>
        <?php else: ?>
            <div style="display:grid; gap:1.5rem;">
                <?php foreach ($addresses as $addr): ?>
                    <div style="border:2px solid <?= $addr->IsDefault ? '#28a745' : '#ddd' ?>; 
                                padding:1.5rem; border-radius:8px; position:relative; background:#fafafa;">
                        
                        <?php if ($addr->IsDefault): ?>
                            <span style="position:absolute; top:10px; right:10px; 
                                         background:#28a745; color:#fff; padding:4px 12px; 
                                         border-radius:15px; font-size:0.85rem; font-weight:bold;">
                                DEFAULT
                            </span>
                        <?php endif; ?>

                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <h4 style="margin:0 0 0.5rem 0; color:#333;">
                                    <?= htmlspecialchars($addr->AddressLabel) ?>
                                </h4>
                                <p style="margin:0.3rem 0; color:#666;">
                                    <strong><?= htmlspecialchars($addr->RecipientName) ?></strong> | 
                                    <?= htmlspecialchars($addr->PhoneNumber) ?>
                                </p>
                                <p style="margin:0.3rem 0; color:#555; line-height:1.6;">
                                    <?= htmlspecialchars($addr->AddressLine1) ?><br>
                                    <?php if ($addr->AddressLine2): ?>
                                        <?= htmlspecialchars($addr->AddressLine2) ?><br>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($addr->PostalCode) ?> <?= htmlspecialchars($addr->City) ?>, 
                                    <?= htmlspecialchars($addr->State) ?><br>
                                    <?= htmlspecialchars($addr->Country) ?>
                                </p>
                            </div>

                            <div style="display:flex; gap:10px; margin-left:20px;">
                                <a href="manage_address.php?action=edit&id=<?= $addr->AddressID ?>" 
                                   style="padding:8px 16px; background:#007bff; color:#fff; 
                                          text-decoration:none; border-radius:5px; font-size:0.9rem;">
                                    Edit
                                </a>
                                <a href="manage_address.php?action=delete&id=<?= $addr->AddressID ?>" 
                                   onclick="return confirm('Delete this address?')"
                                   style="padding:8px 16px; background:#dc3545; color:#fff; 
                                          text-decoration:none; border-radius:5px; font-size:0.9rem;">
                                    Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../_foot.php'; ?>