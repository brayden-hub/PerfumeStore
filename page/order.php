<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$order_id = get('id');

if (!$order_id) {
    redirect('/page/order.php');
}

// Fetch order details
$stmt = $_db->prepare("
    SELECT o.*, u.name as CustomerName, u.email, u.phone_number, u.userID
    FROM `order` o
    JOIN user u ON o.UserID = u.userID
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

$_title = 'Order Details - N°9 Perfume';
include '../_head.php';
?>

<div class="container" style="margin-top: 30px; min-height: 60vh;">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="/page/order.php" style="color: #666; text-decoration: none;">← Back to Order List</a>
        <button onclick="window.print()" style="padding: 0.5rem 1rem; background: #000; color: #D4AF37; border: none; cursor: pointer; border-radius: 4px;">
            🖨️ Print Order
        </button>
    </div>
    
    <h2 style="margin-bottom: 2rem;">Order Details - <?= htmlspecialchars($order->OrderID) ?></h2>
    
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
                    <strong style="width: 150px;">Order Total:</strong>
                    <span style="color: #D4AF37; font-weight: 600; font-size: 1.1rem;">
                        RM <?= number_format($total, 2) ?>
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
                <td colspan="6" style="text-align: right; font-weight: bold; font-size: 1.2rem; padding: 1rem;">
                    Grand Total:
                </td>
                <td style="font-weight: bold; font-size: 1.2rem; color: #D4AF37; padding: 1rem;">
                    RM <?= number_format($total, 2) ?>
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