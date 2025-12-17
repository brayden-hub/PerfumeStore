<?php
require '../_base.php';
header('Content-Type: application/json');

$month = req('month', date('m'));
$year  = req('year', date('Y'));

// Query: Top 10 products sold in that month
$sql = "
    SELECT 
        p.ProductName, 
        SUM(po.Quantity) as TotalQty
    FROM productorder po
    JOIN `order` o ON po.OrderID = o.OrderID
    JOIN product p ON po.ProductID = p.ProductID
    JOIN order_status os ON o.OrderID = os.OrderID
    WHERE os.Status != 'Cancelled'
    AND MONTH(o.PurchaseDate) = ? 
    AND YEAR(o.PurchaseDate) = ?
    GROUP BY p.ProductID
    ORDER BY TotalQty DESC
    LIMIT 10
";

$stm = $_db->prepare($sql);
$stm->execute([$month, $year]);
$results = $stm->fetchAll();

echo json_encode([
    'labels' => array_column($results, 'ProductName'),
    'data'   => array_column($results, 'TotalQty')
]);