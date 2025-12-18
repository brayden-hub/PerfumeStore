<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// 计算当前购物车的 subtotal
$subtotal = 0;
$stmt = $_db->prepare("
    SELECT c.Quantity, p.Price
    FROM cart c
    JOIN product p ON c.ProductID = p.ProductID
    WHERE c.UserID = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

foreach ($cart_items as $item) {
    $subtotal += $item->Price * $item->Quantity;
}

// 获取用户的所有 voucher
$stm = $_db->prepare("
    SELECT 
        uv.UserVoucherID,
        v.Code,
        v.DiscountType,
        v.DiscountValue,
        v.MinSpend,
        v.ExpiryDate,
        uv.IsUsed,
        uv.AssignedAt
    FROM user_voucher uv
    JOIN voucher v ON uv.VoucherID = v.VoucherID
    WHERE uv.UserID = ?
    ORDER BY 
        uv.IsUsed ASC,
        v.MinSpend ASC,
        uv.AssignedAt DESC
");
$stm->execute([$user_id]);
$vouchers = $stm->fetchAll(PDO::FETCH_ASSOC);

// 分类 vouchers
$available_vouchers = array_filter($vouchers, function($v) use ($subtotal) {
    return !$v['IsUsed'] && 
           ($v['ExpiryDate'] === null || strtotime($v['ExpiryDate']) >= strtotime('today')) &&
           $subtotal >= $v['MinSpend'];
});

$locked_vouchers = array_filter($vouchers, function($v) use ($subtotal) {
    return !$v['IsUsed'] && 
           ($v['ExpiryDate'] === null || strtotime($v['ExpiryDate']) >= strtotime('today')) &&
           $subtotal < $v['MinSpend'];
});

$used_vouchers = array_filter($vouchers, function($v) {
    return $v['IsUsed'];
});

$expired_vouchers = array_filter($vouchers, function($v) {
    return !$v['IsUsed'] && 
           $v['ExpiryDate'] !== null && 
           strtotime($v['ExpiryDate']) < strtotime('today');
});

$_title = 'My Vouchers - Nº9 Perfume';
include '../_head.php';
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<style>
.voucher-container {
    max-width: 1200px;
    margin: 100px auto 50px;
    padding: 0 2rem;
}

.voucher-header {
    margin-bottom: 2rem;
}

.voucher-title {
    font-size: 2rem;
    font-weight: 300;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-info {
    background: #fffbf0;
    border: 2px solid #D4AF37;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    text-align: center;
}

.voucher-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #D4AF37;
}

.stat-label {
    color: #666;
    margin-top: 0.5rem;
    font-size: 0.9rem;
}

.voucher-section {
    margin-bottom: 3rem;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.voucher-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.voucher-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
}

.voucher-card.available {
    border-color: #D4AF37;
}

.voucher-card.available:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
}

.voucher-card.locked {
    opacity: 0.6;
    background: #f9f9f9;
}

.voucher-card.used {
    opacity: 0.5;
    background: #fafafa;
}

.voucher-card.expired {
    opacity: 0.4;
    background: #fafafa;
    border-color: #ddd;
}

.voucher-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.voucher-discount {
    font-size: 2rem;
    font-weight: bold;
    color: #D4AF37;
    margin-bottom: 0.5rem;
}

.voucher-code {
    background: #f0f0f0;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-family: monospace;
    font-size: 1rem;
    font-weight: bold;
    color: #333;
    display: inline-block;
    margin-bottom: 1rem;
}

.voucher-details {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.6;
}

.unlock-info {
    color: #dc3545;
    font-weight: 600;
    margin-top: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #999;
}

.btn-checkout {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.8rem 1.5rem;
    background: #D4AF37;
    color: #000;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-checkout:hover {
    background: #000;
    color: #D4AF37;
}

.btn-back {
    display: inline-block;
    margin-bottom: 1rem;
    color: #666;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
}

.btn-back:hover {
    color: #D4AF37;
}

</style>

<div class="voucher-container">
    <div class="voucher-header">
        <a href="/page/profile.php" class="btn-back">← Go Back</a>
        <h1 class="voucher-title">🎟️ My Vouchers</h1>
    </div>

    <?php if ($subtotal > 0): ?>
        <div class="cart-info">
            💰 Your current cart total: <strong>RM <?= number_format($subtotal, 2) ?></strong>
            <br>
            <small>Add more items to unlock higher-value vouchers!</small>
        </div>
    <?php else: ?>
        <div class="cart-info">
            🛒 Your cart is empty. Add items to see which vouchers you can use!
            <br>
            <a href="/page/product.php" class="btn-checkout">Start Shopping</a>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="voucher-stats">
        <div class="stat-card">
            <div class="stat-number"><?= count($available_vouchers) ?></div>
            <div class="stat-label">✅ Ready to Use</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($locked_vouchers) ?></div>
            <div class="stat-label">🔒 Locked</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($used_vouchers) ?></div>
            <div class="stat-label">✓ Used</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($expired_vouchers) ?></div>
            <div class="stat-label">⏰ Expired</div>
        </div>
    </div>

    <!-- Available Vouchers -->
    <?php if (!empty($available_vouchers)): ?>
        <div class="voucher-section">
            <h3 class="section-title">✅ Ready to Use</h3>
            <div class="voucher-grid">
                <?php foreach ($available_vouchers as $v): ?>
                    <div class="voucher-card available">
                        <div class="voucher-icon">🎁</div>
                        <div class="voucher-discount">
                            <?php if ($v['DiscountType'] === 'percent'): ?>
                                <?= $v['DiscountValue'] ?>% OFF
                            <?php else: ?>
                                RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                            <?php endif; ?>
                        </div>
                        <div class="voucher-code"><?= $v['Code'] ?></div>
                        <div class="voucher-details">
                            <?php if ($v['MinSpend'] > 0): ?>
                                Min. spend: RM <?= number_format($v['MinSpend'], 2) ?><br>
                            <?php else: ?>
                                No minimum spend required<br>
                            <?php endif; ?>
                            <?php if ($v['ExpiryDate']): ?>
                                Expires: <?= date('d M Y', strtotime($v['ExpiryDate'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($subtotal > 0): ?>
                            <a href="/page/checkout.php" class="btn-checkout" style="width: 100%; text-align: center; margin-top: 1rem; display: block;">
                                Use at Checkout →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Locked Vouchers -->
    <?php if (!empty($locked_vouchers)): ?>
        <div class="voucher-section">
            <h3 class="section-title">🔒 Locked Vouchers</h3>
            <div class="voucher-grid">
                <?php foreach ($locked_vouchers as $v): ?>
                    <div class="voucher-card locked">
                        <div class="voucher-icon">🔒</div>
                        <div class="voucher-discount">
                            <?php if ($v['DiscountType'] === 'percent'): ?>
                                <?= $v['DiscountValue'] ?>% OFF
                            <?php else: ?>
                                RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                            <?php endif; ?>
                        </div>
                        <div class="voucher-code"><?= $v['Code'] ?></div>
                        <div class="voucher-details">
                            Min. spend: RM <?= number_format($v['MinSpend'], 2) ?><br>
                            <?php if ($v['ExpiryDate']): ?>
                                Expires: <?= date('d M Y', strtotime($v['ExpiryDate'])) ?>
                            <?php endif; ?>
                            <div class="unlock-info">
                                💰 Add RM <?= number_format($v['MinSpend'] - $subtotal, 2) ?> more to unlock
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Used Vouchers -->
    <?php if (!empty($used_vouchers)): ?>
        <div class="voucher-section">
            <h3 class="section-title">✓ Used Vouchers</h3>
            <div class="voucher-grid">
                <?php foreach ($used_vouchers as $v): ?>
                    <div class="voucher-card used">
                        <div class="voucher-icon">✓</div>
                        <div class="voucher-discount">
                            <?php if ($v['DiscountType'] === 'percent'): ?>
                                <?= $v['DiscountValue'] ?>% OFF
                            <?php else: ?>
                                RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                            <?php endif; ?>
                        </div>
                        <div class="voucher-code"><?= $v['Code'] ?></div>
                        <div class="voucher-details">
                            <strong>Used on <?= date('d M Y', strtotime($v['AssignedAt'])) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Expired Vouchers -->
    <?php if (!empty($expired_vouchers)): ?>
        <div class="voucher-section">
            <h3 class="section-title">⏰ Expired Vouchers</h3>
            <div class="voucher-grid">
                <?php foreach ($expired_vouchers as $v): ?>
                    <div class="voucher-card expired">
                        <div class="voucher-icon">⏰</div>
                        <div class="voucher-discount">
                            <?php if ($v['DiscountType'] === 'percent'): ?>
                                <?= $v['DiscountValue'] ?>% OFF
                            <?php else: ?>
                                RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                            <?php endif; ?>
                        </div>
                        <div class="voucher-code"><?= $v['Code'] ?></div>
                        <div class="voucher-details">
                            <strong style="color: #dc3545;">Expired on <?= date('d M Y', strtotime($v['ExpiryDate'])) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($vouchers)): ?>
        <div class="empty-state">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🎫</div>
            <h3>No Vouchers Yet</h3>
            <p>Complete a purchase to receive vouchers for your next order!</p>
            <a href="/page/product.php" class="btn-checkout">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../_foot.php'; ?>

<script>
    window.addEventListener('load', function () {
        window.scrollTo(0, 0);
    });
</script>