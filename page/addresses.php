<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 取用户地址
$stm = $_db->prepare("
    SELECT * FROM user_address 
    WHERE UserID = ? 
    ORDER BY IsDefault DESC, CreatedDate DESC
");
$stm->execute([$user_id]);
$addresses = $stm->fetchAll();

// Flash message
$info_message = temp('info');

$_title = 'My Addresses - Nº9 Perfume';
include '../_head.php';
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>My Addresses</h2>
            <a href="manage_address.php?action=add"
               style="padding:10px 20px; background:#000; color:#fff; border-radius:5px; text-decoration:none;">
                + Add New Address
            </a>
        </div>

        <?php if ($info_message): ?>
            <div style="margin:15px 0; padding:12px; background:#d4edda; color:#155724; border-radius:5px;">
                ✓ <?= htmlspecialchars($info_message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($addresses)): ?>
            <p style="margin-top:3rem; text-align:center; color:#999;">
                You have no saved addresses yet.
            </p>
        <?php else: ?>
            <div style="display:grid; gap:1.5rem; margin-top:2rem;">
                <?php foreach ($addresses as $addr): ?>
                    <div style="border:2px solid <?= $addr->IsDefault ? '#28a745' : '#ddd' ?>;
                                padding:1.5rem; border-radius:8px; background:#fafafa; position:relative;">

                        <?php if ($addr->IsDefault): ?>
                            <span style="position:absolute; top:10px; right:10px;
                                         background:#28a745; color:#fff; padding:4px 12px;
                                         border-radius:15px; font-size:0.8rem;">
                                DEFAULT
                            </span>
                        <?php endif; ?>

                        <h4 style="margin:0 0 6px 0;">
                            <?= htmlspecialchars($addr->AddressLabel) ?>
                        </h4>

                        <p style="margin:4px 0;">
                            <strong><?= htmlspecialchars($addr->RecipientName) ?></strong>
                            | <?= htmlspecialchars($addr->PhoneNumber) ?>
                        </p>

                        <p style="line-height:1.6; color:#555;">
                            <?= htmlspecialchars($addr->AddressLine1) ?><br>
                            <?php if ($addr->AddressLine2): ?>
                                <?= htmlspecialchars($addr->AddressLine2) ?><br>
                            <?php endif; ?>
                            <?= htmlspecialchars($addr->PostalCode) ?> <?= htmlspecialchars($addr->City) ?>,
                            <?= htmlspecialchars($addr->State) ?><br>
                            <?= htmlspecialchars($addr->Country) ?>
                        </p>

                        <div style="margin-top:1rem; display:flex; gap:10px;">
                            <a href="manage_address.php?action=edit&id=<?= $addr->AddressID ?>"
                               style="padding:6px 14px; background:#007bff; color:#fff;
                                      border-radius:5px; text-decoration:none;">
                                Edit
                            </a>

                            <a href="manage_address.php?action=delete&id=<?= $addr->AddressID ?>"
                               onclick="return confirm('Delete this address?')"
                               style="padding:6px 14px; background:#dc3545; color:#fff;
                                      border-radius:5px; text-decoration:none;">
                                Delete
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../_foot.php'; ?>
