<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<meta name='description' content="This site shows some craft beer">
<meta name='keywords' content="craft beer,beer,クラフトビール、ビール、IPA">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Darth Beer.com</title>
<link rel="stylesheet" href="../../../style.css">
<link rel="stylesheet" href="thank.css">
<?php
$value_http = $_POST['value_http'];
require('../../../common/sql_POST.php');
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>

<body>

<header>
<?php require(dirname(__FILE__).'/../../../common/header.php'); ?>
</header>

<div class='first_thank'>
<h2>
Thanks for submitting!
</h2>
<h3>
<a href="../../../index.php">Back to Home</a>
</h3>
</div>

<div id='main_wrap'>
<div id='main'>

<!--
<div class='sec1'>


</div> --> <!--  sec1  -->


</div> <!--  id=main  -->
</div> <!--  id=main_wrap  -->
</body>

</html>

