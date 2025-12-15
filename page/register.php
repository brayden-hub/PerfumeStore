<?php
require '../_base.php';

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
    
    // --- (A) 手机号码验证开始 ---
    if ($phone == '') {
        $_err['phone_number'] = 'Required';
        $step = 4;
    } else {
        $phone_digits = preg_replace('/[^\d]/', '', trim($phone)); 

        if (strlen($phone_digits) != 11) {
            $_err['phone_number'] = 'Phone number must be exactly 11 digits.';
            $step = 4;
        } 
        elseif (!preg_match('/^(60|01)\d{9}$/', $phone_digits)) {
            $_err['phone_number'] = 'Invalid starting digits. Must start with 60 or 01.';
            $step = 4;
        }
        elseif ($phone_digits[0] == '0' && !preg_match('/^01\d-\d{4}-\d{4}$/', $phone)) {
             $_err['phone_number'] = 'Domestic mobile format must be 01x-xxxx-xxxx.';
             $step = 4;
        }
        elseif ($phone_digits[0] == '6' && strpos($phone, '-') !== false) {
             $_err['phone_number'] = 'International format (60) must not contain dashes.';
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
        
        // 存储时只存储纯数字 ($phone_digits)
        $stm = $_db->prepare("INSERT INTO user (email, name, phone_number, password, role, Profile_Photo, status) VALUES (?, ?, ?, ?, 'Member', ?, 'Activated')");
        $stm->execute([$email, $name, $phone_digits, $hashed, $avatar]); 

        // 清除所有临时数据
        unset($_SESSION['reg_name'], $_SESSION['reg_email'], $_SESSION['reg_phone']);

        temp('info', 'Welcome aboard, ' . htmlspecialchars($name) . '! Account created successfully!');
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
<div style="max-width:460px; margin:70px auto 100px; font-family:system-ui,sans-serif;">
    <h2 style="text-align:center; margin-bottom:8px; font-weight:600;">Create your account</h2>
    <p style="text-align:center; color:#666; margin-bottom:35px;">Step <?= $step ?> of 4</p>

    <div style="display:flex; justify-content:space-between; margin-bottom:50px; position:relative;">
        <?php for($i=1; $i<=4; $i++): ?>
        <div style="text-align:center; flex:1;">
            <div style="width:40px; height:40px; margin:0 auto 10px; background:<?= $step>=$i?'#000':'#e0e0e0' ?>; color:#fff; border-radius:50%; line-height:40px; font-weight:bold; font-size:1.1rem;">
                <?= $step > $i ? '✓' : $i ?>
            </div>
            <div style="font-size:0.9rem; color:<?= $step>=$i?'#000':'#999' ?>;">
                <?= ['Name','Email','Password','Phone'][$i-1] ?>
            </div>
        </div>
        <?php endfor; ?>
        <div style="position:absolute; top:20px; left:50px; right:50px; height:3px; background:#e0e0e0; z-index:-1;"></div>
        <div style="position:absolute; top:20px; left:50px; width:<?= ($step-1)/3*100 ?>%; height:3px; background:#000; transition:0.4s; z-index:-1;"></div>
    </div>

    <form method="post" novalidate>
        <?php if ($step == 1): ?>
            <div>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Full Name" autofocus style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px; font-size:1.1rem;">
                <?= err('name') ?>
            </div>
            <button type="submit" name="step" value="2" style="width:100%; padding:17px; background:#000; color:#fff; border:none; border-radius:10px; margin-top:30px; font-size:1.1rem; cursor:pointer;">Next</button>
        <?php endif; ?>

        <?php if ($step == 2): ?>
            <div>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Email address" autofocus style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px; font-size:1.1rem;">
                <?= err('email') ?>
            </div>
            <div style="display:flex; flex-direction:row-reverse; gap:12px; margin-top:30px;">
                <button type="submit" name="step" value="3" style="flex:2; padding:15px; background:#000; color:#fff; border:none; border-radius:10px;">Next</button>
                <button type="submit" name="back" value="1" style="flex:1; padding:15px; background:#f8f8f8; border:1px solid #ddd; border-radius:10px;">Back</button>
            </div>
        <?php endif; ?>

        <?php if ($step == 3): ?>
            <div>
                <div style="display: flex; align-items: center; gap: 5px; margin:10px 0;">
                    <input type="password" id="reg_password" name="password" placeholder="Create password" autofocus style="width:100%; padding:17px; border:1px solid #ccc; border-radius:10px; font-size:1.1rem;">
                    <button type="button" class="show-pass" data-target="#reg_password" style="cursor:pointer; padding:10px; border:1px solid #ccc; border-radius:10px; background:#f8f8f8; font-size:1.2rem;">👁︎</button>
                </div>
                
                <div style="display: flex; align-items: center; gap: 5px; margin:10px 0;">
                    <input type="password" id="reg_confirm_password" name="confirm_password" placeholder="Confirm password" style="width:100%; padding:17px; border:1px solid #ccc; border-radius:10px; font-size:1.1rem;">
                    <button type="button" class="show-pass" data-target="#reg_confirm_password" style="cursor:pointer; padding:10px; border:1px solid #ccc; border-radius:10px; background:#f8f8f8; font-size:1.2rem;">👁︎</button>
                </div>
                
                <?= err('password') ?><?= err('confirm_password') ?>
            </div>
            <div style="display:flex; flex-direction:row-reverse; gap:12px; margin-top:30px;">
                <button type="submit" name="step" value="4" style="flex:2; padding:15px; background:#000; color:#fff; border:none; border-radius:10px;">Next</button>
                <button type="submit" name="back" value="2" style="flex:1; padding:15px; background:#f8f8f8; border:1px solid #ddd; border-radius:10px;">Back</button>
            </div>
        <?php endif; ?>

        <?php if ($step == 4): ?>
            <div>
                <input type="hidden" name="password" value="<?= htmlspecialchars(req('password', '')) ?>">
                <input type="tel" id="reg_phone_number" name="phone_number" value="<?= htmlspecialchars($phone) ?>" placeholder="Phone number" autofocus style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px; font-size:1.1rem;">
                <?= err('phone_number') ?>
            </div>
            
            <div style="margin: 15px 0;">
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div> 
                <?= err('recaptcha') ?> 
            </div>
            <p style="color:#666; font-size:0.92rem; margin:25px 0; line-height:1.5;">
                By clicking Create Account, you agree to our <strong>Terms</strong> and <strong>Privacy Policy</strong>.
            </p>
            <button type="submit" name="step" value="5" style="width:100%; padding:17px; background:#000; color:#fff; border:none; border-radius:10px; font-size:1.1rem; margin-bottom:12px;">Create Account</button>
            <button type="submit" name="back" value="3" style="width:100%; padding:15px; background:transparent; border:1px solid #aaa; border-radius:10px;">Back</button>
        <?php endif; ?>
    </form>
</div>

<?php include '../_foot.php'; ?>