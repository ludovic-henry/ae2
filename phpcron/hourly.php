<?php
if(!isset($argc))
  exit();

$_SERVER['SCRIPT_FILENAME']=dirname(__FILE__);

/*
 * hourly
 */

$topdir=$_SERVER['SCRIPT_FILENAME']."/../";
require_once($topdir. "include/site.inc.php");

$site = new site ();

$site = new site ();

// Tâche 1 [galaxy] : màj, et cycles


require_once($topdir. "include/galaxy.inc.php");

$galaxy = new galaxy($site->db,$site->dbrw);

$galaxy->update();

for($i=0;$i<45;$i++) // Environs 1100 cycles/jours
  $galaxy->cycle();

  $galaxy->mini_render($topdir."data/img/mini_galaxy.png");


// Tâche 2 [verous]
require_once($topdir . "comptoir/include/venteproduit.inc.php");
$req = new requete($site->db,"SELECT * FROM `cpt_verrou` WHERE TIMEDIFF(NOW(),date_res) >= 1");
$vp = new venteproduit($site->db,$site->dbrw);
$client = new utilisateur($site->db);
while ( $row = $req->get_row() )
{
  $client->load_by_id($row['id_utilisateur']);
  $vp->load_by_id ( $row['id_produit'], $row['id_comptoir'], true );
  $vp->debloquer ( $client, $row['quantite'] );
}

?>
