<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

/*
  取得使用者的 voucher
*/
$stm = $_db->prepare("
    SELECT 
        uv.UserVoucherID,
        v.Code,
        v.DiscountType,
        v.DiscountValue,
        v.MinSpend,
        v.ExpiryDate,
        uv.IsUsed
    FROM user_voucher uv
    JOIN voucher v ON uv.VoucherID = v.VoucherID
    WHERE uv.UserID = ?
    ORDER BY uv.AssignedAt DESC
");
$stm->execute([$user_id]);
$vouchers = $stm->fetchAll(PDO::FETCH_ASSOC);

$_title = 'My Voucher - Nº9 Perfume';
include '../_head.php';
?>

<h2 style="margin:2rem;">🎟 My Vouchers</h2>

<?php
// ===== 获取用户已有的未使用 vouchers =====
$user_vouchers = [];
if ($user_id) {
    $stm = $_db->prepare("
        SELECT 
            uv.UserVoucherID,
            v.Code,
            v.DiscountType,
            v.DiscountValue,
            v.MinSpend,
            v.ExpiryDate
        FROM user_voucher uv
        JOIN voucher v ON uv.VoucherID = v.VoucherID
        WHERE uv.UserID = ?
          AND uv.IsUsed = 0
          AND v.status = 'active'
          AND (v.ExpiryDate IS NULL OR v.ExpiryDate >= CURDATE())
        ORDER BY v.MinSpend ASC
    ");
    $stm->execute([$user_id]);
    $user_vouchers = $stm->fetchAll(PDO::FETCH_ASSOC);
}

// 判断每个 voucher 是否可用（达到最低消费）
foreach ($user_vouchers as &$voucher) {
    $voucher['is_available'] = ($subtotal >= $voucher['MinSpend']);
}
unset($voucher);
?>

<style>
.voucher-section {
    background: linear-gradient(135deg, #fffbf0 0%, #ffffff 100%);
    border: 2px solid #D4AF37;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.voucher-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.2rem;
}

.voucher-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
}

.voucher-badge {
    background: #D4AF37;
    color: #000;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
}

.voucher-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.voucher-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.voucher-card.available {
    border-color: #D4AF37;
}

.voucher-card.available:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}

.voucher-card.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f9f9f9;
}

.voucher-card.selected {
    border-color: #D4AF37;
    background: #fffbf0;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
}

.voucher-icon {
    font-size: 2.5rem;
    flex-shrink: 0;
}

.voucher-content {
    flex: 1;
}

.voucher-discount {
    font-size: 1.3rem;
    font-weight: bold;
    color: #D4AF37;
    margin-bottom: 0.3rem;
}

.voucher-code {
    background: #f0f0f0;
    padding: 0.2rem 0.6rem;
    border-radius: 5px;
    font-family: monospace;
    font-size: 0.85rem;
    color: #666;
    display: inline-block;
    margin-bottom: 0.5rem;
}

.voucher-condition {
    font-size: 0.85rem;
    color: #666;
}

.voucher-status {
    flex-shrink: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-available {
    color: #28a745;
}

.status-locked {
    color: #999;
}

.no-vouchers {
    text-align: center;
    padding: 2rem;
    color: #999;
}

.no-vouchers-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}
</style>

<div class="voucher-section">
    <div class="voucher-header">
        <div class="voucher-title">🎟️ My Vouchers</div>
        <?php if (!empty($user_vouchers)): ?>
            <span class="voucher-badge">
                <?= count(array_filter($user_vouchers, fn($v) => $v['is_available'])) ?> Available
            </span>
        <?php endif; ?>
    </div>

    <?php if (empty($user_vouchers)): ?>
        <div class="no-vouchers">
            <div class="no-vouchers-icon">🎫</div>
            <p>You don't have any vouchers yet.</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">
                Complete your purchase to receive vouchers for future orders!
            </p>
        </div>
    <?php else: ?>
        <div class="voucher-list">
            <?php foreach ($user_vouchers as $v): ?>
                <label class="voucher-card <?= $v['is_available'] ? 'available' : 'disabled' ?>">
                    <input type="radio" 
                           name="voucher_selection" 
                           value="<?= $v['Code'] ?>"
                           data-type="<?= $v['DiscountType'] ?>"
                           data-value="<?= $v['DiscountValue'] ?>"
                           data-min="<?= $v['MinSpend'] ?>"
                           <?= !$v['is_available'] ? 'disabled' : '' ?>
                           style="display: none;">
                    
                    <div class="voucher-icon">
                        <?= $v['is_available'] ? '🎁' : '🔒' ?>
                    </div>
                    
                    <div class="voucher-content">
                        <div class="voucher-discount">
                            <?php if ($v['DiscountType'] === 'percent'): ?>
                                <?= $v['DiscountValue'] ?>% OFF
                            <?php else: ?>
                                RM <?= number_format($v['DiscountValue'], 2) ?> OFF
                            <?php endif; ?>
                        </div>
                        
                        <div class="voucher-code"><?= $v['Code'] ?></div>
                        
                        <div class="voucher-condition">
                            Min. spend: RM <?= number_format($v['MinSpend'], 2) ?>
                            <?php if ($v['ExpiryDate']): ?>
                                | Expires: <?= date('d M Y', strtotime($v['ExpiryDate'])) ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$v['is_available']): ?>
                            <div style="color: #dc3545; font-size: 0.85rem; margin-top: 0.3rem;">
                                💰 Add RM <?= number_format($v['MinSpend'] - $subtotal, 2) ?> more to unlock
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="voucher-status">
                        <?php if ($v['is_available']): ?>
                            <span class="status-available">✓ Apply</span>
                        <?php else: ?>
                            <span class="status-locked">🔒 Locked</span>
                        <?php endif; ?>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Voucher 选择逻辑（添加到你现有的 JavaScript 里）
document.addEventListener('DOMContentLoaded', () => {
    const voucherCards = document.querySelectorAll('.voucher-card.available');
    const voucherCodeInput = document.getElementById('voucher_code');
    const voucherDiscountInput = document.getElementById('voucher_discount');
    const orderTotalEl = document.getElementById('orderTotal');
    const originalTotal = parseFloat(orderTotalEl?.dataset.amount || 0);

    voucherCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            
            // 如果点击已选中的，就取消选择
            if (radio.checked && this.classList.contains('selected')) {
                radio.checked = false;
                this.classList.remove('selected');
                
                // 恢复原价
                voucherCodeInput.value = '';
                voucherDiscountInput.value = '0';
                orderTotalEl.textContent = 'RM ' + originalTotal.toFixed(2);
                return;
            }
            
            // 移除其他选中状态
            voucherCards.forEach(c => c.classList.remove('selected'));
            
            // 选中当前
            radio.checked = true;
            this.classList.add('selected');
            
            // 计算折扣
            const type = radio.dataset.type;
            const value = parseFloat(radio.dataset.value);
            
            let discount = 0;
            if (type === 'percent') {
                discount = originalTotal * (value / 100);
            } else {
                discount = value;
            }
            
            const newTotal = Math.max(0, originalTotal - discount);
            
            // 更新
            voucherCodeInput.value = radio.value;
            voucherDiscountInput.value = discount.toFixed(2);
            orderTotalEl.textContent = 'RM ' + newTotal.toFixed(2);
        });
    });
});
</script>

<?php include '../_foot.php'; ?>
