<?php
require '../_base.php';
header('Content-Type: application/json');

$month = req('month', date('n')); // Use 'n' for numeric month without leading zeros
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
    GROUP BY p.ProductID, p.ProductName
    ORDER BY TotalQty DESC
    LIMIT 10
";

try {
    $stm = $_db->prepare($sql);
    $stm->execute([$month, $year]);
    $results = $stm->fetchAll();
    
    $labels = [];
    $data = [];
    
    foreach ($results as $row) {
        $labels[] = $row->ProductName;
        $data[] = (int)$row->TotalQty;
    }
    
    // If no results, return empty arrays
    echo json_encode([
        'labels' => $labels,
        'data'   => $data
    ]);
    
} catch (Exception $e) {
    error_log('Stats products error: ' . $e->getMessage());
    echo json_encode([
        'labels' => [],
        'data'   => []
    ]);
}