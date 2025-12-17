<?php
require '../_base.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$count = 0;

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $_db->prepare("SELECT SUM(Quantity) FROM cart WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $count = (int)($stmt->fetchColumn() ?: 0);
    } catch (Exception $e) {
        error_log('Cart count error: ' . $e->getMessage());
        echo json_encode(['count' => 0, 'error' => 'Database error']);
        exit;
    }
}

echo json_encode(['count' => $count]);
?>