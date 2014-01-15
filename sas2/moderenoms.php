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
$topdir="../";
require_once("include/sas.inc.php");
require_once($topdir."include/cts/gallery.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/cts/video.inc.php");

$site = new sas();
$site->add_css("css/sas.css");

// Initialisation variables
$photo = new photo($site->db,$site->dbrw);
$photo->id=-1;
$filter="";
$page = "moderenoms.php?";
$error=0;

// Permet de restreindre le travail à une catégorie
$cat = new catphoto($site->db,$site->dbrw);
if ( isset($_REQUEST["id_catph"]))
{
  $cat->load_by_id($_REQUEST["id_catph"]);
  if ( $cat->id > 0 )
  {
    $page .= "id_catph=".$cat->id."&";
    $filter = " AND `sas_cat_photos`.`id_catph`='".intval($cat->id)."'";
  }
}
else
  $cat->id = -1;

if ( $_REQUEST["mode"] == "adminzone" )
{
  if ( $cat->id > 0 )
    $id_groupe_admin = $cat->id_groupe_admin;
  else
    $id_groupe_admin = intval($_REQUEST["id_groupe_admin"]);

  if ( !$site->user->is_in_group_id($id_groupe_admin) && !$site->user->is_in_group("gestion_ae") && !$site->user->is_in_group("sas_admin"))
    $site->error_forbidden("sas","group",$id_groupe_admin);
  $page .= "mode=adminzone&id_groupe_admin=".$id_groupe_admin;
  $filter .= " AND `sas_cat_photos`.`id_groupe_admin` ='".$id_groupe_admin."'";
}
else
{
  if ( !$site->user->is_in_group("gestion_ae") && !$site->user->is_in_group("sas_admin"))
    $site->error_forbidden("sas","group",9);
  $page .= "mode=full";
}

$site->start_page("sas","Modération",true);
$cts = new contents("Modérer et completer les noms");


if ( $_REQUEST["action"] == "modere" )
{
  $photo->load_by_id($_REQUEST["id_photo"]);

  $incomplet=!isset($_REQUEST["complet"]);

  $req = new requete($site->db,"SELECT `id_utilisateur`,`modere_phutl` FROM `sas_personnes_photos` WHERE `id_photo`='".$photo->id."'");
  while ( list($id,$modere) = $req->get_row() )
  {
    if ( !isset($_REQUEST["yet"][$id]) )
      $photo->remove_personne($id);
    elseif ( $modere == 0 )
      $photo->modere_personne($id, $site->user->id);
  }

  $photo->set_incomplet($incomplet);

}
else
{
	$photo->load_by_id($_REQUEST["id_photo"]);
}


$req = new requete($site->db,
      "SELECT `sas_photos`.* ".
      "FROM `sas_personnes_photos` ".
      "INNER JOIN `sas_photos` USING(`id_photo`) ".
      "WHERE ( (`sas_personnes_photos`.`modere_phutl` ='0' OR `sas_photos`.propose_incomplet <> `sas_photos`.incomplet ) ".
      "AND `sas_photos`.`id_photo`>'".$photo->id."' )  $filter ".
      "GROUP BY `sas_photos`.`id_photo` " .
      "ORDER BY `sas_photos`.`id_photo` " .
      "LIMIT 1");

if ( $req->lines == 1 )
{
  $row = $req->get_row();
  $photo->_load($row);

  $cat = new catphoto($site->db);
  $catpr = new catphoto($site->db);

  $cat->load_by_id($photo->id_catph);

  $path = $cat->get_html_link()." / ".$photo->get_html_link();
  $catpr->load_by_id($cat->id_catph_parent);
  while ( $catpr->id > 0 )
  {
    $path = $catpr->get_html_link()." / ".$path;
    $catpr->load_by_id($catpr->id_catph_parent);
  }

  $cts->add_title(2,$path);

  $imgcts = new contents();
  if ( $photo->type_media == MEDIA_VIDEOFLV )
    $imgcts->add(new flvideo($photo->id,"sas2/images.php?/".$photo->id.".flv"));
  else
    $imgcts->add(new image($photo->id,"images.php?/".$photo->id.".diapo.jpg"));
  $cts->add($imgcts,false,true,"sasimg");

  $subcts = new contents();

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,CONCAT(`utl_etu_utbm`.`surnom_utbm`, ' (', `utilisateurs`.`prenom_utl`, ' ', `utilisateurs`.`nom_utl`,')'), CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, ".
	"sas_personnes_photos.modere_phutl as modere " .
    "FROM `sas_personnes_photos` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`sas_personnes_photos`.`id_utilisateur` " .
	"LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
    "WHERE `sas_personnes_photos`.`id_photo`='".$photo->id."' " .
    "ORDER BY `nom_utilisateur`");

  $frm = new form("peoples",$page,false,"POST","Est-ce que ces personnes sont bien sur la photo ?");

  $frm->add_hidden("id_photo",$photo->id);
  $frm->add_hidden("action","modere");

  while ( list($id,$nom,$modere) = $req->get_row() )
  {
    if ( $modere )
      $frm->add_checkbox("yet|$id","<a href=\"".$wwwtopdir."user.php?id_utilisateur=$id\" target=\"_blank\">".$nom."</a>",true);
    else
      $frm->add_checkbox("yet|$id","<a href=\"".$wwwtopdir."user.php?id_utilisateur=$id\" target=\"_blank\"><b>".$nom."</b></a>",true);
  }
  if($photo->incomplet == $photo->propose_incomplet)
	  $frm->add_checkbox("complet","Liste complète",$photo->incomplet?false:true);
  else
	  $frm->add_checkbox("complet","<b>Liste complète</b>",$photo->propose_incomplet?false:true);

  $frm->add_submit("valid","Valider");
  $frm->puts("<a href=\"?id_photo=".$photo->id."\">Passer</a>");
  $site->add_box("auto_right_confirmperson",$frm);

  $cts->add($subcts,false,true,"photoinfo");
  $cts->puts("<div class=\"clearboth\"></div>");

}
else
{
  $cts->add_paragraph("Merci de votre aide, vous êtes arrivés à la fin :).");

  $cts->add_paragraph("<a href=\"./\">Retour au SAS</a>");
  if ( $cat->id > 0 )
    $cts->add_paragraph("Retour à ".$cat->get_html_link());
}
$site->add_contents($cts);
$site->end_page ();


?>
