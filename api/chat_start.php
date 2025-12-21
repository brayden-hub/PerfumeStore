<?php
require '../_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Check for existing active session
    $stm = $_db->prepare("
        SELECT SessionID 
        FROM chat_sessions 
        WHERE UserID = ? AND Status = 'active' 
        ORDER BY CreatedAt DESC 
        LIMIT 1
    ");
    $stm->execute([$user_id]);
    $session = $stm->fetch();

    if ($session) {
        echo json_encode(['success' => true, 'session_id' => $session->SessionID]);
    } else {
        // Create new session
        $stm = $_db->prepare("INSERT INTO chat_sessions (UserID) VALUES (?)");
        $stm->execute([$user_id]);
        $session_id = $_db->lastInsertId();
        
        // Send welcome message from system
        $welcome_msg = "Hello! Welcome to Nº9 Perfume Customer Support. How can we help you today?";
        $stm = $_db->prepare("
            INSERT INTO chat_messages (SessionID, SenderID, SenderType, Message, IsRead) 
            VALUES (?, 2, 'admin', ?, 1)
        ");
        $stm->execute([$session_id, $welcome_msg]);
        
        echo json_encode(['success' => true, 'session_id' => $session_id]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}