<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<meta name='description' content="This site shows some craft beer">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Darth Beer.com</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/maker.css">
<?php
$maker_id = $_GET['MakerID'];
$sql_where = "MakerID="."'".$maker_id."'";
require('sql.php');
$keyIndex_mak = array_search($maker_id,$mak_MakerID);
?>

</head>

<body>

<header>
<?php require(dirname(__FILE__).'/header.php'); ?>
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

 <h2><?php echo nl2br($mak_MakerName[$keyIndex_mak]) ?></h2>
 <p class='p2'>
   <?php echo $mak_MakerExplain[$keyIndex_mak] ?> 
 </p>
 <p class='p3'>
   Official page: <a href="<?php echo $mak_URL1[$keyIndex_mak] ?>"><?php echo $mak_URL1[$keyIndex_mak] ?></a> 
 </p>

<div class='chart'>
    <meta name="viewport" content="width=device-width,initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <script
      src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.2.0/chart.min.js"
      integrity="sha512-VMsZqo0ar06BMtg0tPsdgRADvl0kDHpTbugCBBrL55KmucH6hP9zWdLIWY//OTfMnzz6xWQRxQqsUFefwHuHyg=="
      crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.0/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    
    <div class="wrap-chart" > 
        <canvas id="mychart"></canvas> 
      <script type="text/javascript">
      </script>
      <script type="text/javascript" src="../js/bubble.js"></script>
    </div> 
</div>   <!-- chart -->
</div>   <!-- sec1 -->

<div class='sec2'>
<!-- <h2>Beers of the Brewery </h2>  -->
    <ul class="image_list"> 
    <?php
    for($i = 0 ; $i < count($prd_ProductID); $i++){
       echo '<li>';
       echo '<div class="image_box">';
       echo '<a href="product.php?ProductID=';
       echo $prd_ProductID[$i] ;
       echo '">';
       echo '<img class="thumbnail" src="../img/product/';
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

