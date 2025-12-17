<?php
require '../_base.php';

$_err = [];

if (is_post()) {
    $email = req('email');

    

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    }
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email';
    }
    // 1. 先检查邮箱是否存在
    else if (!is_exists($email, 'user', 'email')) {
        $_err['email'] = 'Email not registered';
    }
    else {
        // 2. 核心修复：检查该邮箱对应的账户状态是否为 Activated
        $stm = $_db->prepare('SELECT status FROM user WHERE email = ?');
        $stm->execute([$email]);
        $status = $stm->fetchColumn();

        if ($status !== 'Activated') {
            // 如果账户被禁用，不允许发送重置链接
            $_err['email'] = 'Your account has been deactivated. Please contact support.';
        }
    }

    // Send reset token (if valid)
    if (!$_err) {
        // (1) Select user
        $stm = $_db->prepare('SELECT * FROM user WHERE email = ?');
        $stm->execute([$email]);
        $u = $stm->fetch();

        // (2) Generate token id
        
        $token_id = sha1(uniqid() . rand()); // 变量名建议也改掉，方便理解

        // (3) Delete old and insert new token
        $stm = $_db->prepare('
            DELETE FROM token WHERE userID = ?;
            
            INSERT INTO token (token_id, expire, userID) 
            VALUES (?, ADDTIME(NOW(), "00:05:00"), ?);
        '); // 将 id 改为 token_id
        $stm->execute([$u->userID, $token_id, $u->userID]);

        // (4) Generate token url
        $url = "http://" . $_SERVER['HTTP_HOST'] . "/page/reset_password.php?id=$token_id";

        // (5) Send email
        $m = get_mail();
        $m->addAddress($u->email, $u->name);
        
        // --- START of fix in forgot_password.php ---
        // Get the photo filename, using 'default1.jpg' as the fallback if the DB column is empty.
        $photoFile = $u->Profile_Photo ?: 'default1.jpg';

        // Add embedded image if user has profile photo
        $photoPath = "../images/avatars/" . $photoFile;

        // The file_exists check will now look for a real file (e.g., ../images/avatars/default1.jpg)
        if (file_exists($photoPath)) {
            // The photoPath now includes the filename, resolving the error.
            $m->addEmbeddedImage($photoPath, 'photo'); 
            $photoTag = "<img src='cid:photo' style='width: 150px; height: 150px; border-radius: 50%; border: 2px solid #D4AF37; object-fit: cover;'>";
        } else {
            $photoTag = "";
        }
        // --- END of fix ---
                
        
        $m->isHTML(true);
        $m->Subject = 'Reset Your Password - Nº9 Perfume';
        $m->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;'>
                <div style='background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        {$photoTag}
                        <h2 style='color: #111; margin-top: 15px;'>Password Reset Request</h2>
                    </div>
                    
                    <p style='color: #666; font-size: 16px; line-height: 1.6;'>Dear <strong>{$u->name}</strong>,</p>
                    
                    <p style='color: #666; font-size: 16px; line-height: 1.6;'>
                        We received a request to reset your password for your Nº9 Perfume account. 
                        Click the button below to create a new password:
                    </p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$url}' 
                           style='display: inline-block; padding: 15px 40px; background: #000; color: #D4AF37; 
                                  text-decoration: none; border-radius: 5px; font-weight: bold; 
                                  letter-spacing: 1px; border: 2px solid #D4AF37;'>
                            RESET PASSWORD
                        </a>
                    </div>
                    
                    <p style='color: #999; font-size: 14px; line-height: 1.6;'>
                        Or copy and paste this link into your browser:<br>
                        <a href='{$url}' style='color: #D4AF37; word-break: break-all;'>{$url}</a>
                    </p>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                        <p style='color: #dc3545; font-size: 14px; font-weight: bold;'>
                            ⚠️ This link will expire in 5 minutes.
                        </p>
                        <p style='color: #999; font-size: 13px; margin-top: 10px;'>
                            If you didn't request this password reset, please ignore this email. 
                            Your password will remain unchanged.
                        </p>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px; color: #999; font-size: 12px;'>
                        <p>Best regards,<br><strong style='color: #111;'>Nº9 Perfume Team</strong></p>
                    </div>
                </div>
            </div>
        ";
        $m->send();

        temp('info', 'Password reset link has been sent to your email. Please check your inbox.');
        redirect('login.php');
    }
}

$_title = 'Forgot Password - Nº9 Perfume';
include '../_head.php';
?>

<div style="max-width: 450px; margin: 100px auto; padding: 30px; background: #f9f9f9; border-radius: 10px;">
    <h2 style="text-align: center; margin-bottom: 10px; color: #111;">Forgot Password?</h2>
    <p style="text-align: center; color: #666; margin-bottom: 30px; font-size: 14px;">
        Enter your email address and we'll send you a link to reset your password.
    </p>

    <form method="post">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500;">Email Address</label>
            <input type="email" 
                   name="email" 
                   placeholder="Enter your email" 
                   value="<?= encode(req('email')) ?>" 
                   autofocus
                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
            <?= err('email') ?>
        </div>

        <button type="submit" 
                style="width: 100%; padding: 14px; background: #000; color: #D4AF37; border: 2px solid #D4AF37; 
                       border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; 
                       letter-spacing: 1px; margin-bottom: 15px;">
            SEND RESET LINK
        </button>

        <div style="text-align: center;">
            <a href="login.php" style="color: #666; text-decoration: none; font-size: 14px;">
                ← Back to Login
            </a>
        </div>
    </form>
</div>

<?php include '../_foot.php'; ?>