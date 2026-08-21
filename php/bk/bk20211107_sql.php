<?php
// -------------------------------------------Update database --------------------------------
//$dbh = new PDO('mysql:dbname=beer;host=beer-rds.cqpmrauqnmy0.ap-northeast-1.rds.amazonaws.com','admin','REDACTED_DB_PASSWORD');
//$sql = "UPDATE taste SET popularity = :popularity WHERE id = :id";
//$stmt = $dbh->prepare($sql);
//$params = array(':popularity' => $comment, ':id' => '0000');
//$res = $stmt->execute($params);
// -------------------------------------------Update database --------------------------------

// -------------------------------------------Display the all value--------------------------------
$dbh = new PDO('mysql:dbname=beer;host=beer-rds.cqpmrauqnmy0.ap-northeast-1.rds.amazonaws.com','admin','REDACTED_DB_PASSWORD');
$query = "SELECT * FROM taste where $sql_where";
$res = $dbh->query($query);
$data = $res->fetchALL(PDO::FETCH_ASSOC);
$json_array = json_encode($data);
//echo $json_array;

$data_ID= array_column($data,"ID");
$data_BREWERY= array_column($data,"BREWERY");
$data_NAME= array_column($data,"NAME");
$data_STYLE= array_column($data,"STYLE");
$data_COLOR= array_column($data,"COLOR");
$data_CLARITY= array_column($data,"CLARITY");
$data_ALCOHOL= array_column($data,"ALCOHOL");
$data_IBU= array_column($data,"IBU");
$data_FURITY= array_column($data,"FURITY");
$data_POPULARITY= array_column($data,"POPULARITY");
$data_URL= array_column($data,"URL");


// -------------------------------------------Display the value--------------------------------
?>

<div>
<script>
let js_array = <?php echo $json_array; ?>
</script>

<script> 
var arr_ID=[], arr_BREWERY = [], arr_NAME = [], arr_STYLE=[], arr_COLOR = [], arr_CLARITY=[], arr_ALCOHOL=[], arr_IBU=[], arr_FURITY=[], arr_POPULARITY=[],arr_URL=[];
js_array.forEach(function(value) {
     arr_BREWERY.push(value['BREWERY'])
     arr_ID.push(value['ID'])
     arr_NAME.push(value['NAME'])
     arr_STYLE.push(value['STYLE'])
     arr_COLOR.push(value['COLOR'])
     arr_CLARITY.push(value['CLARITY'])
     arr_ALCOHOL.push(value['ALCOHOL'])
     arr_IBU.push(value['IBU'])
     arr_FURITY.push(value['FURITY'])
     arr_POPULARITY.push(value['POPULARITY'])
     arr_URL.push(value['URL'])
});
</script>

