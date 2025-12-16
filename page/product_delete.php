<?php
require '../_base.php';

// 1. Security Check: Only Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// 2. Get Product ID
$productID = req('productID');

if ($productID) {
    // 3. Get Current Status
    $stm = $_db->prepare('SELECT Status, ProductName FROM product WHERE ProductID = ?');
    $stm->execute([$productID]);
    $p = $stm->fetch();

    if ($p) {
        // Determine new status
        // If "Available" (or "'Available'"), make it "Not Available"
        // We use str_contains because the DB sometimes has extra quotes like "'Available'"
        if (!str_contains($p->Status, 'Not')) {
            $newStatus = 'Not Available';
            $msg = "Product '$p->ProductName' is now hidden (Deactivated).";
        } else {
            $newStatus = 'Available';
            $msg = "Product '$p->ProductName' is now visible (Activated).";
        }

        // 4. Update Database
        $stm = $_db->prepare('UPDATE product SET Status = ? WHERE ProductID = ?');
        $stm->execute([$newStatus, $productID]);

        temp('info', $msg);
    }
}

// 5. Redirect back to list
redirect('productList.php');
?>