<?php
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";
require('sql.php');

$product_id =  $_POST['ProductID'];
$keyIndex_prd = array_search($product_id,$prd_ProductID);

$maker_id =  $_POST['chk_MakerID'];
$keyIndex_mak = array_search($maker_id,$mak_MakerID);

$style_id= array_search($prd_StyleID[$keyIndex_prd],$sty_StyleID);
$keyIndex_sty = array_search($style_id,$sty_StyleID);

$img_name = $_FILES['upimg']['name'];
move_uploaded_file($_FILES['upimg']['tmp_name'], '../img/tmp/' . $img_name);


$value['userid'] = $_POST['UserID'];
$value['productid'] = $_POST['ProductID'];
$value['makerid'] = $_POST['chk_MakerID'];
$value['productname'] = $_POST['chk_productname'];
$value['styleid'] = $_POST['chk_StyleID'];
$value['alcohol'] = $_POST['chk_alcohol'];
$value['ibu'] = $_POST['chk_ibu'];
$value['color'] = $_POST['chk_color'];
$value['clarity'] = $_POST['chk_clarity'];
$value['fruity'] = $_POST['chk_fruity'];
$value['favorite'] = $_POST['chk_favorite'];
$value['new_update'] = $_POST['New_Update'];
$value['productexplain'] = $_POST['ProductExplain'];
$value['comment'] = $_POST['comment'];

?>

<html>
 <head>
<meta charset="utf-8">
<title>Check</title>
<link rel="stylesheet" href="../css/evaluation.css">
</head>

<body>

<div class='top'>
<h1>Check</h1>
</div>

<p class='check'>
<?php 
 if($value['new_update']=='new'){ 
 
 echo 'Brewery : '.$mak_MakerName[$keyIndex_mak].'<br/>'; 
 echo 'BeerName : '.$value['productname'].'<br/>'; 
 echo 'Alcohol : '.$value['alcohol'].'<br/>'; 
 echo 'IBU : '.$value['ibu'].'<br/>'; 
 echo 'Color : '.$value['color'].'<br/>'; 
 echo 'Clarity : '.$value['clarity'].'<br/>';
 echo 'Fruity : '.$value['fruity'].'<br/>'; 
 echo 'Favorite : '.$value['favorite'].'<br/>';
 }else if($value['new_update']=='update'){

 echo 'Brewery : '.$mak_MakerName[$keyIndex_mak].'<br/>'; 
 echo 'BeerName : '.$prd_ProductName[$keyIndex_prd].'<br/>'; 
 echo 'Color : '.$value['color'].'<br/>'; 
 echo 'Clarity : '.$value['clarity'].'<br/>';
 echo 'Fruity : '.$value['fruity'].'<br/>'; 
 echo 'Favorite : '.$value['favorite'].'<br/>';

 }

?>
<?php 
$value_http=http_build_query($value);
?>

 <p> <button type="button" onclick="history.back()">back</button> </p>
 
 <form action = "sql_POST.php" method = "POST">
 <input type = "hidden" name = "value_http" value="<?php echo $value_http ?>"  ><br/>
 <input type = "hidden" name = "image_name" value="<?php echo $img_name ?>"  ><br/>
 <p> <input type = "submit" value ="submit"> </p>
 </form>
</p>

</body>
