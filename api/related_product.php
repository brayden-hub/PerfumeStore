<?php
require '../_base.php';
$current = get('current_id');
$series  = get('series');

$stmt = $_db->prepare("
    SELECT ProductID, ProductName, Price 
    FROM product 
    WHERE Series = ? AND ProductID != ? 
    ORDER BY RAND() LIMIT 3
");
$stmt->execute([$series, $current]);
$items = $stmt->fetchAll();

foreach ($items as $item) {
    echo '
    <a href="/page/product_detail.php?id='.$item['ProductID'].'" style="text-decoration:none;color:inherit;">
        <div style="text-align:center;">
            <img src="/public/images/'.$item['ProductID'].'.png" style="width:100%;height:auto;border-radius:8px;">
            <h4 style="margin:0.8rem 0 0.3rem;font-size:1rem;">'.htmlspecialchars($item['ProductName']).'</h4>
            <p style="color:#D4AF37;">RM '.number_format($item['Price'],2).'</p>
        </div>
    </a>';
}
?>