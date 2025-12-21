<?php
require '../_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$session_id = req('session_id');
$message = trim(req('message'));
$sender_type = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin' ? 'admin' : 'customer';

if (!$message || !$session_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Prevent XSS
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

try {
    // Verify session exists and user has access
    $stm = $_db->prepare("SELECT UserID FROM chat_sessions WHERE SessionID = ?");
    $stm->execute([$session_id]);
    $session = $stm->fetch();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }
    
    // Check permission
    if ($sender_type === 'customer' && $session->UserID != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Insert message
    $stm = $_db->prepare("
        INSERT INTO chat_messages (SessionID, SenderID, SenderType, Message) 
        VALUES (?, ?, ?, ?)
    ");
    $stm->execute([$session_id, $_SESSION['user_id'], $sender_type, $message]);
    
    // Update session timestamp
    $_db->prepare("UPDATE chat_sessions SET LastMessageAt = NOW() WHERE SessionID = ?")->execute([$session_id]);
    
    // Update unread count for recipient
    if ($sender_type === 'customer') {
        // Find assigned admin or all admins
        $stm = $_db->prepare("SELECT AssignedAdminID FROM chat_sessions WHERE SessionID = ?");
        $stm->execute([$session_id]);
        $admin_row = $stm->fetch();
        
        if ($admin_row && $admin_row->AssignedAdminID) {
            $_db->prepare("
                INSERT INTO chat_unread (SessionID, UserID, UnreadCount) 
                VALUES (?, ?, 1) 
                ON DUPLICATE KEY UPDATE UnreadCount = UnreadCount + 1
            ")->execute([$session_id, $admin_row->AssignedAdminID]);
        }
    } else {
        // Admin sent message - update customer unread
        $_db->prepare("
            INSERT INTO chat_unread (SessionID, UserID, UnreadCount) 
            VALUES (?, ?, 1) 
            ON DUPLICATE KEY UPDATE UnreadCount = UnreadCount + 1
        ")->execute([$session_id, $session->UserID]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
