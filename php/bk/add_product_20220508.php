<!DOCTYPE html>
<html lang="ja">
 <head>
<meta charset="utf-8">
<title>Post a new beer</title>
<link rel="stylesheet" href="../css/add_product.css">
</head>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<?php
//$chk_maker = $_POST['chk_maker'];
//$chk_style = $_POST['chk_style'];

//$sql_where = "MakerID="."'".$chk_maker."'";
//$sql_where_sty = "StyleID="."'".$chk_style."'";
$sql_where = "''=''";
$sql_where_sty = "''=''";
$src_sty = "yes";

require('sql.php');

?>

<body>
<div class='top'>
<h1> Post a new beer </h1>
</div>

<p class='p1'>
   <?php echo nl2br($mak_MakerExplain[$keyIndex_mak]) ?> 
</p>

<! -------------------------Sbumit -------------------------------->
 <form action = "check.php" method = "POST" enctype="multipart/form-data">

<input type = "hidden" name = "ProductID" value= "<?php echo $product_id ?>" >
<input type = "hidden" name = "UserID" value='us0001'>
<input type = "hidden" name = "New_Update" value='new'> 

<ul class="image_list">
<p>Brewery<sup>※</sup></p>
<select name='chk_MakerID' required placeholder="Enter a beer name">
<option class='hidden' hidden>Chose one</option>
<?php
for($i = 0 ; $i < count($mak_MakerID); $i++){
   echo '<option value=';
   echo $mak_MakerID[$i];
   echo ' >';
   echo $mak_MakerName[$i];
   echo '</option>';
}
?>
</select>
</ul>

<ul class="image_list">
<p>Beer Name<sup>※</sup></p>
<input type="text" id="name" name="chk_productname" required row="1" minlength="1" maxlength="255" size="50" placeholder="ex) Beer1"></textarea>
</ul>

<ul class="image_list">
<p>Beer Style<sup>※</sup></p>
<select name='chk_StyleID' required >
<option hidden>Chose one</option>
<?php
for($i = 0 ; $i < count($sty_StyleID); $i++){
   echo '<option value=';
   echo $sty_StyleID[$i];
   echo ' >';
   echo $sty_StyleName[$i];
   echo '</option>';
}
?>
</select>
</ul>

<ul class="image_list">
<p>Alcohol<sup>※</sup></p>
<input type="number" step="0.01" id="name" name="chk_alcohol" required minlength="1" maxlength="255" size="50" placeholder="ex) 5.5" ><span> %</span>
</ul>

<ul class="image_list">
<p>IBU</p>
<input type="number" step="0.01" id="name" name="chk_ibu" minlength="1" maxlength="255" size="50" placeholder="ex) 30"><span> IBU</span>
</ul>

<ul class="image_list">
<p>Color<sup>※</sup></p>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" checked name = "chk_color" id = "chk_color_1"value=1><br/>
      <label for="chk_color_1"> <img class="thumbnail" src="../img/color/Color1.png" alt="test" /></label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_2"value=2><br/>
      <label for="chk_color_2"> <img class="thumbnail" src="../img/color/Color2.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_3"value=3><br/>
      <label for="chk_color_3"> <img class="thumbnail" src="../img/color/Color3.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_4"value=4><br/>
      <label for="chk_color_4"> <img class="thumbnail" src="../img/color/Color4.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_5"value=5><br/>
      <label for="chk_color_5"> <img class="thumbnail" src="../img/color/Color5.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_6"value=6><br/>
      <label for="chk_color_6"> <img class="thumbnail" src="../img/color/Color6.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_7"value=7><br/>
      <label for="chk_color_7"> <img class="thumbnail" src="../img/color/Color7.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_8"value=8><br/>
      <label for="chk_color_8"> <img class="thumbnail" src="../img/color/Color8.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_9"value=9><br/>
      <label for="chk_color_9"> <img class="thumbnail" src="../img/color/Color9.png" alt="test" /> </label>
    </div>
  </li>

  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_color" type = "checkbox" name = "chk_color" id = "chk_color_10"value=10><br/>
      <label for="chk_color_10"> <img class="thumbnail" src="../img/color/Color10.png" alt="test" /> </label>
    </div>
  </li>

</ul>
<script>
var class_color='disabled_checkbox_color'
</script>
<script src="../js/check_control.js"></script>

<ul class="image_list">
<p>Clarity<sup>※</sup></p>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_clarity" type = "checkbox"  checked name = "chk_clarity[value]" id = "chk_clarity_1"value=1><br/>
      <input type="hidden" name="chk_clarity[explain]" value="Brilliant" />
      <label for="chk_clarity_1"> 
       <img class="thumbnail" src="../img/clarity/Clarity1.png" alt="test" />
       <p>Brilliant</p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_clarity" type = "checkbox" name = "chk_clarity[value]" id = "chk_clarity_2"value=2><br/>
      <input type="hidden" name="chk_clarity[explain]" value="Clear" />
      <label for="chk_clarity_2"> 
       <img class="thumbnail" src="../img/clarity/Clarity2.png" alt="test" />
       <p>Clear</p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_clarity" type = "checkbox" name = "chk_clarity[value]" id = "chk_clarity_3"value=3><br/>
      <input type="hidden" name="chk_clarity[explain]" value="Slight Haze" />
      <label for="chk_clarity_3"> 
       <img class="thumbnail" src="../img/clarity/Clarity3.png" alt="test" />
       <p>Slight Haze</p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_clarity" type = "checkbox" name = "chk_clarity[value]" id = "chk_clarity_4"value=4><br/>
      <input type="hidden" name="chk_clarity[explain]" value="Hazy" />
      <label for="chk_clarity_4"> 
       <img class="thumbnail" src="../img/clarity/Clarity4.png" alt="test" />
       <p>Hazy</p>
     </label>
    </div>
  </li>
</ul>
<script>
var class_clarity='disabled_checkbox_clarity'
</script>
<script src="../js/check_control.js"></script>

<ul class="image_list">
<p>Fruity<sup>※</sup></p>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_fruity" type = "checkbox" checked  name = "chk_fruity[value]" id = "chk_fruity_1"value=1><br/>
      <input type="hidden" name="chk_fruity[explain]" value="Not Fruity" />
      <label for="chk_fruity_1"> 
       <img class="thumbnail" src="../img/fruity/Fruity1.png" alt="test" />
       <p>Not Fruity</p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_fruity" type = "checkbox" name = "chk_fruity[value]" id = "chk_fruity_2"value=2><br/>
      <input type="hidden" name="chk_fruity[explain]" value="Bit Fruity" />
      <label for="chk_fruity_2"> 
       <img class="thumbnail" src="../img/fruity/Fruity2.png" alt="test" />
       <p>Bit Fruity</p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_fruity" type = "checkbox" name = "chk_fruity[value]" id = "chk_fruity_3"value=3><br/>
      <input type="hidden" name="chk_fruity[explain]" value="Fruity Beer" />
      <label for="chk_fruity_3"> 
       <img class="thumbnail" src="../img/fruity/Fruity3.png" alt="test" />
       <p>Fruity Beer</p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_fruity" type = "checkbox" name = "chk_fruity[value]" id = "chk_fruity_4"value=4><br/>
      <input type="hidden" name="chk_fruity[explain]" value="Almost Smoothie" />
      <label for="chk_fruity_4"> 
       <img class="thumbnail" src="../img/fruity/Fruity4.png" alt="test" />
       <p>Almost Smoothie</p>
     </label>
    </div>
  </li>
</ul>
<script>
var class_fruity='disabled_checkbox_fruity'
</script>
<script src="../js/check_control.js"></script>

<ul class="image_list">
<p>Favorite<sup>※</sup></p>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_favorite" type = "checkbox" checked  name = "chk_favorite" id = "chk_favorite_1"value=1><br/>
      <label for="chk_favorite_1"> 
       <img class="thumbnail" src="../img/favorite/Favorite1.png" alt="test" />
       <p></p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" id = "chk_favorite_2"value=2><br/>
      <label for="chk_favorite_2"> 
       <img class="thumbnail" src="../img/favorite/Favorite2.png" alt="test" />
       <p></p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" id = "chk_favorite_3"value=3><br/>
      <label for="chk_favorite_3"> 
       <img class="thumbnail" src="../img/favorite/Favorite3.png" alt="test" />
       <p></p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" id = "chk_favorite_4"value=4><br/>
      <label for="chk_favorite_4"> 
       <img class="thumbnail" src="../img/favorite/Favorite4.png" alt="test" />
       <p></p>
     </label>
    </div>
  </li>
  <li>
    <div class="image_box">
      <input class ="disabled_checkbox_favorite" type = "checkbox" name = "chk_favorite" id = "chk_favorite_5"value=5><br/>
      <label for="chk_favorite_5"> 
       <img class="thumbnail" src="../img/favorite/Favorite5.png" alt="test" />
       <p></p>
     </label>
    </div>
  </li>
</ul>
<script>
var class_favorite='disabled_checkbox_favorite'
</script>
<script src="../js/check_control.js"></script>

<ul class="image_list">
<p>Comment</p>
<textarea id="name" name="comment" rows="2" minlength="1" maxlength="255" size="50" placeholder="Please enter a comment"></textarea>
</ul>

<ul class="image_list">
<p>Explanation</p>
<textarea id="name" name="ProductExplain" rows="2" minlength="1" maxlength="255" size="50" placeholder="Please enter a product explanation"></textarea>
</ul>

<ul class="image_list">
<p>Photo</p>
<label class="upimg_label" for="upimg"> <input type="file" id="upimg" name="upimg">Select file</label>
<span class="msgimg">File is not selected</span>
</ul>
<script src="../js/upload_limit.js"></script>
<script src="../js/upimg_filename.js"></script>

<div class="submit"> <input type = "submit" value ="Next"> </div>
</form>
<! -------------------------Sbumit -------------------------------->


</body>



