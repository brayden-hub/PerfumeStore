<?php
require '../_base.php';
header('Content-Type: application/json');

$count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $_db->prepare("SELECT SUM(Quantity) FROM cart WHERE UserID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $count = $stmt->fetchColumn() ?: 0;
}

echo json_encode(['count' => $count]);
?>