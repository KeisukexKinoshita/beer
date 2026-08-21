<?php
$baseurl='/var/www/html';
$absolute =dirname(__FILE__);
$relative = str_replace($baseurl,'',$absolute);
?>

<a href="<?php echo $relative ; ?>/../index.php"><h1>Darth Beer.com</h1></a>
<nav class="pc-nav">
<ul>
<li><a href="<?php echo $relative ; ?>/makers.php">ブリュワリー</a></li>
<li><a href="<?php echo $relative ; ?>/add_product.php">新しいビールを投稿する</a></li>
</ul>
</nav>
