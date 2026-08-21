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
require('sql.php');

$beer_id = $_GET['beer_id'];
//$keyIndex = array_search($beer_id, array_column($data,'ID'));
$keyIndex = array_search($beer_id,$data_ID);
?>

<html>
 <head>
<meta charset="utf-8">
<title><?php echo $data_BREWERY[$keyIndex].' '.$data_NAME[$keyIndex] ?> </title>
<link rel="stylesheet" href="../css/beer.css">
</head>

<body>

<div class='top'>
</div>

<div class='left'>
 <img src="../img/UCHU_MARS.png" alt="uchubeer" title="UCHU_MARS" class='img_beer'>
</div>

<div class='right'>
<h1> <?php echo $data_BREWERY[$keyIndex].' '.$data_NAME[$keyIndex] ?> </h1>
 <p class='outline' style="color:white" >
   UCHU MARS is ...
 </p>
 <p class='rate'>
  <?php 
   echo 'STYLE : '.$data_STYLE[$keyIndex].'<br/>'; 
   echo 'COLOR : '.$data_COLOR[$keyIndex].'<br/>'; 
   echo 'CLARITY : '.$data_CLARITY[$keyIndex].'<br/>'; 
   echo 'IBU : '.$data_IBU[$keyIndex].'<br/>'; 
   echo 'FURITY : '.$data_FURITY[$keyIndex].'<br/>'; 
   echo 'ALCOHOL : '.$data_ALCOHOL[$keyIndex].'<br/>'; 
   echo 'POPULARITY : '.$data_POPULARITY[$keyIndex].'<br/>'; 
  ?>

 </p>
 <p class='evaluation'>
  Evaluation for the beer : 
   <?php
   echo '<a href="evaluation.php?beer_id=' . $beer_id . '">'
   .'EVALUATION'.'</a>'
   ?>
 </p>
 <p class='links'>
  Related links <br>
   <a href="https://uchubrew.shop-pro.jp/?pid=142369556">
   UCHU BEER OFFICIAL
   </a> <br>
   <a href="https://151l.shop/?pid=163383108">
   ICHIGO ICHIE
   </a><br>
 </p>
</div>


</body>



