<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$address_id = $_GET['id'] ?? null;

$errors = [];
$address = null;

// ==================== DELETE ====================
if ($action === 'delete' && $address_id) {
    $stm = $_db->prepare("DELETE FROM user_address WHERE AddressID = ? AND UserID = ?");
    $stm->execute([$address_id, $user_id]);
    temp('info', 'Address deleted successfully!');
    redirect('profile.php');
}

// ==================== LOAD ADDRESS FOR EDIT ====================
if ($action === 'edit' && $address_id) {
    $stm = $_db->prepare("SELECT * FROM user_address WHERE AddressID = ? AND UserID = ?");
    $stm->execute([$address_id, $user_id]);
    $address = $stm->fetch();
    
    if (!$address) {
        temp('info', 'Address not found!');
        redirect('profile.php');
    }
}

// ==================== CREATE / UPDATE ====================
if (is_post()) {
    $label = trim(req('label'));
    $recipient = trim(req('recipient'));
    $phone = trim(req('phone'));
    $address1 = trim(req('address1'));
    $address2 = trim(req('address2'));
    $city = trim(req('city'));
    $state = req('state');
    $postcode = trim(req('postcode'));
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // 验证
    if (!$label) $errors['label'] = 'Label is required';
    if (!$recipient) $errors['recipient'] = 'Recipient name is required';
    if (!$phone) $errors['phone'] = 'Phone number is required';
    if (!$address1) $errors['address1'] = 'Address Line 1 is required';
    if (!$city) $errors['city'] = 'City is required';
    if (!$state) $errors['state'] = 'State is required';
    if (!$postcode) $errors['postcode'] = 'Postcode is required';

    if (empty($errors)) {
        // 如果设为 default，先把其他地址的 default 取消
        if ($is_default) {
            $stm = $_db->prepare("UPDATE user_address SET IsDefault = 0 WHERE UserID = ?");
            $stm->execute([$user_id]);
        }

        if ($action === 'add') {
            // CREATE
            $stm = $_db->prepare("
                INSERT INTO user_address 
                (UserID, AddressLabel, RecipientName, PhoneNumber, AddressLine1, AddressLine2, 
                 City, State, PostalCode, IsDefault) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stm->execute([$user_id, $label, $recipient, $phone, $address1, $address2, 
                          $city, $state, $postcode, $is_default]);
            temp('info', 'Address added successfully!');
            redirect('profile.php');
        } 
        elseif ($action === 'edit' && $address_id) {
            // UPDATE
            $stm = $_db->prepare("
                UPDATE user_address SET 
                    AddressLabel = ?, RecipientName = ?, PhoneNumber = ?, 
                    AddressLine1 = ?, AddressLine2 = ?, City = ?, State = ?, 
                    PostalCode = ?, IsDefault = ?
                WHERE AddressID = ? AND UserID = ?
            ");
            $stm->execute([$label, $recipient, $phone, $address1, $address2, 
                          $city, $state, $postcode, $is_default, $address_id, $user_id]);
            temp('info', 'Address updated successfully!');
            redirect('profile.php');
        }
    }
}

// 马来西亚州属列表
$states = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang',
    'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 
    'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'
];

$_title = ($action === 'edit' ? 'Edit' : 'Add') . ' Address - Nº9 Perfume';
include '../_head.php';
?>

<div style="padding:2rem; max-width: 700px; margin: 0 auto;">
    <h2><?= $action === 'edit' ? 'Edit Address' : 'Add New Address' ?></h2>

    <?php if (!empty($errors)): ?>
        <div style="padding:12px; background:#f8d7da; color:#721c24; border-radius:5px; margin:15px 0;">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" style="margin-top:2rem;">
        <!-- Address Label -->
        <div style="margin-bottom:1.2rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                Address Label <span style="color:red;">*</span>
            </label>
            <input type="text" name="label" 
                   value="<?= htmlspecialchars($address->AddressLabel ?? req('label')) ?>"
                   placeholder="e.g. Home, Office"
                   style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
        </div>

        <!-- Recipient Name -->
        <div style="margin-bottom:1.2rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                Recipient Name <span style="color:red;">*</span>
            </label>
            <input type="text" name="recipient" 
                   value="<?= htmlspecialchars($address->RecipientName ?? req('recipient')) ?>"
                   style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
        </div>

        <!-- Phone Number -->
        <div style="margin-bottom:1.2rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                Phone Number <span style="color:red;">*</span>
            </label>
            <input type="text" name="phone" 
                   value="<?= htmlspecialchars($address->PhoneNumber ?? req('phone')) ?>"
                   placeholder="e.g. 0123456789"
                   style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
        </div>

        <!-- Address Line 1 -->
        <div style="margin-bottom:1.2rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                Address Line 1 <span style="color:red;">*</span>
            </label>
            <input type="text" name="address1" 
                   value="<?= htmlspecialchars($address->AddressLine1 ?? req('address1')) ?>"
                   placeholder="Street address, P.O. box"
                   style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
        </div>

        <!-- Address Line 2 -->
        <div style="margin-bottom:1.2rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                Address Line 2 <span style="color:#999;">(Optional)</span>
            </label>
            <input type="text" name="address2" 
                   value="<?= htmlspecialchars($address->AddressLine2 ?? req('address2')) ?>"
                   placeholder="Apartment, suite, unit, building, floor, etc."
                   style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
        </div>

        <!-- City & Postcode -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.2rem;">
            <div>
                <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                    City <span style="color:red;">*</span>
                </label>
                <input type="text" name="city" 
                       value="<?= htmlspecialchars($address->City ?? req('city')) ?>"
                       style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
            </div>
            <div>
                <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                    Postcode <span style="color:red;">*</span>
                </label>
                <input type="text" name="postcode" 
                       value="<?= htmlspecialchars($address->PostalCode ?? req('postcode')) ?>"
                       style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
            </div>
        </div>

        <!-- State -->
        <div style="margin-bottom:1.2rem;">
            <label style="display:block; margin-bottom:0.5rem; color:#333; font-weight:500;">
                State <span style="color:red;">*</span>
            </label>
            <select name="state" 
                    style="width:100%; padding:10px; font-size:1rem; border:1px solid #ddd; border-radius:5px;">
                <option value="">-- Select State --</option>
                <?php foreach ($states as $s): ?>
                    <option value="<?= $s ?>" 
                            <?= ($address->State ?? req('state')) === $s ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Set as Default -->
        <div style="margin-bottom:1.5rem;">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                <input type="checkbox" name="is_default" value="1"
                       <?= ($address->IsDefault ?? req('is_default')) ? 'checked' : '' ?>>
                <span style="color:#333;">Set as default address</span>
            </label>
        </div>

        <!-- Buttons -->
        <div style="display:flex; gap:15px;">
            <button type="submit"
                    style="padding:12px 30px; background:#000; color:#fff; border:none; 
                           cursor:pointer; border-radius:5px; font-size:1rem;">
                <?= $action === 'edit' ? 'Update Address' : 'Add Address' ?>
            </button>

            <a href="profile.php" 
               style="padding:12px 30px; background:#6c757d; color:#fff; text-decoration:none; 
                      border-radius:5px; font-size:1rem; display:inline-block;">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include '../_foot.php'; ?>