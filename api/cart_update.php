<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$cart_id = post('cart_id');
$quantity = (int)post('quantity', 1);
$user_id = $_SESSION['user_id'];

if (!$cart_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Check stock availability for this product
    $stmt = $_db->prepare("
        SELECT p.Stock 
        FROM cart c 
        JOIN product p ON c.ProductID = p.ProductID 
        WHERE c.CartID = ? AND c.UserID = ?
    ");
    $stmt->execute([$cart_id, $user_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
        exit;
    }

    if ($quantity > $product->Stock) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
        exit;
    }

    // Update quantity
    $stmt = $_db->prepare("UPDATE cart SET Quantity = ? WHERE CartID = ? AND UserID = ?");
    $stmt->execute([$quantity, $cart_id, $user_id]);

    // Get new cart count (total quantity of all items)
    $stmt = $_db->prepare("SELECT SUM(Quantity) FROM cart WHERE UserID = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetchColumn() ?: 0;

    // Get new total price for all items
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
        'message' => 'Cart updated successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>