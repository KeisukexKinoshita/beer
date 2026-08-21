<html>
<meta charset="utf-8">
<h1>Beer</h1>
<?php
$comment = $_GET['comment'];
?>
<! -------------------------Sbumit -------------------------------->
<form action = "index.php" method = "GET">
<input type = "number" name = "comment"><br/>
<input type = "submit" value ="submit">
</form>
<! -------------------------Sbumit -------------------------------->
<?php
// -------------------------------------------Update database --------------------------------
$dbh = new PDO('mysql:dbname=beer;host=beer-rds.cqpmrauqnmy0.ap-northeast-1.rds.amazonaws.com','admin','REDACTED_DB_PASSWORD');
$sql = "UPDATE taste SET popularity = :popularity WHERE id = :id";
$stmt = $dbh->prepare($sql);
$params = array(':popularity' => $comment, ':id' => '0000');
$res = $stmt->execute($params);
// -------------------------------------------Update database --------------------------------

// -------------------------------------------Display the value--------------------------------
//$sql = "select * from taste where id=:id";
//$stmt = $dbh->prepare($sql);
//$params = array(':id' => '0000');
//$res = $stmt->execute($params);
//$data = $stmt->fetch();
//echo $data['brewer'].$data['name']." : ".$data['popularity']."<br />";
// -------------------------------------------Display the value--------------------------------

// -------------------------------------------Display the all value--------------------------------
$query = "SELECT * FROM taste";
$res = $dbh->query($query);
$data = $res->fetchALL(PDO::FETCH_ASSOC);
$json_array = json_encode($data);
//echo $json_array;
// -------------------------------------------Display the value--------------------------------
?>

<div>
<script>
let js_array = <?php echo $json_array; ?>
</script>

<script> 
var arr_brewer = [], arr_name = [], arr_hazy = [],arr_alcohol=[],arr_popularity=[],arr_url=[];
js_array.forEach(function(value) {
     arr_brewer.push(value['brewer'])
     arr_name.push(value['name'])
     arr_hazy.push(value['hazy'])
     arr_alcohol.push(value['alcohol'])
     arr_popularity.push(value['popularity'])
     arr_url.push(value['url'])
});
</script>

<!---------------------Graph----------------------------------------->
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.2.0/chart.min.js"
  integrity="sha512-VMsZqo0ar06BMtg0tPsdgRADvl0kDHpTbugCBBrL55KmucH6hP9zWdLIWY//OTfMnzz6xWQRxQqsUFefwHuHyg=="
  crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.0/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<div style="width:60%">
  <canvas id="mychart"></canvas>
</div>

<script type="text/javascript">
var comment_js = <?php echo $comment; ?>;
</script>
<script type="text/javascript" src="js/bubble.js"></script>
</div>
</body>
<!---------------------Graph----------------------------------------->
</html>
