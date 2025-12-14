<?php
require '../_base.php';
$current_step = 1;


if (!isset($_SESSION['user_id'])) {
    redirect('/page/login.php');
}

$_title = 'Shopping Cart - N°9 Perfume';
include '../_head.php';

$user_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = $_db->prepare("
    SELECT c.CartID, c.ProductID, c.Quantity, 
           p.ProductName, p.Price, p.Stock, p.Image, p.Series
    FROM cart c
    JOIN product p ON c.ProductID = p.ProductID
    WHERE c.UserID = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['Price'] * $item['Quantity'];
}

$free_shipping_threshold = 300; 
?>

<style>
.gift-module {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.gift-header {
    padding: 1.2rem 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #f8f8f8 0%, #fff 100%);
    border-bottom: 1px solid #eee;
    transition: all 0.3s ease;
}

.gift-header:hover {
    background: linear-gradient(135deg, #f0f0f0 0%, #f8f8f8 100%);
}

.gift-header-left {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.gift-icon {
    font-size: 1.5rem;
}

.gift-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #D4AF37;
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.gift-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease;
}

.gift-content.active {
    max-height: 800px;
}

.gift-content-inner {
    padding: 1.5rem;
}

.gift-section {
    margin-bottom: 2rem;
}

.gift-section:last-child {
    margin-bottom: 0;
}

.section-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.packaging-options {
    display: grid;
    gap: 1rem;
}

.packaging-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #eee;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.packaging-option:hover {
    border-color: #D4AF37;
    background: #fafafa;
}

.packaging-option.selected {
    border-color: #D4AF37;
    background: #fffbf0;
}

.packaging-option input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.packaging-thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #eee;
}

.packaging-details {
    flex: 1;
}

.packaging-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.3rem;
}

.packaging-price {
    color: #D4AF37;
    font-size: 0.9rem;
}

.message-card-container {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.message-input-wrapper {
    flex: 1;
    min-width: 250px;
}

.message-textarea {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid #eee;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    resize: vertical;
    min-height: 100px;
    transition: border-color 0.3s ease;
}

.message-textarea:focus {
    outline: none;
    border-color: #D4AF37;
}

.char-counter {
    text-align: right;
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.3rem;
}

.char-counter.warning {
    color: #dc3545;
}

.card-preview-wrapper {
    flex: 1;
    min-width: 250px;
}

.card-preview {
    position: relative;
    width: 100%;
    max-width: 300px;
    aspect-ratio: 3/2;
    background: linear-gradient(135deg, #ffffff 0%, #f8f8f8 100%);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    overflow: hidden;
    border: 2px solid #D4AF37;
}

.card-preview::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 40px;
    background: linear-gradient(135deg, #D4AF37 0%, #b8941f 100%);
}

.card-preview::after {
    content: '🎁';
    position: absolute;
    top: 8px;
    right: 12px;
    font-size: 1.5rem;
}

.card-message {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 85%;
    text-align: center;
    font-family: 'Brush Script MT', cursive, serif;
    font-size: 1.1rem;
    color: #333;
    word-wrap: break-word;
    white-space: pre-wrap;
}

.card-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #999;
    font-style: italic;
    font-size: 0.9rem;
}

.privacy-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem;
    background: #f9f9f9;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.privacy-checkbox:hover {
    background: #f0f0f0;
}

.privacy-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.privacy-label {
    font-size: 0.95rem;
    color: #333;
    cursor: pointer;
}

@media (max-width: 768px) {
    .message-card-container {
        flex-direction: column;
    }
    
    .card-preview {
        max-width: 100%;
    }
}
</style>

<div class="cart-container" style="max-width: 1400px; margin: 100px auto 0; padding: 0 5%; display: flex; gap: 4rem; flex-wrap: wrap;">
    <!-- Progress Bar -->
        <div class="progress-bar" style="width: 100%; margin-bottom: 3rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <!-- Step 1: Shopping Cart -->
                <div style="text-align: center;">
                    <div style="width: 40px; height: 40px; background: <?= $current_step >= 1 ? '#D4AF37' : '#eee' ?>; color: <?= $current_step >= 1 ? '#000' : '#666' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">1</div>
                    <p style="margin: 0; font-size: 0.9rem; color: <?= $current_step >= 1 ? '#000' : '#666' ?>;">Shopping Cart</p>
                </div>
                
                <div style="flex: 1; height: 2px; background: <?= $current_step >= 2 ? '#D4AF37' : '#eee' ?>; margin: 0 1rem;"></div>
                
                <!-- Step 2: Checkout & Payment -->
                <div style="text-align: center;">
                    <div style="width: 40px; height: 40px; background: <?= $current_step >= 2 ? '#D4AF37' : '#eee' ?>; color: <?= $current_step >= 2 ? '#000' : '#666' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">2</div>
                    <p style="margin: 0; font-size: 0.9rem; color: <?= $current_step >= 2 ? '#000' : '#666' ?>;">Checkout & Payment</p>
                </div>
                
                <div style="flex: 1; height: 2px; background: <?= $current_step >= 3 ? '#D4AF37' : '#eee' ?>; margin: 0 1rem;"></div>
                
                <!-- Step 3: Done -->
                <div style="text-align: center;">
                    <div style="width: 40px; height: 40px; background: <?= $current_step >= 3 ? '#D4AF37' : '#eee' ?>; color: <?= $current_step >= 3 ? '#000' : '#666' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">3</div>
                    <p style="margin: 0; font-size: 0.9rem; color: <?= $current_step >= 3 ? '#000' : '#666' ?>;">Done</p>
                </div>
            </div>
        </div>

    <!-- Left: Cart Items -->
    <div class="cart-items" style="flex: 2; min-width: 600px;">
        <h2 style="font-size: 2rem; font-weight: 300; margin-bottom: 2rem;">Your Cart</h2>

        <div id="cart-content-wrapper">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart-message" style="text-align: center; padding: 6rem 0; background: #f9f9f9; border-radius: 12px;">
                    <p style="font-size: 1.5rem; color: #666; margin-bottom: 1rem;">Your cart is empty</p>
                    <a href="/page/product.php" style="color: #D4AF37; font-size: 1.1rem; text-decoration: none;">Continue Shopping →</a>
                </div>
            <?php else: ?>
                <div class="cart-list">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item-card" data-cart-id="<?= $item['CartID'] ?>" style="display: flex; gap: 2rem; background: #fff; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); align-items: center;">
                            <img src="/public/images/<?= htmlspecialchars($item['ProductID']) ?>.png" 
                                 alt="<?= htmlspecialchars($item['ProductName']) ?>" 
                                 style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px;">

                            <div style="flex: 1;">
                                <h3 style="font-size: 1.2rem; margin: 0 0 0.5rem;"><?= htmlspecialchars($item['ProductName']) ?></h3>
                                <p style="color: #666; margin: 0;">RM <span class="item-price"><?= number_format($item['Price'], 2) ?></span></p>
                                <?php if ($item['Stock'] < 5): ?>
                                    <p style="color: #dc3545; font-size: 0.9rem; margin-top: 0.5rem;">
                                        ⚠ Only <?= $item['Stock'] ?> left in stock!
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div class="quantity-selector" style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px;">
                                    <button class="qty-btn" data-action="decrease" data-cart-id="<?= $item['CartID'] ?>" style="padding: 0.5rem 1rem; background: none; border: none; cursor: pointer;">−</button>
                                    <input type="number" class="qty-input" data-cart-id="<?= $item['CartID'] ?>" value="<?= $item['Quantity'] ?>" min="1" max="<?= $item['Stock'] ?>" style="width: 60px; text-align: center; border: none;" readonly>
                                    <button class="qty-btn" data-action="increase" data-cart-id="<?= $item['CartID'] ?>" data-max="<?= $item['Stock'] ?>" style="padding: 0.5rem 1rem; background: none; border: none; cursor: pointer;">+</button>
                                </div>
                                <button class="remove-item" data-cart-id="<?= $item['CartID'] ?>" style="background: none; border: none; color: #dc3545; cursor: pointer;">Remove</button>
                            </div>

                            <div style="text-align: right;">
                                <p class="item-total" style="font-size: 1.3rem; font-weight: 600; color: #D4AF37;">
                                    RM <?= number_format($item['Price'] * $item['Quantity'], 2) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 交叉銷售 -->
                <div class="cross-sell" style="margin-top: 4rem;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Complete Your Collection</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <?php
                        $stmt = $_db->query("SELECT * FROM product ORDER BY RAND() LIMIT 3");
                        $cross = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($cross as $c):
                        ?>
                            <div style="text-align: center; background: #f9f9f9; padding: 1rem; border-radius: 12px;">
                                <img src="/public/images/<?= $c['ProductID'] ?>.png" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                                <h4 style="margin: 1rem 0 0.5rem;"><?= $c['ProductName'] ?></h4>
                                <p style="color: #D4AF37;">RM <?= number_format($c['Price'], 2) ?></p>
                                <button class="add-to-cart" data-id="<?= $c['ProductID'] ?>" style="margin-top: 1rem; padding: 0.8rem; background: #D4AF37; color: #000; border: none; cursor: pointer; border-radius: 4px;">Add to Cart</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Order Summary (Sticky) -->
    <div class="order-summary-wrapper" style="flex: 1; min-width: 350px;">
        <div class="order-summary" style="background: #f9f9f9; padding: 2rem; border-radius: 12px; position: sticky; top: 100px;">
            <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Order Summary</h3>

            <!-- Gift Module -->
            <div class="gift-module">
                <div class="gift-header" id="giftHeader">
                    <div class="gift-header-left">
                        <span class="gift-icon">🎁</span>
                        <h4 class="gift-title">Make it a Gift</h4>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="giftToggle">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="gift-content" id="giftContent">
                    <div class="gift-content-inner">
                        <!-- Packaging Selection -->
                        <div class="gift-section">
                            <div class="section-title">
                                <span>📦</span> Gift Wrapping
                            </div>
                            <div class="packaging-options">
                                <label class="packaging-option selected" data-price="0">
                                    <input type="radio" name="packaging" value="standard" checked>
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Crect fill='%23f0f0f0' width='80' height='80'/%3E%3Cpath fill='%23ddd' d='M35 10h10v60h-10z'/%3E%3Cpath fill='%23ddd' d='M10 35h60v10h-60z'/%3E%3Ccircle fill='%23D4AF37' cx='40' cy='40' r='8'/%3E%3C/svg%3E" alt="Standard" class="packaging-thumbnail">
                                    <div class="packaging-details">
                                        <div class="packaging-name">Standard Packaging</div>
                                        <div class="packaging-price">Free</div>
                                    </div>
                                </label>
                                
                                <label class="packaging-option" data-price="5">
                                    <input type="radio" name="packaging" value="luxury">
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Crect fill='%23000' width='80' height='80'/%3E%3Cpath fill='%23D4AF37' d='M35 10h10v60h-10z'/%3E%3Cpath fill='%23D4AF37' d='M10 35h60v10h-60z'/%3E%3Ccircle fill='%23FFD700' cx='40' cy='40' r='8'/%3E%3Cpath fill='%23D4AF37' d='M20 20l5 5-5 5-5-5z M55 20l5 5-5 5-5-5z M20 55l5 5-5 5-5-5z M55 55l5 5-5 5-5-5z'/%3E%3C/svg%3E" alt="Luxury" class="packaging-thumbnail">
                                    <div class="packaging-details">
                                        <div class="packaging-name">Luxury Gift Wrap</div>
                                        <div class="packaging-price">+RM 5.00</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Message Card -->
                        <div class="gift-section">
                            <div class="section-title">
                                <span>💌</span> Gift Message
                            </div>
                            <div class="message-card-container">
                                <div class="message-input-wrapper">
                                    <textarea 
                                        class="message-textarea" 
                                        id="giftMessage" 
                                        placeholder="Write your gift message here..."
                                        maxlength="150"
                                    ></textarea>
                                    <div class="char-counter">
                                        <span id="charCount">0</span>/150
                                    </div>
                                </div>
                                
                                <div class="card-preview-wrapper">
                                    <div class="card-preview">
                                        <div class="card-placeholder" id="cardPlaceholder">Your message will appear here</div>
                                        <div class="card-message" id="cardMessage" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy Option -->
                        <div class="gift-section">
                            <label class="privacy-checkbox">
                                <input type="checkbox" id="hidePrice">
                                <span class="privacy-label">🔒 Hide price on receipt</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Free Shipping -->
            <?php $remaining = $free_shipping_threshold - $total; ?>
            <div class="free-shipping-container" style="margin-top: 1.5rem;">
                <?php if ($total < $free_shipping_threshold): ?>
                    <div class="free-shipping-msg" style="background: #fff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                        <p style="margin: 0; color: #666;">Add RM <span class="remaining-amount"><?= number_format($remaining, 2) ?></span> more for <strong>FREE SHIPPING</strong></p>
                    </div>
                <?php else: ?>
                    <div class="free-shipping-msg" style="background: #d4af37; color: #000; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-weight: bold;">
                        ✓ Congratulations! Free Shipping Unlocked!
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <span>Subtotal</span>
                <span class="subtotal-amount">RM <?= number_format($total, 2) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;" id="giftWrapRow" style="display: none;">
                <span>Gift Wrapping</span>
                <span class="giftwrap-cost">RM 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <span>Shipping</span>
                <span class="shipping-cost"><?= $total >= $free_shipping_threshold ? 'FREE' : 'Calculated at checkout' ?></span>
            </div>
            <hr style="border: none; border-top: 1px solid #eee; margin: 1.5rem 0;">
            <div style="display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: bold;">
                <span>Total</span>
                <span id="cart-total" style="color: #D4AF37;">RM <?= number_format($total, 2) ?></span>
            </div>

            <button id="checkout-btn" style="width: 100%; padding: 1.2rem; background: #000; color: #D4AF37; border: none; font-size: 1.1rem; font-weight: bold; margin-top: 2rem; cursor: pointer; border-radius: 4px;">
                Proceed to Checkout
            </button>

            <a href="/page/product.php" style="display: block; text-align: center; margin-top: 1rem; color: #666; text-decoration: none;">
                ← Continue Shopping
            </a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    window.scrollTo(0, 0);

    let giftWrapPrice = 0;
    let currentSubtotal = <?= $total ?>;

    // Gift Module Toggle
    $('#giftToggle').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#giftContent').toggleClass('active', isChecked);
        
        if (!isChecked) {
            // Reset to standard packaging when closing
            $('input[name="packaging"][value="standard"]').prop('checked', true);
            $('.packaging-option').removeClass('selected');
            $('.packaging-option[data-price="0"]').addClass('selected');
            giftWrapPrice = 0;
            updateTotal();
        }
    });

    // Also toggle on header click (except on toggle switch itself)
    $('#giftHeader').on('click', function(e) {
        if (!$(e.target).closest('.toggle-switch').length) {
            $('#giftToggle').prop('checked', !$('#giftToggle').is(':checked')).trigger('change');
        }
    });

    // Packaging Selection
    $('.packaging-option').on('click', function() {
        $('.packaging-option').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
        
        giftWrapPrice = parseFloat($(this).data('price'));
        updateTotal();
    });

    // Message Card Live Preview
    $('#giftMessage').on('input', function() {
        const text = $(this).val();
        const charCount = text.length;
        
        $('#charCount').text(charCount);
        
        if (charCount > 140) {
            $('#charCount').parent().addClass('warning');
        } else {
            $('#charCount').parent().removeClass('warning');
        }
        
        if (text.trim() === '') {
            $('#cardMessage').hide();
            $('#cardPlaceholder').show();
        } else {
            $('#cardPlaceholder').hide();
            $('#cardMessage').text(text).show();
        }
    });

    // Update Total Function
    function updateTotal() {
        const newTotal = currentSubtotal + giftWrapPrice;
        
        $('#cart-total').text('RM ' + newTotal.toFixed(2));
        
        if (giftWrapPrice > 0) {
            $('#giftWrapRow').show();
            $('.giftwrap-cost').text('RM ' + giftWrapPrice.toFixed(2));
        } else {
            $('#giftWrapRow').hide();
        }
        
        updateFreeShipping(newTotal);
    }

    function updateFreeShipping(total) {
        const threshold = 300;
        const remaining = threshold - total;
        
        if (total >= threshold) {
            $('.free-shipping-container').html(`
                <div class="free-shipping-msg" style="background: #d4af37; color: #000; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-weight: bold;">
                    ✓ Congratulations! Free Shipping Unlocked!
                </div>
            `);
            $('.shipping-cost').text('FREE');
        } else {
            $('.free-shipping-container').html(`
                <div class="free-shipping-msg" style="background: #fff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    <p style="margin: 0; color: #666;">Add RM <span class="remaining-amount">${remaining.toFixed(2)}</span> more for <strong>FREE SHIPPING</strong></p>
                </div>
            `);
            $('.shipping-cost').text('Calculated at checkout');
        }
    }

    // Quantity Buttons
    $(document).on('click', '.qty-btn', function() {
        const action = $(this).data('action');
        const cartId = $(this).data('cart-id');
        const input = $(`.qty-input[data-cart-id="${cartId}"]`);
        let qty = parseInt(input.val());

        if (action === 'increase') {
            const max = parseInt($(this).data('max'));
            if (qty < max) {
                qty++;
            } else {
                showToast('Maximum stock reached');
                return;
            }
        } else if (action === 'decrease') {
            if (qty > 1) {
                qty--;
            } else {
                return;
            }
        }

        input.val(qty);
        updateCartItem(cartId, qty);
    });

    // Cross-sell Add to Cart
    $(document).on('click', '.cross-sell .add-to-cart', function() {
        const productId = $(this).data('id');
        const btn = $(this);
        btn.prop('disabled', true).text('Adding...');

        $.post('/api/cart_add.php', { product_id: productId, quantity: 1 }, function(res) {
            if (res.success) {
                $('#cart-count').text(res.cart_count);
                currentSubtotal = res.new_total;
                reloadCart();
                btn.text('✓ Added').css('background', '#28a745');
                setTimeout(() => {
                    btn.prop('disabled', false).text('Add to Cart').css('background', '#D4AF37');
                }, 2000);
                showToast('Product added to cart!');
            } else {
                alert(res.message || 'Failed to add product');
                btn.prop('disabled', false).text('Add to Cart');
            }
        }, 'json').fail(function() {
            alert('Connection error');
            btn.prop('disabled', false).text('Add to Cart');
        });
    });

    // Remove Item
    $(document).on('click', '.remove-item', function() {
        if (confirm('Remove this item from cart?')) {
            const cartId = $(this).data('cart-id');
            const $cartItem = $(`.cart-item-card[data-cart-id="${cartId}"]`);
            
            $cartItem.fadeOut(300, function() {
                $.post('/api/cart_remove.php', { cart_id: cartId }, function(res) {
                    if (res.success) {
                        $('#cart-count').text(res.cart_count);
                        currentSubtotal = res.new_total;
                        
                        if (res.cart_count === 0) {
                            $('#cart-content-wrapper').html(`
                                <div class="empty-cart-message" style="text-align: center; padding: 6rem 0; background: #f9f9f9; border-radius: 12px;">
                                    <p style="font-size: 1.5rem; color: #666; margin-bottom: 1rem;">Your cart is empty</p>
                                    <a href="/page/product.php" style="color: #D4AF37; font-size: 1.1rem; text-decoration: none;">Continue Shopping →</a>
                                </div>
                            `);
                            
                            $('.order-summary').html(`
                                <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Order Summary</h3>
                                <p style="text-align: center; color: #666; padding: 2rem 0;">Your cart is empty</p>
                            `);
                        } else {
                            $cartItem.remove();
                            updateOrderSummary(res.new_total);
                        }
                        
                        showToast('Item removed from cart');
                    } else {
                        alert('Failed to remove item');
                        $cartItem.fadeIn(300);
                    }
                }, 'json').fail(function() {
                    alert('Connection error');
                    $cartItem.fadeIn(300);
                });
            });
        }
    });

    // Checkout
    $('#checkout-btn').on('click', function(e) {
        e.preventDefault(); // Prevent immediate navigation
        
        // Collect gift options
        const giftEnabled = $('#giftToggle').is(':checked');
        const giftData = {
            enabled: giftEnabled ? '1' : '0',
            packaging: $('input[name="packaging"]:checked').val() || 'standard',
            message: $('#giftMessage').val() || '',
            hidePrice: $('#hidePrice').is(':checked') ? '1' : '0'
        };
        
        
        // Save to PHP session
        $.ajax({
            url: '/api/save_gift_options.php',
            type: 'POST',
            data: giftData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Successfully saved, now go to checkout
                    window.location.href = '/page/checkout.php?debug=1';
                } else {
                    alert('Failed to save gift options: ' + response.message);
                }
            },
        });
    });

    // Update Cart Item
    function updateCartItem(cartId, quantity) {
        $.post('/api/cart_update.php', { cart_id: cartId, quantity: quantity }, function(res) {
            if (res.success) {
                $('#cart-count').text(res.cart_count);
                
                const $cartItem = $(`.cart-item-card[data-cart-id="${cartId}"]`);
                const itemPrice = parseFloat($cartItem.find('.item-price').text().replace(/,/g, ''));
                const newSubtotal = itemPrice * quantity;
                $cartItem.find('.item-total').text('RM ' + newSubtotal.toFixed(2));
                
                currentSubtotal = res.new_total;
                updateOrderSummary(res.new_total);
                
                showToast('Cart updated');
            } else {
                alert(res.message || 'Failed to update cart');
                reloadCart();
            }
        }, 'json').fail(function() {
            alert('Connection error');
            reloadCart();
        });
    }

    // Update Order Summary
    function updateOrderSummary(newTotal) {
        currentSubtotal = newTotal;
        
        $('.subtotal-amount').text('RM ' + newTotal.toFixed(2));
        
        updateTotal();
    }

    // Reload Cart
    function reloadCart() {
        $.get(location.href, function(html) {
            const newPage = $(html);
            
            const newCartContent = newPage.find('#cart-content-wrapper').html();
            if (newCartContent) {
                $('#cart-content-wrapper').html(newCartContent);
            }
            
            const newSubtotal = parseFloat(newPage.find('.subtotal-amount').text().replace(/RM|,/g, '').trim());
            if (!isNaN(newSubtotal)) {
                currentSubtotal = newSubtotal;
                updateTotal();
            }
        }).fail(function() {
            console.error('Failed to reload cart');
        });
    }

    function showToast(msg) {
        const toast = $('<div style="position:fixed;bottom:20px;right:20px;background:#28a745;color:#fff;padding:1rem 2rem;border-radius:8px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);">' + msg + '</div>');
        $('body').append(toast);
        toast.fadeIn().delay(2000).fadeOut(() => toast.remove());
    }
});
</script>

<?php include '../_foot.php'; ?>