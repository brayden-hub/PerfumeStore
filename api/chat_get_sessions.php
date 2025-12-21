<?php
require '../_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

try {
    $stm = $_db->query("
        SELECT cs.*, u.name as CustomerName, u.email as CustomerEmail, u.Profile_Photo,
               (SELECT COUNT(*) 
                FROM chat_messages 
                WHERE SessionID = cs.SessionID 
                  AND IsRead = 0 
                  AND SenderType = 'customer') as UnreadCount,
               (SELECT Message 
                FROM chat_messages 
                WHERE SessionID = cs.SessionID 
                ORDER BY CreatedAt DESC 
                LIMIT 1) as LastMessage
        FROM chat_sessions cs
        JOIN user u ON cs.UserID = u.userID
        WHERE cs.Status = 'active'
        ORDER BY cs.LastMessageAt DESC
    ");
    
    echo json_encode(['success' => true, 'sessions' => $stm->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}