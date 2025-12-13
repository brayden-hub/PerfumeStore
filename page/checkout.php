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

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item->Price * $item->Quantity;
}

// Get gift options from session (set from cart page)
$gift_wrap_cost = 0.00;
$gift_enabled = false;
$gift_packaging = 'standard';
$gift_message = '';
$hide_price = 0;

if (isset($_SESSION['gift_options'])) {
    $gift_data = $_SESSION['gift_options'];
    
    // Convert boolean strings to actual booleans
    $gift_enabled = ($gift_data['enabled'] === true || $gift_data['enabled'] === 'true' || $gift_data['enabled'] === 1);
    $gift_packaging = $gift_data['packaging'] ?? 'standard';
    $gift_message = trim($gift_data['message'] ?? '');
    $hide_price = ($gift_data['hidePrice'] === true || $gift_data['hidePrice'] === 'true' || $gift_data['hidePrice'] === 1) ? 1 : 0;
    
    if ($gift_enabled && $gift_packaging === 'luxury') {
        $gift_wrap_cost = 5.00;
    }
}

// Debug output (add ?debug=1 to URL to see)
if (isset($_GET['debug'])) {
    echo '<div style="background:#000;color:#0f0;padding:1rem;margin:1rem;font-family:monospace;">';
    echo "<strong>SESSION DATA:</strong><br>";
    echo "Raw session: " . print_r($_SESSION['gift_options'] ?? 'NOT SET', true) . "<br><br>";
    echo "<strong>PROCESSED VALUES:</strong><br>";
    echo "Gift Enabled: " . ($gift_enabled ? 'YES' : 'NO') . "<br>";
    echo "Packaging: $gift_packaging<br>";
    echo "Message: " . ($gift_message ?: '(empty)') . "<br>";
    echo "Hide Price: " . ($hide_price ? 'YES' : 'NO') . "<br>";
    echo "Gift Wrap Cost: RM " . number_format($gift_wrap_cost, 2) . "<br>";
    echo "Subtotal: RM " . number_format($subtotal, 2) . "<br>";
    echo "Total: RM " . number_format($subtotal + $gift_wrap_cost, 2) . "<br>";
    echo '</div>';
}

$total = $subtotal + $gift_wrap_cost;

if (is_post()) {
    $payment_method = post('payment_method');
    
    if (!in_array($payment_method, ['Credit Card', 'Online Banking', 'E-Wallet', 'Cash on Delivery'])) {
        temp('error', 'Please select a payment method');
    } else {
        try {
            $_db->beginTransaction();
            
            // Generate OrderID
            $stmt = $_db->query("SELECT OrderID FROM `order` ORDER BY OrderID DESC LIMIT 1");
            $last = $stmt->fetch();
            $next_num = $last ? (int)substr($last->OrderID, 1) + 1 : 1;
            $order_id = 'O' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
            
            // Create order with gift options
            // Prepare values - make sure types are correct for database
            $gift_wrap_value = null;
            $gift_message_value = null;
            
            if ($gift_enabled) {
                $gift_wrap_value = $gift_packaging; // 'standard' or 'luxury'
                if (!empty($gift_message)) {
                    $gift_message_value = $gift_message;
                }
            }
            
            $stmt = $_db->prepare("
                INSERT INTO `order` 
                (OrderID, UserID, PurchaseDate, PaymentMethod, GiftWrap, GiftMessage, HidePrice, GiftWrapCost) 
                VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)
            ");
            
            $params = [
                $order_id, 
                $user_id, 
                $payment_method,
                $gift_wrap_value,          // NULL or 'standard'/'luxury'
                $gift_message_value,       // NULL or message text
                $hide_price,               // 0 or 1
                $gift_wrap_cost            // 0.00 or 5.00
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $error_info = $stmt->errorInfo();
                error_log("Order insert failed: " . print_r($error_info, true));
                throw new Exception("Failed to create order: " . $error_info[2]);
            }
            
            // Create order items and update stock
            foreach ($cart_items as $item) {
                // Generate ProductOrderID
                $stmt = $_db->query("SELECT ProductOrderID FROM productorder ORDER BY ProductOrderID DESC LIMIT 1");
                $last = $stmt->fetch();
                $next_num = $last ? (int)substr($last->ProductOrderID, 2) + 1 : 1;
                $po_id = 'PO' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
                
                $item_subtotal = $item->Price * $item->Quantity;
                
                // Insert product order
                $stmt = $_db->prepare("INSERT INTO productorder (ProductOrderID, OrderID, ProductID, Quantity, TotalPrice) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$po_id, $order_id, $item->ProductID, $item->Quantity, $item_subtotal]);
                
                // Update stock
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

$_title = 'Checkout - N°9 Perfume';
include '../_head.php';
?>

<style>
.gift-summary-box {
    background: linear-gradient(135deg, #fffbf0 0%, #fff 100%);
    border: 2px solid #D4AF37;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
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
    border-bottom: 1px solid #f0f0f0;
}

.gift-detail-row:last-child {
    border-bottom: none;
}

.gift-message-preview {
    background: #fff;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
    font-style: italic;
    color: #666;
    border-left: 3px solid #D4AF37;
}
</style>

<div class="container" style="margin-top: 100px; min-height: 60vh; max-width: 1200px; padding: 0 2rem;">
    <!-- Progress Bar -->
    <div class="progress-bar" style="margin-bottom: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; background: #eee; color: #666; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">1</div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">Shopping Cart</p>
            </div>
            <div style="flex: 1; height: 2px; background: #eee; margin: 0 1rem;"></div>
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; background: #D4AF37; color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">2</div>
                <p style="margin: 0; font-size: 0.9rem;">Checkout</p>
            </div>
            <div style="flex: 1; height: 2px; background: #eee; margin: 0 1rem;"></div>
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; background: #eee; color: #666; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">3</div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">Payment</p>
            </div>
            <div style="flex: 1; height: 2px; background: #eee; margin: 0 1rem;"></div>
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; background: #eee; color: #666; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">4</div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">Done</p>
            </div>
        </div>
    </div>

    <h2 style="margin-bottom: 2rem; font-weight: 300; font-size: 2rem;">Checkout</h2>
    
    <?php if ($err = temp('error')): ?>
        <div style="padding: 1rem; background: #ffe6e6; color: #d00; border-radius: 8px; margin-bottom: 1rem;">
            ⚠ <?= $err ?>
        </div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 3rem;">
        <!-- Order Summary -->
        <div>
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
        
        <!-- Payment Form -->
        <div style="background: #f9f9f9; padding: 2rem; border-radius: 12px; align-self: flex-start; position: sticky; top: 100px;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.3rem; font-weight: 400;">Payment Details</h3>
            
            <form method="post">
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
                        <span><?= $total >= 300 ? 'FREE' : 'Calculated at delivery' ?></span>
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

<style>
    input[type="radio"]:checked + span {
        font-weight: 600;
        color: #D4AF37;
    }
    
    label:has(input[type="radio"]:checked) {
        border-color: #D4AF37 !important;
        background: #fffbf0 !important;
    }
    
    label:has(input[type="radio"]):hover {
        border-color: #D4AF37;
        background: #fafafa;
    }
</style>

<?php include '../_foot.php'; ?>