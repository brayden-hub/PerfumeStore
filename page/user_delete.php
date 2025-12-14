<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$user_id = req('userID');

if (is_post() && $user_id) {
    try {
        // Prevent accidental deletion of the current admin account
        if ((int)$user_id === (int)($_SESSION['user_id'] ?? 0)) {
             temp('info', 'Cannot delete your own account while logged in.');
             redirect('user.php');
        }

        // 1. Delete dependent records (Cart entries)
        $_db->prepare("DELETE FROM cart WHERE UserID = ?")->execute([$user_id]);
        
        // 2. Delete the user (only if they are a Member)
        $stm = $_db->prepare("DELETE FROM user WHERE userID = ? AND role = 'Member'");
        $stm->execute([$user_id]);

        if ($stm->rowCount()) {
            temp('info', 'Member (ID: ' . $user_id . ') deleted successfully.');
        } else {
            temp('info', 'Member not found or deletion failed.');
        }
        
    } catch (PDOException $e) {
        // Handle database errors (e.g., still related orders)
        error_log("User deletion error: " . $e->getMessage());
        temp('info', 'Deletion failed due to a database error. Check if related records (like orders) still exist.');
    }
    redirect('user.php');
} else {
    // Redirect if accessed via GET or without ID
    redirect('user.php');
}