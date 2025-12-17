<?php
require '../_base.php';

header('Content-Type: application/json');

// 1. Prepare the last 7 days range
$days = [];
$totals = [];
$today = new DateTime();

// Initialize the arrays with 0 for the last 7 days
for ($i = 6; $i >= 0; $i--) {
    $d = (clone $today)->modify("-$i days")->format('Y-m-d');
    $days[$d] = 0; // Default to 0 sales
}

// 2. Query Database
// We join `order`, `productorder`, and `order_status`
// We sum up the TotalPrice from productorder for non-cancelled orders
$sql = "
    SELECT 
        o.PurchaseDate, 
        SUM(po.TotalPrice) as DailyTotal
    FROM `order` o
    JOIN productorder po ON o.OrderID = po.OrderID
    JOIN order_status os ON o.OrderID = os.OrderID
    WHERE os.Status != 'Cancelled'
    AND o.PurchaseDate >= DATE(NOW()) - INTERVAL 7 DAY
    GROUP BY o.PurchaseDate
";

$stm = $_db->query($sql);
$results = $stm->fetchAll();

// 3. Merge DB results into our days array
foreach ($results as $row) {
    // Ensure the date exists in our range (security check)
    if (isset($days[$row->PurchaseDate])) {
        $days[$row->PurchaseDate] = (float)$row->DailyTotal;
    }
}

// 4. Return JSON
echo json_encode([
    'labels' => array_keys($days), // The Dates
    'data'   => array_values($days) // The Amounts
]);