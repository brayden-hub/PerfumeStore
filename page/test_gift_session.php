<?php
require '../_base.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gift Options Test</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #1a1a1a; color: #0f0; }
        .section { background: #000; padding: 1rem; margin: 1rem 0; border: 1px solid #0f0; }
        button { padding: 0.5rem 1rem; margin: 0.5rem; cursor: pointer; }
        pre { background: #222; padding: 1rem; overflow: auto; }
    </style>
</head>
<body>
    <h1>🎁 Gift Options Debug Tool</h1>
    
    <div class="section">
        <h2>Current Session Data:</h2>
        <pre><?php 
            if (isset($_SESSION['gift_options'])) {
                print_r($_SESSION['gift_options']);
            } else {
                echo "NO GIFT OPTIONS IN SESSION";
            }
        ?></pre>
    </div>

    <div class="section">
        <h2>Test Gift Options:</h2>
        <button onclick="testSaveGift()">Save Test Gift (Luxury)</button>
        <button onclick="testSaveGiftStandard()">Save Test Gift (Standard)</button>
        <button onclick="clearGift()">Clear Gift Options</button>
        <button onclick="location.reload()">Refresh Page</button>
        <div id="result" style="margin-top: 1rem;"></div>
    </div>

    <div class="section">
        <h2>Database Check:</h2>
        <?php
        // Check if columns exist
        try {
            $stmt = $_db->query("DESCRIBE `order`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p>Order table columns:</p><ul>";
            foreach ($columns as $col) {
                echo "<li>$col</li>";
            }
            echo "</ul>";
            
            $gift_columns = ['GiftWrap', 'GiftMessage', 'HidePrice', 'GiftWrapCost'];
            $missing = array_diff($gift_columns, $columns);
            
            if (empty($missing)) {
                echo "<p style='color:#0f0;'>✓ All gift columns exist!</p>";
            } else {
                echo "<p style='color:#f00;'>✗ Missing columns: " . implode(', ', $missing) . "</p>";
                echo "<p>Run this SQL:</p>";
                echo "<pre style='color:#ff0;'>";
                echo "ALTER TABLE `order` \n";
                echo "ADD COLUMN `GiftWrap` VARCHAR(20) NULL,\n";
                echo "ADD COLUMN `GiftMessage` TEXT NULL,\n";
                echo "ADD COLUMN `HidePrice` TINYINT(1) DEFAULT 0,\n";
                echo "ADD COLUMN `GiftWrapCost` DECIMAL(10,2) DEFAULT 0.00;";
                echo "</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color:#f00;'>Error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>Actions:</h2>
        <a href="/page/cart.php" style="color:#0f0;">Go to Cart</a> | 
        <a href="/page/checkout.php" style="color:#0f0;">Go to Checkout</a> |
        <a href="/page/checkout.php?debug=1" style="color:#0f0;">Checkout (Debug Mode)</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function testSaveGift() {
            $.post('/api/save_gift_options.php', {
                enabled: 'true',
                packaging: 'luxury',
                message: 'Happy Birthday! From Test System',
                hidePrice: 'true'
            }, function(res) {
                $('#result').html('<pre style="color:#0f0;">Success: ' + JSON.stringify(res, null, 2) + '</pre>');
                setTimeout(() => location.reload(), 1000);
            }).fail(function(xhr) {
                $('#result').html('<pre style="color:#f00;">Error: ' + xhr.responseText + '</pre>');
            });
        }

        function testSaveGiftStandard() {
            $.post('/api/save_gift_options.php', {
                enabled: 'true',
                packaging: 'standard',
                message: 'Test gift message',
                hidePrice: 'false'
            }, function(res) {
                $('#result').html('<pre style="color:#0f0;">Success: ' + JSON.stringify(res, null, 2) + '</pre>');
                setTimeout(() => location.reload(), 1000);
            }).fail(function(xhr) {
                $('#result').html('<pre style="color:#f00;">Error: ' + xhr.responseText + '</pre>');
            });
        }

        function clearGift() {
            $.post('/api/save_gift_options.php', {
                enabled: 'false',
                packaging: 'standard',
                message: '',
                hidePrice: 'false'
            }, function(res) {
                $('#result').html('<pre style="color:#0f0;">Cleared!</pre>');
                setTimeout(() => location.reload(), 1000);
            });
        }
    </script>
</body>
</html>