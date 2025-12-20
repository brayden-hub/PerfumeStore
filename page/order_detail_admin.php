<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role'])) {
    redirect('/page/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Admin') {
    redirect('/page/product.php');
    exit();
}

$order_id = get('id');

if (!$order_id) {
    redirect('/page/order.php');
}

// Fetch order details with shipping address
$stmt = $_db->prepare("
    SELECT o.*, 
           os.Status, os.TrackingNumber, os.EstimatedDelivery, 
           os.ProcessedAt, os.ShippedAt, os.DeliveredAt, os.StatusUpdatedAt,
           u.name as CustomerName, u.email, u.phone_number, u.userID,
           ua.AddressLabel, ua.RecipientName, ua.PhoneNumber as ShippingPhone,
           ua.AddressLine1, ua.AddressLine2, ua.City, ua.State, ua.PostalCode, ua.Country
    FROM `order` o
    JOIN order_status os ON o.OrderID = os.OrderID
    JOIN user u ON o.UserID = u.userID
    LEFT JOIN user_address ua ON o.ShippingAddressID = ua.AddressID
    WHERE o.OrderID = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    temp('error', 'Order not found');
    redirect('/page/order.php');
}

// Fetch order items with product details
$stmt = $_db->prepare("
    SELECT po.*, p.ProductName, p.Series, p.Price
    FROM productorder po
    JOIN product p ON po.ProductID = p.ProductID
    WHERE po.OrderID = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $item) {
    $total += $item->TotalPrice;
}

// Calculate grand total with gift wrap, shipping, and voucher
$gift_wrap_cost = $order->GiftWrapCost ?? 0;
$shipping_fee = $order->ShippingFee ?? 0;
$voucher_discount = $order->VoucherDiscount ?? 0;
$grand_total = $total + $gift_wrap_cost + $shipping_fee - $voucher_discount;

// Check if order is delivered or cancelled (cannot be modified)
$is_delivered = ($order->Status === 'Delivered');
$is_cancelled = ($order->Status === 'Cancelled');
$cannot_modify = ($is_delivered || $is_cancelled);

// Status configuration
$status_config = [
    'Pending' => ['icon' => '⏳', 'color' => '#ff9800', 'bg' => '#fff3e0'],
    'Processing' => ['icon' => '📦', 'color' => '#2196f3', 'bg' => '#e3f2fd'],
    'Shipped' => ['icon' => '🚚', 'color' => '#9c27b0', 'bg' => '#f3e5f5'],
    'Delivered' => ['icon' => '✅', 'color' => '#4caf50', 'bg' => '#e8f5e9'],
    'Cancelled' => ['icon' => '❌', 'color' => '#f44336', 'bg' => '#ffebee']
];

$current_status = $status_config[$order->Status ?? 'Pending'] ?? $status_config['Pending'];

$_title = 'Order Details - N°9 Perfume';
include '../_head.php';
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<style>
.shipping-visual {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 2rem;
    align-items: center;
    margin: 2rem 0;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.address-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.address-card.from {
    border: 2px solid #D4AF37;
}

.address-card.to {
    border: 2px solid #4caf50;
}

.address-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.address-card.from .address-header {
    color: #D4AF37;
}

.address-card.to .address-header {
    color: #4caf50;
}

.address-content {
    color: #333;
    line-height: 1.8;
}

.address-content strong {
    display: block;
    margin-bottom: 0.3rem;
}

.delivery-arrow {
    text-align: center;
    font-size: 2.5rem;
    color: #D4AF37;
    animation: pulse-arrow 2s ease-in-out infinite;
}

@keyframes pulse-arrow {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(10px); }
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.delivered-notice {
    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
    border: 2px solid #4caf50;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

@media print {
    .shipping-visual {
        page-break-inside: avoid;
    }
}
</style>

<div class="container" style="margin-top: 30px; min-height: 60vh;">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="/page/order.php" style="color: #666; text-decoration: none;">← Back to Order List</a>
        <div style="display: flex; gap: 1rem;">
            <?php if (!$cannot_modify): ?>
            <a href="/page/order_update_status.php?id=<?= $order->OrderID ?>" 
               style="padding: 0.5rem 1rem; background: #D4AF37; color: #000; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; font-weight: 600;">
                ✏️ Update Status
            </a>
            <?php else: ?>
            <button disabled 
                    style="padding: 0.5rem 1rem; background: #e0e0e0; color: #999; border: none; cursor: not-allowed; border-radius: 4px; font-weight: 600;"
                    title="<?= $is_delivered ? 'Delivered orders cannot be modified' : 'Cancelled orders cannot be modified' ?>">
                🔒 Update Status
            </button>
            <?php endif; ?>
            <button onclick="window.print()" style="padding: 0.5rem 1rem; background: #000; color: #D4AF37; border: none; cursor: pointer; border-radius: 4px; font-weight: 600;">
                🖨️ Print Order
            </button>
        </div>
    </div>
    
    <h2 style="margin-bottom: 1rem;">Order Details - <?= htmlspecialchars($order->OrderID) ?></h2>
    
    <!-- Delivered/Cancelled Notice -->
    <?php if ($is_delivered): ?>
    <div class="delivered-notice">
        <span style="font-size: 1.5rem;">🔒</span>
        <div>
            <strong style="color: #2e7d32;">Order Completed</strong>
            <p style="margin: 0.3rem 0 0 0; color: #666; font-size: 0.9rem;">
                This order has been delivered and can no longer be modified.
            </p>
        </div>
    </div>
    <?php elseif ($is_cancelled): ?>
    <div class="delivered-notice" style="background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%); border-color: #f44336;">
        <span style="font-size: 1.5rem;">🔒</span>
        <div>
            <strong style="color: #c62828;">Order Cancelled</strong>
            <p style="margin: 0.3rem 0 0 0; color: #666; font-size: 0.9rem;">
                This order has been cancelled and can no longer be modified.
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Current Status Badge -->
    <div style="margin-bottom: 2rem;">
        <span class="status-badge" style="background: <?= $current_status['bg'] ?>; color: <?= $current_status['color'] ?>;">
            <?= $current_status['icon'] ?> <?= htmlspecialchars($order->Status ?? 'Pending') ?>
        </span>
        <?php if (!empty($order->TrackingNumber)): ?>
            <span style="margin-left: 1rem; color: #666;">
                Tracking: <strong style="color: #2196f3;"><?= htmlspecialchars($order->TrackingNumber) ?></strong>
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Order and Customer Information -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; margin-bottom: 2rem;">
        <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: #D4AF37;">Order Information</h3>
            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; border-bottom: 1px solid #ddd; padding-bottom: 0.5rem;">
                    <strong style="width: 150px;">Order ID:</strong>
                    <span><?= htmlspecialchars($order->OrderID) ?></span>
                </div>
                <div style="display: flex; border-bottom: 1px solid #ddd; padding-bottom: 0.5rem;">
                    <strong style="width: 150px;">Order Date:</strong>
                    <span><?= date('d F Y', strtotime($order->PurchaseDate)) ?></span>
                </div>
                <div style="display: flex; border-bottom: 1px solid #ddd; padding-bottom: 0.5rem;">
                    <strong style="width: 150px;">Payment Method:</strong>
                    <span><?= htmlspecialchars($order->PaymentMethod) ?></span>
                </div>
                <div style="display: flex;">
                    <strong style="width: 150px;">Grand Total:</strong>
                    <span style="color: #D4AF37; font-weight: 600; font-size: 1.1rem;">
                        RM <?= number_format($grand_total, 2) ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: #D4AF37;">Customer Information</h3>
            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; border-bottom: 1px solid #ddd; padding-bottom: 0.5rem;">
                    <strong style="width: 150px;">Customer ID:</strong>
                    <span><?= htmlspecialchars($order->userID) ?></span>
                </div>
                <div style="display: flex; border-bottom: 1px solid #ddd; padding-bottom: 0.5rem;">
                    <strong style="width: 150px;">Name:</strong>
                    <span><?= htmlspecialchars($order->CustomerName) ?></span>
                </div>
                <div style="display: flex; border-bottom: 1px solid #ddd; padding-bottom: 0.5rem;">
                    <strong style="width: 150px;">Email:</strong>
                    <span><?= htmlspecialchars($order->email) ?></span>
                </div>
                <div style="display: flex;">
                    <strong style="width: 150px;">Phone:</strong>
                    <span><?= htmlspecialchars($order->phone_number) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Shipping Address Visual -->
    <?php if ($order->ShippingAddressID): ?>
    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem; color: #333;">📍 Shipping Route</h3>
        <div class="shipping-visual">
            <!-- Store Address (From) -->
            <div class="address-card from">
                <div class="address-header">
                    🏪 Ship From
                </div>
                <div class="address-content">
                    <strong style="color: #D4AF37;">N°9 Perfume Store</strong>
                    123 Fragrance Avenue<br>
                    Bukit Bintang<br>
                    Kuala Lumpur, 55100<br>
                    Malaysia<br>
                    <span style="color: #666;">📞 +60 3-1234 5678</span>
                </div>
            </div>
            
            <!-- Arrow -->
            <div class="delivery-arrow">
                →
            </div>
            
            <!-- Customer Address (To) -->
            <div class="address-card to">
                <div class="address-header">
                    📦 Ship To
                    <?php if ($order->AddressLabel): ?>
                        <span style="font-size: 0.8rem; font-weight: 400; color: #999;">
                            (<?= htmlspecialchars($order->AddressLabel) ?>)
                        </span>
                    <?php endif; ?>
                </div>
                <div class="address-content">
                    <strong><?= htmlspecialchars($order->RecipientName) ?></strong>
                    <?= htmlspecialchars($order->AddressLine1) ?><br>
                    <?php if ($order->AddressLine2): ?>
                        <?= htmlspecialchars($order->AddressLine2) ?><br>
                    <?php endif; ?>
                    <?= htmlspecialchars($order->City) ?>, <?= htmlspecialchars($order->State) ?> <?= htmlspecialchars($order->PostalCode) ?><br>
                    <?= htmlspecialchars($order->Country) ?><br>
                    <span style="color: #666;">📞 <?= htmlspecialchars($order->ShippingPhone) ?></span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($order->EstimatedDelivery) && $order->Status !== 'Delivered'): ?>
        <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: #e3f2fd; border-radius: 8px;">
            <span style="color: #1976d2; font-weight: 600;">
                📅 Estimated Delivery: <?= date('d F Y', strtotime($order->EstimatedDelivery)) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Gift Options Display -->
    <?php if (!empty($order->GiftWrap)): ?>
    <div style="background: linear-gradient(135deg, #fffbf0 0%, #fff 100%); border: 2px solid #D4AF37; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>🎁</span> Gift Options
        </h3>
        
        <div style="display: grid; gap: 0.8rem;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f0e6d2; padding-bottom: 0.5rem;">
                <span style="color: #666;">Packaging:</span>
                <span style="font-weight: 600;">
                    <?= $order->GiftWrap === 'luxury' ? '💎 Luxury Gift Wrap' : '📦 Standard Packaging' ?>
                </span>
            </div>
            
            <?php if ($gift_wrap_cost > 0): ?>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f0e6d2; padding-bottom: 0.5rem;">
                <span style="color: #666;">Gift Wrap Cost:</span>
                <span style="color: #D4AF37; font-weight: 600;">+RM <?= number_format($gift_wrap_cost, 2) ?></span>
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
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #666;">🔒 Privacy:</span>
                <span style="font-weight: 600;">Price hidden on receipt</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Order Items -->
    <h3 style="margin-bottom: 1rem;">Order Items</h3>
    <table class="product-table" style="margin-bottom: 2rem;">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Image</th>
                <th>Product Name</th>
                <th>Series</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item->ProductID) ?></td>
                <td>
                    <img src="/public/images/<?= htmlspecialchars($item->ProductID) ?>.png" 
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"
                         alt="<?= htmlspecialchars($item->ProductName) ?>">
                </td>
                <td><?= htmlspecialchars($item->ProductName) ?></td>
                <td><?= htmlspecialchars($item->Series) ?></td>
                <td>RM <?= number_format($item->Price, 2) ?></td>
                <td style="text-align: center; font-weight: 600;"><?= $item->Quantity ?></td>
                <td style="font-weight: 600;">RM <?= number_format($item->TotalPrice, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9;">
                <td colspan="6" style="text-align: right; padding: 1rem; font-weight: 600;">
                    Products Subtotal:
                </td>
                <td style="padding: 1rem; font-weight: 600;">
                    RM <?= number_format($total, 2) ?>
                </td>
            </tr>
            <?php if ($gift_wrap_cost > 0): ?>
            <tr style="background: #f9f9f9;">
                <td colspan="6" style="text-align: right; padding: 1rem;">
                    🎁 Gift Wrapping:
                </td>
                <td style="padding: 1rem; color: #D4AF37; font-weight: 600;">
                    +RM <?= number_format($gift_wrap_cost, 2) ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr style="background: #f9f9f9;">
                <td colspan="6" style="text-align: right; padding: 1rem;">
                    🚚 Shipping Fee:
                </td>
                <td style="padding: 1rem; font-weight: 600;">
                    RM <?= number_format($shipping_fee, 2) ?>
                </td>
            </tr>
            
            <!-- Voucher Discount -->
            <?php if ($voucher_discount > 0): ?>
            <tr style="background: linear-gradient(135deg, #e8f5e9 0%, #f9f9f9 100%);">
                <td colspan="6" style="text-align: right; padding: 1rem; color: #2e7d32; font-weight: 600;">
                    🎟️ Voucher Discount:
                </td>
                <td style="padding: 1rem; color: #2e7d32; font-weight: 700;">
                    -RM <?= number_format($voucher_discount, 2) ?>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr style="background: linear-gradient(135deg, #fffbf0 0%, #fff 100%); border-top: 2px solid #D4AF37;">
                <td colspan="6" style="text-align: right; font-weight: bold; font-size: 1.2rem; padding: 1.5rem;">
                    Grand Total:
                </td>
                <td style="font-weight: bold; font-size: 1.3rem; color: #D4AF37; padding: 1.5rem;">
                    RM <?= number_format($grand_total, 2) ?>
                </td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Order Summary Statistics -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 2rem;">
        <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <p style="color: #1976d2; font-size: 0.9rem; margin-bottom: 0.5rem;">Total Items</p>
            <p style="font-size: 2rem; font-weight: bold; color: #1565c0; margin: 0;">
                <?= array_sum(array_column($items, 'Quantity')) ?>
            </p>
        </div>
        <div style="background: #f3e5f5; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <p style="color: #7b1fa2; font-size: 0.9rem; margin-bottom: 0.5rem;">Unique Products</p>
            <p style="font-size: 2rem; font-weight: bold; color: #6a1b9a; margin: 0;">
                <?= count($items) ?>
            </p>
        </div>
        <div style="background: #fff3e0; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <p style="color: #e65100; font-size: 0.9rem; margin-bottom: 0.5rem;">Average Item Price</p>
            <p style="font-size: 2rem; font-weight: bold; color: #d84315; margin: 0;">
                RM <?= count($items) > 0 ? number_format($total / count($items), 2) : '0.00' ?>
            </p>
        </div>
    </div>
</div>

<style media="print">
    header, footer, .admin-header, button, nav { display: none !important; }
    body { padding: 20px; }
</style>

<?php include '../_foot.php'; ?>