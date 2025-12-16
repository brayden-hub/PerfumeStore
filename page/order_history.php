<?php
require '../_base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Member') {
    redirect('/page/login.php');
}

$user_id = $_SESSION['user_id'];

// Get filter and sort parameters
$sort_by = get('sort', 'date_desc');
$filter_payment = get('payment', '');
$search = get('search', '');

// Build query with filters - Get first product info and shipping address
$sql = "
    SELECT o.OrderID, o.PurchaseDate, o.PaymentMethod, o.GiftWrapCost, o.ShippingFee,
           o.ShippingAddressID,
           SUM(po.TotalPrice) as ProductTotal,
           COUNT(po.ProductOrderID) as ItemCount,
           (SELECT po2.ProductID 
            FROM productorder po2 
            WHERE po2.OrderID = o.OrderID 
            ORDER BY po2.ProductOrderID 
            LIMIT 1) as FirstProductID,
           (SELECT p.ProductName 
            FROM productorder po2 
            JOIN product p ON po2.ProductID = p.ProductID
            WHERE po2.OrderID = o.OrderID 
            ORDER BY po2.ProductOrderID 
            LIMIT 1) as FirstProductName,
           ua.RecipientName, ua.PhoneNumber, ua.AddressLine1, ua.AddressLine2,
           ua.City, ua.State, ua.PostalCode, ua.Country
    FROM `order` o
    LEFT JOIN productorder po ON o.OrderID = po.OrderID
    LEFT JOIN user_address ua ON o.ShippingAddressID = ua.AddressID
    WHERE o.UserID = ?
";
$params = [$user_id];

// Add search filter
if ($search !== '') {
    $sql .= " AND o.OrderID LIKE ?";
    $params[] = "%$search%";
}

// Add payment method filter
if ($filter_payment !== '') {
    $sql .= " AND o.PaymentMethod = ?";
    $params[] = $filter_payment;
}

$sql .= " GROUP BY o.OrderID";

// Add sorting
switch ($sort_by) {
    case 'orderid_asc':
        $sql .= " ORDER BY o.OrderID ASC";
        break;
    case 'orderid_desc':
        $sql .= " ORDER BY o.OrderID DESC";
        break;
    case 'date_asc':
        $sql .= " ORDER BY o.PurchaseDate ASC";
        break;
    case 'amount_desc':
        $sql .= " ORDER BY ProductTotal DESC";
        break;
    case 'amount_asc':
        $sql .= " ORDER BY ProductTotal ASC";
        break;
    case 'items_desc':
        $sql .= " ORDER BY ItemCount DESC";
        break;
    case 'items_asc':
        $sql .= " ORDER BY ItemCount ASC";
        break;
    default: // date_desc
        $sql .= " ORDER BY o.PurchaseDate DESC";
}

$stmt = $_db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Calculate statistics - Fixed to include shipping fee
$total_spent = 0;
$total_items = 0;
foreach ($orders as $order) {
    $order_total = $order->ProductTotal + ($order->GiftWrapCost ?? 0) + ($order->ShippingFee ?? 0);
    $total_spent += $order_total;
    $total_items += $order->ItemCount;
}

$_title = 'Order History - N°9 Perfume';
include '../_head.php';
?>

<style>
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    border-radius: 12px;
    color: white;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.stats-card.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stats-card.pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stats-card.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stats-card.gold { background: linear-gradient(135deg, #D4AF37 0%, #FFD700 100%); }

.stats-label {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stats-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}

.filter-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.filter-input {
    width: 100%;
    padding: 0.6rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.filter-input:focus {
    border-color: #D4AF37;
    outline: none;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
}

.order-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-credit { background: #e3f2fd; color: #1976d2; }
.badge-banking { background: #e8f5e9; color: #388e3c; }
.badge-ewallet { background: #fff3e0; color: #f57c00; }
.badge-cod { background: #fce4ec; color: #c2185b; }

.status-pending { background: #fff3e0; color: #ff9800; }
.status-processing { background: #e3f2fd; color: #2196f3; }
.status-shipped { background: #f3e5f5; color: #9c27b0; }
.status-delivered { background: #e8f5e9; color: #4caf50; }
.status-cancelled { background: #ffebee; color: #f44336; }

.order-row {
    transition: all 0.3s;
    cursor: pointer;
}

.order-row:hover {
    background: #f8f9fa;
    transform: translateX(5px);
}

.shipping-row {
    transition: all 0.3s ease-in-out;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* Sortable column header styles */
.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 20px !important;
}

.sortable:hover {
    background: #1a1a1a;
}

.sortable::after {
    content: '⇅';
    position: absolute;
    right: 8px;
    opacity: 0.5;
    font-size: 0.8rem;
}

.sortable.asc::after {
    content: '▲';
    opacity: 1;
    color: #D4AF37;
}

.sortable.desc::after {
    content: '▼';
    opacity: 1;
    color: #D4AF37;
}
</style>

<script>
$(document).ready(function() {
    window.scrollTo(0, 0);
    
    // Clear filters
    $('#clear-filters').on('click', function(e) {
        e.preventDefault();
        window.location.href = '/page/order_history.php';
    });
    
    // Toggle shipping details on row click
    $('.order-row').on('click', function() {
        const orderId = $(this).find('td:first').text().trim();
        const shippingRow = $('#shipping-' + orderId);
        
        if (shippingRow.length) {
            // Close all other shipping rows
            $('.shipping-row').not(shippingRow).slideUp();
            
            // Toggle current shipping row
            shippingRow.slideToggle();
        }
    });
    
    // Prevent row click when clicking on buttons/links
    $('.order-row a, .order-row button').on('click', function(e) {
        e.stopPropagation();
    });
});

if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}

// Sortable column click handler
function sortTable(column) {
    const currentUrl = new URL(window.location.href);
    const currentSort = currentUrl.searchParams.get('sort') || 'date_desc';
    
    // Determine new sort direction
    let newSort;
    if (currentSort === column + '_asc') {
        newSort = column + '_desc';
    } else {
        newSort = column + '_asc';
    }
    
    currentUrl.searchParams.set('sort', newSort);
    window.location.href = currentUrl.toString();
}
</script>

<div class="container" style="margin-top: 100px; min-height: 60vh;">
    <!-- Page Header -->
    <div style="margin-bottom: 2rem;">
        <h2 style="margin-bottom: 0.5rem; font-size: 2rem; color: #111;">My Order History</h2>
        <p style="color: #666; font-size: 0.95rem;">Track and manage all your perfume orders</p>
    </div>
    
    <!-- Success Message -->
    <?php if ($msg = temp('success')): ?>
        <div style="padding: 1rem 1.5rem; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #28a745;">
            <strong>✔</strong> <?= $msg ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($orders)): ?>
        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stats-card purple">
                <p class="stats-label">Total Orders</p>
                <p class="stats-value"><?= count($orders) ?></p>
            </div>
            <div class="stats-card pink">
                <p class="stats-label">Total Spent</p>
                <p class="stats-value">RM <?= number_format($total_spent, 2) ?></p>
            </div>
            <div class="stats-card blue">
                <p class="stats-label">Items Purchased</p>
                <p class="stats-value"><?= $total_items ?></p>
            </div>
            <div class="stats-card gold">
                <p class="stats-label">Average Order</p>
                <p class="stats-value">RM <?= count($orders) > 0 ? number_format($total_spent / count($orders), 2) : '0.00' ?></p>
            </div>
        </div>

        <!-- Filter and Sort Section -->
        <div class="filter-section">
            <form method="get" action="">
                <div class="filter-grid">
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: #333;">
                            🔍 Search Order ID
                        </label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="e.g., O00001" class="filter-input">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: #333;">
                            💳 Payment Method
                        </label>
                        <select name="payment" class="filter-input">
                            <option value="">All Methods</option>
                            <option value="Credit Card" <?= $filter_payment === 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
                            <option value="Online Banking" <?= $filter_payment === 'Online Banking' ? 'selected' : '' ?>>Online Banking</option>
                            <option value="E-Wallet" <?= $filter_payment === 'E-Wallet' ? 'selected' : '' ?>>E-Wallet</option>
                            <option value="Cash on Delivery" <?= $filter_payment === 'Cash on Delivery' ? 'selected' : '' ?>>Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: #333;">
                            📊 Sort By
                        </label>
                        <select name="sort" class="filter-input">
                            <option value="date_desc" <?= $sort_by === 'date_desc' ? 'selected' : '' ?>>Date (Newest First)</option>
                            <option value="date_asc" <?= $sort_by === 'date_asc' ? 'selected' : '' ?>>Date (Oldest First)</option>
                            <option value="amount_desc" <?= $sort_by === 'amount_desc' ? 'selected' : '' ?>>Amount (High to Low)</option>
                            <option value="amount_asc" <?= $sort_by === 'amount_asc' ? 'selected' : '' ?>>Amount (Low to High)</option>
                            <option value="items_desc" <?= $sort_by === 'items_desc' ? 'selected' : '' ?>>Items (Most to Least)</option>
                            <option value="items_asc" <?= $sort_by === 'items_asc' ? 'selected' : '' ?>>Items (Least to Most)</option>
                            <option value="orderid_asc" <?= $sort_by === 'orderid_asc' ? 'selected' : '' ?>>Order ID (A-Z)</option>
                            <option value="orderid_desc" <?= $sort_by === 'orderid_desc' ? 'selected' : '' ?>>Order ID (Z-A)</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" style="padding: 0.6rem 1.5rem; background: #D4AF37; color: #000; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s;">
                        Apply Filters
                    </button>
                    <a href="/page/order_history.php" id="clear-filters" style="padding: 0.6rem 1.5rem; background: #fff; color: #666; border: 2px solid #e0e0e0; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 600; transition: 0.3s;">
                        Clear All
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <p style="margin-bottom: 1rem; color: #666; font-size: 0.95rem;">
            Showing <strong><?= count($orders) ?></strong> order(s)
        </p>
        
        <!-- Orders Table -->
        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <table class="product-table" style="margin-bottom: 0;">
                <thead>
                    <tr style="background: #000; color: #fff;">
                        <th class="sortable <?= strpos($sort_by, 'orderid') === 0 ? (strpos($sort_by, 'asc') ? 'asc' : 'desc') : '' ?>" 
                            onclick="sortTable('orderid')" style="padding: 1rem;">
                            Order ID
                        </th>
                        <th>Product Preview</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Payment Method</th>
                        <th>Products Total</th>
                        <th>Gift Wrap</th>
                        <th>Shipping</th>
                        <th>Grand Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $product_total = $order->ProductTotal;
                        $gift_wrap = $order->GiftWrapCost ?? 0;
                        $shipping_fee = $order->ShippingFee ?? 0;
                        $grand_total = $product_total + $gift_wrap + $shipping_fee;
                        
                        // Payment badge class
                        $badge_class = 'badge-credit';
                        if ($order->PaymentMethod === 'Online Banking') $badge_class = 'badge-banking';
                        elseif ($order->PaymentMethod === 'E-Wallet') $badge_class = 'badge-ewallet';
                        elseif ($order->PaymentMethod === 'Cash on Delivery') $badge_class = 'badge-cod';
                    ?>
                    <tr class="order-row">
                        <td style="font-weight: 700; color: #D4AF37; font-size: 1rem;">
                            <?= htmlspecialchars($order->OrderID) ?>
                        </td>
                        <td>
                            <?php if ($order->FirstProductID): ?>
                                <div style="display: flex; align-items: center; gap: 0.8rem;">
                                    <img src="/public/images/<?= htmlspecialchars($order->FirstProductID) ?>.png" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                         alt="<?= htmlspecialchars($order->FirstProductName) ?>">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 600; font-size: 0.9rem; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($order->FirstProductName) ?>
                                        </div>
                                        <?php if ($order->ItemCount > 1): ?>
                                            <div style="font-size: 0.75rem; color: #999;">
                                                +<?= $order->ItemCount - 1 ?> more item<?= ($order->ItemCount - 1) > 1 ? 's' : '' ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">No products</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #555;">
                            <div style="font-weight: 600;"><?= date('d M Y', strtotime($order->PurchaseDate)) ?></div>
                            <div style="font-size: 0.8rem; color: #999;"><?= date('l', strtotime($order->PurchaseDate)) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: #f0f0f0; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">
                                <?= $order->ItemCount ?> item<?= $order->ItemCount > 1 ? 's' : '' ?>
                            </span>
                        </td>
                        <td>
                            <span class="order-badge <?= $badge_class ?>">
                                <?php if ($order->PaymentMethod === 'Credit Card'): ?>💳<?php endif; ?>
                                <?php if ($order->PaymentMethod === 'Online Banking'): ?>🏦<?php endif; ?>
                                <?php if ($order->PaymentMethod === 'E-Wallet'): ?>📱<?php endif; ?>
                                <?php if ($order->PaymentMethod === 'Cash on Delivery'): ?>💵<?php endif; ?>
                                <?= htmlspecialchars($order->PaymentMethod) ?>
                            </span>
                        </td>
                        <td style="font-weight: 600; color: #333;">
                            RM <?= number_format($product_total, 2) ?>
                        </td>
                        <td style="color: #666;">
                            <?php if ($gift_wrap > 0): ?>
                                <span style="color: #f57c00; font-weight: 600;">🎁 RM <?= number_format($gift_wrap, 2) ?></span>
                            <?php else: ?>
                                <span style="color: #ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #666; font-weight: 600;">
                            RM <?= number_format($shipping_fee, 2) ?>
                        </td>
                        <td style="font-weight: 700; color: #D4AF37; font-size: 1.1rem;">
                            RM <?= number_format($grand_total, 2) ?>
                        </td>
                        <td>
                            <a href="/page/order_detail_member.php?id=<?= $order->OrderID ?>" 
                               class="action-btn btn-edit" 
                               style="text-decoration: none; display: inline-block; padding: 0.5rem 1rem; font-size: 0.85rem;">
                                📋 View Details
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Shipping Details Row (Collapsible) -->
                    <?php if ($order->ShippingAddressID): ?>
                    <tr class="shipping-row" id="shipping-<?= $order->OrderID ?>" style="display: none;">
                        <td colspan="10" style="background: #f8f9fa; padding: 1.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; align-items: center;">
                                <!-- Store Address -->
                                <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 2px solid #D4AF37;">
                                    <h4 style="color: #D4AF37; margin: 0 0 1rem 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                        🏪 <strong>Ship From</strong>
                                    </h4>
                                    <div style="color: #333; line-height: 1.6;">
                                        <strong style="color: #D4AF37;">N°9 Perfume Store</strong><br>
                                        123 Fragrance Avenue<br>
                                        Bukit Bintang<br>
                                        Kuala Lumpur, 55100<br>
                                        Malaysia<br>
                                        <span style="color: #666;">📞 +60 3-1234 5678</span>
                                    </div>
                                </div>
                                
                                <!-- Arrow -->
                                <div style="text-align: center; font-size: 2rem; color: #D4AF37;">
                                    →
                                </div>
                                
                                <!-- Customer Address -->
                                <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 2px solid #4caf50;">
                                    <h4 style="color: #4caf50; margin: 0 0 1rem 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                        📦 <strong>Ship To</strong>
                                    </h4>
                                    <div style="color: #333; line-height: 1.6;">
                                        <strong><?= htmlspecialchars($order->RecipientName) ?></strong><br>
                                        <?= htmlspecialchars($order->AddressLine1) ?><br>
                                        <?php if ($order->AddressLine2): ?>
                                            <?= htmlspecialchars($order->AddressLine2) ?><br>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($order->City) ?>, <?= htmlspecialchars($order->State) ?> <?= htmlspecialchars($order->PostalCode) ?><br>
                                        <?= htmlspecialchars($order->Country) ?><br>
                                        <span style="color: #666;">📞 <?= htmlspecialchars($order->PhoneNumber) ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-icon">🛍️</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #333;">No Orders Found</h3>
            <p style="font-size: 1rem; margin-bottom: 2rem;">
                <?php if ($search || $filter_payment): ?>
                    No orders match your filters. Try adjusting your search criteria.
                <?php else: ?>
                    You haven't placed any orders yet. Start exploring our exquisite perfume collection!
                <?php endif; ?>
            </p>
            <?php if ($search || $filter_payment): ?>
                <a href="/page/order_history.php" style="display: inline-block; padding: 0.8rem 2rem; background: #D4AF37; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600; transition: 0.3s;">
                    Clear Filters
                </a>
            <?php else: ?>
                <a href="/page/product.php" style="display: inline-block; padding: 0.8rem 2rem; background: #D4AF37; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600; transition: 0.3s;">
                    Start Shopping →
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../_foot.php'; ?>