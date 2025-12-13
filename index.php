<?php
require '_base.php';
include '_head.php';

// 1. HERO
$stm_hero = $_db->query("SELECT * FROM product ORDER BY ProductID DESC LIMIT 1");
$hero = $stm_hero->fetch();

// 2. TOP SALES
$sql_top = "
    SELECT p.*, COALESCE(SUM(po.Quantity), 0) as total_sold 
    FROM product p 
    LEFT JOIN productorder po ON p.ProductID = po.ProductID 
    GROUP BY p.ProductID 
    ORDER BY total_sold DESC 
    LIMIT 4
";
$stm_top = $_db->query($sql_top);
$top_products = $stm_top->fetchAll();

// 3. SERIES
$stm_series = $_db->query("SELECT DISTINCT Series FROM product ORDER BY Series");
$series_list = $stm_series->fetchAll(PDO::FETCH_COLUMN);

// 4. SPOTLIGHT: random product
$stm_spot = $_db->query("SELECT * FROM product ORDER BY RAND() LIMIT 1");
$spot = $stm_spot->fetch();
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });

    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);  
    document.addEventListener("DOMContentLoaded", () => window.scrollTo(0, 0));

    
</script>

<section class="hero">
    <div class="hero-bg" style="background-image: url('/public/images/<?= $hero->Image ?>');"></div>
    <div class="hero-content">
        <span style="letter-spacing: 3px; color: #ddd; text-transform: uppercase;">New Arrival</span>
        <h2><?= $hero->ProductName ?></h2>
        <p>“<?= $hero->Description ?>”</p>
            <a href="/page/product_detail.php?id=<?= $hero->ProductID ?>">
                <button class="btn-outline">Discover Now</button>
            </a>
    </div>
</section>

<section class="bestsellers">
    <h3 class="section-title">Most Loved Scents</h3>
    <div class="bs-grid">
        <?php foreach ($top_products as $i => $prod): ?>
                <a href="/page/product_detail.php?id=<?= $prod->ProductID ?>" class="bs-card">
                    <?php if($i == 0): ?><div class="bs-tag">#1 Best Seller</div><?php endif; ?>
                <img src="/public/images/<?= $prod->Image ?>" alt="<?= htmlspecialchars($prod->ProductName) ?>">
                <h4 style="margin: 10px 0; font-size: 1rem;"><?= $prod->ProductName ?></h4>
                <p style="color: #666; font-size: 0.9rem;">RM <?= number_format($prod->Price, 2) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="series-section">
    <h3 class="section-title">Explore Collections</h3>
    <div class="series-container">
        <?php foreach($series_list as $s): ?>
            <?php 
                $stm_img = $_db->prepare("SELECT Image FROM product WHERE Series = ? LIMIT 1");
                $stm_img->execute([$s]);
                $s_img = $stm_img->fetchColumn();
            ?>
            <a href="/page/product.php?series=<?= urlencode($s) ?>" class="series-card" 
               style="background-image: url('/public/images/<?= $s_img ?>'); background-size: cover;">
                <span class="series-name"><?= $s ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="spotlight-section">
    <div class="spotlight-img">
        <img src="/public/images/<?= $spot->Image ?>" alt="<?= $spot->ProductName ?>">
    </div>
    <div class="spotlight-info">
        <span class="spot-tag">Spotlight of the Moment</span>
        <h3><?= $spot->ProductName ?></h3>
        <span class="price">RM <?= number_format($spot->Price, 2) ?></span>
        <p>
            Experience the unique notes of the <?= $spot->Series ?> collection.<br>
            <strong>Description:</strong> <?= $spot->Description ?>
        </p>
            <a href="/page/product_detail.php?id=<?= $spot->ProductID ?>">
                <button class="btn-black">View Details</button>
            </a>
    </div>
</section>

<?php include '_foot.php'; ?>