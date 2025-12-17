<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// STEP 1: GET REQUEST (Load Data)
if (is_get()) {
    $id = req('productID'); // Get ID from URL

    // Fetch Main Product Data
    $stm = $_db->prepare('SELECT * FROM product WHERE ProductID = ?');
    $stm->execute([$id]);
    $p = $stm->fetch();

    if (!$p) {
        redirect('productList.php');
    }

    // Populate variables
    $ProductID   = $p->ProductID;
    $Series      = $p->Series;
    $ProductName = $p->ProductName;
    $Price       = $p->Price;
    $Stock       = $p->Stock;
    $Description = $p->Description;
    $Image       = $p->Image; // Main Image
    
    // NEW: Fetch Gallery Images
    $stm_gal = $_db->prepare("SELECT * FROM product_images WHERE ProductID = ?");
    $stm_gal->execute([$ProductID]);
    $gallery_images = $stm_gal->fetchAll();
}

// STEP 2: POST REQUEST (Update Data)
if (is_post()) {
    $ProductID   = req('ProductID');
    $Series      = req('Series');
    $ProductName = req('ProductName');
    $Price       = req('Price');
    $Stock       = req('Stock');
    $Description = req('Description');
    
    // Main Image File
    $f = get_file('Image'); 
    // Gallery Files
    $gallery = $_FILES['gallery'] ?? null;

    // 1. VALIDATION
    if ($ProductName == '') $_err['ProductName'] = 'Required';
    else if (strlen($ProductName) > 100) $_err['ProductName'] = 'Maximum 100 characters';

    if ($Price == '') $_err['Price'] = 'Required';
    else if (!is_money($Price)) $_err['Price'] = 'Must be money format';

    if ($Stock == '') $_err['Stock'] = 'Required';

    // Validate Main Image (if uploaded)
    if ($f && !str_starts_with($f->type, 'image/')) $_err['Image'] = 'Must be an image';
    if ($f && $f->size > 1 * 1024 * 1024) $_err['Image'] = 'Maximum 1MB';

    // 2. DB OPERATION
    if (!$_err) {
        
        // A. Update Basic Info
        // Check if main image needs updating
        if ($f) {
            $ext = pathinfo($f->name, PATHINFO_EXTENSION);
            $filename = "$ProductID.$ext";
            $target = "../public/images/$filename";
            
            if (!is_dir('../public/images')) { mkdir('../public/images', 0777, true); }
            move_uploaded_file($f->tmp_name, $target);

            $stm = $_db->prepare('UPDATE product SET Series=?, ProductName=?, Price=?, Stock=?, Description=?, Image=? WHERE ProductID=?');
            $stm->execute([$Series, $ProductName, $Price, $Stock, $Description, $filename, $ProductID]);
        } else {
            $stm = $_db->prepare('UPDATE product SET Series=?, ProductName=?, Price=?, Stock=?, Description=? WHERE ProductID=?');
            $stm->execute([$Series, $ProductName, $Price, $Stock, $Description, $ProductID]);
        }

        // B. Handle NEW Gallery Images (Append to existing)
        if ($gallery && !empty($gallery['name'][0])) {
            $stmGallery = $_db->prepare("INSERT INTO product_images (ProductID, Filename) VALUES (?, ?)");
            $count = count($gallery['name']);
            
            for ($i = 0; $i < $count; $i++) {
                if ($gallery['error'][$i] !== 0) continue;
                
                $gName = $gallery['name'][$i];
                $gTmp  = $gallery['tmp_name'][$i];
                $gExt  = pathinfo($gName, PATHINFO_EXTENSION);
                
                // Unique Name: P0001_uniqueid.jpg
                $gFilename = $ProductID . '_' . uniqid() . '.' . $gExt;
                $gTarget   = "../public/images/" . $gFilename;
                
                if (move_uploaded_file($gTmp, $gTarget)) {
                    $stmGallery->execute([$ProductID, $gFilename]);
                }
            }
        }

        temp('info', 'Record updated successfully');
        redirect('productList.php');
    }
}

$_title = 'Edit Product - N°9 Perfume';
include '../_head.php';
?>

<div class="form-container">
    <h2>Edit Product</h2>

    <form method="post" class="form" enctype="multipart/form-data" novalidate>
        
        <input type="hidden" name="ProductID" value="<?= $ProductID ?>">

        <label>Product ID</label>
        <b><?= $ProductID ?></b>
        <br>

        <label for="Series">Series</label>
        <?= html_select('Series', [
            'Fresh' => 'Fresh', 'Floral' => 'Floral', 'Fruity' => 'Fruity', 
            'Green' => 'Green', 'Woody' => 'Woody'
        ], $Series) ?>
        <?= err('Series') ?>

        <label for="ProductName">Product Name</label>
        <?= html_text('ProductName', 'maxlength="100"', $ProductName) ?>
        <?= err('ProductName') ?>

        <label for="Price">Price (RM)</label>
        <?= html_number('Price', 0.01, '', 0.01, $Price) ?>
        <?= err('Price') ?>

        <label for="Stock">Stock</label>
        <?= html_number('Stock', 1, '', 1, $Stock) ?>
        <?= err('Stock') ?>

        <label for="Description">Description</label>
        <textarea name="Description" id="Description" rows="4"><?= htmlspecialchars($Description ?? '') ?></textarea>
        <?= err('Description') ?>

        <label>Main Cover Image</label>
        <label class="upload">
            <input type="file" name="Image" accept="image/*">
            <img src="/public/images/<?= $Image ?>" alt="Product Image" onerror="this.src='/public/images/photo.jpg'">
        </label>
        <small>Click image to change main cover (Optional)</small>
        <?= err('Image') ?>

        <?php if (!empty($gallery_images)): ?>
        <label style="margin-top:30px;">Current Gallery Images</label>
        <div class="gallery-preview-container" style="margin-bottom: 20px;">
            <?php foreach ($gallery_images as $img): ?>
                <div class="img-preview-wrapper">
                    <img src="/public/images/<?= htmlspecialchars($img->Filename) ?>" 
                         class="img-preview-thumb">
                    
                    <button type="button" 
                            data-post="product_image_delete.php?id=<?= $img->ImageID ?>" 
                            data-confirm="Delete this image?"
                            class="btn-img-delete">
                        ✕
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <label style="margin-top: 20px;">Add More Gallery Images</label>
        <div class="gallery-upload-box" id="gallery-drop-zone">
            <p><strong>Click</strong> or <strong>Drag & Drop</strong> to add more photos</p>
            <input type="file" name="gallery[]" multiple accept="image/*" id="gallery-input">
        </div>
        <div class="gallery-preview-container" id="gallery-preview"></div>

        <div style="margin-top:20px;">
            <button class="btn-submit">Update Product</button>
            <a href="productList.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php include '../_foot.php';?>