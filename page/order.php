<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/page/login.php');
}

// Get filter and sort parameters
$sort_by = get('sort', 'date_desc');
$filter_status = get('status', '');
$filter_payment = get('payment', '');
$search = get('search', '');

// Build query with filters - Get first product info, customer details, shipping address, and STATUS
$sql = "
    SELECT o.OrderID, o.PurchaseDate, o.PaymentMethod, o.Status, o.GiftWrapCost, o.ShippingFee,
           o.UserID, o.ShippingAddressID,
           u.name as CustomerName, u.email as CustomerEmail,
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
    JOIN user u ON o.UserID = u.userID
    LEFT JOIN user_address ua ON o.ShippingAddressID = ua.AddressID
    WHERE 1=1
";
$params = [];

// Add search filter
if ($search !== '') {
    $sql .= " AND (o.OrderID LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add status filter
if ($filter_status !== '') {
    $sql .= " AND o.Status = ?";
    $params[] = $filter_status;
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
    case 'customer_asc':
        $sql .= " ORDER BY u.name ASC";
        break;
    case 'customer_desc':
        $sql .= " ORDER BY u.name DESC";
        break;
    case 'status_asc':
        $sql .= " ORDER BY o.Status ASC";
        break;
    case 'status_desc':
        $sql .= " ORDER BY o.Status DESC";
        break;
    default: // date_desc
        $sql .= " ORDER BY o.PurchaseDate DESC";
}

$stmt = $_db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Calculate statistics
$total_revenue = 0;
$total_orders = count($orders);
$total_items = 0;
$status_counts = ['Pending' => 0, 'Processing' => 0, 'Shipped' => 0, 'Delivered' => 0, 'Cancelled' => 0];

foreach ($orders as $order) {
    $order_total = $order->ProductTotal + ($order->GiftWrapCost ?? 0) + ($order->ShippingFee ?? 0);
    $total_revenue += $order_total;
    $total_items += $order->ItemCount;
    
    $status = $order->Status ?? 'Pending';
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}

// Status icons
$status_icons = [
    'Pending' => '⏳',
    'Processing' => '📦',
    'Shipped' => '🚚',
    'Delivered' => '✅',
    'Cancelled' => '❌'
];

$_title = 'Order Management - N°9 Perfume';
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
.stats-card.green { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
.stats-card.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stats-card.orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

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

.status-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.status-summary-item {
    text-align: center;
    padding: 1rem;
    border-radius: 8px;
    transition: transform 0.2s;
    cursor: pointer;
}

.status-summary-item:hover {
    transform: translateY(-3px);
}

.status-summary-item.pending { background: #fff3e0; }
.status-summary-item.processing { background: #e3f2fd; }
.status-summary-item.shipped { background: #f3e5f5; }
.status-summary-item.delivered { background: #e8f5e9; }
.status-summary-item.cancelled { background: #ffebee; }

.status-summary-count {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 0.3rem;
}

.status-summary-label {
    font-size: 0.85rem;
    font-weight: 600;
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
    
    // Status summary click to filter
    $('.status-summary-item').on('click', function() {
        const status = $(this).data('status');
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('status', status);
        window.location.href = currentUrl.toString();
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

<div class="container" style="margin-top: 30px; min-height: 60vh;">
    <!-- Page Header -->
    <div style="margin-bottom: 2rem;">
        <h2 style="margin-bottom: 0.5rem; font-size: 2rem; color: #111;">Order Management</h2>
        <p style="color: #666; font-size: 0.95rem;">View and manage all customer orders</p>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($msg = temp('success')): ?>
        <div style="padding: 1rem 1.5rem; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #28a745;">
            <strong>✓</strong> <?= $msg ?>
        </div>
    <?php endif; ?>
    
    <?php if ($err = temp('error')): ?>
        <div style="padding: 1rem 1.5rem; background: #f8d7da; color: #721c24; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #dc3545;">
            <strong>✗</strong> <?= $err ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($orders)): ?>
        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stats-card purple">
                <p class="stats-label">Total Orders</p>
                <p class="stats-value"><?= $total_orders ?></p>
            </div>
            <div class="stats-card green">
                <p class="stats-label">Total Revenue</p>
                <p class="stats-value">RM <?= number_format($total_revenue, 2) ?></p>
            </div>
            <div class="stats-card blue">
                <p class="stats-label">Items Sold</p>
                <p class="stats-value"><?= $total_items ?></p>
            </div>
            <div class="stats-card orange">
                <p class="stats-label">Average Order</p>
                <p class="stats-value">RM <?= $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00' ?></p>
            </div>
        </div>

        <!-- Status Summary -->
        <div class="status-summary">
            <div class="status-summary-item pending" data-status="Pending">
                <div class="status-summary-count" style="color: #ff9800;">⏳ <?= $status_counts['Pending'] ?></div>
                <div class="status-summary-label" style="color: #e65100;">Pending</div>
            </div>
            <div class="status-summary-item processing" data-status="Processing">
                <div class="status-summary-count" style="color: #2196f3;">📦 <?= $status_counts['Processing'] ?></div>
                <div class="status-summary-label" style="color: #1565c0;">Processing</div>
            </div>
            <div class="status-summary-item shipped" data-status="Shipped">
                <div class="status-summary-count" style="color: #9c27b0;">🚚 <?= $status_counts['Shipped'] ?></div>
                <div class="status-summary-label" style="color: #6a1b9a;">Shipped</div>
            </div>
            <div class="status-summary-item delivered" data-status="Delivered">
                <div class="status-summary-count" style="color: #4caf50;">✅ <?= $status_counts['Delivered'] ?></div>
                <div class="status-summary-label" style="color: #2e7d32;">Delivered</div>
            </div>
            <div class="status-summary-item cancelled" data-status="Cancelled">
                <div class="status-summary-count" style="color: #f44336;">❌ <?= $status_counts['Cancelled'] ?></div>
                <div class="status-summary-label" style="color: #c62828;">Cancelled</div>
            </div>
        </div>

        <!-- Filter and Sort Section -->
        <div class="filter-section">
            <form method="get" action="">
                <div class="filter-grid">
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: #333;">
                            🔍 Search
                        </label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Order ID, Customer Name or Email" class="filter-input">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: #333;">
                            📊 Status
                        </label>
                        <select name="status" class="filter-input">
                            <option value="">All Status</option>
                            <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>⏳ Pending</option>
                            <option value="Processing" <?= $filter_status === 'Processing' ? 'selected' : '' ?>>📦 Processing</option>
                            <option value="Shipped" <?= $filter_status === 'Shipped' ? 'selected' : '' ?>>🚚 Shipped</option>
                            <option value="Delivered" <?= $filter_status === 'Delivered' ? 'selected' : '' ?>>✅ Delivered</option>
                            <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                        </select>
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
                            <option value="customer_asc" <?= $sort_by === 'customer_asc' ? 'selected' : '' ?>>Customer (A-Z)</option>
                            <option value="customer_desc" <?= $sort_by === 'customer_desc' ? 'selected' : '' ?>>Customer (Z-A)</option>
                            <option value="orderid_asc" <?= $sort_by === 'orderid_asc' ? 'selected' : '' ?>>Order ID (A-Z)</option>
                            <option value="orderid_desc" <?= $sort_by === 'orderid_desc' ? 'selected' : '' ?>>Order ID (Z-A)</option>
                            <option value="status_asc" <?= $sort_by === 'status_asc' ? 'selected' : '' ?>>Status (A-Z)</option>
                            <option value="status_desc" <?= $sort_by === 'status_desc' ? 'selected' : '' ?>>Status (Z-A)</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" style="padding: 0.6rem 1.5rem; background: #D4AF37; color: #000; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s;">
                        Apply Filters
                    </button>
                    <a href="/page/order.php" style="padding: 0.6rem 1.5rem; background: #fff; color: #666; border: 2px solid #e0e0e0; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 600; transition: 0.3s;">
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
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="sortable <?= strpos($sort_by, 'status') === 0 ? (strpos($sort_by, 'asc') ? 'asc' : 'desc') : '' ?>" 
                            onclick="sortTable('status')">
                            Status
                        </th>
                        <th>Items</th>
                        <th>Payment Method</th>
                        <th>Grand Total</th>
                        <th>Actions</th>
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
                        
                        // Status badge class
                        $status = $order->Status ?? 'Pending';
                        $status_class = 'status-' . strtolower($status);
                        $status_icon = $status_icons[$status] ?? '⏳';
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
                        <td>
                            <div style="font-weight: 600; color: #333;"><?= htmlspecialchars($order->CustomerName) ?></div>
                            <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($order->CustomerEmail) ?></div>
                        </td>
                        <td style="color: #555;">
                            <div style="font-weight: 600;"><?= date('d M Y', strtotime($order->PurchaseDate)) ?></div>
                            <div style="font-size: 0.8rem; color: #999;"><?= date('g:i A', strtotime($order->PurchaseDate)) ?></div>
                        </td>
                        <td>
                            <span class="order-badge <?= $status_class ?>">
                                <?= $status_icon ?> <?= htmlspecialchars($status) ?>
                            </span>
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
                        <td style="font-weight: 700; color: #D4AF37; font-size: 1.1rem;">
                            RM <?= number_format($grand_total, 2) ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <a href="/page/order_detail_admin.php?id=<?= $order->OrderID ?>" 
                                   class="action-btn btn-edit" 
                                   style="text-decoration: none; display: inline-block; padding: 0.5rem 1rem; font-size: 0.85rem;">
                                    📋 Details
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Shipping Details Row (Collapsible) -->
                    <?php if ($order->ShippingAddressID): ?>
                    <tr class="shipping-row" id="shipping-<?= $order->OrderID ?>" style="display: none;">
                        <td colspan="9" style="background: #f8f9fa; padding: 1.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; align-items: center; gap: 0.5rem;">
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
            <div class="empty-icon">📦</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #333;">No Orders Found</h3>
            <p style="font-size: 1rem; margin-bottom: 2rem;">
                <?php if ($search || $filter_payment): ?>
                    No orders match your filters. Try adjusting your search criteria.
                <?php else: ?>
                    There are no orders in the system yet.
                <?php endif; ?>
            </p>
            <?php if ($search || $filter_payment): ?>
                <a href="/page/order.php" style="display: inline-block; padding: 0.8rem 2rem; background: #D4AF37; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600; transition: 0.3s;">
                    Clear Filters
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../_foot.php'; ?>