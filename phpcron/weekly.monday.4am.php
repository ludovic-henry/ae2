<?php
if(!isset($argc))
  exit();
$_SERVER['SCRIPT_FILENAME']="/var/www/ae2/phpcron";

$topdir=$_SERVER['SCRIPT_FILENAME']."/../";
require_once($topdir. "include/site.inc.php");

$site = new site ();

echo "==== ".date("d/m/Y")." ====\n";

echo ">> OPTIMZE TABLES\n";
$req = new requete($site->db, 'SHOW TABLES');
while(list($table)=$req->get_row())
  new requete($site->dbrw, 'OPTIMIZE TABLE \''.$table.'\'');

// On regenere entierement le cache fsearch pour prendre en compte les nouvelles popularites
echo ">> BEGIN FSEARCH CACHE REGEN: ".date('r')."\n";
require_once ($topdir. "include/cts/fsearch.inc.php");
require_once ($topdir. "include/redis.inc.php");

$redis = redis_open_connection ();
// We remove all entries from this database
$redis->flushDB();
$redis->set ('_disable_cache', true);
$letters = array ('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');

function compute_pattern_with_size ($size)
{
    global $site, $redis;

    // we do all combination of 4 character
    $upper = pow (26, $size);

    for ($i = 0; $i < $upper; ++$i) {
        $str = '';
        for ($j = $size - 1; $j >= 0; $j--)
            $str .= $letters[($i / pow (26, $j)) % 26];

        $_REQUEST['pattern'] = $str;

        $fsearch = new fsearch ($site, false, true);
        if (!empty ($fsearch->buffer))
            $redis->set($str, $fsearch->buffer);
    }
}

compute_pattern_with_size (1);
compute_pattern_with_size (2);
compute_pattern_with_size (3);
compute_pattern_with_size (4);

$redis->del ('_disable_cache');
$redis->close ();

echo ">> END FSEARCH CACHE REGEN: ".date('r')."\n";

?>
