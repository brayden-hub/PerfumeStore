<?php
require '../_base.php';
$id = get('id');
if (!$id) redirect('/page/product.php');

$stmt = $_db->prepare("SELECT * FROM product WHERE ProductID = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    echo "<h2 style='text-align:center;padding:5rem;'>Product not found</h2>";
    return;
}

$_title = $p->ProductName . " - N°9 Perfume";
include '../_head.php';
?>

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
            <h2 style="font-size:1.1rem;margin-bottom:1.5rem;color:#111;">You May Also Like</h2>
            <div class="related-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.5rem;">
                <?php
                $stmt = $_db->prepare("SELECT * FROM product WHERE Series = ? AND ProductID != ? ORDER BY RAND() LIMIT 3");
                $stmt->execute([$p->Series, $id]);
                $related = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($related as $r):
                ?>
                    <a href="/page/product_detail.php?id=<?= $r['ProductID'] ?>" class="related-card" style="text-decoration:none;">
                        <img src="/public/images/<?= $r['ProductID'] ?>.png" alt="<?= $r['ProductName'] ?>" style="width:100%;height:auto;border-radius:8px;">
                        <h4 style="margin:0.8rem 0 0.3rem;font-size:1rem; color:#000"><?= $r['ProductName'] ?></h4>
                        <p style="color:#D4AF37;">RM <?= number_format($r['Price'],2) ?></p>
                    </a>
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

// Add to cart
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
            // Update cart count
            $('#cart-count').text(response.cart_count);
            
            // Show success message
            btn.text('✓ Added to Cart!').css('background', '#28a745');
            
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

// Load related products
$.get('/api/related_product.php', {
    current_id: '<?= $p->ProductID ?>', 
    series: '<?= $p->Series ?>'
}, function(html) {
    $('#related-products').html(html);
});
</script>

<?php include '../_foot.php'; ?>