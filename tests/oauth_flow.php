<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../dropbox.php';

$dbox = new Dropbox(APPLICATION_KEY, APPLICATION_SECRET);

// get token
$req_token = $dbox->oAuthRequestToken();

// get auth url
$auth_url = $dbox->oAuthAuthorizeURL($req_token['oauth_token']);

echo "Go here: ${auth_url}\n";
echo "Come back when you've approved and hit ENTER:\n";
$input = fgets(STDIN); // waits for ENTER

// try to get access token
$dbox->setOauthToken($req_token['oauth_token']);
$dbox->setOauthTokenSecret($req_token['oauth_token_secret']);
$access_tok = $dbox->oAuthAccessToken($req_token['oauth_token']);

if (!$access_tok) {
	echo "request failed for some reason.\n";
	exit();
}

// test out access token
echo "Got access token; retrieving user {$access_tok['uid']} info...\n";
$dbox->setOauthToken($access_tok['oauth_token']);
$dbox->setOauthTokenSecret($access_tok['oauth_token_secret']);
$acc_info = $dbox->accountInfo();

print_r($acc_info);
exit();