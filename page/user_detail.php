<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$user_id = req('userID');
$info = temp('info');

if (!$user_id) {
    redirect('user.php');
}

// Fetch user data
$stm = $_db->prepare("SELECT userID, name, email, phone_number, role, Profile_Photo FROM user WHERE userID = ?");
$stm->execute([$user_id]);
$user = $stm->fetch();

if (!$user) {
    temp('info', 'Member not found.');
    redirect('user.php');
}

// Data is now read-only
$name  = $user->name;
$phone = $user->phone_number;

$_title = "Member Detail - {$user->name}";
include '../_head.php';
?>

<div style="padding:2rem; max-width: 600px; margin: 0 auto;">
    <div class="admin-header">
        <h2>Member Details: <?= htmlspecialchars($user->name) ?> (#<?= $user->userID ?>)</h2>
        <a href="user.php" style="color:#666; text-decoration:underline;">← Back to Member List</a>
    </div>

    <?php if ($info): ?>
        <div style="padding:12px; background:#d4edda; color:#155724; border-radius:5px; margin:15px 0; border-left: 4px solid #28a745;">
            ✓ <?= htmlspecialchars($info) ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap:2.5rem; align-items:flex-start; margin-top:2rem;">
        <div>
            <img src="../images/avatars/<?= htmlspecialchars($user->Profile_Photo ?: 'default1.jpg') ?>"
                 alt="Profile Picture"
                 style="width:150px; height:150px; border-radius:50%; object-fit:cover; border:5px solid #f0f0f0;">
        </div>

        <div style="flex:1;">
            <table style="font-size:1.05rem; line-height:2.2; width:100%;">
                <tr>
                    <td style="color:#666; width:130px;">Name</td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($name) ?></td>
                </tr>
                <tr>
                    <td style="color:#666;">Email</td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($user->email) ?></td>
                </tr>
                <tr>
                    <td style="color:#666;">Phone</td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($phone ?: 'Not provided') ?></td>
                </tr>
                <tr>
                    <td style="color:#666;">Member ID</td>
                    <td style="font-weight:bold;">#<?= $user->userID ?></td>
                </tr>
                <tr>
                    <td style="color:#666;">Role</td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($user->role) ?></td>
                </tr>
            </table>

            <div style="margin-top:2rem;">
                <button type="button" 
                        data-post="user_delete.php?userID=<?= $user->userID ?>" 
                        data-confirm="Are you sure you want to permanently delete member <?= htmlspecialchars($user->name) ?>? This action is irreversible."
                        class="action-btn btn-delete">
                    Delete Member
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>