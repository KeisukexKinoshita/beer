<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<meta name='description' content="This site shows some craft beer">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Darth Beer.com</title>
<link rel="stylesheet" href="../../style.css">
<link rel="stylesheet" href="product.css">
<?php
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";
$sql_where_cla = "''=''";
$sql_where_fru = "''=''";
$src_cla = "yes";
$src_fru = "yes";
require('../../common/sql.php');

$product_id = $_GET['ProductID'];
$keyIndex_prd = array_search($product_id,$prd_ProductID);

//$maker_id = $_GET['MakerID'];
$keyIndex_mak = array_search($prd_MakerID[$keyIndex_prd],$mak_MakerID);

$keyIndex_sty = array_search($prd_StyleID[$keyIndex_prd],$sty_StyleID);

$keyIndex_cla = array_search(round($prd_Clarity[$keyIndex_prd]),$cla_ClarityValue);

$keyIndex_fru = array_search(round($prd_Fruity[$keyIndex_prd]),$fru_FruityValue);
?>

</head>

<body>
<div id='wrap'>

<header>
<?php require(dirname(__FILE__).'/../../common/header.php'); ?>
</header>

<!--
<div class='first'>
<h2>
</h2>
<p>
</p>
</div>
-->

<div id='main_wrap_product'>
<div id='main'>
<div class='sec1'>

<div class='left'>
    <img src="../../img/product/<?php echo $prd_ProductID[$keyIndex_prd]; ?>.png" alt="<?php echo $prd_ProductName[$keyIndex_prd]; ?>" title="<?php echo $prd_ProductName[$keyIndex_prd]; ?>" class='img_beer'>
</div>

<div class='right'>
  <h1> <?php echo $prd_ProductName[$keyIndex_prd]; ?> </h1>
   <p class='outline' style="color:white" >
     <?php echo nl2br($prd_ProductExplain[$keyIndex_prd]) ?> 
   </p>
  
  <ul class='image_list_product'>
     <span>ブリュワリー : </span><a href="../../brewery/detail/maker.php?MakerID=<?php echo $mak_MakerID[$keyIndex_mak]; ?>"><?php echo $mak_MakerName[$keyIndex_mak]; ?></a>
  </ul>

  <ul class='image_list_product'>
     <!-- <span>スタイル : </span><--<a href="../../style/detail/style.php?StyleID=<?php echo $sty_StyleID[$keyIndex_sty]; ?>"><?php echo $sty_StyleName[$keyIndex_sty]; ?></a> -->
     <span>スタイル : <?php echo $sty_StyleName[$keyIndex_sty]; ?></span>
  </ul>
  
  <ul class='image_list_product'>
     <span>色 : </span>
     <label class='img'><img class='thumbnail' src='../../img/color/Color<?php echo round($prd_Color[$keyIndex_prd],0) ?>.png' alt='test' /></label>
  </ul>
  
  <ul class='image_list_product'>
     <span>透明度 : </span>
     <label class='img'><img class='thumbnail' src='../../img/clarity/Clarity<?php echo round($prd_Clarity[$keyIndex_prd],0) ?>.png' alt='test' /><p><?php echo $cla_ClarityName[$keyIndex_cla]; ?></p></label>
  </ul>
  
  <ul class='image_list_product'>
     <span>IBU : <?php echo $prd_IBU[$keyIndex_prd]; ?></span>
  </ul>
  
  <ul class='image_list_product'>
     <span>フルティーさ : </span>
     <label class='img'><img class='thumbnail' src='../../img/fruity/Fruity<?php echo round($prd_Fruity[$keyIndex_prd],0) ?>.png' alt='test' /><p><?php echo $fru_FruityName[$keyIndex_fru]; ?></p></label>
  </ul>
  
  <ul class='image_list_product'>
     <span>アルコール度 : <?php echo $prd_Alcohol[$keyIndex_prd]; ?></span>
  </ul>
  
  <ul class='image_list_product'>
     <span>点数 : </span>
    <div class="reviewStar">
      <p class="reviewStar_grey">★★★★★</p>
      <p id="reviewStar_color" class="reviewStar_color">★★★★★</p>
      <script>
        var reviewNum = <?php echo $prd_Favorite[$keyIndex_prd] ;?>;
      </script>
      <script src="rate_star.js"></script>
    </div>
     <span><?php echo $prd_Favorite[$keyIndex_prd]; ?></span>
  </ul>
  
</div>   <!-- right -->

<div class='center'>
    <div class="evaluation">
   <h3>
      <?php echo '<a href="../../post/evaluation.php?product_id=' . $product_id . '">'.'このビールを評価する'.'</a>'?>
   </h3>
    </div>
</div>

</div> <!--  id=main  -->
</div> <!--  id=main_wrap  -->
</div> <!--  id=wrap  -->
</body>



<?php
$comment = $_GET['comment'];
?>



</html>

