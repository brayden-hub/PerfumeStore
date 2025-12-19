<?php
require '../_base.php';

// Security Check: Must have valid order_id from payment success
if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    redirect('/');
}

$order_id = get('order_id');
$user_id = $_SESSION['user_id'];

// Verify order belongs to user and exists
$stmt = $_db->prepare("
    SELECT o.*, u.name as CustomerName, u.email,
           ua.RecipientName, ua.PhoneNumber, ua.AddressLine1, ua.AddressLine2,
           ua.City, ua.State, ua.PostalCode
    FROM `order` o
    JOIN order_status os ON o.OrderID = os.OrderID
    JOIN user u ON o.UserID = u.userID
    LEFT JOIN user_address ua ON o.ShippingAddressID = ua.AddressID
    WHERE o.OrderID = ? AND o.UserID = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/');
}

// Fetch order items for the receipt
$stmt = $_db->prepare("
    SELECT po.*, p.ProductName, p.Series
    FROM productorder po
    JOIN product p ON po.ProductID = p.ProductID
    WHERE po.OrderID = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Calculate total amount
$stmt = $_db->prepare("SELECT SUM(TotalPrice) FROM productorder WHERE OrderID = ?");
$stmt->execute([$order_id]);
$subtotal = $stmt->fetchColumn() ?: 0;
$gift_wrap_cost = $order->GiftWrapCost ?? 0;
$shipping_fee = $order->ShippingFee ?? 0;
$voucher_discount = $order->VoucherDiscount ?? 0;
$total = $subtotal + $gift_wrap_cost + $shipping_fee - $voucher_discount;

// Send E-Receipt email (if not already sent)
if (!isset($_SESSION['receipt_sent_' . $order_id])) {
    try {
        $m = get_mail();
        $m->addAddress($order->email, $order->CustomerName);
        $m->Subject = "E-Receipt - Order #$order_id | Nº9 Perfume";
        
        // Build items table for email
        $items_html = '';
        foreach ($items as $item) {
            $item_total = number_format($item->TotalPrice, 2);
            $item_price = number_format($item->TotalPrice / $item->Quantity, 2);
            
            // Show price if not hidden (gift option)
            if ($order->HidePrice) {
                $items_html .= "
                <tr style='border-bottom: 1px solid #eee;'>
                    <td style='padding: 12px 8px; color: #333;'>{$item->ProductName}</td>
                    <td style='padding: 12px 8px; text-align: center; color: #666;'>{$item->Quantity}</td>
                    <td style='padding: 12px 8px; text-align: right; color: #999; font-style: italic;'>Hidden</td>
                </tr>";
            } else {
                $items_html .= "
                <tr style='border-bottom: 1px solid #eee;'>
                    <td style='padding: 12px 8px; color: #333;'>{$item->ProductName}</td>
                    <td style='padding: 12px 8px; text-align: center; color: #666;'>{$item->Quantity}</td>
                    <td style='padding: 12px 8px; text-align: right; font-weight: 600; color: #333;'>RM {$item_total}</td>
                </tr>";
            }
        }
        
        // Build cost breakdown (hide if gift option enabled)
        $cost_breakdown = '';
        if (!$order->HidePrice) {
            $cost_breakdown = "
            <tr style='background: #f9f9f9;'>
                <td colspan='2' style='padding: 10px 8px; text-align: right; color: #666;'>Subtotal:</td>
                <td style='padding: 10px 8px; text-align: right; font-weight: 600;'>RM " . number_format($subtotal, 2) . "</td>
            </tr>";
            
            if ($gift_wrap_cost > 0) {
                $cost_breakdown .= "
                <tr style='background: #f9f9f9;'>
                    <td colspan='2' style='padding: 10px 8px; text-align: right; color: #666;'>🎁 Gift Wrapping:</td>
                    <td style='padding: 10px 8px; text-align: right; color: #D4AF37; font-weight: 600;'>RM " . number_format($gift_wrap_cost, 2) . "</td>
                </tr>";
            }
            
            $cost_breakdown .= "
            <tr style='background: #f9f9f9;'>
                <td colspan='2' style='padding: 10px 8px; text-align: right; color: #666;'>Shipping:</td>
                <td style='padding: 10px 8px; text-align: right; font-weight: 600;'>" . ($shipping_fee > 0 ? "RM " . number_format($shipping_fee, 2) : "<span style='color: #4caf50;'>FREE</span>") . "</td>
            </tr>";
            
            if ($voucher_discount > 0) {
                $cost_breakdown .= "
                <tr style='background: linear-gradient(135deg, #e8f5e9 0%, #f9f9f9 100%);'>
                    <td colspan='2' style='padding: 10px 8px; text-align: right; color: #2e7d32; font-weight: 600;'>🎟️ Voucher Discount:</td>
                    <td style='padding: 10px 8px; text-align: right; color: #2e7d32; font-weight: 700;'>-RM " . number_format($voucher_discount, 2) . "</td>
                </tr>";
            }
            
            $cost_breakdown .= "
            <tr style='background: linear-gradient(135deg, #fffbf0 0%, #fff 100%); border-top: 2px solid #D4AF37;'>
                <td colspan='2' style='padding: 15px 8px; text-align: right; font-weight: bold; font-size: 16px;'>Grand Total:</td>
                <td style='padding: 15px 8px; text-align: right; font-weight: bold; font-size: 18px; color: #D4AF37;'>RM " . number_format($total, 2) . "</td>
            </tr>";
        } else {
            // If prices are hidden (gift mode)
            $cost_breakdown = "
            <tr style='background: #fffbf0; border-top: 2px solid #D4AF37;'>
                <td colspan='3' style='padding: 15px 8px; text-align: center; color: #666; font-style: italic;'>
                    🔒 Price information hidden as per gift option
                </td>
            </tr>";
        }
        
        // Gift message section
        $gift_section = '';
        if (!empty($order->GiftMessage)) {
            $gift_section = "
            <div style='background: linear-gradient(135deg, #fffbf0 0%, #fff 100%); padding: 20px; margin: 20px 0; border-radius: 8px; border: 2px solid #D4AF37;'>
                <h3 style='margin: 0 0 10px 0; color: #333; font-size: 16px;'>🎁 Gift Message</h3>
                <p style='margin: 0; font-style: italic; color: #666; line-height: 1.6;'>
                    \"" . htmlspecialchars($order->GiftMessage) . "\"
                </p>
            </div>";
        }
        
        $email_body = "
        <div style='font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; background: #f5f5f5; padding: 20px;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #000 0%, #1a1a1a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='color: #D4AF37; margin: 0; font-size: 28px; letter-spacing: 2px;'>Nº9 PERFUME</h1>
                <p style='color: #fff; margin: 10px 0 0 0; font-size: 14px;'>Electronic Receipt</p>
            </div>
            
            <!-- Main Content -->
            <div style='background: #fff; padding: 30px; border-radius: 0 0 10px 10px;'>
                <!-- Thank You Message -->
                <div style='text-align: center; margin-bottom: 30px;'>
                    <div style='font-size: 48px; margin-bottom: 10px;'>✨</div>
                    <h2 style='color: #333; margin: 0 0 10px 0;'>Thank You for Your Order!</h2>
                    <p style='color: #666; margin: 0;'>Your scent journey begins here.</p>
                </div>
                
                <!-- Order Information -->
                <div style='background: #f9f9f9; padding: 20px; margin-bottom: 25px; border-radius: 8px; border-left: 4px solid #D4AF37;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: 600;'>Order ID:</td>
                            <td style='padding: 8px 0; text-align: right; color: #D4AF37; font-weight: bold; font-size: 16px;'>$order_id</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: 600;'>Order Date:</td>
                            <td style='padding: 8px 0; text-align: right; color: #333;'>" . date('d F Y, g:i A', strtotime($order->PurchaseDate)) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: 600;'>Payment Method:</td>
                            <td style='padding: 8px 0; text-align: right; color: #333;'>{$order->PaymentMethod}</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Shipping Address -->
                <div style='background: #f9f9f9; padding: 20px; margin-bottom: 25px; border-radius: 8px;'>
                    <h3 style='margin: 0 0 15px 0; color: #333; font-size: 16px; display: flex; align-items: center;'>
                        📦 Shipping Address
                    </h3>
                    <p style='margin: 0; line-height: 1.8; color: #555;'>
                        <strong>{$order->RecipientName}</strong><br>
                        {$order->PhoneNumber}<br>
                        {$order->AddressLine1}" . ($order->AddressLine2 ? ", {$order->AddressLine2}" : "") . "<br>
                        {$order->City}, {$order->State} {$order->PostalCode}
                    </p>
                </div>
                
                $gift_section
                
                <!-- Order Items -->
                <h3 style='margin: 0 0 15px 0; color: #333; font-size: 16px;'>🛍️ Order Items</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fff; border: 1px solid #eee; border-radius: 8px; overflow: hidden;'>
                    <thead>
                        <tr style='background: #000;'>
                            <th style='padding: 12px 8px; text-align: left; color: #D4AF37; font-weight: 600;'>Product</th>
                            <th style='padding: 12px 8px; text-align: center; color: #D4AF37; font-weight: 600;'>Qty</th>
                            <th style='padding: 12px 8px; text-align: right; color: #D4AF37; font-weight: 600;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        $items_html
                        $cost_breakdown
                    </tbody>
                </table>
                
                <!-- Estimated Delivery -->
                <div style='text-align: center; padding: 20px; background: linear-gradient(135deg, #e3f2fd 0%, #fff 100%); border-radius: 8px; margin: 25px 0;'>
                    <p style='margin: 0; color: #1976d2; font-weight: 600;'>
                        📅 Estimated Delivery: <strong>3-5 Business Days</strong>
                    </p>
                </div>
                
                <!-- Action Buttons -->
                <div style='text-align: center; margin: 30px 0 20px 0;'>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/page/order_detail_member.php?id=$order_id' 
                       style='display: inline-block; padding: 15px 40px; background: #000; color: #D4AF37; 
                              text-decoration: none; border-radius: 5px; font-weight: bold; 
                              letter-spacing: 1px; margin: 0 10px; border: 2px solid #D4AF37;'>
                        TRACK ORDER
                    </a>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/page/product.php' 
                       style='display: inline-block; padding: 15px 40px; background: #fff; color: #000; 
                              text-decoration: none; border-radius: 5px; font-weight: bold; 
                              letter-spacing: 1px; margin: 0 10px; border: 2px solid #000;'>
                        CONTINUE SHOPPING
                    </a>
                </div>
                
                <!-- Footer -->
                <div style='text-align: center; padding-top: 20px; border-top: 2px solid #eee; margin-top: 30px;'>
                    <p style='color: #999; font-size: 13px; line-height: 1.6; margin: 0 0 10px 0;'>
                        This is an automated email. Please do not reply to this message.<br>
                        If you have any questions, contact us at support@no9perfume.com
                    </p>
                    <p style='color: #666; font-size: 14px; margin: 10px 0 0 0;'>
                        Best regards,<br>
                        <strong style='color: #D4AF37;'>Nº9 Perfume Team</strong>
                    </p>
                </div>
            </div>
            
            <!-- Footer Note -->
            <div style='text-align: center; padding: 20px; color: #999; font-size: 12px;'>
                <p style='margin: 0;'>© 2024 Nº9 Perfume. All rights reserved.</p>
            </div>
        </div>";
        
        $m->Body = $email_body;
        $m->isHTML(true);
        $m->send();
        
        $_SESSION['receipt_sent_' . $order_id] = true;
    } catch (Exception $e) {
        error_log('E-Receipt email failed: ' . $e->getMessage());
    }
}

$_title = 'Order Confirmed - Nº9 Perfume';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?></title>
    <link rel="icon" href="/public/images/icon.png">
    <link rel="stylesheet" href="/public/css/order_confirmation.css">
</head>
<body>
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <svg class="success-icon" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <circle cx="60" cy="60" r="50" fill="#D4AF37" opacity="0.2"/>
                <path d="M35 60 L50 75 L85 40" stroke="#D4AF37" stroke-width="6" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <animate attributeName="stroke-dasharray" from="0, 100" to="100, 0" dur="0.8s" fill="freeze"/>
                </path>
            </svg>
            <h1>Order Confirmed</h1>
            <p class="subtitle">Thank you, <?= htmlspecialchars($order->CustomerName) ?>. Your scent is on its way.</p>
            <div style="background: #e8f5e9; padding: 1rem; border-radius: 8px; margin-top: 1.5rem; border: 1px solid #4caf50;">
                <p style="margin: 0; color: #2e7d32; font-weight: 600;">
                    ✉️ E-Receipt has been sent to <?= htmlspecialchars($order->email) ?>
                </p>
            </div>
        </div>
        
        <!-- Progress Timeline -->
        <div class="timeline">
            <div class="timeline-step active">
                <div class="timeline-node">✓</div>
                <div class="timeline-label">Order Placed</div>
            </div>
            <div class="timeline-step active">
                <div class="timeline-node">✓</div>
                <div class="timeline-label">Processing</div>
            </div>
            <div class="timeline-step">
                <div class="timeline-node">3</div>
                <div class="timeline-label">Shipped</div>
            </div>
            <div class="timeline-step">
                <div class="timeline-node">4</div>
                <div class="timeline-label">Delivered</div>
            </div>
        </div>
        <div class="estimated-delivery">
            Estimated Delivery: 3-5 Business Days
        </div>
        
        <!-- Receipt Card -->
        <div class="receipt-card">
            <div class="receipt-header">
                <div class="order-id">Order ID: <span><?= htmlspecialchars($order_id) ?></span></div>
                <div class="order-date"><?= date('l, d F Y', strtotime($order->PurchaseDate)) ?></div>
            </div>
            
            <!-- Shipping Address -->
            <div class="info-section">
                <h3>📦 Ship to</h3>
                <div class="address-text">
                    <strong><?= htmlspecialchars($order->RecipientName) ?></strong><br>
                    <?= htmlspecialchars($order->PhoneNumber) ?><br>
                    <?= htmlspecialchars($order->AddressLine1) ?>
                    <?= $order->AddressLine2 ? ', ' . htmlspecialchars($order->AddressLine2) : '' ?><br>
                    <?= htmlspecialchars($order->City) ?>, <?= htmlspecialchars($order->State) ?> <?= htmlspecialchars($order->PostalCode) ?>
                </div>
            </div>
            
            <!-- Gift Message -->
            <?php if (!empty($order->GiftMessage)): ?>
                <div class="gift-box">
                    <h3>Gift Message Included</h3>
                    <div class="gift-message">
                        "<?= htmlspecialchars($order->GiftMessage) ?>"
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Amount Summary -->
            <div class="amount-summary">
                <div class="amount-row">
                    <span>Subtotal:</span>
                    <span>RM <?= number_format($subtotal, 2) ?></span>
                </div>
                <?php if ($gift_wrap_cost > 0): ?>
                <div class="amount-row">
                    <span>Gift Wrapping:</span>
                    <span>RM <?= number_format($gift_wrap_cost, 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="amount-row">
                    <span>Shipping:</span>
                    <span>RM <?= number_format($shipping_fee, 2) ?></span>
                </div>
                <?php if ($voucher_discount > 0): ?>
                <div class="amount-row" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 0.8rem; border-radius: 8px; margin: 0.5rem 0;">
                    <span style="color: #2e7d32; font-weight: 600;">🎟️ Voucher Discount:</span>
                    <span style="color: #2e7d32; font-weight: 700;">-RM <?= number_format($voucher_discount, 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="amount-row amount-total">
                    <span>Total Amount:</span>
                    <span>RM <?= number_format($total, 2) ?></span>
                </div>
                <?php if ($order->HidePrice): ?>
                <div class="privacy-note">
                    🔒 Receipt without pricing will be included in the box
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="/page/order_detail_member.php?id=<?= $order_id ?>" class="btn btn-primary">
                    Track My Order
                </a>
                <a href="/page/product.php" class="btn btn-secondary">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</body>
</html>