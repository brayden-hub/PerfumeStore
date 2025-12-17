<?php
require '_base.php';
include '_head.php';

// 1. HERO
$stm_hero = $_db->query("SELECT * FROM product WHERE Status NOT LIKE '%Not%' ORDER BY ProductID DESC LIMIT 1");
$hero = $stm_hero->fetch();

// 2. TOP SALES
$sql_top = "
    SELECT p.*, COALESCE(SUM(po.Quantity), 0) as total_sold 
    FROM product p 
    LEFT JOIN productorder po ON p.ProductID = po.ProductID 
    WHERE p.Status NOT LIKE '%Not%'
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
</script>

<section class="hero">
    <div class="hero-bg" style="background-image: url('/public/images/<?= $hero->Image ?>');"></div>
    <div class="hero-content">
        <span style="letter-spacing: 3px; color: #ddd; text-transform: uppercase;">New Arrival</span>
        <h2><?= $hero->ProductName ?></h2>
        <p>"<?= $hero->Description ?>"</p>
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

<!-- ========================================
     CINEMATIC VIDEO SHOWCASE
     ======================================== -->
<section class="cinematic-showcase">
    <!-- Parallax Background -->
    <div class="parallax-bg" data-parallax="true"></div>
    
    <!-- Content Container -->
    <div class="cinematic-content">
        <div class="cinematic-header">
            <span class="cinematic-tag">Experience</span>
            <h2 class="cinematic-title">The Art of N°9</h2>
            <p class="cinematic-subtitle">Where craftsmanship meets elegance</p>
        </div>
        
        <!-- Video Container -->
        <div class="video-container" id="videoContainer">
            <div class="video-frame">
                <!-- YouTube Iframe -->
                <iframe 
                    id="showcaseVideo"
                    class="showcase-video"
                    src="https://www.youtube.com/embed/MObnCjMmGS8?enablejsapi=1&controls=0&modestbranding=1&rel=0&showinfo=0&loop=1&playlist=MObnCjMmGS8&mute=1&autoplay=1"                                      title="N°9 Perfume Showcase"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
                                
                <!-- Sound Toggle Button -->
                <button class="sound-toggle" id="soundToggle" aria-label="Toggle sound">
                    <svg class="sound-icon sound-off" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5L6 9H2v6h4l5 4V5z"/>
                        <line x1="23" y1="9" x2="17" y2="15"/>
                        <line x1="17" y1="9" x2="23" y2="15"/>
                    </svg>
                    <svg class="sound-icon sound-on" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5L6 9H2v6h4l5 4V5z"/>
                        <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Call to Action -->
        <div class="cinematic-cta">
            <a href="/page/product.php" class="btn-cinematic">
                <span>Explore Collection</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<script>
// Load YouTube IFrame API
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// Cinematic Video Showcase Scripts
var player;
var isMuted = true;
var isPlayerReady = false;

function onYouTubeIframeAPIReady() {
    player = new YT.Player('showcaseVideo', {
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

function onPlayerReady(event) {
    isPlayerReady = true;
    const container = document.getElementById('videoContainer');
    const soundToggle = document.getElementById('soundToggle');
    const hoverHint = document.getElementById('hoverHint');
    const parallaxBg = document.querySelector('.parallax-bg');
    
    // Intersection Observer for autoplay
    const videoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && isPlayerReady) {
                player.playVideo();
            } else if (isPlayerReady) {
                player.pauseVideo();
            }
        });
    }, { threshold: 0.5 });
    
    videoObserver.observe(container);
    
    // Sound toggle
    soundToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!isPlayerReady) return;
        
        isMuted = !isMuted;
        
        if (isMuted) {
            player.mute();
        } else {
            player.unMute();
        }
        
        soundToggle.classList.toggle('active', !isMuted);
        
        if (!isMuted) {
            hoverHint.style.opacity = '0';
            setTimeout(() => hoverHint.style.display = 'none', 300);
        }
    });
    
    // Hover effects
    container.addEventListener('mouseenter', () => {
        container.classList.add('hovered');
        if (isMuted) {
            hoverHint.style.opacity = '1';
        }
    });
    
    container.addEventListener('mouseleave', () => {
        container.classList.remove('hovered');
        hoverHint.style.opacity = '0';
    });
    
    // Parallax effect
    window.addEventListener('scroll', () => {
        if (!parallaxBg) return;
        
        const scrolled = window.pageYOffset;
        const parallaxSection = document.querySelector('.cinematic-showcase');
        
        if (!parallaxSection) return;
        
        const sectionTop = parallaxSection.offsetTop;
        const sectionHeight = parallaxSection.offsetHeight;
        const windowHeight = window.innerHeight;
        
        if (scrolled + windowHeight > sectionTop && scrolled < sectionTop + sectionHeight) {
            const relativeScroll = scrolled - sectionTop + windowHeight;
            const parallaxSpeed = 0.5;
            const yPos = -(relativeScroll * parallaxSpeed);
            
            parallaxBg.style.transform = `translate3d(0, ${yPos}px, 0)`;
        }
    });
    
    // Hide hint after 3 seconds
    setTimeout(() => {
        if (isMuted && hoverHint) {
            hoverHint.style.opacity = '0';
        }
    }, 3000);
}

function onPlayerStateChange(event) {
    // Keep video playing in loop
    if (event.data === YT.PlayerState.ENDED) {
        player.playVideo();
    }
}
</script>

<?php include '_foot.php'; ?>