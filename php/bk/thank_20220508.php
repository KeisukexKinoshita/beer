<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<title>Submited</title>
<link rel="stylesheet" href="../css/thank.css">
</head>

<?php
$value_http = $_POST['value_http'];
require('sql_POST.php');
print_r($value_http);


?>


<body>

<div class='top'>
<h1>Thank you!</h1>
</div>

<div class='main'>
<a href="../index.php">Back to Home</a>
</div>

