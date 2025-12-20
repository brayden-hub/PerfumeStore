<?php
require '../_base.php';
header('Content-Type: application/json');

$year = req('year', date('Y')); // Default to current year

// Initialize all 12 months with 0
$months = [];
$labels = [];
$data = [];

for ($i = 1; $i <= 12; $i++) {
    $monthName = date('M', mktime(0, 0, 0, $i, 1));
    $labels[] = $monthName;
    $months[$i] = 0; // Default to 0
}

// Query: Sum sales grouped by Month (including gift wrap, shipping, voucher)
$sql = "
    SELECT 
        MONTH(o.PurchaseDate) as MonthNum,
        COALESCE(SUM(po.TotalPrice), 0) + 
        COALESCE(SUM(o.GiftWrapCost), 0) + 
        COALESCE(SUM(o.ShippingFee), 0) - 
        COALESCE(SUM(o.VoucherDiscount), 0) as MonthlyTotal
    FROM `order` o
    JOIN productorder po ON o.OrderID = po.OrderID
    JOIN order_status os ON o.OrderID = os.OrderID
    WHERE os.Status != 'Cancelled'
    AND YEAR(o.PurchaseDate) = ?
    GROUP BY MONTH(o.PurchaseDate)
    ORDER BY MONTH(o.PurchaseDate)
";

try {
    $stm = $_db->prepare($sql);
    $stm->execute([$year]);
    $results = $stm->fetchAll();
    
    // Merge DB results
    foreach ($results as $row) {
        $monthNum = (int)$row->MonthNum;
        if (isset($months[$monthNum])) {
            $months[$monthNum] = round((float)$row->MonthlyTotal, 2);
        }
    }
    
    // Convert to array of values (Jan to Dec)
    for ($i = 1; $i <= 12; $i++) {
        $data[] = $months[$i];
    }
    
    // Return JSON
    echo json_encode([
        'labels' => $labels,
        'data'   => $data
    ]);
    
} catch (Exception $e) {
    error_log('Stats yearly error: ' . $e->getMessage());
    echo json_encode([
        'labels' => $labels,
        'data'   => array_fill(0, 12, 0)
    ]);
}