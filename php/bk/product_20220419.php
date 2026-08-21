<link href="jquery.rateyo.min.css" rel="stylesheet" type="text/css">
<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js?ver=1.7.1'></script>
<script src="jquery.rateyo.min.js"></script>

<script type="text/javascript">
$(function () {
    $("#rateYo").rateYo({
        rating: 3.8
    });
});
</script>
<div id="rateYo"></div>


<?php
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";
require('sql.php');

$product_id = $_GET['product_id'];
$keyIndex_prd = array_search($product_id,$prd_ProductID);

$maker_id = $_GET['maker_id'];
$keyIndex_mak = array_search($maker_id,$mak_MakerID);

$style_id= array_search($prd_StyleID[$keyIndex_prd],$sty_StyleID);
$keyIndex_sty = array_search($style_id,$sty_StyleID);
?>

<html>
 <head>
<meta charset="utf-8">
<title><?php echo $prd_ProductName[$keyIndex_prd].' '.$mak_MakerName[$keyIndex_mak]?> </title>
<link rel="stylesheet" href="../css/beer.css">
</head>

<body>

<div class='top'>
</div>

<div class='left'>
<img src="../img/product/<?php echo $prd_ProductID[$keyIndex_prd]; ?>.png" alt="<?php echo $prd_ProductName[$keyIndex_prd]; ?>" title="<?php echo $prd_ProductName[$keyIndex_prd]; ?>" class='img_beer'>
</div>

<div class='right'>
<h1> <?php echo $prd_ProductName[$keyIndex_prd].' ('.$mak_MakerName[$keyIndex_mak].')' ?> </h1>
 <p class='outline' style="color:white" >
   <?php echo nl2br($prd_ProductExplain[$keyIndex_prd]) ?> 
 </p>
 <p class='rate'>
  <?php 
   //echo 'STYLE : '.$prd_Style[$keyIndex_prd].'<br/>'; 
   echo 'STYLE : '.$sty_StyleName[$keyIndex_sty].'<br/>'; 
   echo 'COLOR : '.$prd_Color[$keyIndex_prd].'<br/>'; 
   echo 'CLARITY : '.$prd_Clarity[$keyIndex_prd].'<br/>'; 
   echo 'IBU : '.$prd_IBU[$keyIndex_prd].'<br/>'; 
   echo 'FRUITY : '.$prd_Fruity[$keyIndex_prd].'<br/>'; 
   echo 'ALCOHOL : '.$prd_Alcohol[$keyIndex_prd].'<br/>'; 
   echo 'POPULARITY : '.$prd_Favorite[$keyIndex_prd].'<br/>'; 
  ?>

 </p>
 <p class='evaluation'>
  Evaluation for the beer : 
   <?php
   echo '<a href="evaluation.php?product_id=' . $product_id . '">'
   .'EVALUATION'.'</a>'
   ?>
 </p>
 <!--
 <p class='links'>
  Related links <br>
   <a href="https://uchubrew.shop-pro.jp/?pid=142369556">
   UCHU BEER OFFICIAL
   </a> <br>
   <a href="https://151l.shop/?pid=163383108">
   ICHIGO ICHIE
   </a><br>
 </p>
 -->
</div>


</body>



