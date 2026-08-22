<?php
require_once dirname(__FILE__).'/../db_config.local.php';
// -------------------------------------------Update database --------------------------------
//$dbh = new PDO($db_dsn, $db_user, $db_pass);
//$sql = "UPDATE taste SET popularity = :popularity WHERE id = :id";
//$stmt = $dbh->prepare($sql);
//$params = array(':popularity' => $comment, ':id' => '0000');
//$res = $stmt->execute($params);
// -------------------------------------------Update database --------------------------------
//$src_prd='';
// -------------------------------------------SELECT FROM products--------------------------------
if ($src_prd=='no'){
 // not execute SELECT
}else{

$dbh = new PDO($db_dsn, $db_user, $db_pass);
$query = "SELECT * FROM products WHERE $sql_where";
$res = $dbh->query($query);
$prd= $res->fetchALL(PDO::FETCH_ASSOC);
$json_prd = json_encode($prd);


$prd_ProductID= array_column($prd,"ProductID");
$prd_MakerID= array_column($prd,"MakerID");
$prd_ProductName= array_column($prd,"ProductName");
$prd_StyleID= array_column($prd,"StyleID");
$prd_Alcohol= array_column($prd,"Alcohol");
$prd_IBU= array_column($prd,"IBU_all");
$prd_Color= array_column($prd,"Color");
$prd_Clarity= array_column($prd,"Clarity");
$prd_Fruity= array_column($prd,"Fruity");
$prd_Favorite= array_column($prd,"Favorite");
$prd_ProductExplain= array_column($prd,"ProductExplain");
$prd_IBU_Style= array_column($prd,"IBU_Style");



}

// ---------------------------------------------------------------------------------------------

// -------------------------------------------SELECT FROM maker--------------------------------
if ($src_prd=='no'){
$dbh = new PDO($db_dsn, $db_user, $db_pass);
$query = "SELECT * FROM maker WHERE $sql_where_mak"; // only condition of WHERE is fiffarent from the else. 
$res = $dbh->query($query);
$mak = $res->fetchALL(PDO::FETCH_ASSOC);
$json_mak = json_encode($mak);

$mak_MakerID= array_column($mak,"MakerID");
$mak_MakerName= array_column($mak,"MakerName");
$mak_MakerExplain= array_column($mak,"MakerExplain");
$mak_URL1= array_column($mak,"URL1");
}else{
$dbh = new PDO($db_dsn, $db_user, $db_pass);
$query = "SELECT * FROM maker WHERE $sql_where";
$res = $dbh->query($query);
$mak = $res->fetchALL(PDO::FETCH_ASSOC);
$json_mak = json_encode($mak);

$mak_MakerID= array_column($mak,"MakerID");
$mak_MakerName= array_column($mak,"MakerName");
$mak_MakerExplain= array_column($mak,"MakerExplain");
$mak_URL1= array_column($mak,"URL1");
}
// ---------------------------------------------------------------------------------------------

// -------------------------------------------SELECT FROM style--------------------------------
if ($src_sty=='yes'){
$dbh = new PDO($db_dsn, $db_user, $db_pass);
$query = "SELECT * FROM style WHERE $sql_where_sty"; // only condition of WHERE is fiffarent from the else. 
$res = $dbh->query($query);
$sty = $res->fetchALL(PDO::FETCH_ASSOC);
$json_sty = json_encode($sty);

$sty_StyleID= array_column($sty,"StyleID");
$sty_StyleName= array_column($sty,"StyleName");
$sty_StyleIBU= array_column($sty,"IBU");
}
// ---------------------------------------------------------------------------------------------

// -------------------------------------------SELECT FROM clarity--------------------------------
if ($src_cla=='yes'){
$dbh = new PDO($db_dsn, $db_user, $db_pass);
$query = "SELECT * FROM clarity WHERE $sql_where_cla"; // only condition of WHERE is fiffarent from the else. 
$res = $dbh->query($query);
$cla = $res->fetchALL(PDO::FETCH_ASSOC);
$json_cla = json_encode($cla);

$cla_ClarityID= array_column($cla,"ClarityID");
$cla_ClarityValue= array_column($cla,"ClarityValue");
$cla_ClarityName= array_column($cla,"ClarityName");
}
// ---------------------------------------------------------------------------------------------

// -------------------------------------------SELECT FROM fruity--------------------------------
if ($src_fru=='yes'){
$dbh = new PDO($db_dsn, $db_user, $db_pass);
$query = "SELECT * FROM fruity WHERE $sql_where_fru"; // only condition of WHERE is fiffarent from the else. 
$res = $dbh->query($query);
$fru = $res->fetchALL(PDO::FETCH_ASSOC);
$json_fru = json_encode($fru);

$fru_FruityID= array_column($fru,"FruityID");
$fru_FruityValue= array_column($fru,"FruityValue");
$fru_FruityName= array_column($fru,"FruityName");
}
// ---------------------------------------------------------------------------------------------
?>

<script>
// product
let js_prd = <?php if($json_prd){echo $json_prd;}else{echo '0';} ?>;	 
var prd_ProductID=[], prd_MakerID = [], prd_ProductName = [], prd_StyleID=[], prd_Alcohol = [], prd_IBU=[], prd_Color=[], prd_Clarity=[], prd_Fruity=[], prd_Favorite=[];
js_prd.forEach(function(value) {
     prd_ProductID.push(value['ProductID'])
     prd_MakerID.push(value['MakerID'])
     prd_ProductName.push(value['ProductName'])
     prd_StyleID.push(value['StyleID'])
     prd_Alcohol.push(value['Alcohol'])
     prd_IBU.push(value['IBU_all'])
     prd_Color.push(value['Color'])
     prd_Clarity.push(value['Clarity'])
     prd_Fruity.push(value['Fruity'])
     prd_Favorite.push(value['Favorite'])
});

//maker
let js_mak = <?php if($json_mak){echo $json_mak;}else{echo '0';} ?>;	 
var mak_MakerID=[], mak_MakerName=[];
js_mak.forEach(function(value) {
     mak_MakerID.push(value['MakerID'])
     mak_MakerName.push(value['MakerName'])
});

//style
let js_sty = <?php if($json_sty){echo $json_sty;}else{echo '0';} ?>;	 
var sty_StyleID=[], sty_StyleName=[], sty_StyleIBU=[];
js_sty.forEach(function(value) {
   sty_StyleID.push(value['StyleID'])
   sty_StyleName.push(value['StyleName'])
   sty_StyleIBU.push(value['StyleIBU'])
});

//clarity
let js_cla = <?php if($json_cla){echo $json_cla;}else{echo '0';} ?>;	 
var cla_ClarityID=[], cla_ClarityValue=[], cla_ClarityName=[];
js_cla.forEach(function(value) {
   cla_ClarityID.push(value['ClarityID'])
   cla_ClarityValue.push(value['ClarityValue'])
   cla_ClarityName.push(value['ClarityName'])
});

//fruity
let js_fru = <?php if($json_fru){echo $json_fru;}else{echo '0';} ?>;	 
var fru_FruityID=[], fru_FruityValue=[], fru_FruityName=[];
js_fru.forEach(function(value) {
   fru_FruityID.push(value['FruityID'])
   fru_FruityValue.push(value['FruityValue'])
   fru_FruityName.push(value['FruityName'])
});

</script>
