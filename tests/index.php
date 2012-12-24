<?php

//require
require_once '../../../autoload.php';
require_once 'config.php';

use \TijsVerkoyen\Dropbox\Dropbox;

// create instance
$dropbox = new Dropbox(APPLICATION_KEY, APPLICATION_SECRET);

// The code below will do the oAuth-dance
//$response = $dropbox->oAuthRequestToken();
//if(!isset($_GET['authorize'])) $dropbox->oAuthAuthorize($response['oauth_token'], 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] .'?authorize=true');
//else $response = $dropbox->oAuthAccessToken($_GET['oauth_token']);
//$response = $dropbox->token(EMAIL, PASSWORD);
//var_dump($response);
//exit;

$dropbox->setOAuthToken(TOKEN);
$dropbox->setOAuthTokenSecret(TOKEN_SECRET);

try {
    $response = $dropbox->account(time() . '-dropbox@verkoyen.eu', PASSWORD, 'Tijs', 'Verkoyen');
    $response = $dropbox->accountInfo();
    $response = $dropbox->filesGet(BASE_PATH .'hàh@, $.txt');
    $response = $dropbox->filesPost(BASE_PATH, realpath('../dropbox.php'));
    $response = $dropbox->filesPost(BASE_PATH .'with spaces', realpath('../dropbox.php'));
    $response = $dropbox->filesPost(BASE_PATH .'met spaties/', realpath('/Users/tijs/Projects/dropbox/tests/with spaces.txt'));
    $response = $dropbox->metadata(BASE_PATH .'met spaties');
    $response = $dropbox->thumbnails(BASE_PATH .'image.png');

    $response = $dropbox->fileopsCopy(BASE_PATH . 'image.png', BASE_PATH . 'copy_' . time());
    $response = $dropbox->fileopsCreateFolder(BASE_PATH .'created_'. time());
    $response = $dropbox->fileopsDelete(BASE_PATH .'will_be_deleted');
    $response = $dropbox->fileopsMove(BASE_PATH .'will_be_moved', BASE_PATH .'moved_'. time());
} catch (Exception $e) {
    var_dump($e);
}

// output
var_dump($response);
