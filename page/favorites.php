<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 取收藏商品
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

<div class="account-wrapper">
    <?php include 'account_sidebar.php'; ?>

    <div class="account-content">
        <h2>My Favorites</h2>

        <?php if (empty($favorites)): ?>
            <p style="margin-top:2rem; color:#999;">
                You haven’t added any favorites yet.
            </p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1.5rem; margin-top:2rem;">
                <?php foreach ($favorites as $p): ?>
                    <div style="border:1px solid #ddd; padding:1rem; border-radius:8px; text-align:center;">
                        <img src="../images/products/<?= htmlspecialchars($p->Image) ?>"
                             style="width:100%; height:160px; object-fit:cover; border-radius:5px;">

                        <h4 style="margin:0.6rem 0;">
                            <?= htmlspecialchars($p->ProductName) ?>
                        </h4>

                        <p style="color:#555;">RM <?= number_format($p->Price, 2) ?></p>

                        <a href="remove_favorite.php?id=<?= $p->ProductID ?>"
                           style="color:#c33; font-size:0.9rem;">
                            Remove
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../_foot.php'; ?>
