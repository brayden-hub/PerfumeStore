<?php
require '../_base.php';


if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$user_id = req('userID');
$new_status = req('status'); 

if (is_post() && $user_id && in_array($new_status, ['Activated', 'Deactivated'])) {
    
    
    if ((int)$user_id === (int)($_SESSION['user_id'] ?? 0)) {
         temp('info', 'Cannot change status of your own account.');
         redirect('user.php');
    }

    
    $stm = $_db->prepare("UPDATE user SET status = ? WHERE userID = ? AND role = 'Member'");
    $stm->execute([$new_status, $user_id]);

    if ($stm->rowCount()) {
        $message = "Member (ID: " . $user_id . ") status changed to " . htmlspecialchars($new_status) . " successfully.";
        temp('info', $message);
    } else {
        temp('info', 'Status change failed or member not found.');
    }
    
    redirect('user.php');

} else {
    
    redirect('user.php');
}
?>