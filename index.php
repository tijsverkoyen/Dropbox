<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Zugriff auf die Dropbox mit PHP</title>
<link rel="stylesheet" type="text/css" href="css/base.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="js/base.js"></script>
</head>
<h1>Dropboxzugriff mit PHP</h1>
<?php
include 'config.php'; //instance of $dropbox

function DBSort($root)
{
global $dropbox;
$response = $dropbox->metadata($root);
$count = count($response['contents']);
$isf_dir = array();
  for($i = 0; $i < $count; $i++)
  {
    $sort_1[] = $response['contents'][$i]['is_dir'];
    $isf_dir[$i]= array();
    $isf_dir[$i]['path'] = $response['contents'][$i]['path'];
    if($response['contents'][$i]['is_dir']){
        $isf_dir[$i]['dir'] = DBSort($response['contents'][$i]['path']);
    }
   array_multisort($sort_1,SORT_DESC, $isf_dir);
  }
return $isf_dir;
}

$root = 'iSefrengo_store/plugin_module/plugin_isef/DropBox';
$dbMedia = DBSort($root);


function DBRender($dbSortArray){
    global $secretxorkey;
    $retval = "";
    if(!empty($dbSortArray)){
        $retval .= '<ul>'."\n";
        foreach($dbSortArray as $arrayElement){
            if(empty($arrayElement['dir'])){
		$path = $arrayElement['path'];
		$pathfile = end(explode("/", $path));
                $retval .= '<li class="pg">'.$pathfile."\n";
                $retval .=  '<ul class="actions">'."\n";
                $retval .= '<li class="previews"><a title="view" href="view.php?file='.x0rencrypt(ltrim($path, '/'), $secretxorkey).'">' .$pathfile. ' view</a></li>'."\n";
                $retval .=  '<li class="clone"><a title="copy" href="copy.php?file='.x0rencrypt(ltrim($path, '/'), $secretxorkey) .'">' .$pathfile. ' copy</a></li>'."\n";
                $retval .=  '</ul>'."\n";
                $retval .= '</li>'."\n";

}else{
                $retval .= '<li class="folder">'.end(explode("/", $arrayElement['path']));
                $retval .= DBRender($arrayElement['dir']);
                $retval .= '</li>'."\n";
            }
        }
        $retval .= '</ul>'."\n";
    }
    return $retval;
}
echo '<p class="elements">';
echo 'Elemente im Ordner: '.$root; 
echo '</p>';
echo preg_replace('/ul/', 'ul class="pages"', DBRender($dbMedia), 1);
?>
</html>
