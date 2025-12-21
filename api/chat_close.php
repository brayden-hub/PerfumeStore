<?php
require '../_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$session_id = req('session_id');

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

try {
    $_db->prepare("UPDATE chat_sessions SET Status = 'closed' WHERE SessionID = ?")->execute([$session_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}