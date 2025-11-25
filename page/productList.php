<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$_title = 'Product List - N°9 Perfume';
include '../_head.php';

// Fetch all products
$stm = $_db->prepare('SELECT * FROM product ORDER BY ProductID ASC');
$stm->execute();
$arr = $stm->fetchAll();
?>

<div class="container" style="margin-top: 30px;">
    
    <div class="admin-header">
        <h2>Product Management</h2>
        <a href="product_create.php" class="btn-add">+ Add New Product</a>
    </div>

    <p><?= count($arr) ?> record(s) found</p>

    <table class="product-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Series</th>
                <th>Name</th>
                <th>Price (RM)</th>
                <th>Stock</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($arr as $p): ?>
            <?php 
                $imgName = $p->ProductID;
                $imgSrc = "";
                
                // Check if file exists in public/images
                if (file_exists("../public/images/$imgName.jpg")) {
                    $imgSrc = "/public/images/$imgName.jpg";
                } 
                elseif (file_exists("../public/images/$imgName.png")) {
                    $imgSrc = "/public/images/$imgName.png";
                }
            ?>

            <tr>
                <td><?= htmlspecialchars($p->ProductID) ?></td>
                <td><?= htmlspecialchars($p->Series) ?></td>
                <td><?= htmlspecialchars($p->ProductName) ?></td>
                <td><?= number_format($p->Price, 2) ?></td>
                
                <td class="<?= $p->Stock < 10 ? 'stock-low' : '' ?>">
                    <?= $p->Stock ?>
                </td>
                <td>
                    <?php if ($imgSrc): ?>
                        <img src="<?= $imgSrc ?>" class="thumb-img" alt="<?= htmlspecialchars($p->ProductName) ?>">
                    <?php else: ?>
                        <div style="width:60px; height:60px; background:#eee; display:flex; align-items:center; justify-content:center; border:1px solid #ccc; font-size:10px; color:#999;">
                            No Img
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="product_edit.php?id=<?= $p->ProductID ?>" class="action-btn btn-edit">Edit</a>
                    <a href="product_delete.php?id=<?= $p->ProductID ?>" 
                    class="action-btn btn-delete"
                    onclick="return confirm('Delete <?= htmlspecialchars($p->ProductName) ?>?');">
                    Delete
                    </a>
                </td>
            </tr>
            <?php endforeach ?>
            
            <?php if(count($arr) == 0): ?>
                <tr><td colspan="7" style="text-align:center;">No products found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../_foot.php'; ?>