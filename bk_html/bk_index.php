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
$sql = "select * from taste where id=:id";
$stmt = $dbh->prepare($sql);
$params = array(':id' => '0000');
$res = $stmt->execute($params);
$data = $stmt->fetch();
echo $data['brewer'].$data['name']." : ".$data['popularity']."<br />";
// -------------------------------------------Display the value--------------------------------

?>
<!---------------------Graph----------------------------------------->
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.2.0/chart.min.js"
  integrity="sha512-VMsZqo0ar06BMtg0tPsdgRADvl0kDHpTbugCBBrL55KmucH6hP9zWdLIWY//OTfMnzz6xWQRxQqsUFefwHuHyg=="
  crossorigin="anonymous"></script>
<script
  src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@next/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
</head>
<div style="width:400px">
  <canvas id="mychart"></canvas>
</div>
<script>
let comment_js = <?php echo $comment; ?>;
var ctx = document.getElementById('mychart');
var x_commment = 3;
var myChart = new Chart(ctx, {
  type: 'bubble',
  data: {
    datasets: [{
      label: 'Dataset#1',
      data: [
        { x:3, y: 3, r:5 },
        { x:4, y: 4, r:6 },
        { x:5, y: 5, r:8 },
      ],
      backgroundColor: '#f88',
    }],
  },
  options: {
    scales: {
      y: { min: 0, max: 10 },
      x: { min: 0, max: 10 },
    },
  },
});
//cosole.log(hello);
//cosole.log(myChart.data.datasets[0].data[0][0]);
function updateChart(){
  // Update chart
  myChart.data.datasets[0].data[0].r = comment_js;
  myChart.update();
}
updateChart();
</script>
</div>
</body>
<!---------------------Graph----------------------------------------->
</html>
