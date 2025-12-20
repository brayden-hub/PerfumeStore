<?php
require '_base.php';


$_err = [];
$email = ''; // Initialize

if (is_post()) {
    $email = req('email');

    // 1. Validation
    if ($email == '') {
        $_err['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email format.';
    }

    // Check if already subscribed
    if (!$_err) {
        $stm = $_db->prepare("SELECT status FROM subscriber WHERE email = ?");
        $stm->execute([$email]);
        $existing_status = $stm->fetchColumn();

        if ($existing_status === 'subscribed') {
            temp('info', 'This email address is already subscribed!');
            redirect('/index.php'); // Redirect to homepage or success page
        }
    }

    // Generate and Send Token (If valid)
    if (!$_err) {
        
        // Generate token id (same method as forgot_password.php)
        $id = sha1(uniqid() . rand());
        
        // Store or Update Subscriber Record
        $status = 'unconfirmed';
        
        // Use REPLACE INTO to insert a new record or replace an unconfirmed existing one
        $stm = $_db->prepare('
            REPLACE INTO subscriber (email, token_id, status, created_at)
            VALUES (?, ?, ?, NOW());
        ');
        $stm->execute([$email, $id, $status]);

        // Generate Confirmation URL
        $url = "http://" . $_SERVER['HTTP_HOST'] . "/confirm_subscription.php?token=$id";

        // Send Email
        try {
            $m = get_mail();
            $m->addAddress($email); // No user name available here
            
            $m->isHTML(true);
            $m->Subject = 'Confirm Your Subscription to Nº9 Perfume';
            $m->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;'>
                    <div style='background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                        <h2 style='text-align: center; color: #111;'>Confirm Your Subscription</h2>
                        
                        <p style='color: #666; font-size: 16px; line-height: 1.6;'>
                            Thank you for subscribing to the N°9 Perfume newsletter!
                            Please click the button below to confirm your subscription and start receiving our updates:
                        </p>
                        
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$url}' 
                               style='display: inline-block; padding: 15px 40px; background: #000; color: #D4AF37; 
                                      text-decoration: none; border-radius: 5px; font-weight: bold; 
                                      letter-spacing: 1px; border: 2px solid #D4AF37;'>
                                CONFIRM SUBSCRIPTION
                            </a>
                        </div>
                        
                        <p style='color: #999; font-size: 14px; line-height: 1.6;'>
                            If you did not sign up for this newsletter, please ignore this email.
                        </p>
                    </div>
                </div>
            ";
            $m->send();

            // --- 6. Redirect ---
            temp('info', 'Subscription confirmation link has been sent to your email. Please check your inbox!');
            redirect('/index.php');
            
        } catch (Exception $e) {
            // Log error, but give generic message to user
            error_log("Subscription email failed for $email: " . $e->getMessage());
            $_err['general'] = 'Failed to send confirmation email. Please try again later.';
        }
    }
}

// If accessed directly or validation failed (should not happen if form submits to itself, but good practice)
$_title = 'Subscribe';
include '_head.php';
?>

<div style="max-width: 450px; margin: 100px auto; padding: 30px; background: #f9f9f9; border-radius: 10px;">
    <h2>Subscribe to Newsletter</h2>
    <form method="post">
        <div>
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            <?= err('email') ?>
            <?php if (isset($_err['general'])) echo '<small style="color:red;">'.$_err['general'].'</small>' ?>
        </div>
        <button type="submit">Subscribe</button>
    </form>
</div>

<?php include '_foot.php'; ?>