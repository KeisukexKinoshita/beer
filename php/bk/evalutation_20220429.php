<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<?php
$sql_where = "''=''";
require('sql.php');

$product_id = $_GET['product_id'];
$keyIndex = array_search($product_id,$prd_ProductID);
$rate=[];

?>

<html>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<!-- <script src="../js/base.js"></script> -->
 <head>
<meta charset="utf-8">
<title>Evaluation</title>
<link rel="stylesheet" href="../css/evaluation.css">
</head>

<body>

<div class='top'>
<h1>Evaluation</h1>
</div>

<p class='p1'>
You can post the evaluation for <?php echo $prd_ProductName[$keyIndex] ?>
</p>

<! -------------------------Sbumit -------------------------------->
 <form action = "check.php" method = "POST">

<input type = "hidden" name = "ProductID" value= "<?php echo $product_id ?>" ><br/>
<input type = "hidden" name = "UserID" value='us0001'> <br/>
<input type = "hidden" name = "New_Update" value='update'> <br/>
<input type = "hidden" name = "ProductExplain" value='NoExplain'> <br/>

Color
<!-- <input type = "number" name = "rate[Color_user]"><br/> -->
<ul class="image_list">
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color1.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=1><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color2.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=2><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color3.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=3><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color4.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=4><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color5.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=5><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color6.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=6><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color7.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=7><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color8.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=8><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color9.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=9><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Color10.png" alt="test" />
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" value=10><br/>
    </div>
  </li>
</ul>
<script>
var class_color='disabled_checkbox_color'
</script>
<script src="../js/check_control.js"></script>


Clarity 
<!-- <input type = "number" name = "rate[Clarity_user]"><br/> -->
<ul class="image_list">
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Clarity1.png" alt="test" />
      <input class ="disabled_checkbox_clarity" type = "checkbox" name = "chk_clarity" value=1><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Clarity2.png" alt="test" />
      <input class ="disabled_checkbox_clarity" type = "checkbox" name = "chk_clarity" value=2><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Clarity3.png" alt="test" />
      <input class ="disabled_checkbox_clarity" type = "checkbox" name = "chk_clarity" value=3><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Clarity4.png" alt="test" />
      <input class ="disabled_checkbox_clarity" type = "checkbox" name = "chk_clarity" value=4><br/>
    </div>
  </li>
</ul>
<script>
var class_clarity='disabled_checkbox_clarity'
</script>
<script src="../js/check_control.js"></script>

Fruity 
<!-- <input type = "number" name = "rate[Fruity_user]"><br/> -->
<ul class="image_list">
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Fruity1.png" alt="test" />
      <input class ="disabled_checkbox_fruity" type = "checkbox" name = "chk_fruity" value=1><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Fruity2.png" alt="test" />
      <input class ="disabled_checkbox_fruity" type = "checkbox" name = "chk_fruity" value=2><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Fruity3.png" alt="test" />
      <input class ="disabled_checkbox_fruity" type = "checkbox" name = "chk_fruity" value=3><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Fruity4.png" alt="test" />
      <input class ="disabled_checkbox_fruity" type = "checkbox" name = "chk_fruity" value=4><br/>
    </div>
  </li>
</ul>
<script>
var class_fruity='disabled_checkbox_fruity'
</script>
<script src="../js/check_control.js"></script>

Favorite 
<!-- <input type = "number" name = "rate[Favorite_user]"><br/> -->
<ul class="image_list">
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Favorite1.png" alt="test" />
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" value=1><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Favorite2.png" alt="test" />
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" value=2><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Favorite3.png" alt="test" />
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" value=3><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Favorite4.png" alt="test" />
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" value=4><br/>
    </div>
  </li>
  <li>
    <div class="image_box">
      <img class="thumbnail" src="../img/Favorite5.png" alt="test" />
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" value=5><br/>
    </div>
  </li>
</ul>

Comment
<ul class="image_list">
<input type="text" id="name" name="comment" required minlength="1" maxlength="255" size="50">
</ul>

<script>
var class_favorite='disabled_checkbox_favorite'
</script>
<script src="../js/check_control.js"></script>

<p> <input type = "submit" value ="submit"> </p>
</form>
<! -------------------------Sbumit -------------------------------->



</body>



