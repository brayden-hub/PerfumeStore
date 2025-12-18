<?php
require '../_base.php';

// ===== FETCH USER VOUCHERS =====
$user_id = $_SESSION['user_id'] ?? null;
$vouchers = [];
$voucher_code = $_POST['voucher_code'] ?? null;
$voucher_discount = $_POST['voucher_discount'] ?? 0;


if ($user_id) {
    $stm = $_db->prepare("
        SELECT 
            v.VoucherID,
            v.Code AS VoucherCode,
            v.DiscountType,
            v.DiscountValue,
            v.MinSpend
        FROM user_voucher uv
        JOIN voucher v ON uv.VoucherID = v.VoucherID
        WHERE uv.UserID = ?
          AND uv.IsUsed = 0
          AND v.status = 'active'
          AND (v.ExpiryDate IS NULL OR v.ExpiryDate >= CURDATE())
    ");
    $stm->execute([$user_id]);
    $vouchers = $stm->fetchAll(PDO::FETCH_ASSOC);
}

if (!isset($_SESSION['user_id'])) {
    redirect('/page/login.php');
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $_db->prepare("
    SELECT c.CartID, c.ProductID, c.Quantity, 
           p.ProductName, p.Price, p.Stock
    FROM cart c
    JOIN product p ON c.ProductID = p.ProductID
    WHERE c.UserID = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    redirect('/page/cart.php');
}

// Get user addresses
$stmt = $_db->prepare("SELECT * FROM user_address WHERE UserID = ? ORDER BY IsDefault DESC, AddressID DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item->Price * $item->Quantity;
}

// Get gift options from session
$gift_wrap_cost = 0.00;
$gift_enabled = false;
$gift_packaging = 'standard';
$gift_message = '';
$hide_price = 0;

if (isset($_SESSION['gift_options'])) {
    $gift_data = $_SESSION['gift_options'];
    $gift_enabled = ($gift_data['enabled'] === true || $gift_data['enabled'] === 1 || $gift_data['enabled'] === '1');
    $gift_packaging = $gift_data['packaging'] ?? 'standard';
    $gift_message = trim($gift_data['message'] ?? '');
    $hide_price = ($gift_data['hidePrice'] === true || $gift_data['hidePrice'] === 1 || $gift_data['hidePrice'] === '1') ? 1 : 0;
    
    if ($gift_enabled && $gift_packaging === 'luxury') {
        $gift_wrap_cost = 5.00;
    }
}

$free_shipping_threshold = 300.00;
$cart_total = $subtotal + $gift_wrap_cost;

if ($cart_total >= $free_shipping_threshold) {
    $shipping_fee = 0.00;
    $shipping_display = 'FREE';
} else {
    $shipping_fee = 30.00;
    $shipping_display = 'RM 30.00';
}

$total = $cart_total + $shipping_fee;

if (is_post()) {
    $payment_method = post('payment_method');
    $address_id = post('address_id');
    $voucher_code = post('voucher_code');
    $voucher_discount = floatval(post('voucher_discount'));
    
    if (!in_array($payment_method, ['Credit Card', 'Online Banking', 'E-Wallet', 'Cash on Delivery'])) {
        temp('error', 'Please select a payment method');
    } elseif (empty($address_id)) {
        temp('error', 'Please select a shipping address');
    } else {
        try {
            $_db->beginTransaction();
            
            // Generate OrderID
            $stmt = $_db->query("SELECT OrderID FROM `order` ORDER BY OrderID DESC LIMIT 1");
            $last = $stmt->fetch();
            $next_num = $last ? (int)substr($last->OrderID, 1) + 1 : 1;
            $order_id = 'O' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
            
            // Get VoucherID if voucher is used
            $final_voucher_id = null;
            if (!empty($voucher_code) && $voucher_discount > 0) {
                $stmt_voucher = $_db->prepare("
                    SELECT v.VoucherID, uv.UserVoucherID
                    FROM voucher v
                    JOIN user_voucher uv ON v.VoucherID = uv.VoucherID
                    WHERE v.Code = ? AND uv.UserID = ? AND uv.IsUsed = 0
                ");
                $stmt_voucher->execute([$voucher_code, $user_id]);
                $voucher_data = $stmt_voucher->fetch(PDO::FETCH_ASSOC);
                
                if ($voucher_data) {
                    $final_voucher_id = $voucher_data['VoucherID'];
                    
                    // Mark as used
                    $_db->prepare("UPDATE user_voucher SET IsUsed = 1 WHERE UserVoucherID = ?")
                        ->execute([$voucher_data['UserVoucherID']]);
                }
            }
            
            // Prepare gift wrap values
            $gift_wrap_value = $gift_enabled ? $gift_packaging : null;
            $gift_message_value = ($gift_enabled && !empty($gift_message)) ? $gift_message : null;
            
            // Create order - FIXED: Match column count with values
            $stmt = $_db->prepare("
                INSERT INTO `order` 
                (OrderID, UserID, ShippingAddressID, PurchaseDate, PaymentMethod, GiftWrap, GiftMessage, HidePrice, GiftWrapCost, ShippingFee, VoucherID, VoucherDiscount) 
                VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $order_id,              // 1
                $user_id,               // 2
                $address_id,            // 3
                $payment_method,        // 4
                $gift_wrap_value,       // 5
                $gift_message_value,    // 6
                $hide_price,            // 7
                $gift_wrap_cost,        // 8
                $shipping_fee,          // 9
                $final_voucher_id,      // 10
                $voucher_discount       // 11
            ]);
            
            // NEW: Insert into order_status table
            $stmt = $_db->prepare("INSERT INTO order_status (OrderID, Status) VALUES (?, 'Pending')");
            $stmt->execute([$order_id]);
            
            // Give user a new voucher after purchase
            $stmt = $_db->prepare("
                SELECT VoucherID
                FROM voucher
                WHERE status = 'active'
                  AND (ExpiryDate IS NULL OR ExpiryDate >= CURDATE())
                ORDER BY VoucherID DESC
                LIMIT 1
            ");
            $stmt->execute();
            $new_voucher = $stmt->fetch(PDO::FETCH_ASSOC); 

            if ($new_voucher) {
                $stmt = $_db->prepare("
                    INSERT INTO user_voucher (UserID, VoucherID)
                    VALUES (?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $new_voucher['VoucherID']
                ]);
            }
            
            // Create order items and update stock
            foreach ($cart_items as $item) {
                $stmt = $_db->query("SELECT ProductOrderID FROM productorder ORDER BY ProductOrderID DESC LIMIT 1");
                $last = $stmt->fetch();
                $next_num = $last ? (int)substr($last->ProductOrderID, 2) + 1 : 1;
                $po_id = 'PO' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
                
                $item_subtotal = $item->Price * $item->Quantity;
                
                $stmt = $_db->prepare("INSERT INTO productorder (ProductOrderID, OrderID, ProductID, Quantity, TotalPrice) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$po_id, $order_id, $item->ProductID, $item->Quantity, $item_subtotal]);
                
                $stmt = $_db->prepare("UPDATE product SET Stock = Stock - ? WHERE ProductID = ?");
                $stmt->execute([$item->Quantity, $item->ProductID]);
            }
            
            // Clear cart and gift options
            $stmt = $_db->prepare("DELETE FROM cart WHERE UserID = ?");
            $stmt->execute([$user_id]);
            unset($_SESSION['gift_options']);
            
            $_db->commit();
            
            temp('success', 'Order placed successfully!');
            redirect('/page/order_confirmation.php?order_id=' . $order_id);            
        } catch (Exception $e) {
            $_db->rollBack();
            temp('error', 'Order failed: ' . $e->getMessage());
        }
    }
}

$current_step = 2;
$_title = 'Checkout - N°9 Perfume';
include '../_head.php';
?>

<style>
.payment-option {
    display: flex;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.8rem;
    background: #fff;
    border: 2px solid #eee;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-option:hover {
    border-color: #D4AF37;
    background: #fffbf0;
}

.payment-option.selected {
    border-color: #D4AF37;
    background: #fffbf0;
    box-shadow: 0 2px 8px rgba(212, 175, 55, 0.2);
}

.payment-option input[type="radio"] {
    margin-right: 0.8rem;
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.payment-details {
    display: none;
    margin-top: 1rem;
    padding: 1.5rem;
    background: #f9f9f9;
    border-radius: 8px;
    border: 2px solid #D4AF37;
}

.payment-details.active {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #D4AF37;
}

.card-input-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.bank-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.8rem;
}

.bank-option {
    display: flex;
    align-items: center;
    padding: 0.8rem;
    background: white;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.bank-option:hover {
    border-color: #D4AF37;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.bank-option.selected {
    border-color: #D4AF37;
    background: #fffbf0;
}

.bank-option input[type="radio"] {
    margin-right: 0.6rem;
}

.qr-container {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 8px;
}

.qr-code {
    width: 250px;
    height: 250px;
    margin: 1rem auto;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}

.gift-summary-box {
    background: linear-gradient(135deg, #fffbf0 0%, #fff 100%);
    border: 2px solid #D4AF37;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.gift-summary-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.gift-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0e6d2;
}

.gift-detail-row:last-child {
    border-bottom: none;
}

.gift-message-preview {
    background: #fff;
    padding: 1rem;
    border-radius: 8px;
    font-style: italic;
    color: #666;
    border-left: 3px solid #D4AF37;
}

/* Address Card Styles */
.address-card {
    display: flex;
    gap: 1rem;
    padding: 1.2rem;
    border: 2px solid #eee;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.address-card:has(input:checked) {
    border-color: #D4AF37;
    background: #fffbf0;
}

.address-card:hover {
    border-color: #D4AF37;
    background: #fafafa;
}

.address-actions {
    position: absolute;
    top: 0.8rem;
    right: 0.8rem;
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s;
}

.address-card:hover .address-actions {
    opacity: 1;
}

.address-btn {
    padding: 0.4rem 0.8rem;
    border: none;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
}

.btn-edit-address {
    background: #D4AF37;
    color: #000;
}

.btn-edit-address:hover {
    background: #b8941f;
}

.btn-delete-address {
    background: #dc3545;
    color: #fff;
}

.btn-delete-address:hover {
    background: #c82333;
}
</style>

<div class="container" style="margin-top: 100px; min-height: 60vh; max-width: 1200px; padding: 0 2rem;">
    <?php include 'progress_bar.php'; ?>

    <h2 style="margin-bottom: 2rem; font-weight: 300; font-size: 2rem;">Checkout</h2>
    
    <?php if ($err = temp('error')): ?>
        <div style="padding: 1rem; background: #ffe6e6; color: #d00; border-radius: 8px; margin-bottom: 1rem;">
            ⚠ <?= $err ?>
        </div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 450px; gap: 3rem;">
        <!-- Left: Order Summary & Address -->
        <div>
            <!-- Shipping Address Section -->
            <div style="background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.5rem; font-weight: 400; margin: 0;">Shipping Address</h3>
                    <?php if (count($addresses) < 4): ?>
                        <button id="add-address-btn" style="padding: 0.6rem 1.2rem; background: #D4AF37; color: #000; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            + Add New Address
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($addresses)): ?>
                    <div style="text-align: center; padding: 2rem; background: #f9f9f9; border-radius: 8px; color: #666;">
                        <p>No saved addresses. Please add one to continue.</p>
                        <button onclick="window.location.href='/page/manage_address.php?action=add&redirect=checkout'" style="margin-top: 1rem; padding: 0.8rem 1.5rem; background: #000; color: #D4AF37; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            Add Address Now
                        </button>
                    </div>
                <?php else: ?>
                    <div id="address-list" style="display: grid; gap: 1rem;">
                        <?php foreach ($addresses as $addr): ?>
                            <label class="address-card">
                                <input type="radio" name="selected_address" value="<?= $addr->AddressID ?>" <?= $addr->IsDefault ? 'checked' : '' ?> style="margin-top: 0.3rem;">
                                <div style="flex: 1;">
                                    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                                        <strong style="font-size: 1.05rem;"><?= htmlspecialchars($addr->AddressLabel) ?></strong>
                                        <?php if ($addr->IsDefault): ?>
                                            <span style="background: #D4AF37; color: #000; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">DEFAULT</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="margin: 0.3rem 0; color: #333;"><?= htmlspecialchars($addr->RecipientName) ?> | <?= htmlspecialchars($addr->PhoneNumber) ?></p>
                                    <p style="margin: 0.3rem 0; color: #666; line-height: 1.5;">
                                        <?= htmlspecialchars($addr->AddressLine1) ?>
                                        <?= $addr->AddressLine2 ? ', ' . htmlspecialchars($addr->AddressLine2) : '' ?><br>
                                        <?= htmlspecialchars($addr->City) ?>, <?= htmlspecialchars($addr->State) ?> <?= htmlspecialchars($addr->PostalCode) ?>
                                    </p>
                                </div>
                                <!-- Edit/Delete Buttons -->
                                <div class="address-actions">
                                    <button type="button" class="address-btn btn-edit-address" 
                                            onclick="event.preventDefault(); window.location.href='/page/manage_address.php?action=edit&id=<?= $addr->AddressID ?>&redirect=checkout'">
                                        Edit
                                    </button>
                                    <button type="button" class="address-btn btn-delete-address" 
                                            onclick="event.preventDefault(); deleteAddress(<?= $addr->AddressID ?>)">
                                        Delete
                                    </button>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Summary -->
            <h3 style="margin-bottom: 1.5rem; font-size: 1.5rem; font-weight: 400;">Order Summary</h3>
            
            
            <div style="background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee;">
                            <th style="text-align: left; padding: 1rem 0; font-weight: 600;">Product</th>
                            <th style="text-align: right; padding: 1rem 0; font-weight: 600;">Price</th>
                            <th style="text-align: center; padding: 1rem 0; font-weight: 600;">Qty</th>
                            <th style="text-align: right; padding: 1rem 0; font-weight: 600;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): 
                            $item_subtotal = $item->Price * $item->Quantity;
                        ?>
                        <tr style="border-bottom: 1px solid #f5f5f5;">
                            <td style="padding: 1rem 0;"><?= htmlspecialchars($item->ProductName) ?></td>
                            <td style="text-align: right; padding: 1rem 0;">RM <?= number_format($item->Price, 2) ?></td>
                            <td style="text-align: center; padding: 1rem 0;"><?= $item->Quantity ?></td>
                            <td style="text-align: right; padding: 1rem 0; font-weight: 600;">RM <?= number_format($item_subtotal, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Gift Options Summary -->
            <?php if ($gift_enabled): ?>
                <div class="gift-summary-box">
                    <div class="gift-summary-title">
                        <span>🎁</span> Gift Options
                    </div>
                    
                    <div class="gift-detail-row">
                        <span style="color: #666;">Packaging:</span>
                        <span style="font-weight: 600;">
                            <?= $gift_packaging === 'luxury' ? '💎 Luxury Gift Wrap' : '📦 Standard Packaging' ?>
                        </span>
                    </div>
                    
                    <?php if ($gift_packaging === 'luxury'): ?>
                        <div class="gift-detail-row">
                            <span style="color: #666;">Gift Wrap Cost:</span>
                            <span style="font-weight: 600; color: #D4AF37;">+RM <?= number_format($gift_wrap_cost, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($gift_message)): ?>
                        <div style="margin-top: 1rem;">
                            <div style="color: #666; margin-bottom: 0.5rem;">💌 Gift Message:</div>
                            <div class="gift-message-preview">
                                "<?= htmlspecialchars($gift_message) ?>"
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($hide_price): ?>
                        <div class="gift-detail-row">
                            <span style="color: #666;">🔒 Privacy:</span>
                            <span style="font-weight: 600;">Price hidden on receipt</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="background: #f9f9f9; padding: 2rem; border-radius: 12px; align-self: flex-start; position: sticky; top: 100px;">
    <h3 style="margin-bottom: 1.5rem; font-size: 1.3rem; font-weight: 400;">Payment Details</h3>
    
    <?php
    // ===== VOUCHER SECTION =====
    $user_vouchers = [];
    $subtotal_for_voucher = $subtotal; // 使用已经计算好的 subtotal
    
    if ($user_id) {
        $stm = $_db->prepare("
            SELECT 
                uv.UserVoucherID,
                v.Code,
                v.DiscountType,
                v.DiscountValue,
                v.MinSpend,
                v.ExpiryDate
            FROM user_voucher uv
            JOIN voucher v ON uv.VoucherID = v.VoucherID
            WHERE uv.UserID = ?
              AND uv.IsUsed = 0
              AND v.status = 'active'
              AND (v.ExpiryDate IS NULL OR v.ExpiryDate >= CURDATE())
            ORDER BY v.MinSpend ASC
        ");
        $stm->execute([$user_id]);
        $user_vouchers = $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    // 判断每个 voucher 是否可用
    foreach ($user_vouchers as &$voucher) {
        $voucher['is_available'] = ($subtotal_for_voucher >= $voucher['MinSpend']);
    }
    unset($voucher);

    $available_count = count(array_filter($user_vouchers, fn($v) => $v['is_available']));
    ?>

    <style>
    .voucher-section {
        background: linear-gradient(135deg, #fffbf0 0%, #ffffff 100%);
        border: 2px solid #D4AF37;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .voucher-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.2rem;
    }

    .voucher-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
    }

    .voucher-badge {
        background: #D4AF37;
        color: #000;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
    }

    .voucher-card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .voucher-card.available {
        border-color: #D4AF37;
    }

    .voucher-card.available:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    }

    .voucher-card.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #f9f9f9;
    }

    .voucher-card.selected {
        border-color: #D4AF37;
        background: #fffbf0;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
    }

    .voucher-icon {
        font-size: 2.5rem;
        flex-shrink: 0;
    }

    .voucher-content {
        flex: 1;
    }

    .voucher-discount {
        font-size: 1.3rem;
        font-weight: bold;
        color: #D4AF37;
        margin-bottom: 0.3rem;
    }

    .voucher-code {
        background: #f0f0f0;
        padding: 0.2rem 0.6rem;
        border-radius: 5px;
        font-family: monospace;
        font-size: 0.85rem;
        color: #666;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .voucher-condition {
        font-size: 0.85rem;
        color: #666;
    }

    .voucher-status {
        flex-shrink: 0;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .no-vouchers {
        text-align: center;
        padding: 2rem;
        color: #999;
    }
    </style>

    <div class="voucher-section">
        <div class="voucher-header">
            <div class="voucher-title">🎟️ My Vouchers</div>
            <?php if ($available_count > 0): ?>
                <span class="voucher-badge"><?= $available_count ?> Available</span>
            <?php endif; ?>
        </div>

        <?php if (empty($user_vouchers)): ?>
            <div class="no-vouchers">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🎫</div>
                <p>You don't have any vouchers yet.</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">
                    Complete your purchase to receive vouchers!
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($user_vouchers as $v): ?>
                <label class="voucher-card <?= $v['is_available'] ? 'available' : 'disabled' ?>">
                    <input type="radio" 
                           name="voucher_selection" 
                           class="voucher-radio"
                           value="<?= $v['Code'] ?>"
                           data-type="<?= $v['DiscountType'] ?>"
                           data-value="<?= $v['DiscountValue'] ?>"
                           data-min="<?= $v['MinSpend'] ?>"
                           <?= !$v['is_available'] ? 'disabled' : '' ?>
                           style="display: none;">
                    
                    <div class="voucher-icon">
                        <?= $v['is_available'] ? '🎁' : '🔒' ?>
                    </div>
                    
                    <div class="voucher-content">
                        <div class="voucher-discount">
                            <?php if ($v['DiscountType'] === 'percent'): ?>
                                <?= $v['DiscountValue'] ?>% OFF
                            <?php else: ?>
                                RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                            <?php endif; ?>
                        </div>
                        
                        <div class="voucher-code"><?= $v['Code'] ?></div>
                        
                        <div class="voucher-condition">
                            <?php if ($v['MinSpend'] > 0): ?>
                                Min. spend: RM <?= number_format($v['MinSpend'], 2) ?>
                            <?php else: ?>
                                No minimum spend
                            <?php endif; ?>
                            <?php if ($v['ExpiryDate']): ?>
                                | Expires: <?= date('d M Y', strtotime($v['ExpiryDate'])) ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$v['is_available']): ?>
                            <div style="color: #dc3545; font-size: 0.85rem; margin-top: 0.3rem;">
                                💰 Add RM <?= number_format($v['MinSpend'] - $subtotal_for_voucher, 2) ?> more to unlock
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="voucher-status">
                        <?php if ($v['is_available']): ?>
                            <span style="color: #28a745;">✓ Apply</span>
                        <?php else: ?>
                            <span style="color: #999;">🔒 Locked</span>
                        <?php endif; ?>
                    </div>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="post" id="checkout-form">
        <input type="hidden" name="voucher_code" id="voucher_code">
        <input type="hidden" name="voucher_discount" id="voucher_discount" value="0">
        <input type="hidden" name="address_id" id="address_id" value="<?= $addresses[0]->AddressID ?? '' ?>">

            
            <form method="post" id="checkout-form">
                <input type="hidden" name="voucher_code" id="voucher_code">
                <input type="hidden" name="voucher_discount" id="voucher_discount">
                <input type="hidden" name="address_id" id="address_id" value="<?= $addresses[0]->AddressID ?? '' ?>">
                
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: #333;">Select Payment Method</label>
                    
                    <!-- Credit Card -->
                    <label class="payment-option" data-method="credit-card">
                        <input type="radio" name="payment_method" value="Credit Card" required>
                        <span>💳 Credit Card</span>
                    </label>
                    <div id="credit-card-details" class="payment-details">
                        <h4 style="margin-bottom: 1rem; color: #D4AF37;">💳 Enter Card Details</h4>
                        <div class="form-group">
                            <label>Card Number *</label>
                            <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19" pattern="\d{4} \d{4} \d{4} \d{4}">
                        </div>
                        <div class="form-group">
                            <label>Cardholder Name *</label>
                            <input type="text" id="card-name" placeholder="JOHN DOE">
                        </div>
                        <div class="card-input-group">
                            <div class="form-group">
                                <label>Expiry Date *</label>
                                <input type="text" id="card-expiry" placeholder="MM/YY" maxlength="5" pattern="\d{2}/\d{2}">
                            </div>
                            <div class="form-group">
                                <label>CVV *</label>
                                <input type="text" id="card-cvv" placeholder="123" maxlength="3" pattern="\d{3}">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Online Banking -->
                    <label class="payment-option" data-method="online-banking">
                        <input type="radio" name="payment_method" value="Online Banking" required>
                        <span>🏦 Online Banking</span>
                    </label>
                    <div id="online-banking-details" class="payment-details">
                        <h4 style="margin-bottom: 1rem; color: #D4AF37;">🏦 Select Your Bank</h4>
                        <div class="bank-grid">
                            <label class="bank-option">
                                <input type="radio" name="bank" value="Maybank">
                                <span>Maybank</span>
                            </label>
                            <label class="bank-option">
                                <input type="radio" name="bank" value="CIMB">
                                <span>CIMB Bank</span>
                            </label>
                            <label class="bank-option">
                                <input type="radio" name="bank" value="Public Bank">
                                <span>Public Bank</span>
                            </label>
                            <label class="bank-option">
                                <input type="radio" name="bank" value="RHB">
                                <span>RHB Bank</span>
                            </label>
                            <label class="bank-option">
                                <input type="radio" name="bank" value="Hong Leong">
                                <span>Hong Leong Bank</span>
                            </label>
                            <label class="bank-option">
                                <input type="radio" name="bank" value="AmBank">
                                <span>AmBank</span>
                            </label>
                        </div>
                        <p style="margin-top: 1rem; color: #666; font-size: 0.9rem; text-align: center;">
                            You will be redirected to your bank's secure payment page
                        </p>
                    </div>
                    
                    <!-- E-Wallet -->
                    <label class="payment-option" data-method="e-wallet">
                        <input type="radio" name="payment_method" value="E-Wallet" required>
                        <span>📱 E-Wallet</span>
                    </label>
                    <div id="e-wallet-details" class="payment-details">
                        <div class="qr-container">
                            <h4 style="color: #D4AF37; margin-bottom: 0.5rem;">📱 Scan QR Code to Pay</h4>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                                Amount: <strong style="color: #D4AF37; font-size: 1.2rem;">RM <?= number_format($total, 2) ?></strong>
                            </p>
                            <div class="qr-code">
                                <img src="/public/images/qrcode.jpg" alt="Payment QR Code" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">
                            </div>
                            <p style="color: #666; font-size: 0.85rem; margin-top: 1rem;">
                                Compatible with: Touch 'n Go, GrabPay, Boost, ShopeePay
                            </p>
                        </div>
                    </div>
                    
                    <!-- Cash on Delivery -->
                    <label class="payment-option" data-method="cod">
                        <input type="radio" name="payment_method" value="Cash on Delivery" required>
                        <span>💵 Cash on Delivery</span>
                    </label>
                    <div id="cod-details" class="payment-details">
                        <div style="text-align: center; padding: 1rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">💵</div>
                            <h4 style="color: #D4AF37; margin-bottom: 0.5rem;">Pay When You Receive</h4>
                            <p style="color: #666; margin-bottom: 1rem;">
                                Please prepare the exact amount for payment upon delivery.
                            </p>
                            <div style="background: #fffbf0; padding: 1rem; border-radius: 8px; border: 1px solid #D4AF37;">
                                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                                    Amount to pay: <strong style="color: #D4AF37; font-size: 1.2rem;">RM <?= number_format($total, 2) ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="border-top: 2px solid #ddd; padding-top: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem; color: #666;">
        <span>Subtotal:</span>
        <span>RM <?= number_format($subtotal, 2) ?></span>
    </div>
    
    <?php if ($gift_wrap_cost > 0): ?>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem; color: #666;">
            <span>Gift Wrapping:</span>
            <span>RM <?= number_format($gift_wrap_cost, 2) ?></span>
        </div>
    <?php endif; ?>
    
    <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem; color: #666;">
        <span>Shipping:</span>
        <span><?= $shipping_display ?></span>
    </div>
    
    <!-- Voucher Discount Row -->
    <div id="voucher-discount-row" style="display: none; justify-content: space-between; margin-bottom: 0.8rem; padding: 0.8rem; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 8px; border: 1px solid #4caf50;">
        <span style="color: #2e7d32; font-weight: 600;">🎟️ Voucher Discount:</span>
        <span id="discount-amount" style="color: #2e7d32; font-weight: 700; font-size: 1.1rem;">-RM 0.00</span>
    </div>
    
    <div style="display: flex; justify-content: space-between; font-size: 1.4rem; font-weight: bold; color: #D4AF37; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #eee;">
        <span>Total:</span>
        <span id="final-total-display">RM <?= number_format($total, 2) ?></span>
    </div>
</div>
                
                <button type="submit" id="submit-btn" style="width: 100%; padding: 1.2rem; background: #000; color: #D4AF37; border: none; font-size: 1.1rem; font-weight: bold; cursor: pointer; border-radius: 8px; transition: all 0.3s ease;">
                    Complete Order
                </button>
                
                <a href="/page/cart.php" style="display: block; text-align: center; margin-top: 1rem; color: #666; text-decoration: none;">
                    ← Back to Cart
                </a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

/* ===========================
       VOUCHER SYSTEM
    ============================ */
    
    // 价格数据
    const PRICES = {
        subtotal: <?= $subtotal ?>,
        giftWrap: <?= $gift_wrap_cost ?>,
        shipping: <?= $shipping_fee ?>,
        get originalTotal() {
            return this.subtotal + this.giftWrap + this.shipping;
        }
    };
    
    console.log('💰 Prices loaded:', PRICES);
    
    // DOM 元素
    const voucherCards = document.querySelectorAll('.voucher-card.available');
    const voucherCodeInput = document.getElementById('voucher_code');
    const voucherDiscountInput = document.getElementById('voucher_discount');
    const finalTotalDisplay = document.getElementById('final-total-display');
    const discountRow = document.getElementById('voucher-discount-row');
    const discountAmount = document.getElementById('discount-amount');
    
    console.log('🎟️ Found', voucherCards.length, 'available vouchers');
    
    let selectedVoucher = null;
    let currentDiscount = 0;
    
    // 更新总价显示
    function updatePriceDisplay(discount) {
        currentDiscount = discount;
        
        if (discount > 0) {
            discountRow.style.display = 'flex';
            discountAmount.textContent = '-RM ' + discount.toFixed(2);
            
            const newTotal = Math.max(0, PRICES.originalTotal - discount);
            finalTotalDisplay.textContent = 'RM ' + newTotal.toFixed(2);
            
            voucherDiscountInput.value = discount.toFixed(2);
            
            console.log('✅ Discount applied:', discount);
            console.log('📊 New total:', newTotal);
        } else {
            discountRow.style.display = 'none';
            finalTotalDisplay.textContent = 'RM ' + PRICES.originalTotal.toFixed(2);
            voucherDiscountInput.value = '0';
            
            console.log('❌ Discount removed');
        }
    }
    
    // Voucher 卡片点击
    voucherCards.forEach((card, index) => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const radio = this.querySelector('.voucher-radio');
            if (!radio) return;
            
            const voucherCode = radio.value;
            const voucherType = radio.dataset.type;
            const voucherValue = parseFloat(radio.dataset.value);
            
            console.log(`🎫 Clicked voucher ${index + 1}:`, voucherCode);
            
            // 如果点击已选中的，取消选择
            if (selectedVoucher === voucherCode) {
                voucherCards.forEach(c => c.classList.remove('selected'));
                radio.checked = false;
                selectedVoucher = null;
                
                voucherCodeInput.value = '';
                updatePriceDisplay(0);
                
                showToast('Voucher removed', 'info');
                return;
            }
            
            // 选择新 voucher
            voucherCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            radio.checked = true;
            selectedVoucher = voucherCode;
            
            // 计算折扣
            let discount = 0;
            if (voucherType === 'percent') {
                discount = PRICES.subtotal * (voucherValue / 100);
            } else {
                discount = voucherValue;
            }
            
            discount = Math.min(discount, PRICES.subtotal);
            
            console.log('💵 Calculated discount:', discount);
            
            voucherCodeInput.value = voucherCode;
            updatePriceDisplay(discount);
            
            showToast('✅ Voucher applied! You saved RM ' + discount.toFixed(2), 'success');
        });
    });
    
    // Toast 提示
    function showToast(message, type = 'success') {
        const oldToast = document.getElementById('voucher-toast');
        if (oldToast) oldToast.remove();
        
        const bgColor = type === 'success' ? '#4caf50' : '#6c757d';
        
        const toast = document.createElement('div');
        toast.id = 'voucher-toast';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 120px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            z-index: 9999;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // 动画
    if (!document.getElementById('toast-animation')) {
        const style = document.createElement('style');
        style.id = 'toast-animation';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    console.log('✅ Voucher system initialized successfully');
    
    /* ADDRESS SELECTION */
    const addressRadios = document.querySelectorAll('input[name="selected_address"]');
    const addressInput = document.getElementById('address_id');

    addressRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            addressInput.value = radio.value;
        });
    });

    /* PAYMENT METHOD TOGGLE */
    const paymentOptions = document.querySelectorAll('.payment-option');
    const paymentDetails = document.querySelectorAll('.payment-details');

    paymentOptions.forEach(option => {
        option.addEventListener('click', () => {
            const radio = option.querySelector('input[type="radio"]');
            radio.checked = true;

            // Reset styles
            paymentOptions.forEach(o => o.classList.remove('selected'));
            paymentDetails.forEach(d => d.classList.remove('active'));

            option.classList.add('selected');

            const method = option.dataset.method;
            const detailBox = document.getElementById(method + '-details');
            if (detailBox) {
                detailBox.classList.add('active');
            }
        });
    });
     // Credit Card Number Formatting
    $('#card-number').on('input', function() {
        let value = $(this).val().replace(/\s/g, '');
        value = value.replace(/\D/g, ''); // Remove non-digits
        value = value.substring(0, 16); // Limit to 16 digits
        
        // Add space every 4 digits
        let formatted = value.match(/.{1,4}/g);
        $(this).val(formatted ? formatted.join(' ') : '');
        
        // Visual feedback
        if (value.length === 16) {
            $(this).css('border-color', '#4caf50');
        } else {
            $(this).css('border-color', '#ddd');
        }
    });
    
    // Expiry Date Formatting and Validation
    $('#card-expiry').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        $(this).val(value);
        
        // Validate expiry date
        if (value.length === 5) {
            const isValid = validateExpiryDate(value);
            if (isValid) {
                $(this).css('border-color', '#4caf50');
                $(this).siblings('.error-msg').remove();
            } else {
                $(this).css('border-color', '#f44336');
                if (!$(this).siblings('.error-msg').length) {
                    $(this).after('<div class="error-msg" style="color: #f44336; font-size: 0.85rem; margin-top: 0.3rem;">Invalid or expired date</div>');
                }
            }
        } else {
            $(this).css('border-color', '#ddd');
            $(this).siblings('.error-msg').remove();
        }
    });
    
    // CVV Validation
    $('#card-cvv').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = value.substring(0, 3);
        $(this).val(value);
        
        if (value.length === 3) {
            $(this).css('border-color', '#4caf50');
        } else {
            $(this).css('border-color', '#ddd');
        }
    });
    
    // Cardholder Name Validation
    $('#card-name').on('input', function() {
        let value = $(this).val().toUpperCase();
        // Allow only letters and spaces
        value = value.replace(/[^A-Z\s]/g, '');
        $(this).val(value);
        
        if (value.length >= 3) {
            $(this).css('border-color', '#4caf50');
        } else {
            $(this).css('border-color', '#ddd');
        }
    });
    


     // Bank Selection
    $('.bank-option').on('click', function() {
        $('.bank-option').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
    
    // Form Submission Validation
    $('#checkout-form').on('submit', function(e) {
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        
        // Validate Credit Card
        if (paymentMethod === 'Credit Card') {
            const cardNumber = $('#card-number').val().replace(/\s/g, '');
            const cardName = $('#card-name').val().trim();
            const cardExpiry = $('#card-expiry').val();
            const cardCvv = $('#card-cvv').val();
            
            if (cardNumber.length !== 16) {
                e.preventDefault();
                alert('Please enter a valid 16-digit card number');
                $('#card-number').focus();
                return false;
            }
            
            if (cardName.length < 3) {
                e.preventDefault();
                alert('Please enter the cardholder name');
                $('#card-name').focus();
                return false;
            }
            
            if (!validateExpiryDate(cardExpiry)) {
                e.preventDefault();
                alert('Please enter a valid expiry date (MM/YY). Card must not be expired and month must be between 01-12.');
                $('#card-expiry').focus();
                return false;
            }
            
            if (cardCvv.length !== 3) {
                e.preventDefault();
                alert('Please enter a valid 3-digit CVV');
                $('#card-cvv').focus();
                return false;
            }
        }
        
        // Validate Online Banking
        if (paymentMethod === 'Online Banking') {
            const selectedBank = $('input[name="bank"]:checked').val();
            if (!selectedBank) {
                e.preventDefault();
                alert('Please select a bank');
                return false;
            }
        }
        
        // Show loading state
        $('#submit-btn').prop('disabled', true).text('Processing...');
    });
    
    // Expiry Date Validation Function
    function validateExpiryDate(expiry) {
        if (expiry.length !== 5 || !expiry.includes('/')) {
            return false;
        }
        
        const [month, year] = expiry.split('/');
        const monthNum = parseInt(month, 10);
        const yearNum = parseInt(year, 10);
        
        // Validate month is between 01-12 (not 00)
        if (monthNum < 1 || monthNum > 12) {
            return false;
        }
        
        // Get current date
        const now = new Date();
        const currentYear = now.getFullYear() % 100; // Get last 2 digits
        const currentMonth = now.getMonth() + 1; // getMonth() is 0-indexed
        
        // Check if card is expired
        if (yearNum < currentYear) {
            return false;
        }
        
        if (yearNum === currentYear && monthNum < currentMonth) {
            return false;
        }
        
        return true;
    }

    /* ADD ADDRESS BUTTON */
    const addAddressBtn = document.getElementById('add-address-btn');
    if (addAddressBtn) {
        addAddressBtn.addEventListener('click', () => {
            window.location.href = '/page/manage_address.php?action=add&redirect=checkout';
        });
    }

});

/* DELETE ADDRESS FUNCTION */
function deleteAddress(addressId) {
    if (confirm('Are you sure you want to delete this address?')) {
        // Show loading indicator
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Deleting...';
        btn.disabled = true;
        
        // Send delete request
        fetch('/page/manage_address.php?action=delete&id=' + addressId + '&redirect=checkout', {
            method: 'GET',
        })
        .then(response => {
            // Redirect to checkout to refresh the page
            window.location.href = '/page/checkout.php';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete address. Please try again.');
            btn.textContent = originalText;
            btn.disabled = false;
        });
    }
}
</script>



<?php include '../_foot.php'; ?>