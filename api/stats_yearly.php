<?php
require '../_base.php';
header('Content-Type: application/json');

$year = req('year', date('Y')); // Default to current year

// Initialize all 12 months with 0
$months = [];
for ($i = 1; $i <= 12; $i++) {
    $monthName = date('M', mktime(0, 0, 0, $i, 1));
    $months[$i] = ['label' => $monthName, 'total' => 0];
}

// Query: Sum sales grouped by Month
$sql = "
    SELECT 
        MONTH(o.PurchaseDate) as MonthNum, 
        SUM(po.TotalPrice) as MonthlyTotal
    FROM `order` o
    JOIN productorder po ON o.OrderID = po.OrderID
    JOIN order_status os ON o.OrderID = os.OrderID
    WHERE os.Status != 'Cancelled'
    AND YEAR(o.PurchaseDate) = ?
    GROUP BY MONTH(o.PurchaseDate)
";

$stm = $_db->prepare($sql);
$stm->execute([$year]);
$results = $stm->fetchAll();

// Merge DB results
foreach ($results as $row) {
    $months[$row->MonthNum]['total'] = (float)$row->MonthlyTotal;
}

// Extract data for Chart.js
echo json_encode([
    'labels' => array_column($months, 'label'),
    'data'   => array_column($months, 'total')
]);