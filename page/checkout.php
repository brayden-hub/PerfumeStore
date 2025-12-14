<?php
require '../_base.php';

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

$shipping_fee = 30.00;
$total = $subtotal + $gift_wrap_cost + $shipping_fee;

if (is_post()) {
    $payment_method = post('payment_method');
    $address_id = post('address_id');
    
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
            
            // Create order with address
            $gift_wrap_value = $gift_enabled ? $gift_packaging : null;
            $gift_message_value = ($gift_enabled && !empty($gift_message)) ? $gift_message : null;
            
            $stmt = $_db->prepare("
                INSERT INTO `order` 
                (OrderID, UserID, ShippingAddressID, PurchaseDate, PaymentMethod, GiftWrap, GiftMessage, HidePrice, GiftWrapCost, ShippingFee) 
                VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $order_id, 
                $user_id,
                $address_id,
                $payment_method,
                $gift_wrap_value,
                $gift_message_value,
                $hide_price,
                $gift_wrap_cost,
                $shipping_fee
            ]);
            
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
            redirect('/page/order_detail_member.php?id=' . $order_id);
            
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
                    </div>
                <?php else: ?>
                    <div id="address-list" style="display: grid; gap: 1rem;">
                        <?php foreach ($addresses as $addr): ?>
                            <label class="address-card" style="display: flex; gap: 1rem; padding: 1.2rem; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
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
                <div class="gift-summary-box" style="margin-top: 2rem;">
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
        
        <!-- Right: Payment Form -->
        <div style="background: #f9f9f9; padding: 2rem; border-radius: 12px; align-self: flex-start; position: sticky; top: 100px;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.3rem; font-weight: 400;">Payment Details</h3>
            
            <form method="post" id="checkout-form">
                <input type="hidden" name="address_id" id="address_id" value="<?= $addresses[0]->AddressID ?? '' ?>">
                
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: #333;">Select Payment Method</label>
                    
                    <label style="display: flex; align-items: center; padding: 1rem; margin-bottom: 0.8rem; background: #fff; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        <input type="radio" name="payment_method" value="Credit Card" required style="margin-right: 0.8rem;">
                        <span>💳 Credit Card</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; padding: 1rem; margin-bottom: 0.8rem; background: #fff; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        <input type="radio" name="payment_method" value="Online Banking" required style="margin-right: 0.8rem;">
                        <span>🏦 Online Banking</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; padding: 1rem; margin-bottom: 0.8rem; background: #fff; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        <input type="radio" name="payment_method" value="E-Wallet" required style="margin-right: 0.8rem;">
                        <span>📱 E-Wallet</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; padding: 1rem; margin-bottom: 0.8rem; background: #fff; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        <input type="radio" name="payment_method" value="Cash on Delivery" required style="margin-right: 0.8rem;">
                        <span>💵 Cash on Delivery</span>
                    </label>
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
                        <span>RM <?= number_format($shipping_fee, 2) ?></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 1.4rem; font-weight: bold; color: #D4AF37; margin-top: 1rem;">
                        <span>Total:</span>
                        <span>RM <?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <button type="submit" style="width: 100%; padding: 1.2rem; background: #000; color: #D4AF37; border: none; font-size: 1.1rem; font-weight: bold; cursor: pointer; border-radius: 8px; transition: all 0.3s ease;">
                    Complete Order
                </button>
                
                <a href="/page/cart.php" style="display: block; text-align: center; margin-top: 1rem; color: #666; text-decoration: none;">
                    ← Back to Cart
                </a>
            </form>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div id="address-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <div style="background: #fff; border-radius: 12px; max-width: 600px; width: 100%; padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Add New Address</h3>
                <button id="close-modal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <form id="address-form" method="post" action="/api/add_address.php">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Address Label *</label>
                        <input type="text" name="label" required placeholder="e.g., Home, Office" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Recipient Name *</label>
                            <input type="text" name="recipient_name" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Phone Number *</label>
                            <input type="text" name="phone" required placeholder="e.g., 0123456789" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Address Line 1 *</label>
                        <input type="text" name="address1" required placeholder="Street address" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Address Line 2</label>
                        <input type="text" name="address2" placeholder="Apartment, suite, etc. (optional)" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">City *</label>
                            <input type="text" name="city" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Postal Code *</label>
                            <input type="text" name="postal_code" required placeholder="e.g., 50000" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">State *</label>
                        <select name="state" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="">Select State</option>
                            <option>Johor</option>
                            <option>Kedah</option>
                            <option>Kelantan</option>
                            <option>Melaka</option>
                            <option>Negeri Sembilan</option>
                            <option>Pahang</option>
                            <option>Penang</option>
                            <option>Perak</option>
                            <option>Perlis</option>
                            <option>Sabah</option>
                            <option>Sarawak</option>
                            <option>Selangor</option>
                            <option>Terengganu</option>
                            <option>Kuala Lumpur</option>
                            <option>Labuan</option>
                            <option>Putrajaya</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_default" value="1">
                            <span>Set as default address</span>
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" id="cancel-btn" style="flex: 1; padding: 1rem; background: #eee; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="flex: 1; padding: 1rem; background: #D4AF37; color: #000; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Address selection
    $('input[name="selected_address"]').on('change', function() {
        $('#address_id').val($(this).val());
        $('.address-card').css('border-color', '#eee');
        $(this).closest('.address-card').css('border-color', '#D4AF37');
    });
    
    // Highlight default selected address
    $('input[name="selected_address"]:checked').closest('.address-card').css('border-color', '#D4AF37');
    
    // Modal controls
    $('#add-address-btn').click(() => $('#address-modal').fadeIn(200));
    $('#close-modal, #cancel-btn').click(() => $('#address-modal').fadeOut(200));
    
    // Add address form
    $('#address-form').on('submit', function(e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Failed to add address');
            }
        }, 'json').fail(() => alert('Connection error'));
    });
});
</script>

<?php include '../_foot.php'; ?>