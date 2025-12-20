<?php
require '../_base.php';

header('Content-Type: application/json');

// 1. Prepare the last 7 days range
$days = [];
$labels = [];
$today = new DateTime();

// Initialize arrays for the last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = (clone $today)->modify("-$i days");
    $dateKey = $date->format('Y-m-d');
    $days[$dateKey] = 0; // Default to 0 sales
    $labels[] = $date->format('M d'); // Format: "Dec 14"
}

// 2. Query Database
// IMPORTANT: Use DATE() to extract only the date part from PurchaseDate
$sql = "
    SELECT 
        DATE(o.PurchaseDate) as OrderDate,
        COALESCE(SUM(po.TotalPrice), 0) + 
        COALESCE(SUM(o.GiftWrapCost), 0) + 
        COALESCE(SUM(o.ShippingFee), 0) - 
        COALESCE(SUM(o.VoucherDiscount), 0) as DailyTotal
    FROM `order` o
    JOIN productorder po ON o.OrderID = po.OrderID
    JOIN order_status os ON o.OrderID = os.OrderID
    WHERE os.Status != 'Cancelled'
    AND DATE(o.PurchaseDate) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(o.PurchaseDate)
    ORDER BY DATE(o.PurchaseDate) ASC
";

try {
    $stm = $_db->query($sql);
    $results = $stm->fetchAll();
    
    // 3. Merge DB results into our days array
    foreach ($results as $row) {
        // The OrderDate from query is already in Y-m-d format
        if (isset($days[$row->OrderDate])) {
            $days[$row->OrderDate] = round((float)$row->DailyTotal, 2);
        }
    }
    
    // 4. Return JSON with proper labels
    echo json_encode([
        'labels' => $labels,           // Display labels like "Dec 14", "Dec 15"
        'data'   => array_values($days) // The sales amounts in order
    ]);
    
} catch (Exception $e) {
    error_log('Stats monthly error: ' . $e->getMessage());
    echo json_encode([
        'labels' => $labels,
        'data'   => array_fill(0, 7, 0)
    ]);
}