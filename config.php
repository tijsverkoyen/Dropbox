<?php
include 'dropbox.php';
include 'xorcrypt.php';
$consumerKey = 'yourKey';
$consumerSecret = 'yourSecret';
$secretxorkey = 'foreyesonly';
$dropbox = new Dropbox($consumerKey, $consumerSecret);
/* Use this to generate the token
$token = $dropbox->token('Email', 'PW');
echo $token['token'].'<br>';
echo $token['secret'];
read the variables and fill in the next lines
*/
$tokendb = 'token';
$tokendb_secret = 'token_secret'; 
$dropbox->setOAuthToken($tokendb);
$dropbox->setOAuthTokenSecret($tokendb_secret);
?>
