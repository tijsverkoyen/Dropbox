<?php

// require
require_once 'config.php';
require_once '../dropbox.php';

// create instance
$dropbox = new Dropbox(APPLICATION_KEY, APPLICATION_SECRET);
$dropbox->setOAuthToken(TOKEN);
$dropbox->setOAuthTokenSecret(TOKEN_SECRET);

// oAuth dance
//$response = $dropbox->oAuthRequestToken();
//if(!isset($_GET['authorize'])) $dropbox->oAuthAuthorize($response['oauth_token'], 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] .'?authorize=true');
//else $response = $dropbox->oAuthAccessToken($_GET['oauth_token']);
//$response = $dropbox->token(EMAIL, PASSWORD);

//$response = $dropbox->account(time() . '-dropbox@verkoyen.eu', PASSWORD, 'Tijs', 'Verkoyen');
//$response = $dropbox->accountInfo();

//$response = $dropbox->filesGet(BASE_PATH .'hàh@, $.txt');
//$response = $dropbox->filesPost(BASE_PATH, realpath('../dropbox.php'));
//$response = $dropbox->filesPost(BASE_PATH .'with spaces', realpath('../dropbox.php'));
//$response = $dropbox->filesPost(BASE_PATH .'met spaties/', realpath('/Users/tijs/Projects/dropbox/tests/with spaces.txt'));
//$response = $dropbox->metadata(BASE_PATH .'met spaties');
//$response = $dropbox->thumbnails(BASE_PATH .'image.png');

//$response = $dropbox->fileopsCopy(BASE_PATH . 'image.png', BASE_PATH . 'copy_' . time());
//$response = $dropbox->fileopsCreateFolder(BASE_PATH .'created_'. time());
//$response = $dropbox->fileopsDelete(BASE_PATH .'will_be_deleted');
//$response = $dropbox->fileopsMove(BASE_PATH .'will_be_moved', BASE_PATH .'moved_'. time());

// output (Spoon::dump())
ob_start();
var_dump($response);
$output = ob_get_clean();

// cleanup the output
$output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

// print
echo '<pre>' . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . '</pre>';
