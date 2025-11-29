<?php
require '../_base.php';
echo "<h3>数据库目前存的头像文件名：</h3>";
echo $_SESSION['user_id'] . " → " . ($_SESSION['Profile_Photo'] ?? '空');
echo "<br><br><h3>直接从数据库抓：</h3>";
$stm = $_db->prepare("SELECT Profile_Photo FROM user WHERE userID = ?");
$stm->execute([$_SESSION['user_id']]);
echo $stm->fetchColumn() ?: '真的空';