<?php
  session_start();
  require('dbconnect.php');
  require('signin_check.php');


  //つぶやきを保存
  if (isset($_POST) && !empty($_POST)){

    $sql = 'INSERT INTO `feeds` SET `feed`=?, `user_id`=?, `created`=NOW()';

    $data = array($_POST["feed"],$_SESSION['id']);

    $stmt = $dbh->prepare($sql);
    $stmt->execute($data);


  }

  //ページングの処理
  $page = ""; // ページ番号
  $start = 0; // データの取得開始番号（LIMIT句の指定に使用）
  $page_row = 3; // 1ページ分の表示件数

  //パラメータが存在していたらページ番号を代入
  if (isset($_GET["page"])){
    $page = $_GET["page"];
  }else{
    //存在しない場合1ページ目とみなす
    $page = 1;
  }

  //1以下のイレギュラーな数字が入ってきた時は強制的に1とします
  // max カンマ区切りで羅列された数字の中から最大の数字を取得
  $page = max($page,1);

  //データの件数から最大ページ数を計算する
  $sql_count = "SELECT COUNT(*) AS `cnt` FROM `feeds` ";
  $stmt_count = $dbh->prepare($sql_count);
  $stmt_count->execute();

  $rec_count = $stmt_count->fetch(PDO::FETCH_ASSOC);

  // ceil 小数点の切り上げ
  $all_page_number = ceil($rec_count['cnt'] / $page_row);

  // ページ番号が最大ページの番号を超えていれば、強制的に最後のページ数にする
  // min カンマ区切りの数字の羅列から、最小の数字を取得する
  $page = min($page,$all_page_number);

  //開始番号の計算
  $start = ($page - 1) * $page_row;

  //timelineの情報を取得
  if (isset($_GET["search_word"]) && !empty($_GET["search_word"])){
    //なにか検索ワードで検索した時
    $sql = 'SELECT `feeds`.*,`users`.`name`,`users`.`img_name` as `profile_image` FROM `feeds` INNER JOIN `users` ON `feeds`.`user_id` = `users`.`id` WHERE `feeds`.`feed` LIKE ? ORDER BY `feeds`.`updated` DESC';

    $word = "%".$_GET["search_word"]."%";
    $data = array($word);

  }else{
    //通常
    $sql = 'SELECT `feeds`.*,`users`.`name`,`users`.`img_name` as `profile_image` FROM `feeds` INNER JOIN `users` ON `feeds`.`user_id` = `users`.`id` ORDER BY `feeds`.`updated` DESC LIMIT '.$start.','.$page_row;
    $data = array();
  }

  $stmt = $dbh->prepare($sql);
  $stmt->execute($data);

  //表示部分で使用できるようにタイムラインの情報を格納する配列を用意
  $timeline = array();
  while (1) {
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    // テーブル結合以前
    // $rec = array("id"=>1,"feed"=>"つぶやいた内容",,,,"created"=>"2018-03-03","updated"=>"2018-03-03")
    // テーブル結合後
    // $rec = array("id"=>1,"feed"=>"つぶやいた内容",,,,"created"=>"2018-03-03","updated"=>"2018-03-03","name"=>"demotarou","profile_image"=>"20180303010101test.png")

    // テーブル結合後-ログインユーザーのライク状況を表す情報を追加
    // $rec = array("id"=>1,"feed"=>"つぶやいた内容",,,,"created"=>"2018-03-03","updated"=>"2018-03-03","name"=>"demotarou","profile_image"=>"20180303010101test.png","like_flag"=>0) Likeしてない時

    // $rec = array("id"=>3,"feed"=>"つぶやいた内容",,,,"created"=>"2018-03-03","updated"=>"2018-03-03","name"=>"demotarou","profile_image"=>"20180303010101test.png","like_flag"=>1) Likeしてる時



    if ($rec == false){
      break;
    }

    //ログインユーザーが現在取得feedにlikeしてるかどうかを取得
    //$_SESSION["id"]:ログインしてるユーザーID
    //$rec["id"]:現在取得したfeedのID
    $like_sql = "SELECT COUNT(*) as `cnt` FROM `likes` WHERE `feed_id`=? AND `user_id`= ?";
    $like_data = array($rec["id"],$_SESSION["id"]);
    $like_stmt = $dbh->prepare($like_sql);
    $like_stmt->execute($like_data);

    $like_rec = $like_stmt->fetch(PDO::FETCH_ASSOC);

    if ($like_rec["cnt"] == 0){
      //Likeしてない
      $rec["like_flag"] = 0;
    }else{
      //Like済み
      $rec["like_flag"] = 1;
    }

    //いいね！済みのみ表示するように指示された場合、ログインユーザーがいいね！済みのデータだけをタイムラインに表示するようにしよう
    if (isset($_GET["feed_select"]) && ($_GET["feed_select"] == "likes")){
      //いいね！済みのみ
      if ($rec["like_flag"] == 1){
        $timeline[] = $rec;
      }
    }else{
      $timeline[] = $rec;
    }


  }


?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Learn SNS</title>
  <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.css">
  <link rel="stylesheet" type="text/css" href="assets/font-awesome/css/font-awesome.css">
  <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body style="margin-top: 60px; background: #E4E6EB;">
  <nav class="navbar navbar-default navbar-fixed-top">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse1" aria-expanded="false">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="/">Learn SNS</a>
      </div>
      <div class="collapse navbar-collapse" id="navbar-collapse1">
        <ul class="nav navbar-nav">
          <li class="active"><a href="#">タイムライン</a></li>
          <li><a href="user_index.php">ユーザー一覧</a></li>
        </ul>
        <form method="GET" action="" class="navbar-form navbar-left" role="search">
          <div class="form-group">
            <input type="text" name="search_word" class="form-control" placeholder="投稿を検索">
          </div>
          <button type="submit" class="btn btn-default">検索</button>
        </form>
        <ul class="nav navbar-nav navbar-right">
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><img src="user_profile_img/<?php echo $login_user["img_name"]; ?>" width="18" class="img-circle"><?php echo $login_user['name']; ?> <span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a href="#">マイページ</a></li>
              <li><a href="signout.php">サインアウト</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="row">
      <div class="col-xs-3">
        <ul class="nav nav-pills nav-stacked">
          <?php if (!isset($_GET["feed_select"]) || (isset($_GET["feed_select"]) && $_GET["feed_select"] == "news")){ ?>
          <li class="active">
          <?php }else{ ?>
          <li >
          <?php } ?>
          <a href="timeline.php?feed_select=news">新着順</a></li>
          <?php if ((isset($_GET["feed_select"]) && $_GET["feed_select"] == "likes")){ ?>
          <li class="active">
          <?php }else{ ?>
          <li >
          <?php } ?>
          <a href="timeline.php?feed_select=likes">いいね！済み</a></li>
          <!-- <li><a href="timeline.php?feed_select=follows">フォロー</a></li> -->
        </ul>
      </div>
      <div class="col-xs-9">
        <div class="feed_form thumbnail">
          <form method="POST" action="">
            <div class="form-group">
              <textarea name="feed" class="form-control" rows="3" placeholder="Happy Hacking!" style="font-size: 24px;"></textarea><br>
            </div>
            <input type="submit" value="投稿する" class="btn btn-primary">
          </form>
        </div>
          <?php foreach ($timeline as $timeline_each) {
              include("timeline_oneline.php");
          } ?>
        <nav aria-label="Page navigation">
          <ul class="pager">
            <?php if ($page <= 1){ ?>
            <li class="previous disabled"><a><span aria-hidden="true">&larr;</span> Older</a></li>
            <?php }else{ ?>
            <li class="previous"><a href="timeline.php?page=<?php echo $page - 1; ?>"><span aria-hidden="true">&larr;</span> Older</a></li>
            <?php } ?>
            <?php if ($page >= $all_page_number){ ?>
            <li class="next disabled"><a>Newer <span aria-hidden="true">&rarr;</span></a></li>
            <?php }else{ ?>
            <li class="next"><a href="timeline.php?page=<?php echo $page + 1; ?>">Newer <span aria-hidden="true">&rarr;</span></a></li>
            <?php } ?>
          </ul>
        </nav>
      </div>
    </div>
  </div>
  <script src="assets/js/jquery-3.1.1.js"></script>
  <script src="assets/js/jquery-migrate-1.4.1.js"></script>
  <script src="assets/js/bootstrap.js"></script>
</body>
</html>