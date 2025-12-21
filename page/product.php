<?php
require '../_base.php';
$_title = 'Products - N°9 Perfume';

// Process series filter
$selected_series = get('series', '');

// Check if user is admin
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';

include '../_head.php';  
?>

<link rel="stylesheet" href="/public/css/perfume.css">

<div class="homepage-main" style="display: flex; padding: 4rem 0;">
    <aside class="series-nav" style="flex: 0 0 250px; background: #f8f8f8; padding: 2rem 1rem; border-radius: 0 8px 8px 0;">
        <h3 style="font-size: 1rem; letter-spacing: 2px; margin-bottom: 1.5rem; color: #111;">SERIES</h3>
        <ul style="list-style: none; padding:0; margin:0;">
            <li><a href="/page/product.php" class="series-link <?= $selected_series == '' ? 'active' : '' ?>" data-series="">All</a></li>
            <?php
            $stm = $_db->query("SELECT DISTINCT Series FROM product ORDER BY Series");
            foreach ($stm->fetchAll(PDO::FETCH_COLUMN) as $series):
            ?>
                <li><a href="/page/product.php?series=<?= urlencode($series) ?>" 
                    class="series-link <?= $selected_series == $series ? 'active' : '' ?>" 
                    data-series="<?= htmlspecialchars($series) ?>">
                    <?= htmlspecialchars($series) ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <!-- Products Grid -->
    <section class="products-grid" style="flex: 1; padding: 0 2rem;">
        <div class="filter-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="font-size: 2.5rem; font-weight: 300; letter-spacing: 3px;">OUR SCENTS</h2>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <span>Sort by Price:</span>
                <select id="sort-select" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="asc">Low to High</option>
                    <option value="desc">High to Low</option>
                </select>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <input 
                type="text" 
                id="search-input" 
                class="search-input" 
                placeholder="Search by product ID or name..."
            >
            <button class="clear-search" id="clear-search">×</button>
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
        </div>

        <div id="products-container" class="products-grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            <!-- Products will be loaded here via AJAX -->
        </div>
    </section>

    <!-- Price Filter -->
    <aside class="price-filter" style="flex: 0 0 250px; background: #f8f8f8; padding: 2rem 1rem; border-radius: 8px 0 0 8px;">
        <h3 style="font-size: 1rem; letter-spacing: 2px; margin-bottom: 1.5rem; color: #111;">PRICE RANGE</h3>
        <div class="price-slider">
            <input type="range" id="price-min" min="0" max="600" value="0" style="width: 100%;">
            <input type="range" id="price-max" min="0" max="400" value="400" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                <span>RM 0</span>
                <span id="price-range" style="font-weight: 600; color: #D4AF37;">RM 0 - RM 400</span>
                <span>RM 400</span>
            </div>
        </div>
    </aside>
</div>

<script>
let currentPage = 1;
let searchTimeout;
const isAdmin = <?= $is_admin ? 'true' : 'false' ?>;

// Helper function to find product image with any extension
function getProductImageUrl(productId) {
    const extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    
    // Return a promise that checks each extension
    return new Promise((resolve) => {
        let index = 0;
        
        function checkNext() {
            if (index >= extensions.length) {
                resolve('/public/images/photo.jpg'); // Default fallback
                return;
            }
            
            const img = new Image();
            const url = `/public/images/${productId}.${extensions[index]}`;
            
            img.onload = function() {
                resolve(url);
            };
            
            img.onerror = function() {
                index++;
                checkNext();
            };
            
            img.src = url;
        }
        
        checkNext();
    });
}

$(document).ready(function() {
    window.scrollTo(0, 0);

    // Set active based on the current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const currentSeries = urlParams.get('series') || '';

    $('.series-link').removeClass('active');
    if (currentSeries === '') {
        $('.series-link[data-series=""]').addClass('active');
    } else {
        $('.series-link[data-series="' + currentSeries + '"]').addClass('active');
    }

    loadProducts();

    // Search input with debounce
    $('#search-input').on('input', function() {
        const value = $(this).val().trim();
        
        // Show/hide clear button
        if (value) {
            $('#clear-search').addClass('visible');
        } else {
            $('#clear-search').removeClass('visible');
        }

        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadProducts();
        }, 300);
    });

    // Clear search button
    $('#clear-search').on('click', function() {
        $('#search-input').val('').focus();
        $(this).removeClass('visible');
        currentPage = 1;
        loadProducts();
    });

    // Price range change
    $('#price-min, #price-max').on('input', function() {
        const min = parseInt($('#price-min').val());
        const max = parseInt($('#price-max').val());
        
        if (min > max) {
            if ($(this).attr('id') === 'price-min') {
                $('#price-min').val(max);
            } else {
                $('#price-max').val(min);
            }
        }
        
        const finalMin = parseInt($('#price-min').val());
        const finalMax = parseInt($('#price-max').val());
        $('#price-range').text(`RM ${finalMin} - RM ${finalMax}`);
        
        currentPage = 1;
        loadProducts();
    });

    // Series click
    $('.series-link').on('click', function(e) {
        e.preventDefault();
        $('.series-link').removeClass('active');
        $(this).addClass('active');
        
        const series = $(this).data('series');
        const newUrl = series ? `/page/product.php?series=${encodeURIComponent(series)}` : '/page/product.php';
        window.history.pushState({}, '', newUrl);
        
        currentPage = 1;
        loadProducts();
    });

    $('#sort-select').on('change', function() {
        currentPage = 1;
        loadProducts();
    });

    // Pagination click
    $(document).on('click', '.pagination-btn', function() {
        currentPage = parseInt($(this).data('page'));
        loadProducts();
        
        $('html, body').animate({
            scrollTop: $('.products-grid').offset().top - 100
        }, 500);
    });

    // Handle click on product card to view details
    $(document).on('click', '.product-card', function(e) {
        if (
            $(e.target).hasClass('add-to-cart-btn') ||
            $(e.target).closest('.add-to-cart-btn').length ||
            $(e.target).hasClass('fav-btn') ||
            $(e.target).closest('.fav-btn').length
        ) {
            return;
        }

        const productId = $(this).data('product-id');
        if (!productId) {
            console.error('Product ID not found on card:', this);
            return;
        }
        window.location.href = `/page/product_detail.php?id=${productId}`;
    });

    // Handle add to cart with Admin check
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.stopPropagation();
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            alert('Please login first to add items to cart');
            window.location.href = '/page/login.php';
            return;
        <?php elseif ($is_admin): ?>
            alert('Admin accounts cannot purchase products. Please use a member account.');
            return;
        <?php endif; ?>
        
        const productId = $(this).data('product-id');
        const btn = $(this);
        
        if (btn.prop('disabled')) return;
        
        btn.prop('disabled', true).text('Adding...');

        $.post('/api/cart_add.php', { 
            product_id: productId, 
            quantity: 1 
        }, function(res) {
            if (res.success) {
                $('#cart-count').text(res.cart_count);
                btn.text('✓ Added').css('background', '#28a745');
                showToast('Product added to cart!');
                
                setTimeout(() => {
                    btn.prop('disabled', false)
                       .text('Add to Cart')
                       .css('background', '#000');
                }, 2000);
            } else {
                alert(res.message || 'Failed to add product');
                btn.prop('disabled', false).text('Add to Cart');
            }
        }, 'json').fail(function() {
            alert('Connection error');
            btn.prop('disabled', false).text('Add to Cart');
        });
    });

    // Handle add to favourites - Block for admin
    $(document).on('click', '.fav-btn', function(e) {
        e.stopPropagation();

        <?php if (!isset($_SESSION['user_id'])): ?>
            alert('Please login to use favourites');
            window.location.href = '/page/login.php';
            return;
        <?php elseif ($is_admin): ?>
            alert('Admin accounts cannot use the favorites feature.');
            return;
        <?php endif; ?>

        const productId = $(this).data('product-id');
        const btn = $(this);

        $.post('/api/favorite_toggle.php', { product_id: productId }, function(res) {
            if (res.success) {
                btn.text(res.favorited ? '♥' : '♡');
                btn.toggleClass('active', res.favorited);
            } else {
                alert('Please login to use favourites');
            }
        }, 'json');
    });   
});

function loadProducts() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentSeries = urlParams.get('series') || $('.series-link.active').data('series') || '';
    const min = $('#price-min').val();
    const max = $('#price-max').val();
    const sort = $('#sort-select').val();
    const search = $('#search-input').val().trim();

    $('#products-container').html(`
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
            <div style="font-size: 1.2rem; color: #999;">Loading products...</div>
        </div>
    `);

    $.get('/api/products.php', {
        series: currentSeries, 
        min: min, 
        max: max, 
        sort: sort,
        page: currentPage,
        search: search
    }, function(html) {
        $('#products-container').html(html);
    }).fail(function() {
        $('#products-container').html(`
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                <div style="font-size: 1.2rem; color: #dc3545;">Failed to load products. Please try again.</div>
            </div>
        `);
    });
}

function showToast(message) {
    const toast = $(`
        <div style="
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #28a745;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
        ">
            ${message}
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, () => toast.remove());
    }, 2000);
}
</script>

<?php include '../_foot.php'; ?>