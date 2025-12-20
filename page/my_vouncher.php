<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// 計算當前購物車的 subtotal
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

// 獲取用戶的所有 voucher - 只顯示未使用的
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
      AND uv.IsUsed = 0
    ORDER BY 
        v.MinSpend ASC,
        uv.AssignedAt DESC
");
$stm->execute([$user_id]);
$vouchers = $stm->fetchAll(PDO::FETCH_ASSOC);

// 分類 vouchers (只包含未使用的)
$available_vouchers = array_filter($vouchers, function($v) use ($subtotal) {
    return ($v['ExpiryDate'] === null || strtotime($v['ExpiryDate']) >= strtotime('today')) &&
           $subtotal >= $v['MinSpend'];
});

$locked_vouchers = array_filter($vouchers, function($v) use ($subtotal) {
    return ($v['ExpiryDate'] === null || strtotime($v['ExpiryDate']) >= strtotime('today')) &&
           $subtotal < $v['MinSpend'];
});

$expired_vouchers = array_filter($vouchers, function($v) {
    return $v['ExpiryDate'] !== null && 
           strtotime($v['ExpiryDate']) < strtotime('today');
});

$_title = 'My Vouchers - N°9 Perfume';
include '../_head.php';
?>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
    }
    50% {
        box-shadow: 0 0 30px rgba(212, 175, 55, 0.8);
    }
}

.voucher-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

.voucher-header {
    margin-bottom: 30px;
    animation: fadeInUp 0.6s ease;
}

.voucher-title {
    font-size: 2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #1a1a1a;
    margin: 0;
}

.voucher-title-icon {
    font-size: 2.5rem;
    animation: float 3s ease-in-out infinite;
}

.cart-info {
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
    border: 2px solid #D4AF37;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: center;
    animation: fadeInUp 0.6s ease 0.1s backwards;
    position: relative;
    overflow: hidden;
}

.cart-info::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        45deg,
        transparent,
        rgba(255, 255, 255, 0.5),
        transparent
    );
    animation: shimmer 3s infinite;
}

.cart-info-amount {
    font-size: 1.8rem;
    font-weight: 700;
    color: #D4AF37;
    margin: 10px 0;
}

.voucher-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border: 2px solid #e8e8e8;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
    animation: fadeInUp 0.6s ease backwards;
    position: relative;
    overflow: hidden;
}

.stat-card:nth-child(1) { animation-delay: 0.2s; }
.stat-card:nth-child(2) { animation-delay: 0.3s; }
.stat-card:nth-child(3) { animation-delay: 0.4s; }

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stat-card.ready:hover {
    border-color: #28a745;
}

.stat-card.locked:hover {
    border-color: #ffc107;
}

.stat-card.expired:hover {
    border-color: #dc3545;
}

.stat-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    display: block;
}

.stat-number {
    font-size: 3rem;
    font-weight: bold;
    background: linear-gradient(135deg, #D4AF37 0%, #f4d03f 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    color: #666;
    margin-top: 10px;
    font-size: 1rem;
    font-weight: 500;
}

.voucher-section {
    margin-bottom: 50px;
    animation: fadeInUp 0.6s ease backwards;
}

.voucher-section:nth-of-type(1) { animation-delay: 0.5s; }
.voucher-section:nth-of-type(2) { animation-delay: 0.6s; }
.voucher-section:nth-of-type(3) { animation-delay: 0.7s; }

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 15px;
    border-bottom: 3px solid #f0f0f0;
}

.voucher-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
}

@media (max-width: 768px) {
    .voucher-grid {
        grid-template-columns: 1fr;
    }
}

.voucher-card {
    background: white;
    border: 3px solid #e8e8e8;
    border-radius: 16px;
    padding: 30px;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.voucher-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    transition: left 0.5s ease;
}

.voucher-card:hover::before {
    left: 100%;
}

.voucher-card.available {
    border-color: #D4AF37;
    background: linear-gradient(135deg, #ffffff 0%, #fffef8 100%);
}

.voucher-card.available:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
    animation: glow 2s ease-in-out infinite;
}

.voucher-card.locked {
    background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
    border-color: #ddd;
}

.voucher-card.locked:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.voucher-card.expired {
    opacity: 0.5;
    background: #f9f9f9;
    border-color: #e0e0e0;
}

.voucher-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    display: block;
    text-align: center;
}

.voucher-card.available .voucher-icon {
    animation: pulse 2s ease-in-out infinite;
}

.voucher-discount {
    font-size: 2.5rem;
    font-weight: bold;
    background: linear-gradient(135deg, #D4AF37 0%, #f4d03f 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 15px;
    text-align: center;
}

.voucher-code {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 12px 20px;
    border-radius: 10px;
    font-family: 'Courier New', monospace;
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
    display: inline-block;
    margin-bottom: 20px;
    border: 2px dashed #D4AF37;
    width: 100%;
    text-align: center;
    letter-spacing: 2px;
}

.voucher-details {
    font-size: 0.95rem;
    color: #666;
    line-height: 2;
    text-align: center;
    padding: 15px 0;
    border-top: 1px solid #f0f0f0;
}

.voucher-details svg {
    width: 18px;
    height: 18px;
    vertical-align: middle;
    margin-right: 5px;
}

.unlock-info {
    color: #dc3545;
    font-weight: 600;
    margin-top: 15px;
    padding: 10px;
    background: #fff5f5;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #999;
    animation: fadeInUp 0.6s ease;
}

.empty-state-icon {
    font-size: 6rem;
    margin-bottom: 20px;
    opacity: 0.5;
    animation: float 3s ease-in-out infinite;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: #666;
    margin-bottom: 15px;
}

.btn-checkout {
    display: inline-block;
    margin-top: 15px;
    padding: 12px 30px;
    background: linear-gradient(135deg, #D4AF37 0%, #f4d03f 100%);
    color: #000;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
    background: linear-gradient(135deg, #000 0%, #333 100%);
    color: #D4AF37;
}

.lock-icon {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2rem;
    opacity: 0.3;
}

.available-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    animation: pulse 2s ease-in-out infinite;
}
</style>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content">
        <div class="voucher-container">
            <div class="voucher-header">
                <h1 class="voucher-title">
                    <span class="voucher-title-icon">🎟️</span>
                    My Vouchers
                </h1>
            </div>

            <?php if ($subtotal > 0): ?>
                <div class="cart-info">
                    <div style="position: relative; z-index: 1;">
                        💰 Your current cart total
                        <div class="cart-info-amount">RM <?= number_format($subtotal, 2) ?></div>
                        <small style="color: #666;">Add more items to unlock higher-value vouchers!</small>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-info">
                    <div style="position: relative; z-index: 1;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🛒</div>
                        <div style="font-size: 1.2rem; margin-bottom: 15px;">Your cart is empty</div>
                        <p style="color: #666;">Add items to see which vouchers you can use!</p>
                        <a href="/page/product.php" class="btn-checkout">Start Shopping</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="voucher-stats">
                <div class="stat-card ready">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?= count($available_vouchers) ?></div>
                    <div class="stat-label">Ready to Use</div>
                </div>
                <div class="stat-card locked">
                    <span class="stat-icon">🔒</span>
                    <div class="stat-number"><?= count($locked_vouchers) ?></div>
                    <div class="stat-label">Locked</div>
                </div>
                <div class="stat-card expired">
                    <span class="stat-icon">⏰</span>
                    <div class="stat-number"><?= count($expired_vouchers) ?></div>
                    <div class="stat-label">Expired</div>
                </div>
            </div>

            <!-- Available Vouchers -->
            <?php if (!empty($available_vouchers)): ?>
                <div class="voucher-section">
                    <h3 class="section-title">
                        <span style="font-size: 1.8rem;">✅</span>
                        Ready to Use
                    </h3>
                    <div class="voucher-grid">
                        <?php foreach ($available_vouchers as $v): ?>
                            <div class="voucher-card available">
                                <span class="available-badge">AVAILABLE</span>
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
                                        <div>
                                            <svg fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                            </svg>
                                            Min. spend: <strong>RM <?= number_format($v['MinSpend'], 2) ?></strong>
                                        </div>
                                    <?php else: ?>
                                        <div>✨ No minimum spend required</div>
                                    <?php endif; ?>
                                    <?php if ($v['ExpiryDate']): ?>
                                        <div style="margin-top: 8px;">
                                            <svg fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            Expires: <strong><?= date('d M Y', strtotime($v['ExpiryDate'])) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($subtotal > 0): ?>
                                    <a href="/page/checkout.php" class="btn-checkout" style="width: 100%; text-align: center; display: block;">
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
                    <h3 class="section-title">
                        <span style="font-size: 1.8rem;">🔒</span>
                        Locked Vouchers
                    </h3>
                    <div class="voucher-grid">
                        <?php foreach ($locked_vouchers as $v): ?>
                            <div class="voucher-card locked">
                                <span class="lock-icon">🔒</span>
                                <div class="voucher-icon">💎</div>
                                <div class="voucher-discount">
                                    <?php if ($v['DiscountType'] === 'percent'): ?>
                                        <?= $v['DiscountValue'] ?>% OFF
                                    <?php else: ?>
                                        RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                                    <?php endif; ?>
                                </div>
                                <div class="voucher-code"><?= $v['Code'] ?></div>
                                <div class="voucher-details">
                                    <div>
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                        </svg>
                                        Min. spend: <strong>RM <?= number_format($v['MinSpend'], 2) ?></strong>
                                    </div>
                                    <?php if ($v['ExpiryDate']): ?>
                                        <div style="margin-top: 8px;">
                                            <svg fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            Expires: <strong><?= date('d M Y', strtotime($v['ExpiryDate'])) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="unlock-info">
                                        💰 Add RM <?= number_format($v['MinSpend'] - $subtotal, 2) ?> more to unlock!
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Expired Vouchers -->
            <?php if (!empty($expired_vouchers)): ?>
                <div class="voucher-section">
                    <h3 class="section-title">
                        <span style="font-size: 1.8rem;">⏰</span>
                        Expired Vouchers
                    </h3>
                    <div class="voucher-grid">
                        <?php foreach ($expired_vouchers as $v): ?>
                            <div class="voucher-card expired">
                                <div class="voucher-icon">📅</div>
                                <div class="voucher-discount">
                                    <?php if ($v['DiscountType'] === 'percent'): ?>
                                        <?= $v['DiscountValue'] ?>% OFF
                                    <?php else: ?>
                                        RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                                    <?php endif; ?>
                                </div>
                                <div class="voucher-code"><?= $v['Code'] ?></div>
                                <div class="voucher-details">
                                    <strong style="color: #dc3545;">
                                        Expired on <?= date('d M Y', strtotime($v['ExpiryDate'])) ?>
                                    </strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($vouchers)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎫</div>
                    <h3>No Vouchers Available</h3>
                    <p>Complete a purchase to receive vouchers for your next order!</p>
                    <a href="/page/product.php" class="btn-checkout">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>