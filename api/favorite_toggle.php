<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to use favourites']);
    exit;
}

$userId = $_SESSION['user_id'];
$productId = post('product_id');

try {
    // Check exists
    $stm = $_db->prepare(
        "SELECT COUNT(*) FROM favorites WHERE UserID = ? AND ProductID = ?"
    );
    $stm->execute([$userId, $productId]);

    if ($stm->fetchColumn() > 0) {
        // Remove favourite
        $stm = $_db->prepare(
            "DELETE FROM favorites WHERE UserID = ? AND ProductID = ?"
        );
        $stm->execute([$userId, $productId]);

        echo json_encode(['success' => true, 'favorited' => false]);
    } else {
        // Add favourite ❗没有 created_at
        $stm = $_db->prepare(
            "INSERT INTO favorites (UserID, ProductID) VALUES (?, ?)"
        );
        $stm->execute([$userId, $productId]);

        echo json_encode(['success' => true, 'favorited' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
