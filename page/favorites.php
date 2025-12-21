<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}


// Check if user is admin - redirect to home with message
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
    temp('info', 'Admin accounts cannot access favorites feature.');
    redirect('/');

}

$user_id = $_SESSION['user_id'];

// Handling AJAX requests to remove favorites
if (is_post() && isset($_POST['remove_favorite'])) {
    $product_id = $_POST['product_id'];
    
    $stm = $_db->prepare("DELETE FROM favorites WHERE UserID = ? AND ProductID = ?");
    $stm->execute([$user_id, $product_id]);
    
    echo json_encode(['success' => true]);
    exit();
}

$stm = $_db->prepare("
    SELECT p.*
    FROM favorites f
    JOIN product p ON f.ProductID = p.ProductID
    WHERE f.UserID = ?
    ORDER BY f.CreatedAt DESC
");
$stm->execute([$user_id]);
$favorites = $stm->fetchAll();

$_title = 'My Favorites - Nº9 Perfume';
include '../_head.php';
?>

<!-- An inline script that executes immediately, before any content is rendered. -->
<script>
if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}
window.scrollTo(0, 0);
</script>

<style>
    /* Force the page to start from the top. */
    html, body {
        scroll-behavior: auto !important;
    }
    
    body {
        overflow-x: hidden;
    }

    .account-content {
        max-width: 100% !important;
        width: 100% !important;
        padding: 2rem;
    }

    .favorites-header {
        text-align: center;
        margin-bottom: 3rem;
        animation: fadeInDown 0.6s ease;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .favorites-header h2 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
    }

    .favorites-header p {
        color: #666;
        font-size: 1.1rem;
        margin-top: 0.5rem;
    }

    .heart-animation {
        display: inline-block;
        animation: heartbeat 1.5s ease-in-out infinite;
    }

    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        10%, 30% { transform: scale(0.9); }
        20%, 40% { transform: scale(1.1); }
    }

    .favorites-stats {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 16px;
        margin-bottom: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        box-shadow: 0 8px 24px rgba(255, 107, 107, 0.3);
    }

    .favorites-stats span {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .empty-favorites {
        text-align: center;
        padding: 5rem 2rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .empty-favorites-icon {
        font-size: 5rem;
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .empty-favorites h3 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 1rem;
    }

    .empty-favorites p {
        color: #666;
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    .browse-btn {
        display: inline-block;
        padding: 1rem 2.5rem;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
    }

    .browse-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
    }

    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        transition: all 0.4s ease;
        position: relative;
        animation: fadeInUp 0.6s ease;
    }

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

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.15);
    }

    .product-image-container {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        aspect-ratio: 1;
    }

    .product-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .product-card:hover img {
        transform: scale(1.1);
    }

    .favorite-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.95);
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: heartbeat 1.5s ease-in-out infinite;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .favorite-badge:hover {
        transform: scale(1.15);
        background: #fff;
    }

    .product-info {
        padding: 1.5rem;
    }

    .product-name {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 0.75rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #6366f1;
        margin-bottom: 1.25rem;
    }

    .product-actions {
        display: flex;
        gap: 0.75rem;
    }

    .view-btn {
        flex: 1;
        padding: 0.875rem;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        text-decoration: none;
        text-align: center;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .view-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    }

    .remove-btn {
        padding: 0.875rem 1rem;
        background: white;
        color: #dc2626;
        border: 2px solid #fecaca;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .remove-btn:hover {
        background: #dc2626;
        color: white;
        border-color: #dc2626;
        transform: translateY(-2px);
    }

    .product-tag {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: rgba(0, 0, 0, 0.75);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    /* Staggered animation for cards */
    .product-card:nth-child(1) { animation-delay: 0.1s; }
    .product-card:nth-child(2) { animation-delay: 0.2s; }
    .product-card:nth-child(3) { animation-delay: 0.3s; }
    .product-card:nth-child(4) { animation-delay: 0.4s; }
    .product-card:nth-child(5) { animation-delay: 0.5s; }
    .product-card:nth-child(6) { animation-delay: 0.6s; }

    @media (max-width: 768px) {
        .favorites-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .favorites-header h2 {
            font-size: 2rem;
        }

        .account-content {
            padding: 1rem;
        }
    }

    /* Remove animation */
    .product-card.removing {
        animation: fadeOutScale 0.5s ease forwards;
    }

    @keyframes fadeOutScale {
        0% {
            opacity: 1;
            transform: scale(1);
        }
        100% {
            opacity: 0;
            transform: scale(0.8);
        }
    }

    /* success toast */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Hide the page until the scroll position is set. */
    body.loading {
        opacity: 0;
    }
</style>

<script>
// Add loading class before page rendering
document.documentElement.className = 'loading';
</script>

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content">
        <div class="favorites-header">
            <h2>
                My Favorites 
                <span class="heart-animation">❤️</span>
            </h2>
            <p>Your curated collection of beloved fragrances</p>
        </div>

        <?php if (!empty($favorites)): ?>
            <div class="favorites-stats">
                <span>💝</span>
                <span>You have <?= count($favorites) ?> favorite <?= count($favorites) == 1 ? 'product' : 'products' ?></span>
            </div>

            <div class="favorites-grid">
                <?php foreach ($favorites as $p): ?>
                    <div class="product-card" data-product-id="<?= $p->ProductID ?>">
                        <div class="product-image-container">
                            <div class="product-tag">Favorite</div>
                            <img src="/public/images/<?= htmlspecialchars($p->ProductID) ?>.png" 
                                 alt="<?= htmlspecialchars($p->ProductName) ?>"
                                 onerror="this.src='/public/images/default.png'">
                            <div class="favorite-badge">❤️</div>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($p->ProductName) ?></h3>
                            <div class="product-price">RM <?= number_format($p->Price, 2) ?></div>
                            
                            <div class="product-actions">
                                <a href="product_detail.php?id=<?= $p->ProductID ?>" class="view-btn">
                                    👁️ View Details
                                </a>
                                <button class="remove-btn" onclick="removeFavorite('<?= $p->ProductID ?>')">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="empty-favorites">
                <div class="empty-favorites-icon">💔</div>
                <h3>Your Favorites is Empty</h3>
                <p>Start adding products you love to see them here!</p>
                <a href="/page/product.php" class="browse-btn">
                    🛍️ Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Set the location before the page starts loading.
if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}

// Execute immediately, without waiting for any event.
window.scrollTo(0, 0);
document.documentElement.scrollTop = 0;
document.body.scrollTop = 0;

// Remove the loading class and display the page.
setTimeout(function() {
    document.documentElement.classList.remove('loading');
    document.body.style.opacity = '1';
}, 10);

// Make sure it's at the top again when the page loads.
window.addEventListener('load', function() {
    window.scrollTo(0, 0);
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
});

function removeFavorite(productId) {
    if (!confirm('Remove this product from favorites?')) {
        return;
    }

    const card = document.querySelector(`[data-product-id="${productId}"]`);
    
    // Add/Remove Animations
    card.classList.add('removing');
    
    // send AJAX request to remove favorite
    fetch('favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `remove_favorite=1&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // show success toast
            showToast('✓ Removed from favorites');
            
            // wait for animation to finish before removing from DOM
            setTimeout(() => {
                card.remove();
                
                // check if there are any cards left
                const remainingCards = document.querySelectorAll('.product-card');
                if (remainingCards.length === 0) {
                    // If there are no more products, refresh the page to show an empty state
                    location.reload();
                } else {
                    // Update statistics
                    updateStats();
                }
            }, 500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove from favorites');
        card.classList.remove('removing');
    });
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<span style="font-size:1.5rem;">✓</span><span>${message}</span>`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

function updateStats() {
    const count = document.querySelectorAll('.product-card').length;
    const statsElement = document.querySelector('.favorites-stats span:last-child');
    if (statsElement) {
        statsElement.textContent = `You have ${count} favorite ${count === 1 ? 'product' : 'products'}`;
    }
}
</script>

<?php include '../_foot.php'; ?>