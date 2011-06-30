<?php
include 'dropbox.php';
include 'xorcrypt.php';
$consumerKey = 'yourKey';
$consumerSecret = 'yourSecret';
$secretxorkey = 'foreyesonly';
$dropbox = new Dropbox($consumerKey, $consumerSecret);
//$token = $dropbox->token('Email', 'PW');
$tokendb = 'token';
$tokendb_secret = 'token_secret'; 
$dropbox->setOAuthToken($tokendb);
$dropbox->setOAuthTokenSecret($tokendb_secret);
?>
