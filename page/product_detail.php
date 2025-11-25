<?php
require '../_base.php';
$id = get('id');

$stm = $_db->prepare("SELECT * FROM product WHERE ProductID = ?");
$stm->execute([$id]);
$product = $stm->fetch();

$_title = $product['ProductName'] ?? 'Product Detail';
include '../_head.php';
?>

<div style="padding:2rem;">
    <h2><?= htmlspecialchars($product['ProductName']) ?></h2>
    <img src="public/images/product/<?= htmlspecialchars($product['ProductID']) ?>.png" alt="<?= htmlspecialchars($product['ProductName']) ?>" style="width: 300px;">
    <p>Series: <?= htmlspecialchars($product['Series']) ?></p>
    <p>Description: <?= htmlspecialchars($product['Description']) ?></p>
    <p>Price: RM <?= number_format($product['Price'], 2) ?></p>
    <p>Stock: <?= htmlspecialchars($product['Stock']) ?></p>
    <button class="button product">Add to Cart</button>
    <button class="button wishlist">Add to Wishlist</button>
</div>

<?php include '../_foot.php'; ?>