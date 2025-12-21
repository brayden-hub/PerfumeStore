<?php
require '../_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'unread' => 0]);
    exit;
}

try {
    $is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
    
    if ($is_admin) {
        // Total unread from all customers
        $stm = $_db->query("
            SELECT COALESCE(SUM(
                (SELECT COUNT(*) 
                 FROM chat_messages 
                 WHERE SessionID = cs.SessionID 
                   AND IsRead = 0 
                   AND SenderType = 'customer')
            ), 0) as total
            FROM chat_sessions cs
            WHERE cs.Status = 'active'
        ");
    } else {
        // Customer's unread
        $stm = $_db->prepare("
            SELECT COALESCE(SUM(UnreadCount), 0) as total
            FROM chat_unread
            WHERE UserID = ?
        ");
        $stm->execute([$_SESSION['user_id']]);
    }
    
    $result = $stm->fetch();
    echo json_encode(['success' => true, 'unread' => (int)$result->total]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'unread' => 0]);
}