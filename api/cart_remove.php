<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$cart_id = post('cart_id');
$user_id = $_SESSION['user_id'];

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit;
}

try {
    // Delete the cart item
    $stmt = $_db->prepare("DELETE FROM cart WHERE CartID = ? AND UserID = ?");
    $stmt->execute([$cart_id, $user_id]);

    // Get new cart count
    $stmt = $_db->prepare("SELECT SUM(Quantity) FROM cart WHERE UserID = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetchColumn() ?: 0;

    // Get new total price
    $stmt = $_db->prepare("
        SELECT SUM(p.Price * c.Quantity) as total 
        FROM cart c 
        JOIN product p ON c.ProductID = p.ProductID 
        WHERE c.UserID = ?
    ");
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn() ?: 0;

    echo json_encode([
        'success' => true,
        'cart_count' => (int)$cart_count,
        'new_total' => (float)$total,
        'message' => 'Item removed successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>