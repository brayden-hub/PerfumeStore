<?php
require '../_base.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security Check: Only Members can cancel their own orders
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Member') {
    temp('error', 'Unauthorized access');
    redirect('/page/login.php');
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    temp('error', 'Invalid request method');
    redirect('/page/order_history.php');
    exit();
}

$order_id = post('order_id');
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    temp('error', 'Order ID is required');
    redirect('/page/order_history.php');
    exit();
}

try {
    // Fetch the order to verify ownership and status
    $stmt = $_db->prepare("
        SELECT o.*, os.Status
        FROM `order` o
        JOIN order_status os ON o.OrderID = os.OrderID
        WHERE o.OrderID = ? AND o.UserID = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    // Validate order exists and belongs to user
    if (!$order) {
        temp('error', 'Order not found or you do not have permission to cancel it');
        redirect('/page/order_history.php');
        exit();
    }

    // Check if order can be cancelled (only Pending and Processing orders)
    if (!in_array($order->Status, ['Pending', 'Processing'])) {
        temp('error', 'This order cannot be cancelled. Orders can only be cancelled when they are Pending or Processing.');
        redirect("/page/order_detail_member.php?id=$order_id");
        exit();
    }

    // Begin transaction
    $_db->beginTransaction();

    // Update order status to Cancelled (using StatusUpdatedAt instead of CancelledAt)
    $stmt = $_db->prepare("
        UPDATE order_status 
        SET Status = 'Cancelled',
            StatusUpdatedAt = NOW()
        WHERE OrderID = ?
    ");
    $result = $stmt->execute([$order_id]);
    
    if (!$result || $stmt->rowCount() === 0) {
        throw new Exception('Failed to update order status');
    }

    // Restore product stock quantities (column is 'Stock', not 'StockQuantity')
    $stmt = $_db->prepare("
        SELECT ProductID, Quantity 
        FROM productorder 
        WHERE OrderID = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

    if (count($order_items) > 0) {
        foreach ($order_items as $item) {
            $stmt = $_db->prepare("
                UPDATE product 
                SET Stock = Stock + ? 
                WHERE ProductID = ?
            ");
            $stmt->execute([$item->Quantity, $item->ProductID]);
        }
    }

    // If a voucher was used, restore it (mark as unused in user_voucher)
    if (!empty($order->VoucherID)) {
        // Mark the voucher as unused again for this user
        $stmt = $_db->prepare("
            UPDATE user_voucher 
            SET IsUsed = 0, 
                UsedAt = NULL 
            WHERE UserID = ? AND VoucherID = ? 
            ORDER BY UserVoucherID DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id, $order->VoucherID]);
    }

    // Commit transaction
    $_db->commit();

    temp('success', 'Order has been cancelled successfully. Your refund will be processed within 5-7 business days.');
    redirect("/page/order_detail_member.php?id=$order_id");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if ($_db->inTransaction()) {
        $_db->rollBack();
    }
    
    temp('error', 'Failed to cancel order: ' . $e->getMessage());
    redirect("/page/order_detail_member.php?id=$order_id");
    exit();
}
?>