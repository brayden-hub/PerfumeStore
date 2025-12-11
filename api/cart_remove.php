<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$cart_id = post('cart_id');

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $stmt = $_db->prepare("DELETE FROM cart WHERE CartID = ? AND UserID = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'message' => 'Item removed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Remove failed']);
}
?>
