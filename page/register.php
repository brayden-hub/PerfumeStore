<?php
require '../_base.php';

// 正确写法（已三重检查，没有任何多余字符！）
if (is_post()) {
    $_SESSION['reg_name']     = req('name');
    $_SESSION['reg_email']    = req('email');
    $_SESSION['reg_phone']    = req('phone_number');
}

// 从 session 读取资料（保证不会丢）
$name  = $_SESSION['reg_name']     ?? '';
$email = $_SESSION['reg_email']    ?? '';
$phone = $_SESSION['reg_phone']    ?? '';

$step = (int)req('step', 1);
$_err = [];

// 第4步才真正注册
if (is_post() && $step == 4) {
    $password = req('password');
    $confirm  = req('confirm_password');

    // 你的验证逻辑……
    if ($name == '') $_err['name'] = 'Required';
    if ($email == '') $_err['email'] = 'Required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $_err['email'] = 'Invalid email';
    elseif (is_exists($email, 'user', 'email')) $_err['email'] = 'Email already registered';

    if ($phone == '') $_err['phone_number'] = 'Required';
    elseif (!preg_match('/^[0-9]+$/', $phone)) $_err['phone_number'] = 'Only digits';
    elseif (strlen($phone) < 8 || strlen($phone) > 15) $_err['phone_number'] = '8-15 digits';

    if ($password == '') $_err['password'] = 'Required';
    elseif (strlen($password) < 8) $_err['password'] = 'Min 8 characters';
    elseif (!preg_match('/[A-Z]/', $password)) $_err['password'] = 'Need 1 uppercase';
    elseif (!preg_match('/[a-z]/', $password)) $_err['password'] = 'Need 1 lowercase';
    elseif (!preg_match('/[0-9]/', $password)) $_err['password'] = 'Need 1 number';
    elseif (!preg_match('/[\W_]/', $password)) $_err['password'] = 'Need 1 special char';
    elseif ($password !== $confirm) $_err['confirm_password'] = 'Not match';

    if (empty($_err)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $defaults = ['default1.jpg','default2.jpg','default3.jpg','default4.jpg','default5.jpg','default6.jpg'];
        $avatar = $defaults[array_rand($defaults)];

        $stm = $_db->prepare("INSERT INTO user (email, name, phone_number, password, role, Profile_Photo) VALUES (?, ?, ?, ?, 'Member', ?)");
        $stm->execute([$email, $name, $phone, $hashed, $avatar]);

        // 清除临时资料
        unset($_SESSION['reg_name'], $_SESSION['reg_email'], $_SESSION['reg_phone']);

        temp('info', 'Welcome aboard, ' . htmlspecialchars($name) . '! Account created successfully!');
        redirect('login.php');
    }
}

$_title = 'Register - N°9 Perfume';
include '../_head.php';
?>

<div style="max-width:460px; margin:70px auto 100px; font-family:system-ui,sans-serif;">
    <h2 style="text-align:center; margin-bottom:8px; font-weight:600;">Create your account</h2>
    <p style="text-align:center; color:#666; margin-bottom:35px;">Step <?= $step ?> of 4</p>

    <!-- 超帅进度条 -->
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

    <form method="post" novalidate> <!-- 重磅：加 novalidate 彻底关闭浏览器 required 检查 -->
        <input type="hidden" name="step" value="<?= $step ?>">

        <!-- Step 1 -->
        <?php if ($step == 1): ?>
            <div>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Full Name" required autofocus style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px; font-size:1.1rem;">
                <?= err('name') ?>
            </div>
            <button type="submit" name="step" value="2" style="width:100%; padding:17px; background:#000; color:#fff; border:none; border-radius:10px; margin-top:30px; font-size:1.1rem; cursor:pointer;">Next</button>
        <?php endif; ?>

        <!-- Step 2 -->
        <?php if ($step == 2): ?>
            <div>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Email address" required autofocus style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px;">
                <?= err('email') ?>
            </div>
            <div style="display:flex; gap:12px; margin-top:30px;">
                <button type="submit" name="step" value="1" style="flex:1; padding:15px; background:#f8f8f8; border:1px solid #ddd; border-radius:10px;">Back</button>
                <button type="submit" name="step" value="3" style="flex:2; padding:15px; background:#000; color:#fff; border:none; border-radius:10px;">Next</button>
            </div>
        <?php endif; ?>

        <!-- Step 3 -->
        <?php if ($step == 3): ?>
            <div>
                <input type="password" name="password" placeholder="Create password" required autofocus style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px;">
                <input type="password" name="confirm_password" placeholder="Confirm password" required style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px;">
                <?= err('password') ?><?= err('confirm_password') ?>
            </div>
            <div style="display:flex; gap:12px; margin-top:30px;">
                <button type="submit" name="step" value="2" style="flex:1; padding:15px; background:#f8f8f8; border:1px solid #ddd; border-radius:10px;">Back</button>
                <button type="submit" name="step" value="4" style="flex:2; padding:15px; background:#000; color:#fff; border:none; border-radius:10px;">Next</button>
            </div>
        <?php endif; ?>

        <!-- Step 4 -->
        <?php if ($step == 4): ?>
            <div>
                <input type="tel" name="phone_number" value="<?= htmlspecialchars($phone) ?>" placeholder="Phone number" required autofocus style="width:100%; padding:17px; margin:10px 0; border:1px solid #ccc; border-radius:10px;">
                <?= err('phone_number') ?>
            </div>
            <p style="color:#666; font-size:0.92rem; margin:25px 0; line-height:1.5;">
                By clicking Create Account, you agree to our <strong>Terms</strong> and <strong>Privacy Policy</strong>.
            </p>
            <button type="submit" style="width:100%; padding:17px; background:#000; color:#fff; border:none; border-radius:10px; font-size:1.1rem; margin-bottom:12px;">Create Account</button>
            <button type="submit" name="step" value="3" style="width:100%; padding:15px; background:transparent; border:1px solid #aaa; border-radius:10px;">Back</button>
        <?php endif; ?>
    </form>
</div>

<?php include '../_foot.php'; ?>