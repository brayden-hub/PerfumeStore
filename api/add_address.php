<?php
require '../_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if user already has 4 addresses
$stmt = $_db->prepare("SELECT COUNT(*) FROM user_address WHERE UserID = ?");
$stmt->execute([$user_id]);
$count = $stmt->fetchColumn();

if ($count >= 4) {
    echo json_encode(['success' => false, 'message' => 'Maximum 4 addresses allowed']);
    exit;
}

$label = post('label');
$recipient_name = post('recipient_name');
$phone = post('phone');
$address1 = post('address1');
$address2 = post('address2', '');
$city = post('city');
$state = post('state');
$postal_code = post('postal_code');
$is_default = post('is_default', 0);

if (!$label || !$recipient_name || !$phone || !$address1 || !$city || !$state || !$postal_code) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

try {
    $_db->beginTransaction();
    
    // If set as default, unset other defaults
    if ($is_default) {
        $stmt = $_db->prepare("UPDATE user_address SET IsDefault = 0 WHERE UserID = ?");
        $stmt->execute([$user_id]);
    }
    
    // Insert new address
    $stmt = $_db->prepare("
        INSERT INTO user_address 
        (UserID, AddressLabel, RecipientName, PhoneNumber, AddressLine1, AddressLine2, City, State, PostalCode, IsDefault) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $label,
        $recipient_name,
        $phone,
        $address1,
        $address2,
        $city,
        $state,
        $postal_code,
        $is_default ? 1 : 0
    ]);
    
    $_db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Address added successfully'
    ]);
    
} catch (Exception $e) {
    $_db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>