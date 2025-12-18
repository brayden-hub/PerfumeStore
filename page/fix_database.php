<?php
require '../_base.php';

// ⚠️ 只允許 Admin 執行
if (!isset($_SESSION['user_id'])) {
    die('❌ Please login first');
}

$stmt = $_db->prepare("SELECT role FROM user WHERE userID = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user->role !== 'Admin') {
    die('❌ Only Admin can run this script');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Database Fix Tool</title>
    <style>
        body { font-family: Arial; padding: 2rem; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #D4AF37; border-bottom: 3px solid #D4AF37; padding-bottom: 1rem; }
        .step { background: #f9f9f9; padding: 1.5rem; margin: 1rem 0; border-radius: 8px; border-left: 4px solid #D4AF37; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre { background: #f0f0f0; padding: 1rem; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.8rem; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; background: #D4AF37; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 1rem; }
        .btn:hover { background: #b8941f; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Database Fix Tool</h1>";

try {
    // ==========================================
    // STEP 1: Check and Add Missing Columns
    // ==========================================
    echo "<div class='step'>";
    echo "<h2>Step 1: Checking Order Table Structure</h2>";
    
    // Check if columns exist
    $columns = $_db->query("SHOW COLUMNS FROM `order`")->fetchAll(PDO::FETCH_COLUMN);
    
    $needs_voucher_id = !in_array('VoucherID', $columns);
    $needs_voucher_discount = !in_array('VoucherDiscount', $columns);
    
    if ($needs_voucher_id || $needs_voucher_discount) {
        echo "<p class='warning'>⚠️ Missing columns detected. Adding them now...</p>";
        
        if ($needs_voucher_id) {
            $_db->exec("ALTER TABLE `order` ADD COLUMN `VoucherID` INT NULL AFTER `ShippingFee`");
            echo "<p class='success'>✅ Added VoucherID column</p>";
        }
        
        if ($needs_voucher_discount) {
            $_db->exec("ALTER TABLE `order` ADD COLUMN `VoucherDiscount` DECIMAL(10,2) DEFAULT 0.00 AFTER `VoucherID`");
            echo "<p class='success'>✅ Added VoucherDiscount column</p>";
        }
        
        // Add foreign key
        try {
            $_db->exec("ALTER TABLE `order` ADD CONSTRAINT `fk_order_voucher` FOREIGN KEY (`VoucherID`) REFERENCES `voucher`(`VoucherID`) ON DELETE SET NULL");
            echo "<p class='success'>✅ Added foreign key constraint</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ Foreign key already exists or couldn't be added (this is OK)</p>";
        }
    } else {
        echo "<p class='success'>✅ Order table structure is correct</p>";
    }
    echo "</div>";
    
    // ==========================================
    // STEP 2: Setup Vouchers
    // ==========================================
    echo "<div class='step'>";
    echo "<h2>Step 2: Setting Up Vouchers</h2>";
    
    // Check existing vouchers
    $existing = $_db->query("SELECT COUNT(*) as total FROM voucher")->fetch();
    
    if ($existing->total > 0) {
        echo "<p class='warning'>⚠️ Found {$existing->total} existing vouchers. Clearing them first...</p>";
        $_db->exec("DELETE FROM user_voucher");
        $_db->exec("DELETE FROM voucher");
    }
    
    // Insert vouchers
    $vouchers = [
        ['WELCOME10', 'percent', 10, 0, '2026-12-31'],
        ['SAVE20', 'fixed', 20, 300, '2026-12-31'],
        ['VIP50', 'fixed', 50, 600, '2026-12-31'],
        ['LUXURY15', 'percent', 15, 1000, '2026-12-31']
    ];
    
    $stmt = $_db->prepare("
        INSERT INTO voucher (Code, DiscountType, DiscountValue, MinSpend, ExpiryDate, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    foreach ($vouchers as $v) {
        $stmt->execute($v);
        echo "<p class='success'>✅ Added voucher: <strong>{$v[0]}</strong> - ";
        if ($v[1] === 'percent') {
            echo "{$v[2]}% OFF";
        } else {
            echo "RM {$v[2]} OFF";
        }
        echo " (Min spend: RM {$v[3]})</p>";
    }
    echo "</div>";
    
    // ==========================================
    // STEP 3: Assign Vouchers to Members
    // ==========================================
    echo "<div class='step'>";
    echo "<h2>Step 3: Assigning Vouchers to Members</h2>";
    
    // Get all members
    $members = $_db->query("SELECT userID, name, email FROM user WHERE role = 'Member'")->fetchAll();
    echo "<p>Found <strong>" . count($members) . "</strong> members</p>";
    
    // Assign all vouchers to all members
    $_db->exec("
        INSERT INTO user_voucher (UserID, VoucherID, IsUsed)
        SELECT u.userID, v.VoucherID, 0
        FROM user u
        CROSS JOIN voucher v
        WHERE u.role = 'Member'
    ");
    
    echo "<p class='success'>✅ Assigned 4 vouchers to each member</p>";
    echo "</div>";
    
    // ==========================================
    // STEP 4: Verification
    // ==========================================
    echo "<div class='step'>";
    echo "<h2>Step 4: Verification</h2>";
    
    // Show vouchers
    echo "<h3>📋 All Vouchers:</h3>";
    $vouchers = $_db->query("SELECT * FROM voucher")->fetchAll();
    echo "<table>";
    echo "<tr><th>Code</th><th>Type</th><th>Value</th><th>Min Spend</th><th>Expiry</th><th>Status</th></tr>";
    foreach ($vouchers as $v) {
        echo "<tr>";
        echo "<td><strong>{$v->Code}</strong></td>";
        echo "<td>{$v->DiscountType}</td>";
        echo "<td>" . ($v->DiscountType === 'percent' ? $v->DiscountValue . '%' : 'RM ' . $v->DiscountValue) . "</td>";
        echo "<td>RM {$v->MinSpend}</td>";
        echo "<td>{$v->ExpiryDate}</td>";
        echo "<td>{$v->Status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show user voucher counts
    echo "<h3>👥 Vouchers Per Member:</h3>";
    $user_stats = $_db->query("
        SELECT 
            u.userID,
            u.name,
            u.email,
            COUNT(uv.UserVoucherID) as total_vouchers,
            SUM(CASE WHEN uv.IsUsed = 0 THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN uv.IsUsed = 1 THEN 1 ELSE 0 END) as used
        FROM user u
        LEFT JOIN user_voucher uv ON u.userID = uv.UserID
        WHERE u.role = 'Member'
        GROUP BY u.userID
        ORDER BY u.userID
        LIMIT 10
    ")->fetchAll();
    
    echo "<table>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Total</th><th>Available</th><th>Used</th></tr>";
    foreach ($user_stats as $stat) {
        echo "<tr>";
        echo "<td>{$stat->userID}</td>";
        echo "<td>{$stat->name}</td>";
        echo "<td>{$stat->email}</td>";
        echo "<td><strong>{$stat->total_vouchers}</strong></td>";
        echo "<td style='color:#28a745;'>{$stat->available}</td>";
        echo "<td style='color:#dc3545;'>{$stat->used}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (count($user_stats) >= 10) {
        echo "<p><em>Showing first 10 members...</em></p>";
    }
    
    echo "</div>";
    
    // ==========================================
    // Success Message
    // ==========================================
    echo "<div class='step' style='background:#d4edda;border-left-color:#28a745;'>";
    echo "<h2 class='success'>🎉 Database Fixed Successfully!</h2>";
    echo "<p>All vouchers have been set up and assigned to members.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Update your <code>checkout.php</code> with the fixed version</li>";
    echo "<li>Test the checkout page</li>";
    echo "<li><strong style='color:#dc3545;'>DELETE this file (fix_database.php) for security!</strong></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<a href='/page/checkout.php' class='btn'>Go to Checkout Page</a>";
    echo "<a href='/page/product.php' class='btn' style='background:#6c757d;color:#fff;margin-left:1rem;'>Go to Products</a>";
    
} catch (Exception $e) {
    echo "<div class='step' style='background:#f8d7da;border-left-color:#dc3545;'>";
    echo "<h2 class='error'>❌ Error Occurred</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>