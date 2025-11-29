<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// STEP 1: GET REQUEST (Load Data)
if (is_get()) {
    $id = req('productID'); // Get ID from URL

    $stm = $_db->prepare('SELECT * FROM product WHERE ProductID = ?');
    $stm->execute([$id]);
    $p = $stm->fetch();

    if (!$p) {
        redirect('productList.php');
    }

    // Populate global variables for the html helper functions
    $ProductID   = $p->ProductID;
    $Series      = $p->Series;
    $ProductName = $p->ProductName;
    $Price       = $p->Price;
    $Stock       = $p->Stock;
    $Description = $p->Description;
    $Image       = $p->Image; // Current filename in DB
}

// STEP 2: POST REQUEST (Update Data)
if (is_post()) {
    $ProductID   = req('ProductID'); // Get ID from HIDDEN input
    $Series      = req('Series');
    $ProductName = req('ProductName');
    $Price       = req('Price');
    $Stock       = req('Stock');
    $Description = req('Description');
    
    // Check if new file is uploaded
    $f = get_file('Image'); 

    // 1. VALIDATION
    if ($ProductName == '') {
        $_err['ProductName'] = 'Required';
    } else if (strlen($ProductName) > 100) {
        $_err['ProductName'] = 'Maximum 100 characters';
    }

    if ($Price == '') {
        $_err['Price'] = 'Required';
    } else if (!is_money($Price)) {
        $_err['Price'] = 'Must be money format';
    }

    if ($Stock == '') {
        $_err['Stock'] = 'Required';
    }

    // Validate Image (Only if a new one is uploaded)
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['Image'] = 'Must be an image';
        } else if ($f->size > 1 * 1024 * 1024) {
            $_err['Image'] = 'Maximum 1MB';
        }
    }

    // 2. DB OPERATION
    if (!$_err) {
        
        if ($f) {
            $ext = pathinfo($f->name, PATHINFO_EXTENSION);
            $filename = "$ProductID.$ext";
            $target = "../public/images/$filename";
            
            if (!is_dir('../public/images')) { mkdir('../public/images', 0777, true); }
            move_uploaded_file($f->tmp_name, $target);

            $stm = $_db->prepare('
                UPDATE product
                SET Series = ?, ProductName = ?, Price = ?, Stock = ?, Description = ?, Image = ?
                WHERE ProductID = ?
            ');
            $stm->execute([$Series, $ProductName, $Price, $Stock, $Description, $filename, $ProductID]);

        } else {
            $stm = $_db->prepare('
                UPDATE product
                SET Series = ?, ProductName = ?, Price = ?, Stock = ?, Description = ?
                WHERE ProductID = ?
            ');
            $stm->execute([$Series, $ProductName, $Price, $Stock, $Description, $ProductID]);
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
            'Fresh' => 'Fresh', 
            'Floral' => 'Floral', 
            'Fruity' => 'Fruity', 
            'Green' => 'Green', 
            'Woody' => 'Woody'
        ]) ?>
        <?= err('Series') ?>

        <label for="ProductName">Product Name</label>
        <?= html_text('ProductName', 'maxlength="100"') ?>
        <?= err('ProductName') ?>

        <label for="Price">Price (RM)</label>
        <?= html_number('Price', 0.01, '', 0.01) ?>
        <?= err('Price') ?>

        <label for="Stock">Stock</label>
        <?= html_number('Stock', 1, '', 1) ?>
        <?= err('Stock') ?>

        <label for="Description">Description</label>
        <textarea name="Description" id="Description" rows="4"><?= encode($Description ?? '') ?></textarea>
        <?= err('Description') ?>

        <label>Product Image</label>
        <label class="upload">
            <input type="file" name="Image" accept="image/*">
            <img src="/public/images/<?= $Image ?>" alt="Product Image" onerror="this.src='/public/images/photo.jpg'">
        </label>
        <small>Click image to change (Optional)</small>
        <?= err('Image') ?>

        <div style="margin-top:20px;">
            <button class="btn-submit">Update Product</button>
            <a href="productList.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php include '../_foot.php';?>