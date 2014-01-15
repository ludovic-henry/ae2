<?
exit();

$_SERVER['SCRIPT_FILENAME']="/var/www/ae2/phpcron";
$topdir=$_SERVER['SCRIPT_FILENAME']."/../";

require_once($topdir."include/site.inc.php");
require_once($topdir."planet/include/atomparser.inc.php");
require_once($topdir.'include/cts/cached.inc.php');
require_once($topdir.'include/entities/wiki.inc.php');


$flux = 'http://twitter.com/statuses/user_timeline/14591898.atom';
$page = preg_replace("/[^a-z0-9\-_:#]/",
                     "_",
                     strtolower(utf8_enleve_accents('cms:19:boxes:twitterff1j')));
$page = "articles:".$page;

$site = new site();

$parser = new AtomParser();
$parser->parse($flux);
$max=$parser->getTotalEntries();
if($max>3)
  $max=3;
$cts='';
for($i =0; $i<$max; $i++)
{
  $entry = $parser->getEntry($i);
  $url = explode(':',$entry['ID']);
  $url = $url[count($url)-1];
  $cts.='  * [['.$url.'|'.$entry['TITLE'].']]
';
}

$wiki = new wiki ($site->db,$site->dbrw);
$wiki->load_by_fullpath($page);
if (!$wiki->is_valid() )
  exit();

if($cts == $wiki->rev_contents)
  exit();
$wiki->revision (3538,'Twitter',$cts);

?>
