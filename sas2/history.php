<?php
/* Copyright 2004-2006
 * - Julien Etelain < julien at pmad dot net >
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
$topdir="../";
require_once("include/sas.inc.php");
require_once($topdir. "include/cts/history.inc.php");
require_once($topdir. "include/entities/page.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
$site = new sas();

$site->start_page("sas","Petite histoire en photos");
$site->add_css("css/history.css");
$site->set_side_boxes("right",array(),"nope");
$site->set_side_boxes("left",array(),"nope");

$history = new history("Petite histoire en photos");

$req = new requete($site->db,
  "SELECT sas_cat_photos.*, parent.nom_catph as parent_nom_catph " .
  "FROM sas_cat_photos ".
  "LEFT JOIN sas_cat_photos as parent ON ( parent.id_catph = sas_cat_photos.id_catph_parent ) ".
  "WHERE sas_cat_photos.date_debut_catph IS NOT NULL ".
  "AND sas_cat_photos.meta_mode_catph!='".CATPH_MODE_META_ASSO."' ".
  "AND datediff(sas_cat_photos.date_fin_catph,sas_cat_photos.date_debut_catph) < 10");

while ( $row = $req->get_row() )
{
  if ( $row['parent_nom_catph'] )
    $row['nom_catph'] .= " (".$row['parent_nom_catph'].")";

  $img = $topdir."images/misc/sas-default.png";
  if ( $row['id_photo'] )
    $img = $topdir."sas2/images.php?/".$row['id_photo'].".vignette.jpg";

  $history->add_element(strtotime($row['date_debut_catph']),
      "<a href=\"".$topdir."sas2/?id_catph=".$row['id_catph']."\"><img src=\"$img\" alt=\"".$row['nom_catph']."\" /></a>",
      "<a href=\"".$topdir."sas2/?id_catph=".$row['id_catph']."\">".$row['nom_catph']."</a>");
}


$site->add_contents($history);
$site->end_page();

?>
