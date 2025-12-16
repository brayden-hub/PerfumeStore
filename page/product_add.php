<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// ID Generator Function
function generateNewProductID() {
    global $_db;
    $stm = $_db->query("SELECT ProductID FROM product WHERE ProductID LIKE 'P%' ORDER BY ProductID DESC LIMIT 1");
    $lastID = $stm->fetchColumn();

    if (!$lastID) return 'P0001';

    $num = (int) substr($lastID, 1);
    $num++;
    return 'P' . sprintf('%04d', $num);
}

if (is_post()) {
    // 1. INPUTS (Updated variable names)
    $productID    = generateNewProductID(); 
    $series       = req('series');
    $productName  = req('productName');   
    $price        = req('price');
    $stock        = req('stock');
    $description  = req('description');
    $productImage = get_file('productImage'); 

    // 2. VALIDATION
    if ($productName == '') {
        $_err['productName'] = 'Required';
    } else if (strlen($productName) > 100) {
        $_err['productName'] = 'Maximum 100 characters';
    }

    if ($price == '') {
        $_err['price'] = 'Required';
    } else if (!is_money($price)) {
        $_err['price'] = 'Must be money format';
    }

    if (!$productImage) {
        $_err['productImage'] = 'Required';
    } else if (!str_starts_with($productImage->type, 'image/')) {
        $_err['productImage'] = 'Must be an image';
    } else if ($productImage->size > 1 * 1024 * 1024) {
        $_err['productImage'] = 'Maximum 1MB';
    }

    // 3. DATABASE OPERATION
    if (!$_err) {
        // A. Generate Filename (e.g., "P0001.jpg") using $productImage variable
        $ext = pathinfo($productImage->name, PATHINFO_EXTENSION);
        $filename = "$productID.$ext"; 
        $target = "../public/images/$filename";
        
        if (!is_dir('../public/images')) { mkdir('../public/images', 0777, true); }

        // B. Upload File
        if (move_uploaded_file($productImage->tmp_name, $target)) {
            
            // C. Insert into DB (Saving the FILENAME string)
            $stm = $_db->prepare('
                INSERT INTO product (ProductID, Series, ProductName, Price, Stock, Description, Image)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            // Using $productName and $filename
            $stm->execute([$productID, $series, $productName, $price, $stock, $description, $filename]);

            temp('info', "Product $productID added successfully");
            redirect('productList.php');
        } else {
            $_err['productImage'] = 'Failed to save image file';
        }
    }
}

$_title = 'Insert New Product - N°9 Perfume';
include '../_head.php';
?>

<div class="form-container">
    <h2>Add New Product</h2>

    <form method="post" enctype="multipart/form-data">
        
        <label for="series">Series</label>
        <?= html_select('series', [
            'Fresh' => 'Fresh', 
            'Floral' => 'Floral', 
            'Fruity' => 'Fruity', 
            'Green' => 'Green', 
            'Woody' => 'Woody'
        ]) ?>
        <?= err('series') ?>

        <label for="productName">Product Name</label>
        <?= html_text('productName', 'maxlength="100"') ?>
        <?= err('productName') ?>

        <label for="price">Price (RM)</label>
        <?= html_number('price', 0.01, '', 0.01) ?>
        <?= err('price') ?>

        <label for="stock">Stock Quantity</label>
        <?= html_number('stock', 1, '', 1) ?>
        <?= err('stock') ?>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        <?= err('description') ?>

        <label>Product Image</label>
        <label class="upload">
        <input type="file" name="productImage" accept="image/*">
        <img src="/public/images/photo.jpg" alt="Click or Drag & Drop to upload">
        </label>
        <small>Click or <strong>Drag & Drop</strong> image here. (Saved as <?= generateNewProductID() ?>.jpg)</small>
        <?= err('productImage') ?>

        <button type="submit" class="btn-submit">Add Product</button>
        <a href="productList.php" class="btn-cancel">Cancel</a>
    </form>
</div>

<?php include '../_foot.php'; ?>