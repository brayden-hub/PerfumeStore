<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get POST data with proper type handling
    $enabled = $_POST['enabled'] ?? '0';
    $packaging = $_POST['packaging'] ?? 'standard';
    $message = $_POST['message'] ?? '';
    $hidePrice = $_POST['hidePrice'] ?? '0';

    // Convert to proper boolean values
    $enabled = ($enabled === '1' || $enabled === 'true' || $enabled === true);
    $hidePrice = ($hidePrice === '1' || $hidePrice === 'true' || $hidePrice === true);

    // Validate packaging type
    if (!in_array($packaging, ['standard', 'luxury'])) {
        $packaging = 'standard';
    }

    // Sanitize message
    $message = trim($message);
    if (strlen($message) > 150) {
        $message = substr($message, 0, 150);
    }

    // Save to session
    $_SESSION['gift_options'] = [
        'enabled' => $enabled,
        'packaging' => $packaging,
        'message' => $message,
        'hidePrice' => $hidePrice
    ];

    // Log for debugging
    error_log('Gift options saved: ' . json_encode($_SESSION['gift_options']));

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Gift options saved successfully',
        'saved_data' => $_SESSION['gift_options']
    ]);

} catch (Exception $e) {
    // Log the error
    error_log('Save gift options error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>