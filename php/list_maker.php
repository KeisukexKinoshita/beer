<?php
$sql_where = "''=''";
require('sql.php');

$product_id = $_GET['product_id'];
$keyIndex = array_search($product_id,$prd_ProductID);
$rate=[];

?>

<html>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<!-- <script src="../js/base.js"></script> -->
 <head>
<meta charset="utf-8">
<title>List of brewery</title>
<link rel="stylesheet" href="../css/list_maker.css">
</head>

<body>

<div class='top'>
<h1>List of brewery</h1>
</div>

<p class='p1'>
Chose a brewery
</p>



<form action = "add_product.php" method = "POST">
<?php

for($i = 0 ; $i < count($mak_MakerName); $i++){
   echo '<ul class="image_list">';
   //echo "<li>";
   echo '<div class="image_box">';
   echo '<input class ="disabled_checkbox_maker" type = "checkbox" name = "chk_maker" value=';
   echo $mak_MakerID[$i];
   echo ' >';
   echo $mak_MakerName[$i];
   echo "</div>";
   //echo "</li>";
   echo "</ul>";
}

?>

<script>
var class_color='disabled_checkbox_maker' ;
</script>
<script src="../js/check_control.js"></script>

<p> <input type = "submit" value ="submit"> </p>
</form>
</body>



