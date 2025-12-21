<?php
require '../_base.php';

$series = get('series', '');
$min = (int)get('min', 0);
$max = (int)get('max', 400);
$sort = get('sort', 'asc');
$page = (int)get('page', 1);
$search = get('search', ''); // New search parameter

// Items per page
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Base SQL with filters
$sql = "SELECT * FROM product 
        WHERE Price BETWEEN ? AND ? 
        AND Status LIKE '%Available%' 
        AND Status NOT LIKE '%Not%'";

$params = [$min, $max];

// Add series filter
if ($series !== '') {
    $sql .= " AND Series = ?";
    $params[] = $series;
}

// Add search filter (ID or Name)
if ($search !== '') {
    $sql .= " AND (ProductID LIKE ? OR ProductName LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count for pagination
$count_sql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
$count_stmt = $_db->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// Add sorting and pagination
$sql .= $sort === 'desc' ? " ORDER BY Price DESC" : " ORDER BY Price ASC";
$sql .= " LIMIT $items_per_page OFFSET $offset";

$stmt = $_db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is logged in and NOT admin, then get favorites
$user_favorites = [];
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';

if (isset($_SESSION['user_id']) && !$is_admin) {
    $fav_stmt = $_db->prepare("SELECT ProductID FROM favorites WHERE UserID = ?");
    $fav_stmt->execute([$_SESSION['user_id']]);
    $user_favorites = $fav_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Helper function to get product image path
function getProductImagePath($productId) {
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    
    foreach ($extensions as $ext) {
        $path = "../public/images/{$productId}.{$ext}";
        if (file_exists($path)) {
            return "/public/images/{$productId}.{$ext}";
        }
    }
    
    // Fallback to default image
    return '/public/images/photo.jpg';
}

if (empty($products)):
?>
    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: #999;">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem;">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">No products found</h3>
        <p>Try adjusting your filters or search terms</p>
    </div>
<?php
else:
    foreach ($products as $p):
        $stockStatus = $p['Stock'] > 10 ? 'in-stock' : ($p['Stock'] > 0 ? 'low-stock' : 'out-of-stock');
        $stockText = $p['Stock'] > 10 ? 'In Stock' : ($p['Stock'] > 0 ? "Only {$p['Stock']} left" : 'Out of Stock');
        $stockDotClass = $p['Stock'] > 10 ? '' : ($p['Stock'] > 0 ? 'low' : 'out');
        $productId = htmlspecialchars($p['ProductID']);
        $isFavorited = in_array($p['ProductID'], $user_favorites);
        
        // Get the correct image path (supports all formats)
        $imagePath = getProductImagePath($p['ProductID']);
?>
        <div class="product-card" data-product-id="<?= $productId ?>">
            <div class="product-image-wrapper">
                <img src="<?= $imagePath ?>" 
                     alt="<?= htmlspecialchars($p['ProductName']) ?>" 
                     class="product-image"
                     data-product-id="<?= $productId ?>">
                <div class="product-image-overlay"></div>
                <div class="view-details-badge">VIEW DETAILS</div>
                <div class="series-badge"><?= htmlspecialchars($p['Series']) ?></div>

                <!-- Favorite Button - Only show for non-admin users -->
                <?php if (!$is_admin): ?>
                <button 
                    class="fav-btn <?= $isFavorited ? 'active' : '' ?>" 
                    data-product-id="<?= $productId ?>"
                >
                    <?= $isFavorited ? '♥' : '♡' ?>
                </button>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h3 class="product-name"><?= htmlspecialchars($p['ProductName']) ?></h3>
                <div class="product-price">RM <?= number_format($p['Price'], 2) ?></div>
                
                <p class="product-description"><?= htmlspecialchars($p['Description']) ?></p>
                
                <div class="stock-indicator">
                    <span class="stock-dot <?= $stockDotClass ?>"></span>
                    <span><?= $stockText ?></span>
                </div>

                <button class="add-to-cart-btn" 
                        data-product-id="<?= $productId ?>"
                        <?= $p['Stock'] <= 0 ? 'disabled' : '' ?>>
                    <?= $p['Stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
            </div>
        </div>
<?php
    endforeach;
    
    // Pagination Controls
    if ($total_pages > 1):
?>
    <div style="grid-column: 1 / -1; display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem;">
        <?php if ($page > 1): ?>
            <button class="pagination-btn" data-page="<?= $page - 1 ?>" style="padding: 0.5rem 1rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                ← Previous
            </button>
        <?php endif; ?>
        
        <?php
        // Show page numbers with ellipsis for large page counts
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1): ?>
            <button class="pagination-btn" data-page="1" style="padding: 0.5rem 1rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">1</button>
            <?php if ($start_page > 2): ?>
                <span style="padding: 0 0.5rem;">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <button class="pagination-btn" 
                    data-page="<?= $i ?>" 
                    style="padding: 0.5rem 1rem; background: <?= $i === $page ? '#D4AF37' : '#f0f0f0' ?>; color: <?= $i === $page ? '#000' : '#333' ?>; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-weight: <?= $i === $page ? 'bold' : 'normal' ?>;">
                <?= $i ?>
            </button>
        <?php endfor; ?>
        
        <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
                <span style="padding: 0 0.5rem;">...</span>
            <?php endif; ?>
            <button class="pagination-btn" data-page="<?= $total_pages ?>" style="padding: 0.5rem 1rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;"><?= $total_pages ?></button>
        <?php endif; ?>
        
        <?php if ($page < $total_pages): ?>
            <button class="pagination-btn" data-page="<?= $page + 1 ?>" style="padding: 0.5rem 1rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                Next →
            </button>
        <?php endif; ?>
    </div>
    
    <div style="grid-column: 1 / -1; text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem;">
        Showing <?= ($offset + 1) ?> - <?= min($offset + $items_per_page, $total_items) ?> of <?= $total_items ?> products
    </div>
<?php
    endif;
endif;
?>