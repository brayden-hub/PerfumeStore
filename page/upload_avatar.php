<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$info = '';
$error = '';

// === 1. 处理上传 ===
if (is_post() && isset($_FILES['avatar_upload'])) {
    $file = $_FILES['avatar_upload'];

    if ($file['error'] === 0 && $file['size'] <= 2*1024*1024) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png'])) {
            $new_name = $user_id . '_' . time() . '.' . $ext;
            $path = "../images/avatars/" . $new_name;

            if (move_uploaded_file($file['tmp_name'], $path)) {
                // 只删除「自定义上传」的头像，default1~6 永远不删！
                $current = $_SESSION['Profile_Photo'] ?? '';
                if ($current && !in_array($current, ['default1.jpg','default2.jpg','default3.jpg','default4.jpg','default5.jpg','default6.jpg'])) {
                    @unlink("../images/avatars/" . $current);
                }

                $stm = $_db->prepare("UPDATE user SET Profile_Photo = ? WHERE userID = ?");
                $stm->execute([$new_name, $user_id]);

                $_SESSION['Profile_Photo'] = $new_name;
                $info = 'Avatar uploaded successfully!';
            }
        } else $error = 'Only JPG/PNG allowed';
    } else $error = 'File too big or upload error';
}

// === 2. 选择默认头像 ===
if (is_post() && isset($_POST['default_avatar'])) {
    $chosen = $_POST['default_avatar']; // default1.jpg ~ default6.jpg

    if ($current && !in_array($current, ['default1.jpg','default2.jpg','default3.jpg','default4.jpg','default5.jpg','default6.jpg'])) {
        @unlink("../images/avatars/" . $_SESSION['Profile_Photo']);
    }

    $stm = $_db->prepare("UPDATE user SET Profile_Photo = ? WHERE userID = ?");
    $stm->execute([$chosen, $user_id]);
    $_SESSION['Profile_Photo'] = $chosen;
    $info = 'Default avatar selected!';
}

// 当前头像
$current_avatar = $_SESSION['Profile_Photo'] ?? 'default1.jpg';

$_title = 'Change Avatar - N°9 Perfume';
include '../_head.php';
?>

<!-- 下面的 HTML 完全不用改，直接贴上 -->
<div style="padding:2rem; max-width: 700px; margin: 0 auto; text-align:center;">
    <h2>Change Avatar</h2>

    <?php if ($info): ?><p style="color:green; font-weight:bold;">Success: <?= $info ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>

    <div style="margin:2rem 0;">
        <img src="../images/avatars/<?= htmlspecialchars($current_avatar) ?>"
             style="width:200px; height:200px; border-radius:50%; object-fit:cover; border:6px solid #eee;">
        <p style="margin-top:10px; color:#666;">Current Avatar</p>
    </div>

    <hr style="margin:3rem 0; border:none; border-top:1px dashed #ddd;">

    <h3>Upload Your Own</h3>
    <form method="post" enctype="multipart/form-data" style="margin:2rem 0;">
        <input type="file" name="avatar_upload" accept="image/jpeg,image/png" required>
        <button type="submit" style="margin-left:10px; padding:10px 20px; background:#000; color:#fff; border:none;">
            Upload
        </button>
    </form>

    <hr style="margin:3rem 0; border:none; border-top:1px dashed #ddd;">

    <h3>Or Choose Default Avatar</h3>
    <form method="post" style="display:grid; grid-template-columns: repeat(3,1fr); gap:1rem; max-width:500px; margin:2rem auto;">
        <?php for ($i=1; $i<=6; $i++): 
            $def = "default$i.jpg";
            $selected = ($current_avatar === $def) ? 'border:4px solid #000;' : '';
        ?>
            <label style="cursor:pointer;">
                <input type="radio" name="default_avatar" value="<?= $def ?>" 
                       <?= ($current_avatar === $def) ? 'checked' : '' ?> style="display:none;">
                <img src="../images/avatars/<?= $def ?>" style="width:100%; border-radius:50%; <?= $selected ?>">
            </label>
        <?php endfor; ?>
        <div></div><div></div>
        <button type="submit" style="grid-column:1/4; padding:12px; background:#000; color:#fff; border:none; margin-top:1rem;">
            Select This Avatar
        </button>
    </form>

    <a href="profile.php" style="color:#666; text-decoration:underline;">← Back to Profile</a>
</div>

<?php include '../_foot.php'; ?>