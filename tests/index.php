<?php

//require
require_once '../../../autoload.php';
require_once 'config.php';
session_start();

use \TijsVerkoyen\Dropbox\Dropbox;

// create instance
$dropbox = new Dropbox(APPLICATION_KEY, APPLICATION_SECRET);

// The code below will do the oAuth-dance
//if (!isset($_GET['authorize'])) {
//  $response = $dropbox->oAuthRequestToken();
//  $_SESSION['oauth_token_secret'] = $response['oauth_token_secret'];
//  $dropbox->oAuthAuthorize($response['oauth_token'], 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] .'?authorize=true');
//} else {
//  $dropbox->setOAuthTokenSecret($_SESSION['oauth_token_secret']);
//  $response = $dropbox->oAuthAccessToken($_GET['oauth_token']);
//}
//var_dump($response);
//exit;

$dropbox->setOAuthToken(TOKEN);
$dropbox->setOAuthTokenSecret(TOKEN_SECRET);

try {
//  $response = $dropbox->accountInfo();
//
//  $response = $dropbox->filesGet(BASE_PATH .'hàh@, $.txt');
//  $response = $dropbox->filesPost(BASE_PATH .'with spaces/', realpath(dirname(__FILE__)) . '/with spaces.txt', true);
//
//  $response = $dropbox->metadata(BASE_PATH .'met spaties');
//  $response = $dropbox->delta();
//  $response = $dropbox->revisions(BASE_PATH .'hàh@, $.txt');
//  $response = $dropbox->restore(BASE_PATH .'hàh@, $.txt', '368c7df600088e34');
//  $response = $dropbox->search(BASE_PATH, 'txt');
//  $response = $dropbox->thumbnails(BASE_PATH .'image.png');
//
//  $response = $dropbox->fileopsCopy(BASE_PATH . 'image.png', BASE_PATH . 'copy_' . time());
//  $response = $dropbox->fileopsCreateFolder(BASE_PATH .'created_'. time());
//  $response = $dropbox->fileopsDelete(BASE_PATH .'will_be_deleted');
//  $response = $dropbox->fileopsMove(BASE_PATH .'will_be_moved', BASE_PATH .'moved_'. time());
} catch (Exception $e) {
  var_dump($e);
}

// output
var_dump($response);
