<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title></title>
</head>
<body>
  <?php echo $_SESSION['register']['name']; ?><br>
  <?php echo $_SESSION['register']['email']; ?><br>
  <?php echo $_SESSION['register']['password']; ?><br>
  <?php echo $_SESSION['register']['img_name']; ?><br>

  <img src="../user_profile_img/<?php echo $_SESSION['register']['img_name']; ?>" width="60">
</body>
</html>