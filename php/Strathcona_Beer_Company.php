<html>
 <head>
<meta charset="utf-8">
<title>Strathcona Beer Company</title>
<link rel="stylesheet" href="../css/brewery.css">
</head>

<body>

<div class='top'>
<h1>Strathcona Beer Company</h1>
</div>

<p class='p1'>
  Strathcona Beer Company is ...
</p>


<div class='chart'>
<?php
$sql_where = "BREWERY='Strathcona Beer Company'";
require('sql.php');
?>

<meta name="viewport" content="width=device-width,initial-scale=1.0">
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.2.0/chart.min.js"
  integrity="sha512-VMsZqo0ar06BMtg0tPsdgRADvl0kDHpTbugCBBrL55KmucH6hP9zWdLIWY//OTfMnzz6xWQRxQqsUFefwHuHyg=="
  crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.0/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<div style="position: relative,width:60%,hight:40%">
  <canvas id="mychart"></canvas>

<script type="text/javascript">
//var comment_js = <?php echo $comment; ?>;
</script>
<script type="text/javascript" src="../js/bubble.js"></script>
</div>

</body>



