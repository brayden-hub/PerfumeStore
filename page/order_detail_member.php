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
    SELECT o.*, u.name as CustomerName, u.email, u.phone_number
    FROM `order` o
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
    SELECT po.*, p.ProductName, p.Series
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

$_title = 'Order Details - N°9 Perfume';
include '../_head.php';
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });

    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);  
    document.addEventListener("DOMContentLoaded", () => window.scrollTo(0, 0));
</script>

<div class="container" style="margin-top: 100px; min-height: 60vh;">
    <div style="margin-bottom: 2rem;">
        <a href="/page/order_history.php" style="color: #666; text-decoration: none;">← Back to Order History</a>
    </div>
    
    <h2 style="margin-bottom: 2rem;">Order Details</h2>
    
    <!-- Order Information -->
    <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
            <div>
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Order Information</h3>
                <p style="margin-bottom: 0.5rem;"><strong>Order ID:</strong> <?= htmlspecialchars($order->OrderID) ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Order Date:</strong> <?= date('d F Y', strtotime($order->PurchaseDate)) ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Payment Method:</strong> <?= htmlspecialchars($order->PaymentMethod) ?></p>
            </div>
            <div>
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Customer Information</h3>
                <p style="margin-bottom: 0.5rem;"><strong>Name:</strong> <?= htmlspecialchars($order->CustomerName) ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Email:</strong> <?= htmlspecialchars($order->email) ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Phone:</strong> <?= htmlspecialchars($order->phone_number) ?></p>
            </div>
        </div>
    </div>

    <!-- Gift Options Display (if applicable) -->
    <?php if (!empty($order->GiftWrap)): ?>
        <div style="background: linear-gradient(135deg, #fffbf0 0%, #fff 100%); border: 2px solid #D4AF37; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
            <div style="font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🎁</span> Gift Options
            </div>
            
            <div style="display: grid; gap: 0.8rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0;">
                    <span style="color: #666;">Packaging:</span>
                    <span style="font-weight: 600;">
                        <?= $order->GiftWrap === 'luxury' ? '💎 Luxury Gift Wrap' : '📦 Standard Packaging' ?>
                    </span>
                </div>
                
                <?php if ($gift_wrap_cost > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0;">
                        <span style="color: #666;">Gift Wrap Cost:</span>
                        <span style="font-weight: 600; color: #D4AF37;">+RM <?= number_format($gift_wrap_cost, 2) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($order->GiftMessage)): ?>
                    <div style="margin-top: 0.5rem;">
                        <div style="color: #666; margin-bottom: 0.5rem;">💌 Gift Message:</div>
                        <div style="background: #fff; padding: 1rem; border-radius: 8px; font-style: italic; color: #666; border-left: 3px solid #D4AF37;">
                            "<?= htmlspecialchars($order->GiftMessage) ?>"
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($order->HidePrice): ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
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
                <th>Product Name</th>
                <th>Series</th>
                <th>Quantity</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item->ProductID) ?></td>
                <td><?= htmlspecialchars($item->ProductName) ?></td>
                <td><?= htmlspecialchars($item->Series) ?></td>
                <td><?= $item->Quantity ?></td>
                <td>RM <?= number_format($item->TotalPrice, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9;">
                <td colspan="4" style="text-align: right; padding: 0.8rem;">Subtotal:</td>
                <td style="padding: 0.8rem;">RM <?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php if ($gift_wrap_cost > 0): ?>
            <tr style="background: #f9f9f9;">
                <td colspan="4" style="text-align: right; padding: 0.8rem;">Gift Wrapping:</td>
                <td style="padding: 0.8rem; color: #D4AF37;">RM <?= number_format($gift_wrap_cost, 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr style="background: #fffbf0;">
                <td colspan="4" style="text-align: right; font-weight: bold; font-size: 1.1rem; padding: 1rem;">
                    Total:
                </td>
                <td style="font-weight: bold; font-size: 1.1rem; color: #D4AF37; padding: 1rem;">
                    RM <?= number_format($total, 2) ?>
                </td>
            </tr>
        </tfoot>
    </table>
    
    <div style="text-align: center; padding: 2rem; background: #f0f0f0; border-radius: 8px;">
        <p style="color: #666;">Thank you for your purchase!</p>
        <p style="color: #666; margin-top: 0.5rem;">Your order has been confirmed and will be processed soon.</p>
    </div>
</div>

<?php include '../_foot.php'; ?>