<?php
/* Copyright 2006
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
/** Affiche les informations publiques sur une association.
 * @see ae/asso.php
 * @see asso/asso.php
 */

$topdir = "../";
include($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/history.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/page.inc.php");
require_once($topdir. "sas2/include/sas.inc.php");

$site = new site ();
$site->add_css("css/history.css");

$asso = new asso($site->db,$site->dbrw);

if ( !isset($_REQUEST["id_asso"]) )
{
  $site->error_not_found("presentation");
  exit();
}


$asso_parent = new asso($site->db);
$asso->load_by_id($_REQUEST["id_asso"]);
if ( $asso->id < 1 )
{
  $site->error_not_found("presentation");
  exit();
}

$site->start_page("presentation",$asso->nom,true);
$site->set_side_boxes("right",array(),"nope");
$site->set_side_boxes("left",array(),"nope");

$cts = new contents($asso->nom);
$cts->add(new tabshead($asso->get_tabs($site->user),"info"));

$history = new history("Petite histoire de ".$asso->nom);


$cat = new catphoto($site->db);
$cat->load_by_asso_summary($asso->id);

if ( $cat->id < 1 )
{
  $cat->meta_id_asso = $asso->id;
  $cat->id = -1;
}

$cats=array();

$req = new requete($site->db,
  "SELECT DISTINCT(sas_photos.id_catph),sas_cat_photos.*, parent.nom_catph as parent_nom_catph " .
  "FROM `sas_photos` ".
  "INNER JOIN sas_cat_photos ON ( sas_photos.id_catph = sas_cat_photos.id_catph ) " .
  "LEFT JOIN sas_cat_photos as parent ON ( parent.id_catph = sas_cat_photos.id_catph_parent ) ".
  "WHERE  sas_photos.meta_id_asso_ph='".$cat->meta_id_asso."' " .
  "AND sas_cat_photos.id_catph!='".$cat->id."' ".
  "AND sas_cat_photos.id_catph_parent!='".$cat->id."' ".
  "AND parent.id_catph_parent!='".$cat->id."' ".
  "AND (sas_cat_photos.meta_id_asso_catph!='".$cat->meta_id_asso."' OR sas_cat_photos.meta_id_asso_catph IS NULL)");

while ( $row = $req->get_row() )
{

  $req2 = new requete($site->db,
    "SELECT id_photo FROM `sas_photos` ".
    "WHERE meta_id_asso_ph='".$cat->meta_id_asso."' " .
    "AND id_catph='".$row['id_catph']."' " .
    "AND droits_acquis =1 " .
    "AND (droits_acces_ph & 1) = 1 " .
    "ORDER BY date_prise_vue");

  if ( $req2->lines > 0 )
    list($row['id_photo']) = $req2->get_row();

  $cats[] = $row;
}

$req = new requete($site->db,
  "SELECT sas_cat_photos.*, parent.nom_catph as parent_nom_catph " .
  "FROM sas_cat_photos ".
  "LEFT JOIN sas_cat_photos as parent ON ( parent.id_catph = sas_cat_photos.id_catph_parent ) ".
  "WHERE sas_cat_photos.meta_id_asso_catph='".$cat->meta_id_asso."' ".
  "AND sas_cat_photos.id_catph!='".$cat->id."' ".
  "AND sas_cat_photos.id_catph_parent!='".$cat->id."' ".
  "AND parent.id_catph_parent!='".$cat->id."' ".
  "AND sas_cat_photos.meta_mode_catph!='".CATPH_MODE_META_ASSO."'");

while ( $row = $req->get_row() ) $cats[] = $row;

if ( $cat->id > 0 )
{
  $req = $cat->get_categories ( $cat->id, $site->user, $site->user->get_groups_csv());
  while ( $row = $req->get_row() ) $cats[] = $row;
}

foreach ( $cats as $row )
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

$sql = new requete($site->db,"SELECT nvl_dates.*,nvl_nouvelles.* FROM nvl_dates " .
      "INNER JOIN nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
      "WHERE nvl_nouvelles.id_asso='".$asso->id."'");

while ( $row = $sql->get_row() )
{
  $history->add_element(strtotime($row['date_debut_eve']),
      "<a href=\"".$topdir."news.php?id_nouvelle=".$row['id_nouvelle']."\"><img src=\"".$topdir."images/default/news.small.png\" /></a>",
      "<a href=\"".$topdir."news.php?id_nouvelle=".$row['id_nouvelle']."\">".$row['titre_nvl']."</a>");
}

$req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "`asso_membre`.`date_debut`, `asso_membre`.`role` " .
    "FROM `asso_membre` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
    "WHERE `asso_membre`.`id_asso`='".$asso->id."' " .
    "AND `asso_membre`.`role` >= ".ROLEASSO_VICEPRESIDENT." ".
    "ORDER BY `asso_membre`.`date_debut`, `asso_membre`.`desc_role`");



if ( !is_null($asso->id_parent) )
{
  $role[ROLEASSO_PRESIDENT] = "Responsable";
  $role[ROLEASSO_VICEPRESIDENT] = "Vice-Responsable";
}
else
{
  $role[ROLEASSO_PRESIDENT] = "Président(e)";
  $role[ROLEASSO_VICEPRESIDENT] = "Vice-Président(e)";
}

while ( $row = $req->get_row() )
{

  $img = $topdir."images/icons/128/user.png";
  if ( file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg") )
    $img = $topdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg";
  elseif( file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".jpg") )
    $img = $topdir."data/matmatronch/".$row['id_utilisateur'].".jpg";

  $history->add_element(strtotime($row['date_debut']),
  "<a href=\"../user.php?id_utilisateur=".$row['id_utilisateur']."\"><img src=\"$img\" alt=\"Photo\" height=\"105\"></a>",
  $role[$row['role']]." : <a href=\"../user.php?id_utilisateur=".$row['id_utilisateur']."\">".htmlentities($row['nom_utilisateur'],ENT_NOQUOTES,"UTF-8")."</a>");
}

$cts->add($history,true);
$site->add_contents($cts);
$site->end_page();

?>
