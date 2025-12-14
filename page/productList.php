<?php
require '../_base.php';
require '../lib/SimplePager.php'; // Include the Pager class

// Security Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// ============================================================================
// 1. GET PARAMETERS (Search, Filter, Sort)
// ============================================================================
$search = req('search');
$series = req('series');
$sort   = req('sort');
$dir    = req('dir');

// Define allowed sorting columns (Key = URL param, Value = DB Column)
$sort_columns = [
    'id'     => 'ProductID',
    'series' => 'Series',
    'name'   => 'ProductName',
    'price'  => 'Price',
    'stock'  => 'Stock'
];

// Validate Sort Column (Default to 'id')
if (!array_key_exists($sort, $sort_columns)) {
    $sort = 'id';
}

// Validate Direction (Default to 'ASC')
$dir = ($dir === 'DESC') ? 'DESC' : 'ASC';

// ============================================================================
// 2. BUILD QUERY
// ============================================================================
$sql = "SELECT * FROM product WHERE 1=1";
$params = [];

// Apply Search (Name or ID)
if ($search) {
    $sql .= " AND (ProductName LIKE ? OR ProductID LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Apply Series Filter
if ($series) {
    $sql .= " AND Series = ?";
    $params[] = $series;
}

// Apply Sorting
$order_by = $sort_columns[$sort];
$sql .= " ORDER BY $order_by $dir";

// ============================================================================
// 3. EXECUTE WITH PAGER
// ============================================================================
// Using SimplePager(Query, Params, ItemsPerPage, CurrentPage)
$page = req('page', 1);
$pager = new SimplePager($sql, $params, 10, $page);
$arr = $pager->result;

// ============================================================================
// 4. HELPER: Generate Sort Link
// ============================================================================
function sort_link($label, $col, $current_sort, $current_dir) {
    global $search, $series; // Keep search/series when sorting
    
    // Toggle direction if clicking the same column
    $new_dir = ($col === $current_sort && $current_dir === 'ASC') ? 'DESC' : 'ASC';
    
    // Add arrow icon
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
    $(document).ready(function() {
        window.scrollTo(0, 0);
    })
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
                    'Fresh' => 'Fresh', 
                    'Floral' => 'Floral', 
                    'Fruity' => 'Fruity', 
                    'Green' => 'Green', 
                    'Woody' => 'Woody'
                ], $series) ?>
            </select>
            
            <button type="submit" class="btn-search">Search</button>
            <a href="productList.php" class="btn-reset">Reset</a>
        </div>
        
        <div>
            <strong><?= $pager->item_count ?></strong> record(s) found
        </div>
    </form>

    <table class="product-table">
        <thead>
            <tr>
                <th><?= sort_link('ID', 'id', $sort, $dir) ?></th>
                <th><?= sort_link('Series', 'series', $sort, $dir) ?></th>
                <th><?= sort_link('Name', 'name', $sort, $dir) ?></th>
                <th><?= sort_link('Price (RM)', 'price', $sort, $dir) ?></th>
                <th><?= sort_link('Stock', 'stock', $sort, $dir) ?></th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($arr as $p): ?>
            <?php 
                $imgName = $p->ProductID;
                $imgSrc = "";
                // Logic to find image (jpg/png) or use placeholder
                if ($p->Image && file_exists("../public/images/{$p->Image}")) {
                     $imgSrc = "/public/images/{$p->Image}";
                }
                // Fallback for older logic
                elseif (file_exists("../public/images/$imgName.jpg")) {
                    $imgSrc = "/public/images/$imgName.jpg";
                } 
                elseif (file_exists("../public/images/$imgName.png")) {
                    $imgSrc = "/public/images/$imgName.png";
                }
            ?>

            <tr>
                <td><?= $p->ProductID ?></td>
                <td><?= $p->Series ?></td>
                <td><?= $p->ProductName ?></td>
                <td><?= number_format($p->Price, 2) ?></td>
                
                <td class="<?= $p->Stock < 10 ? 'stock-low' : '' ?>">
                    <?= $p->Stock ?>
                </td>
                <td>
                    <?php if ($imgSrc): ?>
                        <img src="<?= $imgSrc ?>" class="thumb-img" alt="<?= $p->ProductName ?>">
                    <?php else: ?>
                        <div style="width:60px; height:60px; background:#eee; display:flex; align-items:center; justify-content:center; border:1px solid #ccc; font-size:10px; color:#999;">
                            No Img
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <button data-get="product_edit.php?productID=<?= $p->ProductID ?>" class="action-btn btn-edit">Edit</button>
                    <button data-post="product_delete.php?productID=<?= $p->ProductID ?>" 
                            data-confirm="Are you sure you want to delete <?= htmlspecialchars($p->ProductName) ?>?"
                            class="action-btn btn-delete">
                        Delete
                    </button>
                </td>
            </tr>
            <?php endforeach ?>
            
            <?php if(count($arr) == 0): ?>
                <tr><td colspan="7" style="text-align:center; padding: 20px;">No products found matching your criteria.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?= $pager->html("search=$search&series=$series&sort=$sort&dir=$dir") ?>
</div>

<?php include '../_foot.php'; ?>

<?php
// Helper to generate options for select without echoing directly (since html_select in _base echoes)
function html_select_options($items, $selected) {
    $html = '';
    foreach ($items as $val => $text) {
        $state = ($val == $selected) ? 'selected' : '';
        $html .= "<option value='$val' $state>$text</option>";
    }
    return $html;
}
?>