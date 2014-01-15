<?php
/* Copyright 2007
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

define("WATERMARK", FALSE); // watermark TRUE ou FALSE

$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/pgsqlae.inc.php");
require_once($topdir. "include/cts/imgcarto.inc.php");
require_once ($topdir . "include/watermark.inc.php");

$site = new site ();

$img = new imgcarto(800, 10);
$img->addcolor('pblue_dark', 51, 102, 153);
$img->addcolor('pblue', 222, 235, 245);

$nbpaliers = 5;

$img->addcolor('l0', 255, 255, 255);
$img->addcolor('l1', 255, 220, 0);
$img->addcolor('l2', 255, 198, 0);
$img->addcolor('l3', 255, 176, 0);
$img->addcolor('l4', 255, 154, 0);
$img->addcolor('l5', 255, 143, 0);
$img->addcolor('l6', 255, 121, 0);
$img->addcolor('l7', 255, 114, 0);
$img->addcolor('l8', 255, 101, 0);
$img->addcolor('l9', 255, 68, 0);
$img->addcolor('l10', 255, 0, 0);




$pgconn = new pgsqlae();


$statscotis = new requete($site->db, "SELECT
                                      COUNT(`id_utilisateur`) AS num
                                      , substring(cpostal_ville,1,2) AS cpostal
                                      FROM `utl_etu`
                                      INNER JOIN `loc_ville` ON `loc_ville`.`id_ville` = `utl_etu`.`id_ville`
                                      WHERE `utl_etu`.`id_ville` IS NOT NULL
                                      GROUP BY substring(cpostal_ville,1,2)");

while ($rs = $statscotis->get_row())
{
  $statsdep[$rs['cpostal']] = $rs['num'];
}

$pgreq = new pgrequete($pgconn, "SELECT code_dept, nom_dept, asText(simplify(the_geom, 2000)) AS points FROM deptfr");

$rs = $pgreq->get_all_rows();

$numdept = 0;
$dept=array();

foreach($rs as $result)
{
  $astext = $result['points'];
  $matched = array();
  preg_match_all("/\(([^)]*)\)/", $astext, $matched);
  $i = 0;
  foreach ($matched[1] as $polygon)
  {
    $polygon = str_replace("(", "", $polygon);
    $points = explode(",", $polygon);
    foreach ($points as $point)
    {
      $coord = explode(" ", $point);
      $dept[$numdept]['plgs'][$i][] = $coord[0];
      $dept[$numdept]['plgs'][$i][] = $coord[1];
    }
    $i++;
  }
  $dept[$numdept]['name'] = $result['nom_dept'];
  $dept[$numdept]['iddept'] = $result['code_dept'];

  $numdept++;
}

foreach($dept as $departement)
{
  foreach($departement['plgs'] as $plg)
  {
    if ($statsdep[$departement['iddept']] == 0)
      $img->addpolygon($plg, 'l0', true,
           array('id' =>$departement['gid'],
           'url' => "javascript:ploufdept(this, ".
           $departement['iddept']. ")"));
    else
      $img->addpolygon($plg, 'l' . (int) (1 + $statsdep[$departement['iddept']] / 20), true,
                       array('id' =>$departement['gid'],
                             'url' => "javascript:ploufdept(this, ".
                             $departement['iddept']. ")"));
    $img->addpolygon($plg, 'black', false);
  }
}

$img->draw();

if ($_REQUEST['generate'] == 1)
{
  if (WATERMARK == TRUE)
    {
      require_once($topdir . 'include/watermark.inc.php');
      $wm_img = new img_watermark ($img->imgres);
      $wm_img->output();
    }
  else
    $img->output();

  exit();
}

if (isset($_REQUEST['getinfodepts']))
{
  $cp = mysql_real_escape_string($_REQUEST['getinfodepts']);

  echo "<h1>Ils viennent de ce département (".sprintf("%02d",$cp).") :</h1>";

  echo "<center>\n";
  $cp = sprintf('%02d',$cp).'___';

  $req = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`
                                        , `utilisateurs`.`prenom_utl`
                                        , `utilisateurs`.`nom_utl`
                                        , `utl_etu_utbm`.`surnom_utbm`
                                 FROM `utilisateurs`
                                 INNER JOIN `utl_etu` ON `utl_etu`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`
                                 INNER JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`
                                 INNER JOIN `loc_ville` ON `loc_ville`.`id_ville` = `utl_etu`.`id_ville`
                                 WHERE `loc_ville`.`cpostal_ville` LIKE '".$cp."'
                                 AND `publique_utl`>='".($site->user->cotisant ? '1' : '2')."'
                                 ORDER BY RAND()
                                 LIMIT 10");


  if ($req->lines <= 0)
    {
      echo "<p><b>Apparemment, personne ;-(</b></p>";
      exit();
    }
  require_once($topdir . "include/cts/sqltable.inc.php");

  $sqlt = new sqltable('userslst',
                       'Liste des utilisateurs',
                       $req,
                       '../user.php',
                       'id_utilisateur',
                       array('prenom_utl' => 'prenom', 'nom_utl' => 'nom', 'surnom_utbm' => 'surnom'),
                       array('view' => 'Voir la fiche'),
                       array(),
                       array());

  echo $sqlt->html_render();
  echo "</center>";

  exit();
}

$site = new site ();
$site->start_page("matmatronch","Carte de France de l'AE");

/*cts 1 : liste des gens */
$cts = new contents("", "");

$cts->add_paragraph("<script language=\"javascript\">
document.getElementById('cts1').style.display = 'none';
</script>\n");

$site->add_contents($cts);

/* cts 2 : la carte de France */
$cts = new contents("La carte de France de l'AE", "");

$cts->add_paragraph("<script language=\"javascript\">
function ploufdept(obj, id)
  {
    openInContents('cts1', './mapfrance.php', 'getinfodepts='+id);
    document.getElementById('cts1').style.display = 'block';
  }
</script>\n");


$cts->add_paragraph($img->map_area("carte_de_france"));

$cts->add_paragraph("<center><img class=\"cartefr\" src=\"mapfrance.php?generate=1\" alt=\"plouf\" usemap=\"#carte_de_france\" /></center>\n");

$site->add_contents($cts);

$site->end_page();

?>
