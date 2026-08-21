<?php
$baseurl='/var/www/html';
$absolute =dirname(__FILE__);
$relative = str_replace($baseurl,'',$absolute);
?>

<script src="https://code.jquery.com/jquery-2.2.4.js" integrity="sha256-iT6Q9iMJYuQiMWNd9lDyBUStIq/8PuOW33aOqmvFpqI=" crossorigin="anonymous"></script>
   <script>
   $(function() {
      const hum = $('#hamburger, .close')
      const nav = $('.sp-nav')
      hum.on('click', function(){
         nav.toggleClass('toggle');
      });
   });
   </script>

<a href="<?php echo $relative ; ?>/../index.php"><h1>Darth Beer.com</h1></a>
<nav class="pc-nav">
<ul>
	<li><a href="<?php echo $relative ; ?>/../beer/products.php">ビール</a></li>
	<li><a href="<?php echo $relative ; ?>/../brewery/makers.php">ブリュワリー</a></li>
	<!-- <li><a href="<?php echo $relative ; ?>/../style/styles.php">スタイル</a></li> -->
	<li><a href="<?php echo $relative ; ?>/../post/add_product.php">ビールを投稿</a></li>
</ul>
</nav>
<nav class="sp-nav">
   <ul>
	<li><a href="<?php echo $relative ; ?>/../beer/products.php">ビール</a></li>
	<li><a href="<?php echo $relative ; ?>/../brewery/makers.php">ブリュワリー</a></li>
	<!-- <li><a href="<?php echo $relative ; ?>/../style/styles.php">スタイル</a></li> -->
	<li><a href="<?php echo $relative ; ?>/../post/add_product.php">ビールを投稿</a></li>
      <li class="close"><span>閉じる</span></li>
   </ul>
 </nav>
<div id="hamburger">
      <span></span>
</div>
