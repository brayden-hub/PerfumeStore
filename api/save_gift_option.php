<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Debug: Log what we receive
error_log('POST data: ' . print_r($_POST, true));

// Get gift options from POST - handle both string and boolean values
$enabled = ($_POST['enabled'] ?? 'false') === 'true' || ($_POST['enabled'] ?? false) === true;
$packaging = $_POST['packaging'] ?? 'standard';
$message = $_POST['message'] ?? '';
$hidePrice = ($_POST['hidePrice'] ?? 'false') === 'true' || ($_POST['hidePrice'] ?? false) === true;

// Save to session
$_SESSION['gift_options'] = [
    'enabled' => $enabled,
    'packaging' => $packaging,
    'message' => $message,
    'hidePrice' => $hidePrice
];

// Debug: Log what we saved
error_log('Saved to session: ' . print_r($_SESSION['gift_options'], true));

echo json_encode([
    'success' => true,
    'message' => 'Gift options saved',
    'data' => $_SESSION['gift_options']
]);
?>