<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<meta name='description' content="This site shows some craft beer">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Darth Beer.com</title>
<link rel="stylesheet" href="../../style.css">
<link rel="stylesheet" href="../../brewery/detail/maker.css">
<?php
$style_id = $_GET['StyleID'];
//$sql_where = "''=''";
$sql_where = "StyleID="."'".$style_id."'";
$sql_where_sty = "StyleID="."'".$style_id."'";
$src_mak = "no";
$src_sty = "yes";
require('../../common/sql.php');
$keyIndex_sty = array_search($style_id,$sty_StyleID);
?>

</head>

<body>

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

<div id='main_wrap_maker'>
<div id='main'>

<div class='sec1_maker'>

 <h2><?php echo nl2br($sty_StyleName[$keyIndex_sty]) ?></h2>
 <p class='p2'>
   <?php echo $sty_StyleExplain[$keyIndex_sty] ?> 
 </p>

</div>   <!-- sec1 -->


<div class='sec2'>
 <h3><?php echo nl2br($sty_StyleName[$keyIndex_sty]) ?>のビール一覧</h3> 
    <ul class="image_list"> 
    <?php
    for($i = 0 ; $i < count($prd_ProductID); $i++){
       echo '<li>';
       echo '<div class="image_box">';
       echo '<a href="../../beer/detail/product.php?ProductID=';
       echo $prd_ProductID[$i] ;
       echo '">';
       echo '<img class="thumbnail" src="../../img/product/';
       echo $prd_ProductID[$i];
       echo '.png" alt="';
       echo $prd_ProductName[$i];
       echo '" title="';
       echo $prd_ProductName[$i];
       echo '">';
       echo '<h3 class="ProductName">';
       echo $prd_ProductName[$i] ;
       echo ' </h3>' ;
       echo '</a> ';
       echo ' <p class="ProductExplain">' ; 
       echo $prd_ProductExplain[$i];
       echo '</p>';
       /*
       echo ' <p class="Favorite">' ; 
       echo $prd_Favorite[$i];
       echo '</p>';
       */
       echo '</div>';
       echo '</li>';
    }
    ?>
    </ul>
</div>  <!-- sec2 -->

</div> <!--  id=main  -->
</div> <!--  id=main_wrap  -->
</body>

</html>

