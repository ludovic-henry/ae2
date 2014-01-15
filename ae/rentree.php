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

define("WATERMARK", TRUE); // watermark TRUE ou FALSE

$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/pgsqlae.inc.php");
require_once($topdir. "include/cts/imgcarto.inc.php");
require_once ($topdir . "include/watermark.inc.php");

$site = new site ();

if ($_REQUEST['generate'] == 1)
{
  require_once($topdir . 'include/watermark.inc.php');
  $img = new imgcarto(800, 10);
  $img->addcolor('pblue_dark', 51, 102, 153);
  $img->addcolor('pblue', 222, 235, 245);


  // couleurs des points, du plus ancien au plus récent :)
  $img->addcolor('p1',   255, 231, 0);
  $img->addcolor('p2',   255, 220, 0);
  $img->addcolor('p3',   255, 198, 0);
  $img->addcolor('p4',   255, 176, 0);
  $img->addcolor('p5',   255, 165, 0);
  $img->addcolor('p6',   255, 154, 0);
  $img->addcolor('p7',   255, 143, 0);
  $img->addcolor('p8',   255, 132, 0);
  $img->addcolor('p9',   255, 121, 0);
  $img->addcolor('p10',  255, 114, 0);
  $img->addcolor('p11',  255, 101, 0);
  $img->addcolor('p12',  255, 90, 0);
  $img->addcolor('p13',  255, 80, 0);
  $img->addcolor('p14',  255, 68, 0);
  $img->addcolor('p15',  255, 58, 0);
  $img->addcolor('p16',  255, 47, 0);
  $img->addcolor('p17',  255, 36, 0);
  $img->addcolor('p18',  255, 26, 0);
  $img->addcolor('p19',  255, 16, 0);
  $img->addcolor('p20',  255, 0, 0);

  $pgconn = new pgsqlae();
  $pgreq = new pgrequete($pgconn, "SELECT asText(simplify(the_geom,2000)) AS points FROM deptfr");
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
    $numdept++;
  }

  foreach($dept as $departement)
  {
    foreach($departement['plgs'] as $plg)
    {
      $img->addpolygon($plg, 'pblue', true);
      $img->addpolygon($plg, 'pblue_dark', false);
    }
  }

  $req = new requete($site->db, "SELECT `lat_ville`, `long_ville` FROM `utl_etu`
    LEFT JOIN `ae_cotisations` ON `ae_cotisations`.`id_utilisateur` = `utl_etu`.`id_utilisateur`
    INNER JOIN `loc_ville` ON `utl_etu`.`id_ville` = `loc_ville`.`id_ville`
    WHERE `date_fin_cotis` > NOW() AND `utl_etu`.`id_ville` IS NOT NULL
    ORDER BY `id_cotisation` DESC
    LIMIT 20");

  $loc = array();
  while(list($_lat, $_long) = $req->get_row())
  {
    $lat  = rad2deg($_lat);
    $long = rad2deg($_long);
    $lat = str_replace(",", ".", $lat);
    $long = str_replace(",", ".", $long);
    $loc[$i]['lat']=$lat;
    $loc[$i]['long']=$long;
    $i++;
  }
  $i=20;
  foreach($loc AS $point)
  {
    $pgreq = new pgrequete($pgconn, "SELECT AsText(TRANSFORM(GeomFromText('POINT(".$point['long']." ".$point['lat'].")', 4030), 27582)) AS villecoords ".
      "FROM deptfr LIMIT 1");
    $rs = $pgreq->get_all_rows();
    if(isset($rs[0]))
    {
      $ville = $rs[0]['villecoords'];
      $villecoords = str_replace("POINT(", "", $ville);
      $villecoords = str_replace(")", "", $villecoords);
      $villecoords = explode(" ", $villecoords);
      $color="p".$i;
      $img->addpoint($villecoords[0], $villecoords[1], 10, $color);
      $i--;
    }
  }

  $img->draw();

  $wm_img = new img_watermark ($img->imgres);
  $wm_img->output();

  exit();
}


$site->start_page("services","Carte de France de l'AE en temps réel");

$cts = new contents("La carte de France de l'AE", "");
$cts->add_paragraph("<center><img id=\"cartefr\" class=\"cartefr\" src=\"rentree.php?generate=1\" alt=\"plouf\" /></center>\n");


$script = "<script language=\"javascript\">
function cartefr_refresh()
{
  document.getElementById('cartefr').src = 'rentree.php?generate=1&'+(new Date()).getTime();
  setTimeout('cartefr_refresh()', 5000);

}
cartefr_refresh();
</script>";


$cts->add_paragraph($script);

$site->add_contents($cts);

$site->end_page();

?>
