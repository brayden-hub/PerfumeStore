<?php
require '../_base.php';

// Only Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$imageID = req('id');

if ($imageID) {
    // 1. Get the filename and ProductID (so we can redirect back)
    $stm = $_db->prepare("SELECT Filename, ProductID FROM product_images WHERE ImageID = ?");
    $stm->execute([$imageID]);
    $img = $stm->fetch();

    if ($img) {
        // 2. Delete the file from the folder
        $file_path = "../public/images/" . $img->Filename;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // 3. Delete from Database
        $stm = $_db->prepare("DELETE FROM product_images WHERE ImageID = ?");
        $stm->execute([$imageID]);
        
        temp('info', 'Image deleted successfully');
        redirect("product_edit.php?productID={$img->ProductID}");
    }
}

// Fallback
redirect('productList.php');
?>