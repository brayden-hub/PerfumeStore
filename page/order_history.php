<?php
require '../_base.php';
require '../lib/SimplePager.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Member') {
    redirect('/page/login.php');
}

$user_id = $_SESSION['user_id'];

// 2. Get Parameters
$search = req('search');
$sort   = req('sort');
$dir    = req('dir');
$page   = req('page', 1);

// 3. Define Sort Columns (REMOVED 'total' to fix error)
$sort_columns = [
    'id'     => 'o.OrderID',
    'date'   => 'o.PurchaseDate',
    'status' => 'os.Status'
];

// Validate Sort
if (!array_key_exists($sort, $sort_columns)) {
    $sort = 'date';
}
$dir = ($dir === 'ASC') ? 'ASC' : 'DESC';

// 4. Build Query
$sql = "
    SELECT o.OrderID, o.PurchaseDate, o.PaymentMethod, o.GiftWrapCost, o.ShippingFee, o.VoucherDiscount,
           os.Status,
           /* Subqueries for totals */
           (SELECT COALESCE(SUM(TotalPrice), 0) FROM productorder WHERE OrderID = o.OrderID) as ProductTotal,
           (SELECT COUNT(*) FROM productorder WHERE OrderID = o.OrderID) as ItemCount,
           (SELECT p.ProductName FROM productorder po2 JOIN product p ON po2.ProductID = p.ProductID WHERE po2.OrderID = o.OrderID ORDER BY po2.ProductOrderID ASC LIMIT 1) as FirstProductName,
           (SELECT ProductID FROM productorder WHERE OrderID = o.OrderID ORDER BY ProductOrderID ASC LIMIT 1) as FirstProductID
    FROM `order` o
    JOIN order_status os ON o.OrderID = os.OrderID
    WHERE o.UserID = ?
";

$params = [$user_id];

// 5. Apply Search
if ($search) {
    $sql .= " AND (o.OrderID LIKE ? OR os.Status LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 6. Apply Sorting
$order_by = $sort_columns[$sort];
$sql .= " ORDER BY $order_by $dir";

// 7. Pagination
$pager = new SimplePager($sql, $params, 10, $page);
$orders = $pager->result;

// 8. Helper
function sort_link($label, $col, $current_sort, $current_dir, $search) {
    $new_dir = ($col === $current_sort && $current_dir === 'ASC') ? 'DESC' : 'ASC';
    $icon = ($col === $current_sort) ? ($current_dir === 'ASC' ? ' ▲' : ' ▼') : '';
    $url = "?sort=$col&dir=$new_dir&search=" . urlencode($search);
    return "<a href='$url' class='sort-link'>$label $icon</a>";
}

$_title = 'My Order History - N°9 Perfume';
include '../_head.php';
?>

<style>
    .filter-container { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
    .filter-group { display: flex; align-items: center; gap: 10px; }
    .filter-group input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; width: 300px; }
    .btn-search { background:#000; color:#D4AF37; border:1px solid #000; padding:8px 15px; border-radius:4px; cursor:pointer; }
    .btn-reset { color:#666; margin-left:10px; text-decoration:none; font-size:0.9rem; }
    
    th a.sort-link { color: #fff; text-decoration: none; display: block; }
    th a.sort-link:hover { color: #D4AF37; }
    
    .status-badge { padding: 4px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: 600; display:inline-block; }
    .status-Pending { background: #fff3e0; color: #e65100; }
    .status-Processing { background: #e3f2fd; color: #1565c0; }
    .status-Shipped { background: #f3e5f5; color: #7b1fa2; }
    .status-Delivered { background: #e8f5e9; color: #2e7d32; }
    .status-Cancelled { background: #ffebee; color: #c62828; }
</style>

<div class="container" style="margin-top: 30px; min-height: 60vh;">
    
    <div style="margin-bottom: 20px;">
        <h2>My Order History</h2>
    </div>

    <form method="get" class="filter-container">
        <div class="filter-group">
            <input type="text" name="search" placeholder="Search Order ID or Status..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-search">Search</button>
            <a href="order_history.php" class="btn-reset">Reset</a>
        </div>
        <div><strong><?= $pager->item_count ?></strong> record(s) found</div>
    </form>

    <table class="product-table">
        <thead>
            <tr>
                <th><?= sort_link('Order ID', 'id', $sort, $dir, $search) ?></th>
                <th>Product Preview</th>
                <th><?= sort_link('Date', 'date', $sort, $dir, $search) ?></th>
                <th>Total (RM)</th> 
                <th><?= sort_link('Status', 'status', $sort, $dir, $search) ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): 
                $grand_total = $o->ProductTotal + ($o->GiftWrapCost ?? 0) + ($o->ShippingFee ?? 0) - ($o->VoucherDiscount ?? 0);
                
                $imgSrc = '/public/images/photo.jpg'; 
                if ($o->FirstProductID) {
                    if (file_exists("../public/images/{$o->FirstProductID}.jpg")) $imgSrc = "/public/images/{$o->FirstProductID}.jpg";
                    elseif (file_exists("../public/images/{$o->FirstProductID}.png")) $imgSrc = "/public/images/{$o->FirstProductID}.png";
                }
            ?>
            <tr>
                <td style="font-weight:bold; color:#D4AF37;"><?= $o->OrderID ?></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="<?= $imgSrc ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #eee;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($o->FirstProductName ?? 'Unknown Product') ?></div>
                            <?php if ($o->ItemCount > 1): ?>
                                <div style="font-size: 0.8rem; color: #999;">+ <?= $o->ItemCount - 1 ?> more items</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><?= date('d M Y', strtotime($o->PurchaseDate)) ?></td>
                <td style="font-weight: 600;">RM <?= number_format($grand_total, 2) ?></td>
                <td><span class="status-badge status-<?= $o->Status ?>"><?= $o->Status ?></span></td>
                <td>
                    <a href="order_detail_member.php?id=<?= $o->OrderID ?>" class="action-btn" style="background: #000;">Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(count($orders) == 0): ?>
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?= $pager->html("search=$search&sort=$sort&dir=$dir") ?>
</div>

<?php include '../_foot.php'; ?>