<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// Get filter parameters
$search = get('search', '');
$payment = get('payment', '');
$date_from = get('date_from', '');
$date_to = get('date_to', '');

// Build query
$sql = "
    SELECT o.OrderID, o.PurchaseDate, o.PaymentMethod,
           u.name as CustomerName, u.email,
           SUM(po.TotalPrice) as OrderTotal,
           COUNT(po.ProductOrderID) as ItemCount
    FROM `order` o
    JOIN user u ON o.UserID = u.userID
    LEFT JOIN productorder po ON o.OrderID = po.OrderID
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (o.OrderID LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($payment !== '') {
    $sql .= " AND o.PaymentMethod = ?";
    $params[] = $payment;
}

if ($date_from !== '') {
    $sql .= " AND o.PurchaseDate >= ?";
    $params[] = $date_from;
}

if ($date_to !== '') {
    $sql .= " AND o.PurchaseDate <= ?";
    $params[] = $date_to;
}

$sql .= " GROUP BY o.OrderID ORDER BY o.PurchaseDate DESC";

$stmt = $_db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Calculate statistics
$total_revenue = 0;
$total_orders = count($orders);
foreach ($orders as $order) {
    $total_revenue += $order->OrderTotal;
}

$_title = 'Order Management - N°9 Perfume';
include '../_head.php';
?>

<div class="container" style="margin-top: 30px;">
    <div class="admin-header">
        <h2>Order Management</h2>
    </div>
    
    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 8px; color: white;">
            <p style="font-size: 0.9rem; margin-bottom: 0.5rem; opacity: 0.9;">Total Orders</p>
            <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?= $total_orders ?></p>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 1.5rem; border-radius: 8px; color: white;">
            <p style="font-size: 0.9rem; margin-bottom: 0.5rem; opacity: 0.9;">Total Revenue</p>
            <p style="font-size: 2rem; font-weight: bold; margin: 0;">RM <?= number_format($total_revenue, 2) ?></p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 1.5rem; border-radius: 8px; color: white;">
            <p style="font-size: 0.9rem; margin-bottom: 0.5rem; opacity: 0.9;">Average Order Value</p>
            <p style="font-size: 2rem; font-weight: bold; margin: 0;">
                RM <?= $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00' ?>
            </p>
        </div>
    </div>
    
    <!-- Filter Form -->
    <form method="get" style="background: #f9f9f9; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Order ID, Customer..."
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Payment Method</label>
                <select name="payment" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">All Methods</option>
                    <option value="Credit Card" <?= $payment === 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
                    <option value="Online Banking" <?= $payment === 'Online Banking' ? 'selected' : '' ?>>Online Banking</option>
                    <option value="E-Wallet" <?= $payment === 'E-Wallet' ? 'selected' : '' ?>>E-Wallet</option>
                    <option value="Cash on Delivery" <?= $payment === 'Cash on Delivery' ? 'selected' : '' ?>>Cash on Delivery</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date From</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date To</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        </div>
        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1.5rem; background: #000; color: #D4AF37; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Apply Filters
            </button>
            <a href="/page/order.php" style="padding: 0.5rem 1.5rem; background: #ddd; color: #333; border: none; border-radius: 4px; text-decoration: none; display: inline-block;">
                Clear
            </a>
        </div>
    </form>
    
    <p style="margin-bottom: 1rem;"><?= count($orders) ?> order(s) found</p>
    
    <table class="product-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Items</th>
                <th>Payment</th>
                <th>Total Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="8" style="text-align: center;">No orders found</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($order->OrderID) ?></td>
                    <td><?= date('d M Y', strtotime($order->PurchaseDate)) ?></td>
                    <td><?= htmlspecialchars($order->CustomerName) ?></td>
                    <td><?= htmlspecialchars($order->email) ?></td>
                    <td><?= $order->ItemCount ?></td>
                    <td><?= htmlspecialchars($order->PaymentMethod) ?></td>
                    <td style="color: #D4AF37; font-weight: 600;">
                        RM <?= number_format($order->OrderTotal, 2) ?>
                    </td>
                    <td>
                        <a href="/page/order_detail_admin.php?id=<?= $order->OrderID ?>" 
                           class="action-btn btn-edit" 
                           style="text-decoration: none; display: inline-block;">
                            View Details
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../_foot.php'; ?>