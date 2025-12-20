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

// Fetch order details
$stmt = $_db->prepare("
    SELECT o.*, os.* FROM `order` o
    JOIN order_status os ON o.OrderID = os.OrderID
    WHERE o.OrderID = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    temp('error', 'Order not found');
    redirect('/page/order.php');
}

// If order is already Delivered or Cancelled, prevent further changes
if ($order->Status === 'Delivered') {
    temp('error', 'Cannot modify a delivered order. This order has been completed.');
    redirect('/page/order_detail_admin.php?id=' . $order_id);
    exit();
}

if ($order->Status === 'Cancelled') {
    temp('error', 'Cannot modify a cancelled order.');
    redirect('/page/order_detail_admin.php?id=' . $order_id);
    exit();
}

if (is_post()) {
    $new_status = post('status');
    $tracking_number = post('tracking_number', '');
    $estimated_delivery = post('estimated_delivery', '');
    
    $valid_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    
    if (!in_array($new_status, $valid_statuses)) {
        temp('error', 'Invalid status');
    } else {
        try {
            $_db->beginTransaction();
            
            // If cancelling, restore stock
            if ($new_status === 'Cancelled' && $order->Status !== 'Cancelled') {
                $stmt_items = $_db->prepare("
                    SELECT po.ProductID, po.Quantity 
                    FROM productorder po
                    WHERE po.OrderID = ?
                ");
                $stmt_items->execute([$order_id]);
                $order_items = $stmt_items->fetchAll();
                
                foreach ($order_items as $item) {
                    $stmt_restore = $_db->prepare("
                        UPDATE product 
                        SET Stock = Stock + ? 
                        WHERE ProductID = ?
                    ");
                    $stmt_restore->execute([$item->Quantity, $item->ProductID]);
                }
            }

            // Update order status and timestamps
            $update_fields = ['Status = ?', 'StatusUpdatedAt = NOW()'];
            $params = [$new_status];
            
            // Set timestamp based on status
            switch($new_status) {
                case 'Processing':
                    $update_fields[] = 'ProcessedAt = COALESCE(ProcessedAt, NOW())';
                    break;
                case 'Shipped':
                    $update_fields[] = 'ProcessedAt = COALESCE(ProcessedAt, NOW())';
                    $update_fields[] = 'ShippedAt = COALESCE(ShippedAt, NOW())';
                    if (!empty($tracking_number)) {
                        $update_fields[] = 'TrackingNumber = ?';
                        $params[] = $tracking_number;
                    }
                    break;
                case 'Delivered':
                    $update_fields[] = 'ProcessedAt = COALESCE(ProcessedAt, NOW())';
                    $update_fields[] = 'ShippedAt = COALESCE(ShippedAt, NOW())';
                    $update_fields[] = 'DeliveredAt = COALESCE(DeliveredAt, NOW())';
                    break;
            }
            
            // Add estimated delivery if provided
            if (!empty($estimated_delivery)) {
                $update_fields[] = 'EstimatedDelivery = ?';
                $params[] = $estimated_delivery;
            }
            
            $params[] = $order_id;
            
            $sql = "UPDATE order_status SET " . implode(', ', $update_fields) . " WHERE OrderID = ?";
            $stmt = $_db->prepare($sql);
            $stmt->execute($params);
            
            $_db->commit();
            
            temp('success', 'Order status updated successfully' . 
                 ($new_status === 'Cancelled' ? ' and stock restored' : ''));
            redirect('/page/order_detail_admin.php?id=' . $order_id);
            
        } catch (Exception $e) {
            $_db->rollBack();
            temp('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }
}

$_title = 'Update Order Status - N°9 Perfume';
include '../_head.php';
?>

<style>
.status-option {
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s;
}

.status-option:hover {
    border-color: #D4AF37;
    background: #fffbf0;
}

.status-option input[type="radio"] {
    display: none;
}

.status-option input[type="radio"]:checked + label {
    color: #D4AF37;
    font-weight: bold;
}

.status-option input[type="radio"]:checked ~ .status-icon {
    transform: scale(1.2);
}

.status-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    transition: transform 0.3s;
}

.delivered-notice {
    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
    border: 2px solid #4caf50;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}
</style>

<div class="container" style="margin-top: 100px; min-height: 60vh;">
    <div style="margin-bottom: 2rem;">
        <a href="/page/order_detail_admin.php?id=<?= $order_id ?>" style="color: #666; text-decoration: none;">
            ← Back to Order Details
        </a>
    </div>
    
    <h2 style="margin-bottom: 2rem;">Update Order Status</h2>
    
    <?php if ($err = temp('error')): ?>
        <div style="padding: 1rem; background: #ffe6e6; color: #d00; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #d00;">
            ⚠️ <?= $err ?>
        </div>
    <?php endif; ?>
    
    <div style="max-width: 800px;">
        <div style="background: #f9f9f9; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <p><strong>Order ID:</strong> <?= htmlspecialchars($order->OrderID) ?></p>
            <p><strong>Current Status:</strong> <span style="color: #D4AF37;"><?= htmlspecialchars($order->Status) ?></span></p>
            <p><strong>Order Date:</strong> <?= date('d F Y', strtotime($order->PurchaseDate)) ?></p>
        </div>
        
        <form method="post">
            <h3 style="margin-bottom: 1.5rem;">Select New Status</h3>
            
            <!-- Pending -->
            <div class="status-option" onclick="this.querySelector('input').checked = true;">
                <input type="radio" name="status" value="Pending" id="status-pending" 
                       <?= $order->Status === 'Pending' ? 'checked' : '' ?>>
                <label for="status-pending">
                    <div class="status-icon">⏳</div>
                    <div style="font-size: 1.1rem; font-weight: 600;">Pending</div>
                    <div style="color: #666; font-size: 0.9rem;">Order placed, awaiting processing</div>
                </label>
            </div>
            
            <!-- Processing -->
            <div class="status-option" onclick="this.querySelector('input').checked = true;">
                <input type="radio" name="status" value="Processing" id="status-processing" 
                       <?= $order->Status === 'Processing' ? 'checked' : '' ?>>
                <label for="status-processing">
                    <div class="status-icon">📦</div>
                    <div style="font-size: 1.1rem; font-weight: 600;">Processing</div>
                    <div style="color: #666; font-size: 0.9rem;">Order is being prepared</div>
                </label>
            </div>
            
            <!-- Shipped -->
            <div class="status-option" onclick="this.querySelector('input').checked = true;">
                <input type="radio" name="status" value="Shipped" id="status-shipped" 
                       <?= $order->Status === 'Shipped' ? 'checked' : '' ?>>
                <label for="status-shipped">
                    <div class="status-icon">🚚</div>
                    <div style="font-size: 1.1rem; font-weight: 600;">Shipped</div>
                    <div style="color: #666; font-size: 0.9rem;">Order is on the way to customer</div>
                </label>
                <div id="tracking-fields" style="margin-top: 1rem; display: none;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tracking Number:</label>
                    <input type="text" name="tracking_number" 
                           value="<?= htmlspecialchars($order->TrackingNumber ?? '') ?>"
                           placeholder="e.g., TRK123456789"
                           style="width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <!-- Delivered -->
            <div class="status-option" onclick="this.querySelector('input').checked = true;">
                <input type="radio" name="status" value="Delivered" id="status-delivered" 
                       <?= $order->Status === 'Delivered' ? 'checked' : '' ?>>
                <label for="status-delivered">
                    <div class="status-icon">✅</div>
                    <div style="font-size: 1.1rem; font-weight: 600;">Delivered</div>
                    <div style="color: #666; font-size: 0.9rem;">Order successfully delivered</div>
                    <div style="color: #ff9800; font-size: 0.85rem; margin-top: 0.5rem;">
                        ⚠️ Warning: Once marked as delivered, the status cannot be changed
                    </div>
                </label>
            </div>
            
            <!-- Cancelled -->
            <div class="status-option" onclick="this.querySelector('input').checked = true;" 
                 style="border-color: #ffcdd2;">
                <input type="radio" name="status" value="Cancelled" id="status-cancelled" 
                       <?= $order->Status === 'Cancelled' ? 'checked' : '' ?>>
                <label for="status-cancelled">
                    <div class="status-icon">❌</div>
                    <div style="font-size: 1.1rem; font-weight: 600; color: #f44336;">Cancelled</div>
                    <div style="color: #666; font-size: 0.9rem;">Order has been cancelled</div>
                    <div style="color: #4caf50; font-size: 0.85rem; margin-top: 0.5rem;">
                        ✓ Product stock will be automatically restored
                    </div>
                    <div style="color: #ff9800; font-size: 0.85rem; margin-top: 0.3rem;">
                        ⚠️ Warning: Once marked as cancelled, the status cannot be changed
                    </div>
                </label>
            </div>
            
            <!-- Estimated Delivery -->
            <div style="margin-top: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Estimated Delivery Date (Optional):
                </label>
                <input type="date" name="estimated_delivery" 
                       value="<?= htmlspecialchars($order->EstimatedDelivery ?? '') ?>"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" 
                        style="flex: 1; padding: 1rem; background: #D4AF37; color: #000; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer;">
                    Update Status
                </button>
                <a href="/page/order_detail_admin.php?id=<?= $order_id ?>" 
                   style="flex: 1; padding: 1rem; background: #fff; color: #666; border: 2px solid #e0e0e0; border-radius: 6px; text-align: center; text-decoration: none; font-weight: bold; display: flex; align-items: center; justify-content: center;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Show tracking number field when Shipped is selected
$('input[name="status"]').on('change', function() {
    if ($(this).val() === 'Shipped') {
        $('#tracking-fields').slideDown();
    } else {
        $('#tracking-fields').slideUp();
    }
});

// Initialize on page load
if ($('#status-shipped').is(':checked')) {
    $('#tracking-fields').show();
}

// Confirm status change on form submit
$('form').on('submit', function(e) {
    e.preventDefault();
    
    const selectedStatus = $('input[name="status"]:checked').val();
    const currentStatus = '<?= htmlspecialchars($order->Status) ?>';
    
    // If status hasn't changed, just submit
    if (selectedStatus === currentStatus) {
        this.submit();
        return;
    }
    
    // Status display names
    const statusNames = {
        'Pending': '⏳ Pending',
        'Processing': '📦 Processing',
        'Shipped': '🚚 Shipped',
        'Delivered': '✅ Delivered',
        'Cancelled': '❌ Cancelled'
    };
    
    // Warning messages for critical statuses
    let warningMessage = '';
    if (selectedStatus === 'Delivered') {
        warningMessage = '\n\n⚠️ Warning: Once marked as Delivered, this status CANNOT be changed!';
    } else if (selectedStatus === 'Cancelled') {
        warningMessage = '\n\n⚠️ Warning: Once marked as Cancelled, this status CANNOT be changed!\n✓ Product stock will be automatically restored.';
    }
    
    const confirmMessage = `Are you sure you want to change the order status?\n\nFrom: ${statusNames[currentStatus]}\nTo: ${statusNames[selectedStatus]}${warningMessage}`;
    
    if (confirm(confirmMessage)) {
        this.submit();
    }
});
</script>

<?php include '../_foot.php'; ?>