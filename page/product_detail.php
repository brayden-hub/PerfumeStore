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

$_title = $p['ProductName'] . " - N°9 Perfume";
include '../_head.php';
?>

<div class="detail-container" style="max-width:1400px;margin:0 auto;padding:4rem 5%;display:flex;gap:6rem;flex-wrap:wrap;align-items:flex-start;">
    <div class="detail-image" style="flex:1;min-width:400px;">
        <img src="/public/images/<?= htmlspecialchars($p['ProductID']) ?>.png" 
             alt="<?= htmlspecialchars($p['ProductName']) ?>" 
             style="width:100%;height:auto;border-radius:8px;box-shadow:0 20px 40px rgba(0,0,0,0.1);">
    </div>

    <div class="detail-info" style="flex:1;min-width:350px;">
        <a href="/page/product.php" style="color:#666;font-size:0.9rem;text-decoration:none;">
            ← Back to collection
        </a>

        <h1 style="font-size:3rem;font-weight:300;margin:1rem 0 0.5rem;">
            <?= htmlspecialchars($p['ProductName']) ?>
        </h1>

        <p style="color:#666;margin-bottom:1.5rem;">
            Series：<?= htmlspecialchars($p['Series']) ?>
        </p>

        <p style="font-size:2.2rem;font-weight:500;color:#D4AF37;margin:2rem 0;">
            RM <?= number_format($p['Price'], 2) ?>
        </p>

        <div style="display:flex;gap:1rem;margin:2rem 0;">
            <button class="add-to-cart btn-gold" data-id="<?= $p['ProductID'] ?>" 
                    style="flex:1;padding:1rem;background:#D4AF37;color:#000;border:none;font-size:1rem;cursor:pointer;">
                Add to Cart
            </button>
            <button class="wishlist" data-id="<?= $p['ProductID'] ?>" 
                    style="padding:1rem 1.5rem;background:transparent;border:1px solid #666;color:#666;cursor:pointer;">
                ♡ Wishlist
            </button>
        </div>

        <!-- 香調金字塔（可自行填） -->
        <div style="margin:3rem 0;line-height:2;">
            <h3 style="font-size:1.1rem;margin-bottom:1rem;color:#111;">Fragrance Notes</h3>
            <p><strong>Top：</strong> Bergamot, Pink Pepper</p>
            <p><strong>Heart：</strong> Oud, Rose, Saffron</p>
            <p><strong>Base：</strong> Amber, Vanilla, Patchouli</p>
        </div>

        <!-- 調香故事 -->
        <div style="margin:3rem 0;">
            <h3 style="font-size:1.1rem;margin-bottom:1rem;color:#111;">The Story</h3>
            <p style="color:#444;line-height:1.8;">
                <?= nl2br(htmlspecialchars($p['Description'])) ?>
            </p>
        </div>

        <!-- 你可能也喜歡 -->
        <div style="margin-top:4rem;">
            <h3 style="font-size:1.1rem;margin-bottom:1.5rem;color:#111;">You May Also Like</h3>
            <div id="related-products" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.5rem;">
                <!-- AJAX 載入 -->
            </div>
        </div>
    </div>
</div>

<script>
$('.add-to-cart').on('click', function() {
    alert('Added to cart!（之後會改成真正的 cart 功能）');
});

$.get('/api/related_products.php', {current_id: '<?= $p['ProductID'] ?>', series: '<?= $p['Series'] ?>'}, function(html) {
    $('#related-products').html(html);
});
</script>

<?php include '../_foot.php'; ?>