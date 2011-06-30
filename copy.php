<?php
include 'config.php'; //instance of $dropbox
$dest = 'test';//folder on your pc
if (isset($_GET['file'])) {
$dbfile = x0rdecrypt($_GET['file'], $secretxorkey) ;
$response = $dropbox->filesGet($dbfile);
$filename = array_pop(explode('/', $dbfile));
$file = base64_decode($response['data']);
$file_copy = fopen( './'.$dest.'/'.$filename,"wb");
fwrite($file_copy, $file);
fclose($file_copy);
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Message</title>
</head>
<h1>Status:</h1>
<p class="success">Success!</p>
<?php
echo '<a href="'.htmlentities($_SERVER['HTTP_REFERER']).'" title="back">back</a>';
?>
</html>


