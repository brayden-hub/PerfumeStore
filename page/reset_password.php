<?php
require '../_base.php';


$_db->query('DELETE FROM token WHERE expire < NOW()');



$id = req('id'); 


if (!is_exists($id, 'token', 'token_id')) { 
    temp('info', 'Invalid or expired token. Please request a new password reset link.');
    redirect('forgot_password.php');
}

$_err = [];

if (is_post()) {
    $password = req('password');
    $confirm  = req('confirm');

    
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

    
    if ($confirm == '') {
        $_err['confirm'] = 'Required';
    }
    else if ($confirm != $password) {
        $_err['confirm'] = 'Passwords do not match';
    }

    
    if (!$_err) {
        
        $stm = $_db->prepare('SELECT userID FROM token WHERE token_id = ?'); 
        $stm->execute([$id]);
        $token = $stm->fetch();
        
        
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stm = $_db->prepare('
            UPDATE user
            SET password = ?
            WHERE userID = ?;
        ');
        $stm->execute([$hashed, $token->userID]);
        
        
        $stm = $_db->prepare('DELETE FROM token WHERE token_id = ?'); 
        $stm->execute([$id]);

        temp('info', 'Password successfully reset!');
        redirect('login.php');
    }
}

$_title = 'Reset Password - Nº9 Perfume';
include '../_head.php';
?>

<div class="auth-container">
    <h2 class="auth-title">Create New Password</h2>
    <p class="auth-subtitle">
        Please enter your new password below.
    </p>

    <form method="post">
        <div class="auth-input-group">
            <label class="auth-label">New Password</label>
            <div class="auth-pass-wrapper">
                <input type="password" 
                       id="reset_password" 
                       name="password"
                       class="auth-input"
                       placeholder="Enter new password"
                       autofocus>
                <button type="button" 
                        class="show-pass auth-btn-show" 
                        data-target="#reset_password">
                    👁️
                </button>
            </div>
            <?= err('password') ?>
            <small class="auth-help-text">
                Must be 8-20 characters with uppercase, lowercase, number and special character
            </small>
        </div>

        <div class="auth-input-group" style="margin-bottom: 25px;">
            <label class="auth-label">Confirm Password</label>
            <div class="auth-pass-wrapper">
                <input type="password" 
                       id="reset_confirm" 
                       name="confirm"
                       class="auth-input"
                       placeholder="Confirm new password">
                <button type="button" 
                        class="show-pass auth-btn-show" 
                        data-target="#reset_confirm">
                    👁️
                </button>
            </div>
            <?= err('confirm') ?>
        </div>

        <button type="submit" class="auth-btn-main">
            RESET PASSWORD
        </button>
    </form>
</div>

<?php include '../_foot.php'; ?>