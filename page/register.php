<?php
require '../_base.php';



if (is_post()) {
    // Input
    
    $email       = req('email');
    $name    = req('name');
    $phone = req('phone_number');
    $role = 'Member';
    $password = req('password');



    
    // Validate name
    if ($name == '') {
        $_err['name'] = 'Required';
    }
    else if (strlen($name) > 100) {
        $_err['name'] = 'Maximum length 100';
    }

    // Validate email
    if ($email == '') {
        $_err['email'] = 'Required';
    }
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email format';
    }
    else if (is_exists($email, 'user', 'email')) { 
        $_err['email'] = 'Email already registered';
    }
    else if (strlen($email) > 100) {
        $_err['email'] = 'Maximum length 100';
    }


    if ($phone == '') {
        $_err['phone_number'] = 'Required';
    } else if (!preg_match('/^[0-9]+$/', $phone)) {
        $_err['phone_number'] = 'Only digits allowed';
    } else if (strlen($phone) < 8 || strlen($phone) > 15) {
        $_err['phone_number'] = 'Length must be 8-15 digits';
    }


    // Password validation
    if ($password == '') {
        $_err['password'] = 'Required';
    }
    else if (strlen($password) < 8) {
        $_err['password'] = 'Minimum length 8';
    }
    else if (strlen($password) > 20) {
        $_err['password'] = 'Maximum length 20';
    }
    else if (!preg_match('/[A-Z]/', $password)) {
        $_err['password'] = 'Must include at least 1 uppercase letter';
    }
    else if (!preg_match('/[a-z]/', $password)) {
        $_err['password'] = 'Must include at least 1 lowercase letter';
    }
    else if (!preg_match('/[0-9]/', $password)) {
        $_err['password'] = 'Must include at least 1 number';
    }
    else if (!preg_match('/[\W_]/', $password)) {
        $_err['password'] = 'Must include at least 1 special character';
    }

    if (empty($_err)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // INSERT INTO database
    }

    // Output
    if (!$_err) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 随机给一个默认头像（1~6）
        $defaults = ['default1.jpg','default2.jpg','default3.jpg','default4.jpg','default5.jpg','default6.jpg'];
        $random_avatar = $defaults[array_rand($defaults)];

        // 插入数据库（多了 Profile_Photo）
        $stm = $_db->prepare('INSERT INTO user (email, name, phone_number, password, role, Profile_Photo) 
                              VALUES (?, ?, ?, ?, ?, ?)');
        $stm->execute([$email, $name, $phone, $hashed_password, $role, $random_avatar]);

        temp('info', 'Registration successful!');
        redirect('login.php');
    }
}

// ----------------------------------------------------------------------------
$_title = 'Register';
include '../_head.php';
?>

<form method="post" class="form">
    

    <label for="name">Name</label>
    <?= html_text('name', 'maxlength="100"') ?>
    <?= err('name') ?>

     <!-- Email -->
    <label for="email">Email</label>
    <?= html_text('email', 'maxlength="100"') ?>
    <?= err('email') ?>

    

    <!-- Password -->
    <label for="password">Password(minimum 8 length to 20 length,atleast one upper and lowercase,number and special character)</label>
    <div style="display: flex; align-items: center; gap: 5px;">
        <input type="password" id="password" name="password" maxlength="20">
        <button type="button" class="show-pass" data-target="#password" style="cursor: pointer;">👁️</button>
    </div>
    <?= err('password') ?>

    <!-- Confirm Password -->
    <label for="confirm_password">Confirm Password</label>
    <div style="display: flex; align-items: center; gap: 5px;">
        <input type="password" id="confirm_password" name="confirm_password" maxlength="20">
        <button type="button" class="show-pass" data-target="#confirm_password" style="cursor: pointer;">👁️</button>
    </div>
    <div class="err" id="confirmErr" style="color:red;"></div>



    <!-- Phone Number -->
    <label for="phone_number">Phone Number</label>
    <?= html_text('phone_number', 'maxlength="15"') ?>
    <?= err('phone_number') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>







<?php include '../_foot.php'; ?>