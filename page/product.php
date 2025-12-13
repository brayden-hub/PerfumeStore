<?php
require '../_base.php';
$_title = 'Products - N°9 Perfume';

// process series filter
$selected_series = get('series', '');

include '../_head.php';  
?>

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

    <!-- sort by price -->
    <section class="products-grid" style="flex: 1; padding: 0 2rem;">
        <div class="filter-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="font-size: 2.5rem; font-weight: 300; letter-spacing: 3px;">OUR SCENTS</h2>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <span>Sort by Price:</span>
                <select id="sort-select" style="padding: 0.5rem; border: 1px solid #ddd;">
                    <option value="asc">Low to High</option>
                    <option value="desc">High to Low</option>
                </select>
            </div>
        </div>
        <div id="products-container" class="products-grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
        </div>
    </section>

    <!-- right fillter range for cost -->
    <aside class="price-filter" style="flex: 0 0 250px; background: #f8f8f8; padding: 2rem 1rem; border-radius: 8px 0 0 8px;">
        <h3 style="font-size: 1rem; letter-spacing: 2px; margin-bottom: 1.5rem; color: #111;">PRICE RANGE</h3>
        <div class="price-slider">
            <input type="range" id="price-min" min="0" max="600" value="0" style="width: 100%;">
            <input type="range" id="price-max" min="0" max="400" value="400" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #666;">
                <span>RM 0</span>
                <span id="price-range">RM 0 - RM 400</span>
                <span>RM 400</span>
            </div>
        </div>
    </aside>
</div>

<script>
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

        // price range change
        $('#price-min, #price-max').on('input', function() {
            const min = $('#price-min').val();
            const max = $('#price-max').val();
            $('#price-range').text(`RM ${min} - RM ${max}`);
            loadProducts();
        });

        // Series click
        $('.series-link').on('click', function(e) {
            e.preventDefault();
            $('.series-link').removeClass('active');
            $(this).addClass('active');
            loadProducts();
        });

        $('#sort-select').on('change', loadProducts);
    });

    function loadProducts() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentSeries = urlParams.get('series') || $('.series-link.active').data('series') || '';
        const min = $('#price-min').val();
        const max = $('#price-max').val();
        const sort = $('#sort-select').val();

        $.get('/api/products.php', {series: currentSeries, min: min, max: max, sort: sort}, function(html) {
            $('#products-container').html(html);
        });
    }
</script>

<?php include '../_foot.php'; ?>