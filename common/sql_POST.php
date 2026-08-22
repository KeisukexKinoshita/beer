<?php
require_once dirname(__FILE__).'/../db_config.local.php';
parse_str($value_http, $value);

$dbh = new PDO($db_dsn, $db_user, $db_pass);

if($value['new_update']=='new'){
  //------------------------------SELECT FROM products-----------------------------------------
  $query = "SELECT MAX(ProductID) FROM products";
  $res = $dbh->query($query);
  $arr_MAX_ProductID = $res->fetchAll(PDO::FETCH_ASSOC);
  $MAX_ProductID = array_column($arr_MAX_ProductID,"MAX(ProductID)")[0];
  $NEXT_ProductID =++$MAX_ProductID;
  $value['productid']=$NEXT_ProductID; // use for updating rate_user table

  // -------------------------------------------SELECT FROM style --------------------------------
  $query = "SELECT IBU FROM style WHERE StyleID='".$value['styleid']."'";  
  $res = $dbh->query($query);
  $sty = $res->fetchAll(PDO::FETCH_ASSOC);
  $json_sty = json_encode($sty);

  $IBU_Style= array_column($sty,"IBU")[0];
  // -------------------------------------------INSERT INTO products --------------------------------
  $sql = "INSERT INTO  products VALUES(:ProductID,:MakerID,:ProductName,:StyleID,:Alcohol,:IBU_all,:IBU,:Color,:Clarity,:Fruity,:Favorite,:ProductExplain,:IBU_Style, :Comment)";
  $stmt = $dbh->prepare($sql);
  $params = array(':ProductID' => $NEXT_ProductID, ':MakerID' => $value['makerid'],':ProductName' => $value['productname'],
	':StyleID' => $value['styleid'], ':Alcohol' => $value['alcohol'],':IBU_all' => $value['ibu'],':IBU' => $value['ibu'],':Color' => $value['color'] ,':Clarity' => $value['clarity'] ,':Fruity' => $value['fruity'],':Favorite' => $value['favorite'],':ProductExplain' => $value['productexplain'],':IBU_Style' => $IBU_Style,':Comment' => $value['comment']);
  $res = $stmt->execute($params);

  // -------------------------------------------UPDATE products SET IBU_all --------------------------------
  $sql = "UPDATE products SET IBU_all=(select IBU_Style) where  IBU=0.000";
  $stmt = $dbh->prepare($sql);
  $res = $stmt->execute();
  
  // -------------------------------------------move temp file --------------------------------
  $image_name_http = $_POST['image_name'];
  //$image_rename = $NEXT_ProductID.'.'.pathinfo($image_name_http, PATHINFO_EXTENSION);
  $image_rename = $NEXT_ProductID.'.png';
  rename('../img/tmp/'.$image_name_http, '../img/product/'. $image_rename);
}



//-------------------------------------------------------------------------------------

//------------------------------SELECT FROM rate_user-----------------------------------------
$query = "SELECT MAX(Rate_userID) FROM rate_user";
$res = $dbh->query($query);
$arr_MAX_Rate_userID = $res->fetchAll(PDO::FETCH_ASSOC);
$MAX_Rate_userID = array_column($arr_MAX_Rate_userID,"MAX(Rate_userID)")[0];
$NEXT_Rate_userID =++$MAX_Rate_userID;
//-------------------------------------------------------------------------------------

// -------------------------------------------INSERT INTO rate_user --------------------------------
$sql = "INSERT INTO  rate_user VALUES(:Rate_userID,:ProductID,:UserID,:Color_user,:Clarity_user,:Fruity_user,:Favorite_user,:New_Update,:Comment)";
$stmt = $dbh->prepare($sql);
$params = array(':Rate_userID' => $NEXT_Rate_userID, ':ProductID' => $value['productid'],':UserID' => $value['userid'],
	':Color_user' => $value['color'], ':Clarity_user' => $value['clarity'],':Fruity_user' => $value['fruity'],':Favorite_user' => $value['favorite'] ,':New_Update' => $value['new_update'] ,':Comment' => $value['comment'] );
$res = $stmt->execute($params);
// --------------------------------------------------------------------------------------

?>
