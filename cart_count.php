<?php
session_start();

$count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=n9_perfume", "root", "");
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM carts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $count = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {}
}
echo $count;
?>