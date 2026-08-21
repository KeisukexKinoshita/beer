<!DOCTYPE html>
<html lang="ja">
 <head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3292112504579579"
     crossorigin="anonymous"></script>

<meta charset="utf-8">
<meta name='description' content="世界中のあらゆるクラフトビールを見つけることができます。">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Darth Beer.com</title>
<link rel="stylesheet" href="style.css">
<?php
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";
require('common/sql.php');
?>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-249697077-2"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-249697077-2');
</script>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-3XWPBKZR5Z"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-3XWPBKZR5Z');
</script>

</head>

<body>

<header>
<?php require(dirname(__FILE__).'/common/header.php'); ?>
</header>

<div class='first'>
<!-- <img src='../img/top.jpg' alt='top'>  -->
<h2>
  Discover Your Favorite Craft Beer!
</h2>
<p>
このサイトはクラフトビールを専門に扱います。</br>
おすすめのクラフトビールやブリュワリー、スタイルを参考にしてお気に入りのクラフトビールを見つけましょう。
</p>
</div>

<div id='main_wrap'>
<div id='main'>
<div class='sec1'>
<h2 class='Brewery'>新着のクラフトビール</h2>

<ul class="image_list"> 
<?php
for($i = count($prd_ProductID) -1; $i > count($prd_ProductID) -7; $i--){
   echo '<li>';
   echo '<div class="image_box">';
   echo '<a href="beer/detail/product.php?ProductID=';
   echo $prd_ProductID[$i] ;
   echo '">';
   echo '<img class="thumbnail" src="../img/product/';
   echo $prd_ProductID[$i];
   echo '.png" alt="';
   echo $prd_ProductName[$i];
   echo '" title="';
   echo $prd_ProductName[$i];
   echo '">';
   echo '</div>';
   echo '<h3 class="MakerName">';
   echo $prd_ProductName[$i] ;
   echo ' </h3>' ;
   echo '</a> ';
   echo ' <p class="MakerExplain">' ;
   echo $prd_ProductExplain[$i] ;
   echo ' </p>' ;
   echo '</li>';
}
?>
</ul>
<p class='seemore'>
<button type=“button” onclick="location.href='beer/products.php'"><h3>全てのクラフトビールを見る</h3></button>
</p>
</div>   <!-- sec1 -->

<div class='sec1'>
<h2 class='Brewery'>新着のブリュワリー</h2>

<ul class="image_list"> 
<?php
for($i = count($mak_MakerID) -1; $i > count($mak_MakerID) -7; $i--){
   echo '<li>';
   echo '<div class="image_box">';
   echo '<a href="brewery/detail/maker.php?MakerID=';
   echo $mak_MakerID[$i] ;
   echo '">';
   echo '<img class="thumbnail" src="../img/maker/';
   echo $mak_MakerID[$i];
   echo '.png" alt="';
   echo $mak_MakerName[$i];
   echo '" title="';
   echo $mak_MakerName[$i];
   echo '">';
   echo '</div>';
   echo '<h3 class="MakerName">';
   echo $mak_MakerName[$i] ;
   echo ' </h3>' ;
   echo '</a> ';
   echo ' <p class="MakerExplain">' ;
   require('brewery/detail/explain/'.$mak_MakerID[$i].'.html'); 
   echo ' </p>' ;
   echo '</li>';
}
?>
</ul>
<p class='seemore'>
<button type=“button” onclick="location.href='brewery/makers.php'"><h3>全てのブリュワリーを見る</h3></button>
</p>

</div>   <!-- sec1 -->

<div class='sec1'>
<!-- スタイルを非表示
<h2 class='Brewery'>新着のスタイル</h2>

<ul class="image_list"> 
<?php
/*スタイルを非表示
for($i = count($sty_StyleID) -1; $i > count($sty_StyleID) -7; $i--){
   echo '<li>';
   echo '<div class="image_box">';
   echo '<a href="style/detail/style.php?StyleID=';
   echo $sty_StyleID[$i] ;
   echo '">';
   echo '<img class="thumbnail" src="../img/style/';
   echo $sty_StyleID[$i];
   echo '.png" alt="';
   echo $sty_StyleName[$i];
   echo '" title="';
   echo $sty_StyleName[$i];
   echo '">';
   echo '</div>';
   echo '<h3 class="MakerName">';
   echo $sty_StyleName[$i] ;
   echo ' </h3>' ;
   echo '</a> ';
   echo ' <p class="MakerExplain">' ;
   echo $sty_StyleExplain[$i] ;
   echo ' </p>' ;
   echo '</li>';
}
スタイルを非表示*/
?>
</ul>
<p class='seemore'>
<button type=“button” onclick="location.href='style/styles.php'"><h3>全てのスタイルを見る</h3></button>
</p>

スタイルを非表示 -->
</div>   <!-- sec1 -->


</div> <!--  id=main  -->
</div> <!--  id=main_wrap  -->
</body>

</html>
