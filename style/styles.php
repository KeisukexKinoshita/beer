<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<meta name='description' content="スタイルの一覧">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> 
<script src="../js/jPages-master/js/jPages.js"></script>
<title>Darth Beer.com</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="../common/pages.css">
<?php
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";
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
<h2 class='Brewery'>Styles <?php echo count($sty_StyleID) ?>件</h2>

<ul class="image_list" id="JPages"> 
<?php
for($i = count($sty_StyleID) -1; $i >-1; $i--){
   echo '<li>';
   echo '<div class="image_box">';
   echo '<a href="detail/style.php?StyleID=';
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
?>
</ul>

<div class="customBtns">
    <span class="arrowPrev"></span>
    <span class="arrowNext"></span>
    <div class="holder"></div>
</div>
<script>
var class_color='disabled_checkbox_color'
</script>
<script src="../common/pages.js"></script>


</div>  <!-- sec1 -->

</div> <!--  id=main  -->
</div> <!--  id=main_wrap  -->
</div> <!--  id=wrap  -->
</body>



<?php
$comment = $_GET['comment'];
?>




</html>
