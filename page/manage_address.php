<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$address_id = $_GET['id'] ?? null;
$redirect_page = $_GET['redirect'] ?? 'profile'; 

$errors = [];
$address = null;

// ==================== DELETE ====================
if ($action === 'delete' && $address_id) {
    $stm = $_db->prepare("DELETE FROM user_address WHERE AddressID = ? AND UserID = ?");
    $stm->execute([$address_id, $user_id]);
    temp('info', 'Address deleted successfully!');
    redirect($redirect_page . '.php');
}

// ==================== LOAD ADDRESS FOR EDIT ====================
if ($action === 'edit' && $address_id) {
    $stm = $_db->prepare("SELECT * FROM user_address WHERE AddressID = ? AND UserID = ?");
    $stm->execute([$address_id, $user_id]);
    $address = $stm->fetch();
    
    if (!$address) {
        temp('info', 'Address not found!');
        redirect($redirect_page . '.php');
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

    // validation
    if (!$label) {
        $errors['label'] = 'Label is required';
    }
    
    if (!$recipient) {
        $errors['recipient'] = 'Recipient name is required';
    }
    
    if (!$phone) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,12}$/', $phone)) {
        $errors['phone'] = 'Phone number must be 10-12 digits';
    }
    
    if (!$address1) {
        $errors['address1'] = 'Address Line 1 is required';
    }
    
    if (!$city) {
        $errors['city'] = 'City is required';
    }
    
    if (!$state) {
        $errors['state'] = 'State is required';
    }
    
    if (!$postcode) {
        $errors['postcode'] = 'Postcode is required';
    } elseif (!preg_match('/^[0-9]{5}$/', $postcode)) {
        $errors['postcode'] = 'Postcode must be exactly 5 digits';
    }

    if (empty($errors)) {
        try {
            $_db->beginTransaction();
            
            // If set as default, first disable the default for other addresses
            if ($is_default) {
                $stm = $_db->prepare("UPDATE user_address SET IsDefault = 0 WHERE UserID = ?");
                $stm->execute([$user_id]);
            }

            if ($action === 'add') {
                // Check address quantity limit
                $stm = $_db->prepare("SELECT COUNT(*) FROM user_address WHERE UserID = ?");
                $stm->execute([$user_id]);
                $count = $stm->fetchColumn();
                
                if ($count >= 4) {
                    $errors['limit'] = 'Maximum 4 addresses allowed';
                } else {
                    // CREATE
                    $stm = $_db->prepare("
                        INSERT INTO user_address 
                        (UserID, AddressLabel, RecipientName, PhoneNumber, AddressLine1, AddressLine2, 
                         City, State, PostalCode, IsDefault) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stm->execute([$user_id, $label, $recipient, $phone, $address1, $address2, 
                                  $city, $state, $postcode, $is_default]);
                    
                    $_db->commit();
                    temp('info', 'Address added successfully!');
                    redirect($redirect_page . '.php');
                }
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
                
                $_db->commit();
                temp('info', 'Address updated successfully!');
                redirect($redirect_page . '.php');
            }
            
            if (isset($errors['limit'])) {
                $_db->rollBack();
            }
        } catch (Exception $e) {
            $_db->rollBack();
            $errors['general'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// malaysia states
$states = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang',
    'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 
    'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'
];

$_title = ($action === 'edit' ? 'Edit' : 'Add') . ' Address - N°9 Perfume';
include '../_head.php';
?>

<style>
.form-container {
    max-width: 700px;
    margin: 100px auto 50px;
    padding: 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    font-size: 2rem;
    font-weight: 300;
    color: #333;
    margin-bottom: 0.5rem;
}

.form-header p {
    color: #666;
    font-size: 0.95rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
    font-weight: 500;
}

.form-group label .required {
    color: #dc3545;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #D4AF37;
}

.form-group .error {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 0.3rem;
    display: block;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-group label {
    margin: 0;
    cursor: pointer;
    font-weight: normal;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    padding: 14px 30px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #000;
    color: #D4AF37;
    border: 2px solid #D4AF37;
    flex: 1;
}

.btn-primary:hover {
    background: #D4AF37;
    color: #000;
}

.btn-secondary {
    background: #f0f0f0;
    color: #666;
    flex: 1;
}

.btn-secondary:hover {
    background: #e0e0e0;
    color: #333;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

@media (max-width: 768px) {
    .form-container {
        margin: 80px 1rem 30px;
        padding: 1.5rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<div class="form-container">
    <div class="form-header">
        <h2><?= $action === 'edit' ? 'Edit Address' : 'Add New Address' ?></h2>
        <p>Please fill in all required fields</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <!-- Address Label -->
        <div class="form-group">
            <label>
                Address Label <span class="required">*</span>
            </label>
            <input type="text" name="label" 
                   value="<?= htmlspecialchars($address->AddressLabel ?? req('label')) ?>"
                   placeholder="e.g. Home, Office"
                   required>
            <?php if (isset($errors['label'])): ?>
                <span class="error"><?= $errors['label'] ?></span>
            <?php endif; ?>
        </div>

        <!-- Recipient Name -->
        <div class="form-group">
            <label>
                Recipient Name <span class="required">*</span>
            </label>
            <input type="text" name="recipient" 
                   value="<?= htmlspecialchars($address->RecipientName ?? req('recipient')) ?>"
                   required>
        </div>

        <!-- Phone Number -->
        <div class="form-group">
            <label>
                Phone Number <span class="required">*</span>
            </label>
            <input type="text" name="phone" 
                   value="<?= htmlspecialchars($address->PhoneNumber ?? req('phone')) ?>"
                   placeholder="e.g. 0123456789 (10-12 digits)"
                   pattern="[0-9]{10,12}"
                   maxlength="12"
                   required>
            <small style="color: #666; font-size: 0.85rem;">Enter 10-12 digits only</small>
            <?php if (isset($errors['phone'])): ?>
                <span class="error"><?= $errors['phone'] ?></span>
            <?php endif; ?>
        </div>

        <!-- Address Line 1 -->
        <div class="form-group">
            <label>
                Address Line 1 <span class="required">*</span>
            </label>
            <input type="text" name="address1" 
                   value="<?= htmlspecialchars($address->AddressLine1 ?? req('address1')) ?>"
                   placeholder="Street address, P.O. box"
                   required>
        </div>

        <!-- Address Line 2 -->
        <div class="form-group">
            <label>
                Address Line 2 <span style="color: #999;">(Optional)</span>
            </label>
            <input type="text" name="address2" 
                   value="<?= htmlspecialchars($address->AddressLine2 ?? req('address2')) ?>"
                   placeholder="Apartment, suite, unit, building, floor, etc.">
        </div>

        <!-- City & Postcode -->
        <div class="form-row">
            <div class="form-group">
                <label>
                    City <span class="required">*</span>
                </label>
                <input type="text" name="city" 
                       value="<?= htmlspecialchars($address->City ?? req('city')) ?>"
                       required>
            </div>
            <div class="form-group">
                <label>
                    Postcode <span class="required">*</span>
                </label>
                <input type="text" name="postcode" 
                       value="<?= htmlspecialchars($address->PostalCode ?? req('postcode')) ?>"
                       placeholder="e.g. 12345 (5 digits)"
                       pattern="[0-9]{5}"
                       maxlength="5"
                       required>
                <small style="color: #666; font-size: 0.85rem;">Enter exactly 5 digits</small>
                <?php if (isset($errors['postcode'])): ?>
                    <span class="error"><?= $errors['postcode'] ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- State -->
        <div class="form-group">
            <label>
                State <span class="required">*</span>
            </label>
            <select name="state" required>
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
        <div class="checkbox-group">
            <input type="checkbox" name="is_default" id="is_default" value="1"
                   <?= ($address->IsDefault ?? req('is_default')) ? 'checked' : '' ?>>
            <label for="is_default">Set as default address</label>
        </div>

        <!-- Buttons -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $action === 'edit' ? 'Update Address' : 'Add Address' ?>
            </button>
            <a href="<?= $redirect_page ?>.php" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const phoneInput = document.querySelector('input[name="phone"]');
    const postcodeInput = document.querySelector('input[name="postcode"]');
    
    // Phone number validation - only allow digits
    phoneInput.addEventListener('input', function(e) {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit to 12 characters
        if (this.value.length > 12) {
            this.value = this.value.substring(0, 12);
        }
        
        // Visual feedback
        if (this.value.length >= 10 && this.value.length <= 12) {
            this.style.borderColor = '#28a745';
        } else if (this.value.length > 0) {
            this.style.borderColor = '#dc3545';
        } else {
            this.style.borderColor = '#ddd';
        }
    });
    
    // Postcode validation - only allow digits, exactly 5
    postcodeInput.addEventListener('input', function(e) {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit to 5 characters
        if (this.value.length > 5) {
            this.value = this.value.substring(0, 5);
        }
        
        // Visual feedback
        if (this.value.length === 5) {
            this.style.borderColor = '#28a745';
        } else if (this.value.length > 0) {
            this.style.borderColor = '#dc3545';
        } else {
            this.style.borderColor = '#ddd';
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessage = '';
        
        // Validate phone number
        const phoneValue = phoneInput.value;
        if (phoneValue.length < 10 || phoneValue.length > 12) {
            isValid = false;
            errorMessage += '• Phone number must be 10-12 digits\n';
            phoneInput.style.borderColor = '#dc3545';
        }
        
        // Validate postcode
        const postcodeValue = postcodeInput.value;
        if (postcodeValue.length !== 5) {
            isValid = false;
            errorMessage += '• Postcode must be exactly 5 digits\n';
            postcodeInput.style.borderColor = '#dc3545';
        }
        
        // If validation fails, prevent form submission
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errorMessage);
            return false;
        }
    });
});
</script>

<?php include '../_foot.php'; ?>