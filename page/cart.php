<?php
require '../_base.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/page/login.php');
}

$_title = 'Your Cart - N°9 Perfume';
include '../_head.php';

$user_id = $_SESSION['user_id'];

// Fetch cart items with product details
$stmt = $_db->prepare("
    SELECT c.CartID, c.ProductID, c.Quantity, 
           p.ProductName, p.Price, p.Stock, p.Series
    FROM cart c
    JOIN product p ON c.ProductID = p.ProductID
    WHERE c.UserID = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$total = 0;
?>

<div class="container" style="margin-top: 100px; min-height: 60vh;">
    <h2 style="margin-bottom: 2rem;">Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 4rem; color: #666;">
            <p style="font-size: 1.2rem; margin-bottom: 1rem;">Your cart is empty</p>
            <a href="/page/product.php" style="color: #D4AF37; text-decoration: none;">Continue Shopping →</a>
        </div>
    <?php else: ?>
        <table class="product-table" style="margin-bottom: 2rem;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Series</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): 
                    $subtotal = $item->Price * $item->Quantity;
                    $total += $subtotal;
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <img src="/public/images/<?= htmlspecialchars($item->ProductID) ?>.png" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <span><?= htmlspecialchars($item->ProductName) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($item->Series) ?></td>
                    <td>RM <?= number_format($item->Price, 2) ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <button class="qty-btn" data-action="decrease" data-cart-id="<?= $item->CartID ?>" 
                                    style="padding: 0.3rem 0.7rem; background: #f0f0f0; border: none; cursor: pointer;">-</button>
                            <input type="number" value="<?= $item->Quantity ?>" min="1" max="<?= $item->Stock ?>" 
                                   class="qty-input" data-cart-id="<?= $item->CartID ?>"
                                   style="width: 60px; text-align: center; padding: 0.3rem; border: 1px solid #ddd;">
                            <button class="qty-btn" data-action="increase" data-cart-id="<?= $item->CartID ?>" 
                                    data-max="<?= $item->Stock ?>"
                                    style="padding: 0.3rem 0.7rem; background: #f0f0f0; border: none; cursor: pointer;">+</button>
                        </div>
                    </td>
                    <td style="font-weight: 600;">RM <?= number_format($subtotal, 2) ?></td>
                    <td>
                        <button class="remove-item" data-cart-id="<?= $item->CartID ?>"
                                style="padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; cursor: pointer; border-radius: 4px;">
                            Remove
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold; font-size: 1.2rem;">Total:</td>
                    <td colspan="2" style="font-weight: bold; font-size: 1.2rem; color: #D4AF37;">
                        RM <span id="cart-total"><?= number_format($total, 2) ?></span>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
            <a href="/page/product.php" style="color: #666; text-decoration: none;">← Continue Shopping</a>
            <button id="checkout-btn" style="padding: 1rem 3rem; background: #D4AF37; color: #000; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; border-radius: 4px;">
                Proceed to Checkout
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
// Update quantity
$('.qty-btn').on('click', function() {
    const action = $(this).data('action');
    const cartId = $(this).data('cart-id');
    const input = $(`.qty-input[data-cart-id="${cartId}"]`);
    let qty = parseInt(input.val());
    
    if (action === 'increase') {
        const max = parseInt($(this).data('max'));
        if (qty < max) qty++;
    } else if (action === 'decrease' && qty > 1) {
        qty--;
    }
    
    input.val(qty);
    updateCart(cartId, qty);
});

$('.qty-input').on('change', function() {
    const cartId = $(this).data('cart-id');
    const qty = parseInt($(this).val());
    if (qty > 0) {
        updateCart(cartId, qty);
    }
});

// Remove item
$('.remove-item').on('click', function() {
    if (confirm('Remove this item from cart?')) {
        const cartId = $(this).data('cart-id');
        $.post('/api/cart_remove.php', { cart_id: cartId }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message || 'Failed to remove item');
            }
        }, 'json');
    }
});

// Checkout
$('#checkout-btn').on('click', function() {
    window.location.href = '/page/checkout.php';
});

function updateCart(cartId, quantity) {
    $.post('/api/cart_update.php', { cart_id: cartId, quantity: quantity }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.message || 'Failed to update cart');
        }
    }, 'json');
}
</script>

<?php include '../_foot.php'; ?>