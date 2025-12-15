<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'login']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['status' => 'error']);
    exit;
}

$stm = $_db->prepare("
    INSERT IGNORE INTO favorites (UserID, ProductID)
    VALUES (?, ?)
");
$stm->execute([$user_id, $product_id]);

echo json_encode(['status' => 'ok']);
