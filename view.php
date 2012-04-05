<?php
include 'config.php'; //instance of $dropbox
if (isset($_GET['file'])) {
$file = x0rdecrypt($_GET['file'], $secretxorkey);
$response = $dropbox->filesGet($file);
// set headers and output the file
header('Content-type: '. $response['content_type']);
echo base64_decode($response['data']);
exit;
} else {
    echo "acces denied";
}

?>



