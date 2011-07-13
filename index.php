<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Zugriff auf die Dropbox mit PHP</title>
<link rel="stylesheet" type="text/css" href="css/base.css_" />
</head>
<h1>Dropboxmanager</h1>
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

$root = 'public';


print_r(DBSort($root));
/*
echo '<ul class="pages">';
foreach(DBSort($root) as $v)
{
  $path = $v['path'];
  $explodedStuff = explode('/', $path);
  $pathfile = end($explodedStuff);
  if(!empty($v['dir']) && $v['dir'] != '1')
  { 
echo '<li class="pg">'.$pathfile."\n";
echo '<ul class="actions">'."\n";
echo '<li class="previews"><a title="view" href="view.php?file='.x0rencrypt(ltrim($path, '/'), $secretxorkey) .'">' .$pathfile. ' view</a></li>'."\n";
echo '<li class="edit"><a title="copy" href="copy.php?file='.x0rencrypt(ltrim($path, '/'), $secretxorkey) .'">' .$pathfile. ' copy</a></li>'."\n";
echo '</ul>'."\n";
echo '</li>'."\n";
   }else{
     echo '<li class="folder">'.$pathfile. "\n";
	   echo '<ul>';
	    foreach(DBSort($root."/".$pathfile) as $subpath)
      {
          $explodedStuff = explode('/', $subpath['path']);
	      $subpathfile = end($explodedStuff);
        if($subpath['dir'] != '1')
        { 
echo '<li class="pg">'.$subpathfile.'</li>'. "\n";
	      }else{
echo '<li class="folder">'.$subpathfile.'</li>';
        }
      }
echo '</ul></li>'. "\n";
	}
}
echo '</ul>';

*/
//var_dump($response);
?>
</html>
