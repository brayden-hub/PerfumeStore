<?php
require '_base.php';

$token_id = req('token');

// (1) Check if token is valid and unconfirmed
$stm = $_db->prepare('
    SELECT email FROM subscriber
    WHERE token_id = ? AND status = "unconfirmed"
');
$stm->execute([$token_id]);
$email = $stm->fetchColumn();

if (!$email) {
    temp('info', 'Invalid or expired confirmation link.');
    redirect('/index.php');
}

// (2) Update status to subscribed
$stm = $_db->prepare('
    UPDATE subscriber
    SET status = "subscribed", subscribed_at = NOW()
    WHERE token_id = ?
');
$stm->execute([$token_id]);

// --- Success Actions ---

// Optional: Send a final Welcome Email (as discussed)
try {
    $m = get_mail();
    $m->addAddress($email); 
    
    $m->isHTML(true);
    $m->Subject = 'Welcome! You are now subscribed to Nº9 Perfume';
    $m->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2>Welcome to Nº9 Perfume!</h2>
            <p>Thank you for confirming your subscription. You are all set to receive exclusive updates, promotions, and news from us.</p>
            <p>Start shopping our bestsellers now!</p>
            <a href='http://" . $_SERVER['HTTP_HOST'] . "/page/product.php'>Shop Now</a>
        </div>
    ";
    $m->send();
} catch (Exception $e) {
    error_log("Welcome email failed for $email: " . $e->getMessage());
}

// Final Success Message
temp('info', 'Subscription successfully confirmed! Welcome aboard!');
redirect('/index.php');