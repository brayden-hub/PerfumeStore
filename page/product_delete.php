<?php
require '../_base.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

if (is_post()) {
    $productID = req('productID'); 
    $stm = $_db->prepare('SELECT Image FROM product WHERE ProductID = ?');
    $stm->execute([$productID]);
    $image = $stm->fetchColumn();
    unlink("../public/images/$image"); // delete the file

    $stm = $_db->prepare('DELETE FROM product WHERE ProductID = ?');
    $stm->execute([$productID]);

    temp('info', 'Record deleted successfully');
}

redirect('productList.php');