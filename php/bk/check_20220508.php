<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<title>Check</title>
<link rel="stylesheet" href="../css/check.css">
</head>

<?php
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";
require('sql.php');

$product_id =  $_POST['ProductID'];
$keyIndex_prd = array_search($product_id,$prd_ProductID);

$maker_id =  $_POST['chk_MakerID'];
$keyIndex_mak = array_search($maker_id,$mak_MakerID);

$keyIndex_sty = array_search($prd_StyleID[$keyIndex_prd],$sty_StyleID);

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
$value['clarity'] = $_POST['chk_clarity']['value'];
$value['fruity'] = $_POST['chk_fruity']['value'];
$value['favorite'] = $_POST['chk_favorite'];
$value['new_update'] = $_POST['New_Update'];
$value['productexplain'] = $_POST['ProductExplain'];
$value['comment'] = $_POST['comment'];

?>

<body>

<div class='top'>
<h1>Check</h1>
</div>

<p class='check'>
<?php 
 if($value['new_update']=='new'){ 
 
 echo "<ul class='image_list'>";
 echo '<p>Brewery</p>'; 
 echo "<label class='text'><span>".$mak_MakerName[$keyIndex_mak]."</span></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>BeerName</p>'; 
 echo "<label class='text'><span>".$value['productname']."</span></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Alcohol</p>'; 
 echo "<label class='text'><span>".$value['alcohol']." %</span></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>IBU</p>'; 
 echo "<label class='text'><span>".$value['ibu']." IBU</span></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Color</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/color/Color".$value['color'].".png' alt='test' /></label>"; 
 echo "</ul>";


 echo "<ul class='image_list'>";
 echo '<p>Clarity</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/clarity/Clarity".$value['clarity'].".png' alt='test' /><p>".$_POST['chk_clarity']['explain']."</p></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Fruity</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/fruity/Fruity".$value['fruity'].".png' alt='test' /><p>".$_POST['chk_fruity']['explain']."</p></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Favorite</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/favorite/Favorite".$value['favorite'].".png' alt='test' /></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Comment</p>'; 
 echo "<label class='text'><span>".$value['comment']."</span></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Photo</p>'; 
 echo "<label class='photo'><img class='thumbnail' src='../img/tmp/".$img_name."' alt='test' /></label>";
 echo "</ul>";

 }else if($value['new_update']=='update'){
$value['productid'] = $prd_ProductID[$keyIndex_prd];

 
 echo "<ul class='image_list'>";
 echo '<p>Brewery</p>'; 
 echo "<label class='text'><span>".$mak_MakerName[$keyIndex_mak]."</span></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>BeerName</p>'; 
 echo "<label class='text'><span>".$prd_ProductName[$keyIndex_prd]."</span></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Color</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/color/Color".$value['color'].".png' alt='test' /></label>"; 
 echo "</ul>";


 echo "<ul class='image_list'>";
 echo '<p>Clarity</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/clarity/Clarity".$value['clarity'].".png' alt='test' /><p>".$_POST['chk_clarity']['explain']."</p></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Fruity</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/fruity/Fruity".$value['fruity'].".png' alt='test' /><p>".$_POST['chk_fruity']['explain']."</p></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Favorite</p>'; 
 echo "<label class='img'><img class='thumbnail' src='../img/favorite/Favorite".$value['favorite'].".png' alt='test' /></label>"; 
 echo "</ul>";

 echo "<ul class='image_list'>";
 echo '<p>Comment</p>'; 
 echo "<label class='text'><span>".$value['comment']."</span></label>"; 
 echo "</ul>";

 }

?>
<?php 
$value_http=http_build_query($value);
?>

 <div class="submit">  
 <button type="button" onclick="history.back()">back</button>
 
 <form action = "thank.php" method = "POST">
 <input type = "hidden" name = "value_http" value="<?php echo $value_http ?>"  >
 <input type = "hidden" name = "image_name" value="<?php echo $img_name ?>"  >
 <input type = "submit" value ="Submit"> 
 </form>
 </div>
</p>

</body>
