<?php
/*
$sql_where = "''=''";
require('sql.php');

$product_id = $_GET['product_id'];
$keyIndex = array_search($product_id,$prd_ProductID);
$rate=[];
 */
?>

<html>
 <head>
<meta charset="utf-8">
<title>Evaluation</title>
<link rel="stylesheet" href="../css/evaluation.css">
</head>

<body>

<div class='top'>
<h1>Evaluation</h1>
</div>

<p class='p1'>
You can add a new beer!!
</p>

<! -------------------------Sbumit -------------------------------->
 <form action = "post_product.php" method = "GET">

<p> Serch the brewery! </p>
<input type = "serch" name = "serch" placeholder='Enter a brewery name' ><br/>
<p> <input type = "submit" value ="submit"> </p>
</form>
<! -------------------------Sbumit -------------------------------->

<?php
$serch = $_GET['serch']; 
$src_prd='no';
//$sql_where_mak = "MakerName="."'".$serch."'";
$sql_where_mak = "MakerName like"."'%".$serch."%'";
require('sql.php');

for ($i = 0; $i < count($mak_MakerID); $i++){ 
//print_r($mak_MakerID[$i]);
print_r($mak_MakerName[$i]);
}


?>
</body>



