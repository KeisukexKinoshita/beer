<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<meta name='description' content="This site shows some craft beer">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Darth Beer.com</title>
<link rel="stylesheet" href="../style.css">
<?php
$sql_where = "''=''";
require('../common/sql.php');
?>

</head>

<body>
<div id='wrap'>

<header>
<?php require(dirname(__FILE__).'/../common/header.php'); ?>
</header>

<!--
<div class='first'>
<h2>
</h2>
<p>
</p>
</div>
-->

<div id='main_wrap'>
<div id='main'>
<div class='sec1'>
<h2 class='Brewery'>Breweries <?php echo count($mak_MakerID) ?>件</h2>

<ul class="image_list"> 
<?php
for($i = 0 ; $i < count($mak_MakerID); $i++){
   echo '<li>';
   echo '<div class="image_box">';
   echo '<a href="detail/maker.php?MakerID=';
   echo $mak_MakerID[$i] ;
   echo '">';
   echo '<img class="thumbnail" src="../img/maker/';
   echo $mak_MakerID[$i];
   echo '.png" alt="';
   echo $mak_MakerName[$i];
   echo '" title="';
   echo $mak_MakerName[$i];
   echo '">';
   echo '<h3 class="MakerName">';
   echo $mak_MakerName[$i] ;
   echo ' </h3>' ;
   echo '</a> ';
   echo ' <p class="MakerExplain">' ;
   echo $mak_MakerExplain[$i] ;
   echo ' </p>' ;
   echo '</div>';
   echo '</li>';
}
?>
</ul>

</div>

</div> <!--  id=main  -->
</div> <!--  id=main_wrap  -->
</div> <!--  id=wrap  -->
</body>



<?php
$comment = $_GET['comment'];
?>




</html>
