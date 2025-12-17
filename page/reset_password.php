<?php
require '../_base.php';

// (1) Delete expired tokens
$_db->query('DELETE FROM token WHERE expire < NOW()');



$id = req('id'); // URL 里的参数名可以保持 'id'，但数据库字段名要改

// (2) Is token id valid?
if (!is_exists($id, 'token', 'token_id')) { // 将 id 改为 token_id
    temp('info', 'Invalid or expired token. Please request a new password reset link.');
    redirect('forgot_password.php');
}

$_err = [];

if (is_post()) {
    $password = req('password');
    $confirm  = req('confirm');

    // Validate: password
    if ($password == '') {
        $_err['password'] = 'Required';
    } else {
        $errors = [];
        $len = strlen($password);
        
        if ($len < 8 || $len > 20) {
            $errors[] = '8-20 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) $errors[] = '1 uppercase letter';
        if (!preg_match('/[a-z]/', $password)) $errors[] = '1 lowercase letter';
        if (!preg_match('/[0-9]/', $password)) $errors[] = '1 number';
        if (!preg_match('/[\W_]/', $password)) $errors[] = '1 special character';
        
        if (!empty($errors)) {
            $_err['password'] = 'Password must contain: ' . implode(', ', $errors);
        }
    }

    // Validate: confirm
    if ($confirm == '') {
        $_err['confirm'] = 'Required';
    }
    else if ($confirm != $password) {
        $_err['confirm'] = 'Passwords do not match';
    }

    // DB operation
    if (!$_err) {
        // Get user info from token
        $stm = $_db->prepare('SELECT userID FROM token WHERE token_id = ?'); // 将 id 改为 token_id
        $stm->execute([$id]);
        $token = $stm->fetch();
        
        // Update user password & delete token
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stm = $_db->prepare('
            UPDATE user
            SET password = ?
            WHERE userID = ?;
        ');
        $stm->execute([$hashed, $token->userID]);
        
        // Delete the used token
        $stm = $_db->prepare('DELETE FROM token WHERE token_id = ?'); // 将 id 改为 token_id
        $stm->execute([$id]);

        temp('info', 'Password successfully reset!');
        redirect('login.php');
    }
}

$_title = 'Reset Password - Nº9 Perfume';
include '../_head.php';
?>

<div style="max-width: 450px; margin: 100px auto; padding: 30px; background: #f9f9f9; border-radius: 10px;">
    <h2 style="text-align: center; margin-bottom: 10px; color: #111;">Create New Password</h2>
    <p style="text-align: center; color: #666; margin-bottom: 30px; font-size: 14px;">
        Please enter your new password below.
    </p>

    <form method="post">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500;">New Password</label>
            <div style="display: flex; align-items: center; gap: 5px;">
                <input type="password" 
                       id="reset_password" 
                       name="password"
                       placeholder="Enter new password"
                       autofocus
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                <button type="button" 
                        class="show-pass" 
                        data-target="#reset_password" 
                        style="cursor: pointer; padding: 12px; background: #f0f0f0; border: 1px solid #ddd; 
                               border-radius: 5px; font-size: 1.2rem;">
                    👁️
                </button>
            </div>
            <?= err('password') ?>
            <small style="display: block; margin-top: 8px; color: #999; font-size: 13px;">
                Must be 8-20 characters with uppercase, lowercase, number and special character
            </small>
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500;">Confirm Password</label>
            <div style="display: flex; align-items: center; gap: 5px;">
                <input type="password" 
                       id="reset_confirm" 
                       name="confirm"
                       placeholder="Confirm new password"
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                <button type="button" 
                        class="show-pass" 
                        data-target="#reset_confirm" 
                        style="cursor: pointer; padding: 12px; background: #f0f0f0; border: 1px solid #ddd; 
                               border-radius: 5px; font-size: 1.2rem;">
                    👁️
                </button>
            </div>
            <?= err('confirm') ?>
        </div>

        <button type="submit" 
                style="width: 100%; padding: 14px; background: #000; color: #D4AF37; border: 2px solid #D4AF37; 
                       border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; 
                       letter-spacing: 1px;">
            RESET PASSWORD
        </button>
    </form>
</div>

<?php include '../_foot.php'; ?>