<?php
require '../_base.php';

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$user_id = req('userID');
$new_status = req('status'); // Expected: 'Activated' or 'Deactivated'

if (is_post() && $user_id && in_array($new_status, ['Activated', 'Deactivated'])) {
    
    // 1. 防止管理员禁用自己的账户
    if ((int)$user_id === (int)($_SESSION['user_id'] ?? 0)) {
         temp('info', 'Cannot change status of your own account.');
         redirect('user.php');
    }

    // 2. 更新数据库状态
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
    // Redirect if accessed via GET or without necessary parameters
    redirect('user.php');
}
?>