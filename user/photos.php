<?php
/* Copyright 2004-2006
 * - Julien Etelain < julien at pmad dot net >
 * - Benjamin Collet < bcollet at oxynux dot org >
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
require_once($topdir."include/site.inc.php");
require_once($topdir."sas2/include/cat.inc.php");
require_once($topdir."sas2/include/photo.inc.php");
require_once($topdir."include/cts/gallery.inc.php");
require_once($topdir."include/cts/user.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");

$site = new site ();
$site->allow_only_logged_users("matmatronch");

if ( ($site->get_param("closed.sas",false) && !$site->user->is_in_group("root")) || !is_dir("/var/www/ae2/data/sas") )
  $site->fatal_partial("sas");

if ( isset($_REQUEST['id_utilisateur']) )
{
  $user = new utilisateur($site->db);
  $user->load_by_id($_REQUEST["id_utilisateur"]);

  if ( !$user->is_valid() )
    $site->error_not_found("matmatronch");

  $can_edit = ( $user->id==$site->user->id || $site->user->is_in_group("gestion_ae") );

  if ( $user->id != $site->user->id && !$site->user->utbm && !$site->user->ae )
    $site->error_forbidden("matmatronch","group",10001);
}
else
{
  $user = &$site->user;
  $can_edit = true;
}

$grps = $site->user->get_groups_csv();
$site->add_css("css/sas.css");

$site->start_page("matmatronch","Stock à Souvenirs");

$cts = new contents($user->prenom." ".$user->nom);

$cts->add(new tabshead($user->get_tabs($site->user),"photos"));

if ( $user->id==$site->user->id )
{
  $tabs = array(
    array("","user/photos.php?id_utilisateur=".$user->id,"Photos où je suis présent"),
    array("","user/photos.php?see=photograph&id_utilisateur=".$user->id,"Photos comme photographe"),
    array("stats","user/photos.php?see=stats&id_utilisateur=".$user->id,"Statistiques"),
    array("new","user/photos.php?see=new&id_utilisateur=".$user->id,"Nouvelles photos"));
}
else
{
  $tabs = array(
    array("","user/photos.php?id_utilisateur=".$user->id,"Photos"),
    array("stats","user/photos.php?see=stats&id_utilisateur=".$user->id,"Statistiques"));
}
$cts->add(new tabshead($tabs,
  isset($_REQUEST["see"])?$_REQUEST["see"]:"","","subtab"));


if ( isset($_REQUEST["see"]) && $_REQUEST["see"] == "stats" )
{
  $req = new requete ($site->db, "SELECT COUNT(*) as count ".
      "FROM `sas_personnes_photos` WHERE id_utilisateur='".$user->id."'");
  $row = $req->get_row ();
  if ($row['count'] > 1)
    $cts->add_paragraph ("<b>Présent(e) sur :</b> " . $row['count'] . " photos.");
  else
    $cts->add_paragraph ("<b>Présent(e) sur :</b> " . $row['count'] . " photo.");

  $req = new requete($site->db,"SELECT COUNT(liste.id_photo) as `count`, ".
    "liste.id_utilisateur, ".
    "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur` ".
    "FROM `sas_personnes_photos` AS liste ".
    "LEFT JOIN `sas_personnes_photos` as `source` ON ( source.id_photo=liste.id_photo AND liste.id_utilisateur!='".$user->id."') ".
    "INNER JOIN utilisateurs ON (liste.id_utilisateur=utilisateurs.id_utilisateur) ".
    "LEFT JOIN `utl_etu_utbm` ON (`utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur`) ".
    "WHERE source.id_utilisateur='".$user->id."' ".
    "GROUP BY liste.id_utilisateur ".
    "ORDER BY 1 DESC" );

  $lst = new itemlist("Photographi&eacute; le plus avec...");
  $n=1;
  while ( $row = $req->get_row() )
  {
    $lst->add("$n : <a href=\"../user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a>  (<a href=\"../sas2/search.php?action=search&id_utilisateurs_presents[]=".$user->id."&id_utilisateurs_presents[]=".$row["id_utilisateur"]."\">".$row['count']." photos</a>)");
    $n++;
  }

  $cts->add($lst,true);

}
elseif ( $user->id == $site->user->id && isset($_REQUEST["see"]) && $_REQUEST["see"] == "new" )
{
  if ( $_REQUEST["action"] == "vu" )
  {
    /* On usine */
    $req = new requete($site->dbrw,"UPDATE sas_personnes_photos " .
            "SET vu_phutl='1' " .
            "WHERE id_utilisateur='".$user->id."' AND vu_phutl='0'");

    $cts->add_paragraph("Toutes vos photos ont été marquées comme vues.");
    $cts->add_paragraph("<a href=\"photos.php\">Retourner à vos photos</a>");
  }
  else
  {
    $req = new requete($site->db,"SELECT sas_photos.*,sas_cat_photos.nom_catph " .
                       "FROM sas_personnes_photos AS `p2` " .
                       "INNER JOIN sas_photos ON p2.id_photo=sas_photos.id_photo " .
                       "INNER JOIN sas_cat_photos ON sas_cat_photos.id_catph=sas_photos.id_catph " .
                       "LEFT JOIN sas_personnes_photos AS `p1` ON " .
                       "(p1.id_photo=sas_photos.id_photo " .
                       "AND p1.id_utilisateur='". $user->id."' " .
                       "AND p1.modere_phutl='1') " .
                       "WHERE " .
                       "p2.vu_phutl='0' AND " .
                       "p2.id_utilisateur='". $user->id."' ".
                       "ORDER BY sas_cat_photos.date_debut_catph DESC, sas_cat_photos.id_catph DESC, date_prise_vue "
                       );

    $prev_id_catph=-1;
    $gal=null;
    while ( $row = $req->get_row())
    {
      if ( $prev_id_catph != $row['id_catph'] )
      {
        if ( $gal )
          $cts->add($gal,true);

        $gal = new gallery($row['nom_catph'],"photos");

        $prev_id_catph = $row['id_catph'];
      }

      $img = "../sas2/images.php?/".$row['id_photo'].".vignette.jpg";
      $gal->add_item("<a href=\"../sas2/?id_photo=".$row['id_photo']."\"><img src=\"$img\" alt=\"Photo\"></a>");
    }
    if ( $gal )
    {
      $cts->add($gal,true);
      $cts->add_paragraph("<a href=\"photos.php?see=new&id_utilisateur=".$user->id."&action=vu\">Marquer toutes les photos commes vues</a>");
    }
    else
    {
      $cts->add_paragraph("Vous n'avez pas de nouvelles photos");
    }
  }
}
elseif ( $user->id == $site->user->id && isset($_REQUEST["see"]) && $_REQUEST["see"] == "photograph" ) {
  $req = new requete($site->db,"SELECT sas_photos.*,sas_cat_photos.nom_catph " .
                     "FROM sas_photos " .
                     "INNER JOIN sas_cat_photos ON sas_cat_photos.id_catph=sas_photos.id_catph " .
                     "WHERE " .
                     "sas_photos.id_utilisateur_photographe = '". $user->id."' ".
                     "ORDER BY sas_cat_photos.date_debut_catph DESC, sas_cat_photos.id_catph DESC, date_prise_vue"
                     );

  $prev_id_catph=-1;
  $gal=null;
  while ( $row = $req->get_row())
  {
    if ( $prev_id_catph != $row['id_catph'] )
    {
      if ( $gal )
        $cts->add($gal,true);

      $gal = new gallery($row['nom_catph'],"photos");

      $prev_id_catph = $row['id_catph'];
    }

    $img = "../sas2/images.php?/".$row['id_photo'].".vignette.jpg";
    $gal->add_item("<a href=\"../sas2/?id_photo=".$row['id_photo']."\"><img src=\"$img\" alt=\"Photo\"></a>");
  }
  if ( $gal )
    $cts->add($gal,true);

}
else
{
  $req = false;

  if ($site->user->id != $user->id) {
    $req = new requete($site->db,"SELECT sas_photos.*,sas_cat_photos.nom_catph " .
                       "FROM sas_personnes_photos AS `p2` " .
                       "INNER JOIN sas_photos ON p2.id_photo=sas_photos.id_photo " .
                       "INNER JOIN sas_cat_photos ON sas_cat_photos.id_catph=sas_photos.id_catph " .
                       "LEFT JOIN sas_personnes_photos AS `p1` ON " .
                       "(p1.id_photo=sas_photos.id_photo " .
                       "AND p1.id_utilisateur='". $site->user->id."' " .
                       "AND p1.modere_phutl='1') " .
                       "WHERE " .
                       "p2.id_utilisateur='". $user->id."' AND " .
                       "((((droits_acces_ph & 0x1) OR " .
                       "((droits_acces_ph & 0x10) AND sas_photos.id_groupe IN ($grps))) " .
                       "AND droits_acquis='1') OR " .
                       "(sas_photos.id_groupe_admin IN ($grps)) OR " .
                       "((droits_acces_ph & 0x100) AND sas_photos.id_utilisateur='". $site->user->id."') OR " .
                       "((droits_acces_ph & 0x100) AND p1.id_utilisateur IS NOT NULL) ) " .
                       "ORDER BY sas_cat_photos.date_debut_catph DESC, sas_cat_photos.id_catph DESC, date_prise_vue "
                       );
  } else {
    // Dans le cas où on regarde les photos où on apparait, pas de calcul de droit
    $req = new requete($site->db,"SELECT sas_photos.*,sas_cat_photos.nom_catph " .
                       "FROM sas_personnes_photos AS `p` " .
                       "INNER JOIN sas_photos ON p.id_photo=sas_photos.id_photo " .
                       "INNER JOIN sas_cat_photos ON sas_cat_photos.id_catph=sas_photos.id_catph " .
                       "WHERE " .
                       "p.id_utilisateur='".$user->id."' ".
                       "ORDER BY sas_cat_photos.date_debut_catph DESC, sas_cat_photos.id_catph DESC, date_prise_vue "
                       );
  }

  $prev_id_catph=-1;
  $gal=null;
  while ( $row = $req->get_row())
  {
    if ( $prev_id_catph != $row['id_catph'] )
    {
      if ( $gal )
        $cts->add($gal,true);

      $gal = new gallery($row['nom_catph'],"photos");

      $prev_id_catph = $row['id_catph'];
    }

    $img = "../sas2/images.php?/".$row['id_photo'].".vignette.jpg";
    $gal->add_item("<a href=\"../sas2/?id_photo=".$row['id_photo']."\"><img src=\"$img\" alt=\"Photo\"></a>");
  }
  if ( $gal )
    $cts->add($gal,true);

}

$site->add_contents($cts);

$site->end_page ();

?>
