<?php
require '../_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$session_id = req('session_id');
$last_id = req('last_id', 0);

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

try {
    // Verify access
    $stm = $_db->prepare("SELECT UserID FROM chat_sessions WHERE SessionID = ?");
    $stm->execute([$session_id]);
    $session = $stm->fetch();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }
    
    // Check permission
    $is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
    if (!$is_admin && $session->UserID != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get new messages
    $stm = $_db->prepare("
        SELECT m.*, u.name as SenderName, u.Profile_Photo
        FROM chat_messages m
        JOIN user u ON m.SenderID = u.userID
        WHERE m.SessionID = ? AND m.MessageID > ?
        ORDER BY m.CreatedAt ASC
    ");
    $stm->execute([$session_id, $last_id]);
    $messages = $stm->fetchAll();
    
    // Mark as read
    if (count($messages) > 0) {
        $_db->prepare("
            UPDATE chat_messages 
            SET IsRead = 1 
            WHERE SessionID = ? AND SenderID != ? AND IsRead = 0
        ")->execute([$session_id, $_SESSION['user_id']]);
        
        // Reset unread count
        $_db->prepare("
            UPDATE chat_unread 
            SET UnreadCount = 0 
            WHERE SessionID = ? AND UserID = ?
        ")->execute([$session_id, $_SESSION['user_id']]);
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}