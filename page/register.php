<?php
require '../_base.php';
if (isset($_SESSION['user_id'])) {
    // 如果已经登录，直接重定向到首页或个人中心
    temp('info', 'You are already logged in.');
    redirect('profile.php'); 
    exit();
}

// === 请将 YOUR_SECRET_KEY 替换为你的实际 Secret Key ===
const RECAPTCHA_SECRET_KEY = '6LdrnSwsAAAAALWa_WN9Xl8i0SVpFJYwsXWbKUHK';
// === 请将 YOUR_SITE_KEY 替换为你的实际 Site Key ===
const RECAPTCHA_SITE_KEY   = '6LdrnSwsAAAAAMPFof5OMJw0bGbsL_OBjdktbhJv';


$_err = [];
$step = (int)req('step', 1);

// 处理 Back 按钮（直接跳转，不验证）
if (is_post() && isset($_POST['back'])) {
    $step = (int)$_POST['back'];
}

// ===== Step 1: 验证 Name =====
elseif (is_post() && $step == 2) {
    $name = req('name');
    
    if ($name == '') {
        $_err['name'] = 'Required';
        $step = 1; // 停留在 Step 1
    } else {
        $_SESSION['reg_name'] = $name;
    }
}

// ===== Step 2: 验证 Email =====
elseif (is_post() && $step == 3 && empty($_err)) {
    $email = req('email');
    
    if ($email == '') {
        $_err['email'] = 'Required';
        $step = 2;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email';
        $step = 2;
    } elseif (is_exists($email, 'user', 'email')) {
        $_err['email'] = 'Email already registered';
        $step = 2;
    } else {
        $_SESSION['reg_email'] = $email;
    }
}

// ===== Step 3: 验证 Password =====
elseif (is_post() && $step == 4 && empty($_err)) {
    $password = req('password');
    $confirm  = req('confirm_password');
    
    if ($password == '') {
        $_err['password'] = 'Required';
        $step = 3;
    } else {
        $errors = [];
        $len = strlen($password);
        
        if ($len < 8 || $len > 20) {
            $errors[] = '8-20 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) $errors[] = '1 uppercase';
        if (!preg_match('/[a-z]/', $password)) $errors[] = '1 lowercase';
        if (!preg_match('/[0-9]/', $password)) $errors[] = '1 number';
        if (!preg_match('/[\W_]/', $password)) $errors[] = '1 special character';
        
        if (!empty($errors)) {
            $_err['password'] = 'Password must contain: ' . implode(', ', $errors);
            $step = 3;
        } elseif ($password !== $confirm) {
            $_err['confirm_password'] = 'Passwords do not match';
            $step = 3;
        }
        // 验证通过，不保存密码，直接进入 Step 4
    }
}

// ===== Step 4: 验证 Phone + reCAPTCHA + 最终注册 =====
elseif (is_post() && $step == 5 && empty($_err)) {
    $phone    = req('phone_number');
    $password = req('password'); // 从表单重新读取密码
    
    // register.php - 后端同步验证规则
    if ($phone == '') {
        $_err['phone_number'] = 'Required';
        $step = 4;
    } else {
        $phone_digits = preg_replace('/[^\d]/', '', trim($phone)); // 获取纯数字
        $phone_len = strlen($phone_digits);

        if (str_starts_with($phone_digits, '60')) {
            // 60 开头：11-12位
            if ($phone_len < 11 || $phone_len > 12) {
                $_err['phone_number'] = '60 format must be 11-12 digits.';
                $step = 4;
            }
        } 
        elseif (str_starts_with($phone_digits, '01')) {
            // 01 开头：10-11位
            if ($phone_len < 10 || $phone_len > 11) {
                $_err['phone_number'] = '01 format must be 10-11 digits.';
                $step = 4;
            }
            // 检查格式：必须在第3位后面有一个破折号
            elseif (!preg_match('/^01\d-\d{7,8}$/', $phone)) {
                 $_err['phone_number'] = 'Format must be 01x-xxxxxxx.';
                 $step = 4;
            }
        }
        else {
            $_err['phone_number'] = 'Must start with 60 or 01.';
            $step = 4;
        }
    }
    // --- (A) 手机号码验证结束 ---
    
    // --- (B) reCAPTCHA 验证 ---
    // 只有在手机号验证通过时才进行 reCAPTCHA 验证
    if ($step == 5) {
        $recaptcha_response = post('g-recaptcha-response');
        $verify_url = "https://www.google.com/recaptcha/api/siteverify";
        
        if (!$recaptcha_response) {
            $_err['recaptcha'] = 'Please check the "I\'m not a robot" box.';
            $step = 4;
        } else {
            // 使用 cURL 进行安全验证
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $verify_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret' => RECAPTCHA_SECRET_KEY,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response);
            
            // 检查 $data 是否存在以及 success 字段是否为 true
            if (!$data || !($data->success ?? false)) {
                $_err['recaptcha'] = 'CAPTCHA verification failed. Try again.';
                $step = 4;
            }
        }
    }
    // --- (B) reCAPTCHA 验证结束 ---


    // --- (C) 最终注册 ---
    if ($step == 5) {
        // CAPTCHA 和所有验证通过，执行注册
        $name  = $_SESSION['reg_name'];
        $email = $_SESSION['reg_email'];
        
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $defaults = ['default1.jpg','default2.jpg','default3.jpg','default4.jpg','default5.jpg','default6.jpg'];
        $avatar = $defaults[array_rand($defaults)];
        
        $stm = $_db->prepare("INSERT INTO user (email, name, phone_number, password, role, Profile_Photo, status) VALUES (?, ?, ?, ?, 'Member', ?, 'Pending')");
        $stm->execute([$email, $name, $phone_digits, $hashed, $avatar]);

        // 获取刚插入的用户 ID
        $userID = $_db->lastInsertId();

        // 🎁 自動分配歡迎優惠券給新用戶
        try {
            $stm_voucher = $_db->prepare("
                INSERT INTO user_voucher (UserID, VoucherID, IsUsed)
                SELECT ?, VoucherID, 0
                FROM voucher
                WHERE Status = 'active'
            ");
            $stm_voucher->execute([$userID]);
        } catch (Exception $e) {
            error_log("Failed to assign vouchers to new user (ID: $userID): " . $e->getMessage());
        }

        // 2. 生成验证/登录 Token
        $token_id = sha1(uniqid() . rand());
        $stm = $_db->prepare('INSERT INTO token (token_id, expire, userID) VALUES (?, ADDTIME(NOW(), "00:10:00"), ?)');
        $stm->execute([$token_id, $userID]);

        // 3. 生成自动登录链接
        $url = "http://" . $_SERVER['HTTP_HOST'] . "/page/verify_register.php?id=$token_id";

        // 4. 发送邮件
        $m = get_mail();
        $m->addAddress($email, $name);
        $m->isHTML(true);
        $m->Subject = 'Verify Your Account - Nº9 Perfume';
        $m->Body = "<h1>Welcome $name!</h1><p>Please click below to verify and log in:</p><a href='$url'>VERIFY & LOGIN NOW</a>";
        $m->send();

        // 5. 跳转到一个提示页面，告诉用户去查收邮件
        unset($_SESSION['reg_name'], $_SESSION['reg_email']);
        temp('info', 'Registration successful! Please check your email to verify and access your account.');
        redirect('login.php');
    }
}


// 从 session 读取已保存的数据
$name     = $_SESSION['reg_name']     ?? '';
$email    = $_SESSION['reg_email']    ?? '';
$phone    = $_SESSION['reg_phone']    ?? req('phone_number', '');
// 密码不从 session 读取（让用户每次都重新输入）

$_title = 'Register - N°9 Perfume';
include '../_head.php';
?>

<script src="https://www.google.com/recaptcha/api.js" async defer></script> 
<script src="https://www.google.com/recaptcha/api.js" async defer></script> 

<div class="register-container">
    <h2 class="register-title">Create your account</h2>
    <p class="register-subtitle">Step <?= $step ?> of 4</p>

    <div class="stepper-wrapper">
        <?php for($i=1; $i<=4; $i++): ?>
        <div class="step-item">
            <div class="step-circle" style="background:<?= $step>=$i?'#000':'#e0e0e0' ?>;">
                <?= $step > $i ? '✓' : $i ?>
            </div>
            <div class="step-label" style="color:<?= $step>=$i?'#000':'#999' ?>;">
                <?= ['Name','Email','Password','Phone'][$i-1] ?>
            </div>
        </div>
        <?php endfor; ?>
        <div class="stepper-bg-line"></div>
        <div class="stepper-progress-line" style="width:<?= ($step-1)/3*100 ?>%;"></div>
    </div>

    <form method="post" novalidate>
        <?php if ($step == 1): ?>
            <div>
                <input type="text" name="name" class="reg-input" value="<?= htmlspecialchars($name) ?>" placeholder="Full Name" autofocus>
                <?= err('name') ?>
            </div>
            <button type="submit" name="step" value="2" class="btn-primary" style="margin-top:30px;">Next</button>
        <?php endif; ?>

        <?php if ($step == 2): ?>
            <div>
                <input type="email" name="email" class="reg-input" value="<?= htmlspecialchars($email) ?>" placeholder="Email address" autofocus>
                <?= err('email') ?>
            </div>
            <div style="display:flex; flex-direction:row-reverse; gap:12px; margin-top:30px;">
                <button type="submit" name="step" value="3" class="btn-next">Next</button>
                <button type="submit" name="back" value="1" class="btn-secondary">Back</button>
            </div>
        <?php endif; ?>

        <?php if ($step == 3): ?>
            <div>
                <div class="password-group">
                    <input type="password" id="reg_password" name="password" class="reg-input" placeholder="Create password" autofocus style="margin:0;">
                    <button type="button" class="show-pass btn-secondary" data-target="#reg_password" style="padding:10px; flex:none;">👁︎</button>
                </div>
                
                <div class="password-group">
                    <input type="password" id="reg_confirm_password" name="confirm_password" class="reg-input" placeholder="Confirm password" style="margin:0;">
                    <button type="button" class="show-pass btn-secondary" data-target="#reg_confirm_password" style="padding:10px; flex:none;">👁︎</button>
                </div>
                <?= err('password') ?><?= err('confirm_password') ?>
            </div>
            <div style="display:flex; flex-direction:row-reverse; gap:12px; margin-top:30px;">
                <button type="submit" name="step" value="4" class="btn-next">Next</button>
                <button type="submit" name="back" value="2" class="btn-secondary">Back</button>
            </div>
        <?php endif; ?>

        <?php if ($step == 4): ?>
            <div>
                <input type="hidden" name="password" value="<?= htmlspecialchars(req('password', '')) ?>">
                <input type="tel" id="reg_phone_number" name="phone_number" class="reg-input" value="<?= htmlspecialchars($phone) ?>" placeholder="Phone number" autofocus>
                <?= err('phone_number') ?>
            </div>
            
            <div style="margin: 15px 0;">
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div> 
                <?= err('recaptcha') ?> 
            </div>
            <p class="terms-text">
                By clicking Create Account, you agree to our <strong>Terms</strong> and <strong>Privacy Policy</strong>.
            </p>
            <button type="submit" name="step" value="5" class="btn-primary" style="margin-bottom:12px;">Create Account</button>
            <button type="submit" name="back" value="3" class="btn-primary" style="background:transparent; border:1px solid #aaa; color:#000;">Back</button>
        <?php endif; ?>
    </form>
</div>

<?php include '../_foot.php'; ?>