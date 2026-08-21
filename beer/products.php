<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<meta name='description' content="クラフトビールの一覧">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> 
<script src="../js/jPages-master/js/jPages.js"></script>
<title>Darth Beer.com</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="../common/pages.css">
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
<h2 class='Brewery'>ビール <?php echo count($prd_ProductID) ?>件</h2>

<ul class="image_list" id="JPages"> 
<?php
for($i = count($prd_ProductID) -1 ; $i >-1; $i--){
   echo '<li>';
   echo '<div class="image_box">';
   echo '<a href="detail/product.php?ProductID=';
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
