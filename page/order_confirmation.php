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

// Calculate total amount
$stmt = $_db->prepare("SELECT SUM(TotalPrice) FROM productorder WHERE OrderID = ?");
$stmt->execute([$order_id]);
$subtotal = $stmt->fetchColumn() ?: 0;
$gift_wrap_cost = $order->GiftWrapCost ?? 0;
$shipping_fee = $order->ShippingFee ?? 0;
$total = $subtotal + $gift_wrap_cost + $shipping_fee;

// Send confirmation email (if not already sent)
if (!isset($_SESSION['email_sent_' . $order_id])) {
    try {
        $m = get_mail();
        $m->addAddress($order->email, $order->CustomerName);
        $m->Subject = "Order Confirmation - N°9 Perfume #$order_id";
        
        $email_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h1 style='color: #D4AF37; text-align: center;'>Order Confirmed</h1>
            <p>Dear {$order->CustomerName},</p>
            <p>Thank you for your order. Your scent is on its way.</p>
            
            <div style='background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 8px;'>
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> $order_id</p>
                <p><strong>Order Date:</strong> " . date('d F Y', strtotime($order->PurchaseDate)) . "</p>
                <p><strong>Total Amount:</strong> RM " . number_format($total, 2) . "</p>
            </div>
            
            <div style='background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 8px;'>
                <h3>Shipping Address</h3>
                <p>{$order->RecipientName}<br>
                {$order->PhoneNumber}<br>
                {$order->AddressLine1}" . ($order->AddressLine2 ? ", {$order->AddressLine2}" : "") . "<br>
                {$order->City}, {$order->State} {$order->PostalCode}</p>
            </div>";
        
        if (!empty($order->GiftMessage)) {
            $email_body .= "
            <div style='background: #fffbf0; padding: 20px; margin: 20px 0; border-radius: 8px; border: 2px solid #D4AF37;'>
                <h3>🎁 Gift Message</h3>
                <p style='font-style: italic; color: #666;'>\"{$order->GiftMessage}\"</p>
            </div>";
        }
        
        $email_body .= "
            <p style='margin-top: 30px;'>Estimated delivery: 3-5 business days</p>
            <p>Best regards,<br>N°9 Perfume Team</p>
        </div>";
        
        $m->Body = $email_body;
        $m->isHTML(true);
        $m->send();
        
        $_SESSION['email_sent_' . $order_id] = true;
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $e->getMessage());
    }
}

$_title = 'Order Confirmed - N°9 Perfume';
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