<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = post('product_id');
$quantity = (int)post('quantity', 1);

if (!$product_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Check if product exists and has stock
$stmt = $_db->prepare("SELECT Stock FROM product WHERE ProductID = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product->Stock < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit;
}

// Check if item already in cart
$stmt = $_db->prepare("SELECT CartID, Quantity FROM cart WHERE UserID = ? AND ProductID = ?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch();

try {
    if ($existing) {
        // Update quantity
        $new_qty = $existing->Quantity + $quantity;
        if ($new_qty > $product->Stock) {
            echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
            exit;
        }
        
        $stmt = $_db->prepare("UPDATE cart SET Quantity = ? WHERE CartID = ?");
        $stmt->execute([$new_qty, $existing->CartID]);
    } else {
        // Generate new CartID
        $stmt = $_db->query("SELECT CartID FROM cart ORDER BY CartID DESC LIMIT 1");
        $last = $stmt->fetch();
        $next_num = $last ? (int)substr($last->CartID, 1) + 1 : 1;
        $cart_id = 'C' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
        
        // Insert new cart item
        $stmt = $_db->prepare("INSERT INTO cart (CartID, UserID, ProductID, Quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cart_id, $user_id, $product_id, $quantity]);
    }
    
    // Get total cart count
    $stmt = $_db->prepare("SELECT SUM(Quantity) FROM cart WHERE UserID = ?");
    $stmt->execute([$user_id]);
    $total_count = $stmt->fetchColumn() ?: 0;

    // 新增：計算新總價（給前端更新）
    $stmt = $_db->prepare("SELECT SUM(c.Quantity * p.Price) FROM cart c JOIN product p ON c.ProductID = p.ProductID WHERE c.UserID = ?");
    $stmt->execute([$user_id]);
    $new_total = $stmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Added to cart successfully',
        'cart_count' => (int)$total_count,
        'new_total' => (float)$new_total
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
