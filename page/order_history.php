<?php
require '../_base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Member') {
    redirect('/page/login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders with totals
$stmt = $_db->prepare("
    SELECT o.OrderID, o.PurchaseDate, o.PaymentMethod,
           SUM(po.TotalPrice) as OrderTotal,
           COUNT(po.ProductOrderID) as ItemCount
    FROM `order` o
    LEFT JOIN productorder po ON o.OrderID = po.OrderID
    WHERE o.UserID = ?
    GROUP BY o.OrderID
    ORDER BY o.PurchaseDate DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$_title = 'Order History - N°9 Perfume';
include '../_head.php';
?>

<div class="container" style="margin-top: 100px; min-height: 60vh;">
    <h2 style="margin-bottom: 2rem;">My Order History</h2>
    
    <?php if ($msg = temp('success')): ?>
        <div style="padding: 1rem; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 1rem;">
            <?= $msg ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 4rem; color: #666;">
            <p style="font-size: 1.2rem; margin-bottom: 1rem;">You haven't placed any orders yet</p>
            <a href="/page/product.php" style="color: #D4AF37; text-decoration: none;">Start Shopping →</a>
        </div>
    <?php else: ?>
        <p style="margin-bottom: 1.5rem;"><?= count($orders) ?> order(s) found</p>
        
        <table class="product-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Payment Method</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($order->OrderID) ?></td>
                    <td><?= date('d M Y', strtotime($order->PurchaseDate)) ?></td>
                    <td><?= $order->ItemCount ?> item(s)</td>
                    <td><?= htmlspecialchars($order->PaymentMethod) ?></td>
                    <td style="color: #D4AF37; font-weight: 600;">
                        RM <?= number_format($order->OrderTotal, 2) ?>
                    </td>
                    <td>
                        <a href="/page/order_detail_member.php?id=<?= $order->OrderID ?>" 
                           class="action-btn btn-edit" 
                           style="text-decoration: none; display: inline-block;">
                            View Details
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../_foot.php'; ?>