<?php
  session_start();
  require('dbconnect.php');
  require('signin_check.php');

  //いいね！を取り消す機能

  //誰がどの記事のいいねを取り消したいか認識し、削除
  //var_dump($_REQUEST["feed_id"]);

  $sql = 'DELETE FROM `likes` WHERE `user_id`=? AND `feed_id`=?';

  // DELETE文実行
  $data = array($_SESSION["id"],$_REQUEST["feed_id"]);
  $stmt = $dbh->prepare($sql);
  $stmt->execute($data);



  //いいねされた記事のlike_count を再計算する

  //いいねされた数を取得
  $sql = 'SELECT COUNT(*) as `cnt` FROM `likes` WHERE `feed_id` = ?';
  
  $data = array($_REQUEST["feed_id"]);
  $stmt = $dbh->prepare($sql);
  $stmt->execute($data);

  $rec = $stmt->fetch(PDO::FETCH_ASSOC);

  //取得できた件数をvar_dumpで確認（その時はtimeline.phpへ移動してる部分をコメント）
  // var_dump($rec["cnt"]);

  //いいねされた記事のlike_count をUpdate
  $sql = 'UPDATE `feeds` SET `like_count`=? WHERE `id`=?';

  // UPDATE文実行
  $data = array($rec["cnt"],$_REQUEST["feed_id"]);
  $stmt = $dbh->prepare($sql);
  $stmt->execute($data);

  //timeline.phpに戻る
  header("Location: timeline.php");


?>