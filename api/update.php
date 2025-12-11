<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$cart_id = post('cart_id');
$quantity = (int)post('quantity', 1);

if (!$cart_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Check stock
$stmt = $_db->prepare("
    SELECT p.Stock 
    FROM cart c 
    JOIN product p ON c.ProductID = p.ProductID 
    WHERE c.CartID = ? AND c.UserID = ?
");
$stmt->execute([$cart_id, $_SESSION['user_id']]);
$result = $stmt->fetch();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit;
}

if ($quantity > $result->Stock) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit;
}

try {
    $stmt = $_db->prepare("UPDATE cart SET Quantity = ? WHERE CartID = ? AND UserID = ?");
    $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>
