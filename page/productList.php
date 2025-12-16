<?php
require '../_base.php';
require '../lib/SimplePager.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$search = req('search');
$series = req('series');
$sort   = req('sort');
$dir    = req('dir');

$sort_columns = [
    'id'     => 'ProductID',
    'series' => 'Series',
    'name'   => 'ProductName',
    'price'  => 'Price',
    'stock'  => 'Stock',
    'status' => "CASE WHEN Status LIKE '%Not%' THEN 1 ELSE 0 END"
];

if (!array_key_exists($sort, $sort_columns)) $sort = 'id';
$dir = ($dir === 'DESC') ? 'DESC' : 'ASC';

$sql = "SELECT * FROM product WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (ProductName LIKE ? OR ProductID LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($series) {
    $sql .= " AND Series = ?";
    $params[] = $series;
}

$order_by = $sort_columns[$sort];

$sql .= " ORDER BY $order_by $dir, ProductID ASC";

$page = req('page', 1);
$pager = new SimplePager($sql, $params, 10, $page);
$arr = $pager->result;

function sort_link($label, $col, $current_sort, $current_dir) {
    global $search, $series;
    $new_dir = ($col === $current_sort && $current_dir === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($col === $current_sort) {
        $icon = ($current_dir === 'ASC') ? ' ▲' : ' ▼';
    }
    $url = "?sort=$col&dir=$new_dir&search=$search&series=$series";
    return "<a href='$url' class='sort-link'>$label $icon</a>";
}

$_title = 'Product List - N°9 Perfume';
include '../_head.php';
?>

<script>
    $(document).ready(function() { window.scrollTo(0, 0); })
</script>

<div class="container" style="margin-top: 30px;">
    
    <div class="admin-header">
        <h2>Product Management</h2>
        <a href="product_add.php" class="btn-add">+ Add New Product</a>
    </div>

    <form method="get" class="filter-container">
        <div class="filter-group">
            <input type="text" name="search" placeholder="Search ID or Name..." value="<?= htmlspecialchars($search) ?>">
            <select name="series">
                <option value="">- All Series -</option>
                <?= html_select_options([
                    'Fresh' => 'Fresh', 'Floral' => 'Floral', 'Fruity' => 'Fruity', 
                    'Green' => 'Green', 'Woody' => 'Woody'
                ], $series) ?>
            </select>
            <button type="submit" class="btn-search">Search</button>
            <a href="productList.php" class="btn-reset">Reset</a>
        </div>
        <div><strong><?= $pager->item_count ?></strong> record(s) found</div>
    </form>

    <table class="product-table">
        <thead>
            <tr>
                <th><?= sort_link('ID', 'id', $sort, $dir) ?></th>
                <th><?= sort_link('Series', 'series', $sort, $dir) ?></th>
                <th><?= sort_link('Name', 'name', $sort, $dir) ?></th>
                <th><?= sort_link('Price (RM)', 'price', $sort, $dir) ?></th>
                <th><?= sort_link('Stock', 'stock', $sort, $dir) ?></th>
                <th><?= sort_link('Status', 'status', $sort, $dir) ?></th> <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($arr as $p): ?>
            <?php 
                $imgName = $p->ProductID;
                $imgSrc = "";
                if ($p->Image && file_exists("../public/images/{$p->Image}")) {
                     $imgSrc = "/public/images/{$p->Image}";
                } elseif (file_exists("../public/images/$imgName.png")) {
                    $imgSrc = "/public/images/$imgName.png";
                }
                
                // Determine if product is currently available
                $isAvailable = !str_contains($p->Status, 'Not');
                $rowStyle = $isAvailable ? '' : 'opacity: 0.6; background: #f9f9f9;';
            ?>

            <tr style="<?= $rowStyle ?>">
                <td><?= $p->ProductID ?></td>
                <td><?= $p->Series ?></td>
                <td><?= $p->ProductName ?></td>
                <td><?= number_format($p->Price, 2) ?></td>
                <td class="<?= $p->Stock < 10 ? 'stock-low' : '' ?>"><?= $p->Stock ?></td>
                
                <td>
                    <?php if ($isAvailable): ?>
                        <span style="color:green; font-weight:bold;">Active</span>
                    <?php else: ?>
                        <span style="color:red; font-weight:bold;">Inactive</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($imgSrc): ?>
                        <img src="<?= $imgSrc ?>" class="thumb-img" alt="<?= $p->ProductName ?>">
                    <?php else: ?>
                        <small>No Img</small>
                    <?php endif; ?>
                </td>
                
                <td>
                    <button data-get="product_edit.php?productID=<?= $p->ProductID ?>" class="action-btn btn-edit">Edit</button>
                    
                    <?php if ($isAvailable): ?>
                        <button data-post="product_delete.php?productID=<?= $p->ProductID ?>" 
                                data-confirm="Hide <?= htmlspecialchars($p->ProductName) ?> from the shop?"
                                class="action-btn btn-delete"
                                style="background-color: #dc3545;">
                            Deactivate
                        </button>
                    <?php else: ?>
                        <button data-post="product_delete.php?productID=<?= $p->ProductID ?>" 
                                data-confirm="Show <?= htmlspecialchars($p->ProductName) ?> in the shop again?"
                                class="action-btn btn-delete" 
                                style="background-color: #28a745;">
                            Activate
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?= $pager->html("search=$search&series=$series&sort=$sort&dir=$dir") ?>
</div>

<?php include '../_foot.php'; ?>

<?php
function html_select_options($items, $selected) {
    $html = '';
    foreach ($items as $val => $text) {
        $state = ($val == $selected) ? 'selected' : '';
        $html .= "<option value='$val' $state>$text</option>";
    }
    return $html;
}
?>