    <?php
    require '../_base.php';

    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }

    $user_id = $_SESSION['user_id'];

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

    <h2 style="margin:2rem;">My Favorites ❤️</h2>

    <?php if (empty($favorites)): ?>
        <p style="margin:2rem; color:#999;">No favorite products yet.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:2rem; padding:2rem;">
            <?php foreach ($favorites as $p): ?>
                <div style="border:1px solid #eee; padding:1rem;">
                    <img src="/public/images/<?= htmlspecialchars($p->ProductID) ?>.png" style="width:100%;">
                    <h4><?= htmlspecialchars($p->ProductName) ?></h4>
                    <p>RM <?= number_format($p->Price,2) ?></p>
                    <a href="product_detail.php?id=<?= $p->ProductID ?>">View</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php include '../_foot.php'; ?>
