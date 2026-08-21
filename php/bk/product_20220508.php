<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="UTF-8" >
<title><?php echo $prd_ProductName[$keyIndex_prd].' '.$mak_MakerName[$keyIndex_mak]?></title>
<link rel="stylesheet" href="../css/product.css">
</head>

<?php
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";
$sql_where_cla = "''=''";
$sql_where_fru = "''=''";
$src_cla = "yes";
$src_fru = "yes";
require('sql.php');

$product_id = $_GET['product_id'];
$keyIndex_prd = array_search($product_id,$prd_ProductID);

$maker_id = $_GET['maker_id'];
$keyIndex_mak = array_search($maker_id,$mak_MakerID);

$keyIndex_sty = array_search($prd_StyleID[$keyIndex_prd],$sty_StyleID);
echo $keyIndex_sty;

$keyIndex_cla = array_search(round($prd_Clarity[$keyIndex_prd]),$cla_ClarityValue);

$keyIndex_fru = array_search(round($prd_Fruity[$keyIndex_prd]),$fru_FruityValue);
?>
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

<ul class='image_list'>
   <span>STYLE : <?php echo $sty_StyleName[$keyIndex_sty]; ?></span>
</ul>

<ul class='image_list'>
   <span>COLOR : </span>
   <label class='img'><img class='thumbnail' src='../img/color/Color<?php echo round($prd_Color[$keyIndex_prd],0) ?>.png' alt='test' /></label>
</ul>

<ul class='image_list'>
   <span>CLARITY : </span>
   <label class='img'><img class='thumbnail' src='../img/clarity/Clarity<?php echo round($prd_Clarity[$keyIndex_prd],0) ?>.png' alt='test' /><p><?php echo $cla_ClarityName[$keyIndex_cla]; ?></p></label>
</ul>

<ul class='image_list'>
   <span>IBU : <?php echo $prd_IBU[$keyIndex_prd]; ?></span>
</ul>

<ul class='image_list'>
   <span>FRUITY : </span>
   <label class='img'><img class='thumbnail' src='../img/fruity/Fruity<?php echo round($prd_Fruity[$keyIndex_prd],0) ?>.png' alt='test' /><p><?php echo $fru_FruityName[$keyIndex_fru]; ?></p></label>
</ul>

<ul class='image_list'>
   <span>ALCOHOL : <?php echo $prd_Alcohol[$keyIndex_prd]; ?></span>
</ul>

<ul class='image_list'>
   <span>POPULARITY : </span>
  <div class="reviewStar">
    <p class="reviewStar_grey">★★★★★</p>
    <p id="reviewStar_color" class="reviewStar_color">★★★★★</p>
    <script>
      var reviewNum = <?php echo $prd_Favorite[$keyIndex_prd] ;?>;
    </script>
    <script src="../js/rate_star.js"></script>
  </div>
   <span><?php echo $prd_Favorite[$keyIndex_prd]; ?></span>
</ul>

<ul class='image_list'>
  <div class="evaluation">
 <span>
    <?php echo '<a href="evaluation.php?product_id=' . $product_id . '">'.'Evaluation for this beer'.'</a>'?>
 </span>
  </div>
</ul>
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



