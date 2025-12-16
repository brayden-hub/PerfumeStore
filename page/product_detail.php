<<?php
require '../_base.php';
$id = get('id');
if (!$id) redirect('/page/product.php');

$stmt = $_db->prepare("SELECT * FROM product WHERE ProductID = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

// MODIFIED: Check if product exists AND if it is "Not Available"
// If it contains "Not" in the status, we treat it as not found
if (!$p || str_contains($p->Status, 'Not')) {
    echo "<h2 style='text-align:center;padding:5rem;'>Product not found or unavailable.</h2>";
    echo "<div style='text-align:center;'><a href='/page/product.php'>Return to Shop</a></div>";
    return;
}

$_title = $p->ProductName . " - N°9 Perfume";
include '../_head.php';
?>

<link rel="stylesheet" href="/public/css/product_detail.css">

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });

    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);  
    document.addEventListener("DOMContentLoaded", () => window.scrollTo(0, 0));
</script>

<div class="detail-container" style="max-width:1400px;margin:0 auto;padding:4rem 5%;display:flex;gap:6rem;flex-wrap:wrap;align-items:flex-start;">
    <div class="detail-image" style="flex:1;min-width:400px;">
        <img src="/public/images/<?= htmlspecialchars($p->ProductID) ?>.png" 
             alt="<?= htmlspecialchars($p->ProductName) ?>" 
             style="width:100%;height:auto;border-radius:8px;box-shadow:0 20px 40px rgba(0,0,0,0.1);">
    </div>

    <div class="detail-info" style="flex:1;min-width:350px;">
        <a href="/page/product.php" style="color:#666;font-size:0.9rem;text-decoration:none;">
            ← Back to collection
        </a>

        <h1 style="font-size:3rem;font-weight:300;margin:1rem 0 0.5rem;">
            <?= htmlspecialchars($p->ProductName) ?>
        </h1>

        <p style="color:#666;margin-bottom:1.5rem;">
            Series: <?= htmlspecialchars($p->Series) ?>
        </p>

        <p style="font-size:2.2rem;font-weight:500;color:#D4AF37;margin:2rem 0;">
            RM <?= number_format($p->Price, 2) ?>
        </p>

        <p style="color:<?= $p->Stock > 10 ? '#28a745' : ($p->Stock > 0 ? '#ff9800' : '#dc3545') ?>; font-weight: 600; margin-bottom: 1rem;">
            <?php if ($p->Stock > 10): ?>
                ✓ In Stock (<?= $p->Stock ?> available)
            <?php elseif ($p->Stock > 0): ?>
                ⚠ Low Stock (Only <?= $p->Stock ?> left)
            <?php else: ?>
                ✗ Out of Stock
            <?php endif; ?>
        </p>

        <?php if ($p->Stock > 0): ?>
        <div style="display:flex;gap:1rem;margin:2rem 0;align-items:center;">
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label style="font-weight:600;">Quantity:</label>
                <button id="qty-decrease" style="padding:0.5rem 1rem;background:#f0f0f0;border:none;cursor:pointer;font-size:1.2rem;">-</button>
                <input type="number" id="quantity" value="1" min="1" max="<?= $p->Stock ?>" 
                       style="width:60px;text-align:center;padding:0.5rem;border:1px solid #ddd;font-size:1rem;">
                <button id="qty-increase" style="padding:0.5rem 1rem;background:#f0f0f0;border:none;cursor:pointer;font-size:1.2rem;">+</button>
            </div>
        </div>

        <div style="display:flex;gap:1rem;margin:2rem 0;">
            <button class="add-to-cart" data-id="<?= $p->ProductID ?>" 
                    style="flex:1;padding:1rem;background:#D4AF37;color:#000;border:none;font-size:1rem;font-weight:bold;cursor:pointer;border-radius:4px;transition:0.3s;">
                🛒 Add to Cart
            </button>
        </div>
        <?php else: ?>
        <div style="padding:1rem;background:#ffe6e6;color:#d00;border-radius:4px;margin:2rem 0;">
            This product is currently out of stock
        </div>
        <?php endif; ?>

        <!-- Fragrance Notes -->
        <div style="margin:3rem 0;line-height:2;">
            <h3 style="font-size:1.1rem;margin-bottom:1rem;color:#111;">Fragrance Notes</h3>
            <p><strong>Top:</strong> Bergamot, Pink Pepper</p>
            <p><strong>Heart:</strong> Oud, Rose, Saffron</p>
            <p><strong>Base:</strong> Amber, Vanilla, Patchouli</p>
        </div>

        <!-- Description -->
        <div style="margin:3rem 0;">
            <h3 style="font-size:1.1rem;margin-bottom:1rem;color:#111;">Description</h3>
            <p style="color:#444;line-height:1.8;">
                <?= nl2br(htmlspecialchars($p->Description)) ?>
            </p>
        </div>

        <!-- You May Also Like -->
        <div class="related" style="margin-top:4rem;">
            <h2 style="font-size:1.5rem;margin-bottom:2rem;color:#111;font-weight:400;">You May Also Like</h2>
            <div class="related-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem;">
                <?php
                $stmt = $_db->prepare("SELECT * FROM product WHERE Series = ? AND ProductID != ? ORDER BY RAND() LIMIT 3");
                $stmt->execute([$p->Series, $id]);
                $related = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($related as $r):
                    $relatedStockStatus = $r['Stock'] > 10 ? 'in-stock' : ($r['Stock'] > 0 ? 'low-stock' : 'out-of-stock');
                    $relatedStockText = $r['Stock'] > 10 ? 'In Stock' : ($r['Stock'] > 0 ? "Only {$r['Stock']} left" : 'Out of Stock');
                    $relatedStockDotClass = $r['Stock'] > 10 ? '' : ($r['Stock'] > 0 ? 'low' : 'out');
                ?>
                    <div class="related-card" data-product-id="<?= htmlspecialchars($r['ProductID']) ?>">
                        <div class="related-image-wrapper">
                            <img src="/public/images/<?= htmlspecialchars($r['ProductID']) ?>.png" 
                                 alt="<?= htmlspecialchars($r['ProductName']) ?>" 
                                 class="related-image">
                            <div class="related-image-overlay"></div>
                            <div class="view-details-badge">VIEW DETAILS</div>
                        </div>
                        <div class="related-info">
                            <h4 class="related-name"><?= htmlspecialchars($r['ProductName']) ?></h4>
                            <p class="related-price">RM <?= number_format($r['Price'], 2) ?></p>
                            <div class="stock-indicator-small">
                                <span class="stock-dot-small <?= $relatedStockDotClass ?>"></span>
                                <span><?= $relatedStockText ?></span>
                            </div>
                            <button class="quick-add-btn" 
                                    data-product-id="<?= htmlspecialchars($r['ProductID']) ?>"
                                    <?= $r['Stock'] <= 0 ? 'disabled' : '' ?>>
                                <?= $r['Stock'] <= 0 ? 'Out of Stock' : 'Quick Add' ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Quantity controls
const qtyInput = $('#quantity');
const maxStock = <?= $p->Stock ?>;

$('#qty-decrease').on('click', function() {
    let val = parseInt(qtyInput.val());
    if (val > 1) qtyInput.val(val - 1);
});

$('#qty-increase').on('click', function() {
    let val = parseInt(qtyInput.val());
    if (val < maxStock) qtyInput.val(val + 1);
});

// Add to cart (main product)
$('.add-to-cart').on('click', function() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Please login first to add items to cart');
        window.location.href = '/page/login.php';
        return;
    <?php endif; ?>
    
    const productId = $(this).data('id');
    const quantity = parseInt($('#quantity').val());
    const btn = $(this);
    
    btn.prop('disabled', true).text('Adding...');
    
    $.post('/api/cart_add.php', {
        product_id: productId,
        quantity: quantity
    }, function(response) {
        if (response.success) {
            $('#cart-count').text(response.cart_count);
            btn.text('✓ Added to Cart!').css('background', '#28a745');
            showToast('Product added to cart!');
            
            setTimeout(function() {
                btn.prop('disabled', false).text('🛒 Add to Cart').css('background', '#D4AF37');
            }, 2000);
        } else {
            alert(response.message || 'Failed to add to cart');
            btn.prop('disabled', false).text('🛒 Add to Cart');
        }
    }, 'json').fail(function() {
        alert('Error adding to cart. Please try again.');
        btn.prop('disabled', false).text('🛒 Add to Cart');
    });
});

// Related product card click (navigate to detail)
$(document).on('click', '.related-card', function(e) {
    // Don't navigate if clicking the quick add button
    if ($(e.target).hasClass('quick-add-btn') || $(e.target).closest('.quick-add-btn').length) {
        return;
    }
    
    const productId = $(this).data('product-id');
    if (productId) {
        window.location.href = `/page/product_detail.php?id=${productId}`;
    }
});

// Quick add to cart (related products)
$(document).on('click', '.quick-add-btn', function(e) {
    e.stopPropagation();
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Please login first to add items to cart');
        window.location.href = '/page/login.php';
        return;
    <?php endif; ?>
    
    const productId = $(this).data('product-id');
    const btn = $(this);
    
    if (btn.prop('disabled')) return;
    
    btn.prop('disabled', true).text('Adding...');
    
    $.post('/api/cart_add.php', {
        product_id: productId,
        quantity: 1
    }, function(response) {
        if (response.success) {
            $('#cart-count').text(response.cart_count);
            btn.text('✓ Added').css('background', '#28a745');
            showToast('Product added to cart!');
            
            setTimeout(function() {
                btn.prop('disabled', false).text('Quick Add').css('background', '#000');
            }, 2000);
        } else {
            alert(response.message || 'Failed to add to cart');
            btn.prop('disabled', false).text('Quick Add');
        }
    }, 'json').fail(function() {
        alert('Error adding to cart. Please try again.');
        btn.prop('disabled', false).text('Quick Add');
    });
});

// Toast notification
function showToast(message) {
    const toast = $(`
        <div style="
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #28a745;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
        ">
            ${message}
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, () => toast.remove());
    }, 2000);
}
</script>

<?php include '../_foot.php'; ?>