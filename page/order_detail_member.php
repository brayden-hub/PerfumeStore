<?php
require '../_base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Member') {
    redirect('/page/login.php');
}

$order_id = get('id');
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    redirect('/page/order_history.php');
}

// Fetch order details
$stmt = $_db->prepare("
    SELECT o.*, os.*, u.name as CustomerName, u.email, u.phone_number
    FROM `order` o
    JOIN order_status os ON o.OrderID = os.OrderID
    JOIN user u ON o.UserID = u.userID
    WHERE o.OrderID = ? AND o.UserID = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    temp('error', 'Order not found');
    redirect('/page/order_history.php');
}

// Fetch order items
$stmt = $_db->prepare("
    SELECT po.*, p.ProductName, p.Series, p.ProductID
    FROM productorder po
    JOIN product p ON po.ProductID = p.ProductID
    WHERE po.OrderID = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Calculate subtotal from items
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item->TotalPrice;
}

// Add gift wrap cost from order table
$gift_wrap_cost = $order->GiftWrapCost ?? 0;
$total = $subtotal + $gift_wrap_cost;

// Calculate estimated delivery if not set
if (empty($order->EstimatedDelivery) && $order->Status !== 'Delivered' && $order->Status !== 'Cancelled') {
    $order_date = new DateTime($order->PurchaseDate);
    $order_date->modify('+5 days'); // 5 business days
    $estimated_delivery = $order_date->format('Y-m-d');
} else {
    $estimated_delivery = $order->EstimatedDelivery;
}

// Status configuration
$status_config = [
    'Pending' => ['icon' => '⏳', 'color' => '#ff9800', 'bg' => '#fff3e0', 'step' => 1],
    'Processing' => ['icon' => '📦', 'color' => '#2196f3', 'bg' => '#e3f2fd', 'step' => 2],
    'Shipped' => ['icon' => '🚚', 'color' => '#9c27b0', 'bg' => '#f3e5f5', 'step' => 3],
    'Delivered' => ['icon' => '✅', 'color' => '#4caf50', 'bg' => '#e8f5e9', 'step' => 4],
    'Cancelled' => ['icon' => '❌', 'color' => '#f44336', 'bg' => '#ffebee', 'step' => 0]
];

$current_status = $status_config[$order->Status] ?? $status_config['Pending'];

$_title = 'Order Details - N°9 Perfume';
include '../_head.php';
?>

<style>
.status-tracker {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin: 2rem 0;
    padding: 2rem 0;
}

.status-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.status-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 0.8rem;
    border: 3px solid;
    transition: all 0.3s;
    background: #fff;
}

.status-step.active .status-icon {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.status-step.completed .status-icon {
    background: #4caf50;
    border-color: #4caf50;
    color: white;
}

.status-step.active .status-icon {
    border-color: currentColor;
    animation: pulse 2s infinite;
}

.status-step.pending .status-icon {
    border-color: #e0e0e0;
    color: #999;
    background: #f5f5f5;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(212, 175, 55, 0); }
}

.status-line {
    position: absolute;
    top: 30px;
    left: 0;
    right: 0;
    height: 3px;
    background: #e0e0e0;
    z-index: 1;
}

.status-line-progress {
    height: 100%;
    background: linear-gradient(90deg, #4caf50 0%, #D4AF37 100%);
    transition: width 0.5s ease;
}

.status-label {
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
    color: #666;
}

.status-step.active .status-label {
    color: #D4AF37;
    font-weight: 700;
}

.status-step.completed .status-label {
    color: #4caf50;
}

.status-date {
    font-size: 0.75rem;
    color: #999;
    margin-top: 0.3rem;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #666;
    font-weight: 500;
}

.info-value {
    font-weight: 600;
    color: #333;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
}

.gift-card {
    background: linear-gradient(135deg, #fffbf0 0%, #fff 100%);
    border: 2px solid #D4AF37;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
</style>

<script>
$(document).ready(function() {
    window.scrollTo(0, 0);
    
    // Auto refresh page every 2 minutes to check status updates
    setTimeout(function() {
        location.reload();
    }, 120000); // 2 minutes
});

if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}
</script>

<div class="container" style="margin-top: 100px; min-height: 60vh;">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="/page/order_history.php" style="color: #666; text-decoration: none; font-weight: 600;">
            ← Back to Order History
        </a>
        <div style="color: #999; font-size: 0.9rem;">
            Order ID: <strong style="color: #D4AF37;"><?= htmlspecialchars($order->OrderID) ?></strong>
        </div>
    </div>
    
    <!-- Current Status Badge -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="margin-bottom: 1rem; font-size: 2rem;">Order Status</h2>
        <div class="status-badge" style="background: <?= $current_status['bg'] ?>; color: <?= $current_status['color'] ?>; border: 2px solid <?= $current_status['color'] ?>;">
            <span style="font-size: 1.5rem;"><?= $current_status['icon'] ?></span>
            <span><?= htmlspecialchars($order->Status) ?></span>
        </div>
        <?php if (!empty($estimated_delivery) && $order->Status !== 'Delivered' && $order->Status !== 'Cancelled'): ?>
            <p style="margin-top: 1rem; color: #666; font-size: 0.95rem;">
                📅 Estimated Delivery: <strong><?= date('d F Y', strtotime($estimated_delivery)) ?></strong>
            </p>
        <?php endif; ?>
    </div>

    <?php if ($order->Status !== 'Cancelled'): ?>
    <!-- Status Tracker -->
    <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
        <div class="status-tracker">
            <div class="status-line">
                <div class="status-line-progress" style="width: <?= min(100, ($current_status['step'] - 1) * 33.33) ?>%;"></div>
            </div>
            
            <!-- Pending -->
            <div class="status-step <?= $current_status['step'] >= 1 ? 'completed' : 'pending' ?> <?= $order->Status === 'Pending' ? 'active' : '' ?>">
                <div class="status-icon" style="color: #ff9800; border-color: <?= $current_status['step'] >= 1 ? '#4caf50' : '#e0e0e0' ?>;">
                    <?= $current_status['step'] >= 1 ? '✓' : '⏳' ?>
                </div>
                <div class="status-label">Order Placed</div>
                <div class="status-date"><?= date('d M Y', strtotime($order->PurchaseDate)) ?></div>
            </div>
            
            <!-- Processing -->
            <div class="status-step <?= $current_status['step'] >= 2 ? 'completed' : 'pending' ?> <?= $order->Status === 'Processing' ? 'active' : '' ?>">
                <div class="status-icon" style="color: #2196f3; border-color: <?= $current_status['step'] >= 2 ? '#4caf50' : '#e0e0e0' ?>;">
                    <?= $current_status['step'] >= 2 ? '✓' : '📦' ?>
                </div>
                <div class="status-label">Processing</div>
                <?php if (!empty($order->ProcessedAt)): ?>
                    <div class="status-date"><?= date('d M Y', strtotime($order->ProcessedAt)) ?></div>
                <?php endif; ?>
            </div>
            
            <!-- Shipped -->
            <div class="status-step <?= $current_status['step'] >= 3 ? 'completed' : 'pending' ?> <?= $order->Status === 'Shipped' ? 'active' : '' ?>">
                <div class="status-icon" style="color: #9c27b0; border-color: <?= $current_status['step'] >= 3 ? '#4caf50' : '#e0e0e0' ?>;">
                    <?= $current_status['step'] >= 3 ? '✓' : '🚚' ?>
                </div>
                <div class="status-label">Shipped</div>
                <?php if (!empty($order->ShippedAt)): ?>
                    <div class="status-date"><?= date('d M Y', strtotime($order->ShippedAt)) ?></div>
                <?php endif; ?>
            </div>
            
            <!-- Delivered -->
            <div class="status-step <?= $current_status['step'] >= 4 ? 'completed' : 'pending' ?> <?= $order->Status === 'Delivered' ? 'active' : '' ?>">
                <div class="status-icon" style="color: #4caf50; border-color: <?= $current_status['step'] >= 4 ? '#4caf50' : '#e0e0e0' ?>;">
                    <?= $current_status['step'] >= 4 ? '✓' : '🎉' ?>
                </div>
                <div class="status-label">Delivered</div>
                <?php if (!empty($order->DeliveredAt)): ?>
                    <div class="status-date"><?= date('d M Y', strtotime($order->DeliveredAt)) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Cancelled Status -->
    <div style="background: #ffebee; border: 2px solid #f44336; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
        <h3 style="color: #f44336; margin-bottom: 0.5rem;">Order Cancelled</h3>
        <p style="color: #666;">This order has been cancelled and will not be processed.</p>
    </div>
    <?php endif; ?>

    <!-- Tracking Number -->
    <?php if (!empty($order->TrackingNumber) && $order->Status === 'Shipped'): ?>
    <div style="background: linear-gradient(135deg, #e3f2fd 0%, #fff 100%); border: 2px solid #2196f3; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="color: #2196f3; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📍</span> Tracking Information
        </h3>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.3rem;">Tracking Number:</div>
                <div style="font-size: 1.3rem; font-weight: bold; color: #2196f3; font-family: monospace;">
                    <?= htmlspecialchars($order->TrackingNumber) ?>
                </div>
            </div>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($order->TrackingNumber) ?>')" 
                    style="padding: 0.6rem 1.2rem; background: #2196f3; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                📋 Copy
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Order Information -->
        <div class="info-card">
            <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: #D4AF37; display: flex; align-items: center; gap: 0.5rem;">
                <span>📋</span> Order Information
            </h3>
            <div class="info-row">
                <span class="info-label">Order ID:</span>
                <span class="info-value" style="color: #D4AF37;"><?= htmlspecialchars($order->OrderID) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Date:</span>
                <span class="info-value"><?= date('d F Y, g:i A', strtotime($order->PurchaseDate)) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value">
                    <?php if ($order->PaymentMethod === 'Credit Card'): ?>💳<?php endif; ?>
                    <?php if ($order->PaymentMethod === 'Online Banking'): ?>🏦<?php endif; ?>
                    <?php if ($order->PaymentMethod === 'E-Wallet'): ?>📱<?php endif; ?>
                    <?php if ($order->PaymentMethod === 'Cash on Delivery'): ?>💵<?php endif; ?>
                    <?= htmlspecialchars($order->PaymentMethod) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value" style="color: <?= $current_status['color'] ?>;">
                    <?= $current_status['icon'] ?> <?= htmlspecialchars($order->Status) ?>
                </span>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="info-card">
            <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: #D4AF37; display: flex; align-items: center; gap: 0.5rem;">
                <span>👤</span> Customer Information
            </h3>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?= htmlspecialchars($order->CustomerName) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?= htmlspecialchars($order->email) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?= htmlspecialchars($order->phone_number) ?></span>
            </div>
        </div>
    </div>

    <!-- Gift Options Display -->
    <?php if (!empty($order->GiftWrap)): ?>
    <div class="gift-card">
        <h3 style="font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>🎁</span> Gift Options
        </h3>
        
        <div style="display: grid; gap: 0.8rem;">
            <div class="info-row" style="border-bottom: 1px solid #f0e6d2;">
                <span class="info-label">Packaging:</span>
                <span class="info-value">
                    <?= $order->GiftWrap === 'luxury' ? '💎 Luxury Gift Wrap' : '📦 Standard Packaging' ?>
                </span>
            </div>
            
            <?php if ($gift_wrap_cost > 0): ?>
            <div class="info-row" style="border-bottom: 1px solid #f0e6d2;">
                <span class="info-label">Gift Wrap Cost:</span>
                <span class="info-value" style="color: #D4AF37;">+RM <?= number_format($gift_wrap_cost, 2) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order->GiftMessage)): ?>
            <div style="margin-top: 0.5rem;">
                <div style="color: #666; margin-bottom: 0.5rem; font-weight: 500;">💌 Gift Message:</div>
                <div style="background: #fff; padding: 1rem; border-radius: 8px; font-style: italic; color: #666; border-left: 3px solid #D4AF37;">
                    "<?= htmlspecialchars($order->GiftMessage) ?>"
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($order->HidePrice): ?>
            <div class="info-row">
                <span class="info-label">🔒 Privacy:</span>
                <span class="info-value">Price hidden on receipt</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Order Items -->
    <h3 style="margin-bottom: 1rem; font-size: 1.2rem; color: #333;">🛍️ Order Items</h3>
    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 2rem;">
        <table class="product-table" style="margin-bottom: 0;">
            <thead>
                <tr style="background: #000; color: #fff;">
                    <th>Image</th>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Series</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <img src="/public/images/<?= htmlspecialchars($item->ProductID) ?>.png" 
                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                             alt="<?= htmlspecialchars($item->ProductName) ?>">
                    </td>
                    <td style="font-weight: 600; color: #666;"><?= htmlspecialchars($item->ProductID) ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($item->ProductName) ?></td>
                    <td><?= htmlspecialchars($item->Series) ?></td>
                    <td style="text-align: center;">
                        <span style="background: #f0f0f0; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600;">
                            <?= $item->Quantity ?>
                        </span>
                    </td>
                    <td style="font-weight: 600;">RM <?= number_format($item->TotalPrice, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f9f9f9;">
                    <td colspan="5" style="text-align: right; padding: 1rem; font-weight: 600;">Subtotal:</td>
                    <td style="padding: 1rem; font-weight: 600;">RM <?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php if ($gift_wrap_cost > 0): ?>
                <tr style="background: #f9f9f9;">
                    <td colspan="5" style="text-align: right; padding: 1rem;">🎁 Gift Wrapping:</td>
                    <td style="padding: 1rem; color: #D4AF37; font-weight: 600;">+RM <?= number_format($gift_wrap_cost, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr style="background: linear-gradient(135deg, #fffbf0 0%, #fff 100%); border-top: 2px solid #D4AF37;">
                    <td colspan="5" style="text-align: right; font-weight: bold; font-size: 1.2rem; padding: 1.5rem;">
                        Grand Total:
                    </td>
                    <td style="font-weight: bold; font-size: 1.3rem; color: #D4AF37; padding: 1.5rem;">
                        RM <?= number_format($total, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Thank You Message -->
    <div style="text-align: center; padding: 2.5rem; background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%); border-radius: 12px; border: 2px solid #e0e0e0;">
        <?php if ($order->Status === 'Delivered'): ?>
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
            <h3 style="color: #4caf50; margin-bottom: 0.5rem;">Order Delivered!</h3>
            <p style="color: #666;">Thank you for shopping with N°9 Perfume. We hope you enjoy your fragrance!</p>
        <?php elseif ($order->Status === 'Cancelled'): ?>
            <div style="font-size: 3rem; margin-bottom: 1rem;">😔</div>
            <p style="color: #666;">If you have any questions about this cancellation, please contact our support.</p>
        <?php else: ?>
            <div style="font-size: 3rem; margin-bottom: 1rem;">✨</div>
            <h3 style="color: #D4AF37; margin-bottom: 0.5rem;">Thank You for Your Purchase!</h3>
            <p style="color: #666;">Your order is being processed. We'll notify you once it ships.</p>
            <p style="color: #999; font-size: 0.9rem; margin-top: 0.5rem;">This page auto-refreshes every 2 minutes to show the latest status.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../_foot.php'; ?>