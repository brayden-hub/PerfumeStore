<?php
require '../_base.php';

$series = get('series', '');
$min    = (float)get('min', 0);
$max    = (float)get('max', 1000);
$sort   = get('sort', 'asc') === 'desc' ? 'DESC' : 'ASC';

$sql = "SELECT ProductID, ProductName, Description, Price FROM product WHERE Price BETWEEN ? AND ?";
$params = [$min, $max];

if ($series !== '') {
    $sql .= " AND Series = ?";
    $params[] = $series;
}

$sql .= " ORDER BY Price $sort";

$stmt = $_db->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() == 0) {
    echo '<p style="text-align: center; color: #666;">No products found.</p>';
} else {
    while ($p = $stmt->fetch()) {
        $img_file = '/public/images/' . htmlspecialchars($p->ProductID) . '.png';  
        $img = file_exists($_SERVER['DOCUMENT_ROOT'] . $img_file) ? $img_file : 'https://placehold.co/400x500?text=No+Image';
        echo '
        <div class="product-card" style="background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); transition:0.3s;">
            <img src="'.$img.'" style="width:100%; height:320px; object-fit:cover;">
            <div style="padding:1.5rem;">
                <h3 style="font-size:1.1rem; margin:0 0 0.5rem; color:#111;">'.htmlspecialchars($p->ProductName).'</h3>
                <p style="color:#666; font-size:0.9rem; margin:0 0 1rem;">'.htmlspecialchars($p->Description).'</p>
                <p style="font-size:1.6rem; font-weight:600; color:#D4AF37;">RM '.number_format($p->Price,2).'</p>
                <a href="/page/product_detail.php?id='.$p->ProductID.'" style="display:block; margin-top:1rem; text-align:center; color:#D4AF37; text-decoration:none;">View Detail →</a>
            </div>
        </div>';
    }
}
?>