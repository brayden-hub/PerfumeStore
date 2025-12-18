<?php
require '../_base.php';

echo "<h1>Setting up Vouchers...</h1>";

try {
    // 清空旧数据
    echo "<p>🗑️ Cleaning old vouchers...</p>";
    $_db->exec("DELETE FROM user_voucher");
    $_db->exec("DELETE FROM voucher");
    
    // 添加 Vouchers
    echo "<p>➕ Adding vouchers...</p>";
    
    $vouchers = [
        ['WELCOME10', 'percent', 10, 0, '2026-12-31'],
        ['SAVE20', 'fixed', 20, 300, '2026-12-31'],
        ['VIP50', 'fixed', 50, 600, '2026-12-31'],
        ['LUXURY15', 'percent', 15, 1000, '2026-12-31']
    ];
    
    $stm = $_db->prepare("
        INSERT INTO voucher (Code, DiscountType, DiscountValue, MinSpend, ExpiryDate, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    foreach ($vouchers as $v) {
        $stm->execute($v);
        echo "✅ {$v[0]}<br>";
    }
    
    // 给所有 Member 发放
    echo "<p>🎁 Distributing to all members...</p>";
    $_db->query("
        INSERT INTO user_voucher (UserID, VoucherID, IsUsed)
        SELECT u.userID, v.VoucherID, 0
        FROM user u
        CROSS JOIN voucher v
        WHERE u.role = 'Member'
    ");
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p><a href='checkout.php'>Go to Checkout</a></p>";
    echo "<p style='color:red;'>⚠️ Delete this file after setup!</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>