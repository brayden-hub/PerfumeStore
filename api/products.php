<?php
require '../_base.php';

$series = get('series', '');
$min = (int)get('min', 0);
$max = (int)get('max', 400);
$sort = get('sort', 'asc');

// Build query with filters
$sql = "SELECT * FROM product WHERE Price BETWEEN ? AND ?";
$params = [$min, $max];

if ($series !== '') {
    $sql .= " AND Series = ?";
    $params[] = $series;
}

// Add sorting
$sql .= $sort === 'desc' ? " ORDER BY Price DESC" : " ORDER BY Price ASC";

$stmt = $_db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)):
?>
    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: #999;">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem;">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">No products found</h3>
        <p>Try adjusting your filters or price range</p>
    </div>
<?php
else:
    foreach ($products as $p):
        $stockStatus = $p['Stock'] > 10 ? 'in-stock' : ($p['Stock'] > 0 ? 'low-stock' : 'out-of-stock');
        $stockText = $p['Stock'] > 10 ? 'In Stock' : ($p['Stock'] > 0 ? "Only {$p['Stock']} left" : 'Out of Stock');
        $stockDotClass = $p['Stock'] > 10 ? '' : ($p['Stock'] > 0 ? 'low' : 'out');
        $productId = htmlspecialchars($p['ProductID']);
?>
        <div class="product-card" data-product-id="<?= $productId ?>">
            <div class="product-image-wrapper">
                <img src="/public/images/<?= $productId ?>.png" 
                     alt="<?= htmlspecialchars($p['ProductName']) ?>" 
                     class="product-image"
                     data-product-id="<?= $productId ?>">
                <div class="product-image-overlay"></div>
                <div class="view-details-badge">VIEW DETAILS</div>
                <div class="series-badge"><?= htmlspecialchars($p['Series']) ?></div>

                    <!-- ❤️ FAVORITE BUTTON -->
                <button class="fav-btn" data-product-id="<?= $productId ?>">♡</button>
            </div>
            
            <div class="product-info">
                <h3 class="product-name"><?= htmlspecialchars($p['ProductName']) ?></h3>
                <div class="product-price">RM <?= number_format($p['Price'], 2) ?></div>
                
                <p class="product-description"><?= htmlspecialchars($p['Description']) ?></p>
                
                <div class="stock-indicator">
                    <span class="stock-dot <?= $stockDotClass ?>"></span>
                    <span><?= $stockText ?></span>
                </div>

                <button class="fav-btn" data-product-id="<?= $productId ?>">♡</button>
                <button class="add-to-cart-btn" 
                        data-product-id="<?= $productId ?>"
                        <?= $p['Stock'] <= 0 ? 'disabled' : '' ?>>
                    <?= $p['Stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
                
            </div>
        </div>
<?php
    endforeach;
endif;
?>