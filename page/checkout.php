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

$total = 0;
foreach ($cart_items as $item) {
    $total += $item->Price * $item->Quantity;
}

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
            
            // Create order
            $stmt = $_db->prepare("INSERT INTO `order` (OrderID, UserID, PurchaseDate, PaymentMethod) VALUES (?, ?, CURDATE(), ?)");
            $stmt->execute([$order_id, $user_id, $payment_method]);
            
            // Create order items and update stock
            foreach ($cart_items as $item) {
                // Generate ProductOrderID
                $stmt = $_db->query("SELECT ProductOrderID FROM productorder ORDER BY ProductOrderID DESC LIMIT 1");
                $last = $stmt->fetch();
                $next_num = $last ? (int)substr($last->ProductOrderID, 2) + 1 : 1;
                $po_id = 'PO' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
                
                $subtotal = $item->Price * $item->Quantity;
                
                // Insert product order
                $stmt = $_db->prepare("INSERT INTO productorder (ProductOrderID, OrderID, ProductID, Quantity, TotalPrice) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$po_id, $order_id, $item->ProductID, $item->Quantity, $subtotal]);
                
                // Update stock
                $stmt = $_db->prepare("UPDATE product SET Stock = Stock - ? WHERE ProductID = ?");
                $stmt->execute([$item->Quantity, $item->ProductID]);
            }
            
            // Clear cart
            $stmt = $_db->prepare("DELETE FROM cart WHERE UserID = ?");
            $stmt->execute([$user_id]);
            
            $_db->commit();
            
            temp('success', 'Order placed successfully!');
            redirect('/page/order_detail_member.php?id=' . $order_id);
            
        } catch (Exception $e) {
            $_db->rollBack();
            temp('error', 'Order failed. Please try again.');
        }
    }
}

$_title = 'Checkout - N°9 Perfume';
include '../_head.php';
?>

<div class="container" style="margin-top: 100px; min-height: 60vh;">
    <h2 style="margin-bottom: 2rem;">Checkout</h2>
    
    <?php if ($err = temp('error')): ?>
        <div style="padding: 1rem; background: #ffe6e6; color: #d00; border-radius: 4px; margin-bottom: 1rem;">
            <?= $err ?>
        </div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 3rem;">
        <!-- Order Summary -->
        <div>
            <h3 style="margin-bottom: 1rem;">Order Summary</h3>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): 
                        $subtotal = $item->Price * $item->Quantity;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item->ProductName) ?></td>
                        <td>RM <?= number_format($item->Price, 2) ?></td>
                        <td><?= $item->Quantity ?></td>
                        <td>RM <?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Payment Form -->
        <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px;">
            <h3 style="margin-bottom: 1.5rem;">Payment Details</h3>
            
            <form method="post">
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 1rem; font-weight: bold;">Payment Method</label>
                    
                    <label style="display: block; margin-bottom: 0.8rem; cursor: pointer;">
                        <input type="radio" name="payment_method" value="Credit Card" required>
                        <span style="margin-left: 0.5rem;">Credit Card</span>
                    </label>
                    
                    <label style="display: block; margin-bottom: 0.8rem; cursor: pointer;">
                        <input type="radio" name="payment_method" value="Online Banking" required>
                        <span style="margin-left: 0.5rem;">Online Banking</span>
                    </label>
                    
                    <label style="display: block; margin-bottom: 0.8rem; cursor: pointer;">
                        <input type="radio" name="payment_method" value="E-Wallet" required>
                        <span style="margin-left: 0.5rem;">E-Wallet</span>
                    </label>
                    
                    <label style="display: block; margin-bottom: 0.8rem; cursor: pointer;">
                        <input type="radio" name="payment_method" value="Cash on Delivery" required>
                        <span style="margin-left: 0.5rem;">Cash on Delivery</span>
                    </label>
                </div>
                
                <div style="border-top: 2px solid #ddd; padding-top: 1rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Subtotal:</span>
                        <span>RM <?= number_format($total, 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; color: #D4AF37;">
                        <span>Total:</span>
                        <span>RM <?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <button type="submit" style="width: 100%; padding: 1rem; background: #D4AF37; color: #000; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; border-radius: 4px;">
                    Place Order
                </button>
                
                <a href="/page/cart.php" style="display: block; text-align: center; margin-top: 1rem; color: #666; text-decoration: none;">
                    ← Back to Cart
                </a>
            </form>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>