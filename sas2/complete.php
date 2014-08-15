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
require_once($topdir."include/entities/asso.inc.php");

$site = new sas();
$site->add_css("css/sas.css");

// Initialisation variables
$photo = new photo($site->db,$site->dbrw);
$filter="";
$page = "complete.php?";
$error=0;
$phasso = new asso($site->db);
$ptasso = new asso($site->db);
// Permet de restreindre le travail à une catégorie
$cat = new catphoto($site->db,$site->dbrw);
if ( isset($_REQUEST["id_catph"]) )
{
  $cat->load_by_id($_REQUEST["id_catph"]);
  if ( $cat->is_valid() )
  {
    $page .= "id_catph=".$cat->id."&";
    $filter = " AND `id_catph`='".intval($cat->id)."'";
  }
}

// Génère le nom de la page et le filtre SQL en fonction des modes (utilisateur/administrateur zone/administrateur SAS)
if ( $_REQUEST["mode"] == "userphoto" )
{
  $filter .= " AND (`droits_acces_ph` & 0x100) AND `id_utilisateur` ='".$site->user->id."'";
  $page .= "mode=userphoto";
}
elseif ( $_REQUEST["mode"] == "adminzone" )
{
  if ( $cat->is_valid() )
    $id_groupe_admin = $cat->id_groupe_admin;
  else
    $id_groupe_admin = intval($_REQUEST["id_groupe_admin"]);

  if ( !$site->user->is_in_group_id($id_groupe_admin) && !$site->user->is_in_group("gestion_ae") && !$site->user->is_in_group("sas_admin"))
    $site->error_forbidden("sas","group",$id_groupe_admin);
  $page .= "mode=adminzone&id_groupe_admin=".$id_groupe_admin;
  $filter .= " AND `id_groupe_admin` ='".$id_groupe_admin."'";
}
else
{
  if ( !$site->user->is_in_group("gestion_ae") && !$site->user->is_in_group("sas_admin"))
    $site->error_forbidden("sas","group",9);
  $page .= "mode=full";
}

$photo->load_by_id($_REQUEST["id_photo"]);

if ( $_REQUEST["action"] == "complete" )
{


  if ( $photo->is_right($site->user,DROIT_ECRITURE))
  {
    $incomplet=!isset($_REQUEST["complet"]);

    $req = new requete($site->db,"SELECT `id_utilisateur` FROM `sas_personnes_photos` WHERE `id_photo`='".$photo->id."'");
    while ( list($id) = $req->get_row() )
    {
      if ( !isset($_REQUEST["yet"][$id]) )
        $photo->remove_personne($id);
    }

    $utl = new utilisateur($site->db);
    if ( isset($_REQUEST["id_utilisateur"]) && count($_REQUEST["id_utilisateur"]) > 0 )
    foreach( $_REQUEST["id_utilisateur"] as $id )
    {
      if ( !empty($id ) )
      {
        $utl->load_by_id($id);
        if ( $utl->is_valid() )
          $photo->add_personne($utl,true, $site->user->id);
        else
        {
          $incomplet|=true;
          $error++;
        }
      }
    }

    if ( !$incomplet )
      $photo->set_incomplet(false);

    $phasso->load_by_id($_REQUEST["id_asso"]);
    $ptasso->load_by_id($_REQUEST["id_asso_photographe"]);

    if ( $_REQUEST["restrict"] == "limittogroup" )
    {
      $photo->droits_acces = 0x310;
      $photo->id_groupe = $_REQUEST["id_group"];
    }
    else
      $photo->droits_acces = 0x311;

    $photo->update_photo(
        $photo->date_prise_vue,
        $photo->commentaire,
        NULL,
        $phasso->id,
        $_REQUEST["titre"],
        $ptasso->id
        );
    $photo->set_tags($_REQUEST["tags"]);
  }
}
if($site->user->is_in_group("sas_admin") && intval($_REQUEST["id_catph"]) == 1)
{
	$filter = "";
}

$site->start_page("sas","Completer les noms",true);
$cts = new contents("Completer les noms");

if ( $error > 0 )
  $req = new requete($site->db, "SELECT * FROM `sas_photos` ".
      "WHERE `id_photo`='".$photo->id."' AND $filter ".
      "LIMIT 1");
else if ( $photo->is_valid() )
  $req = new requete($site->db, "SELECT * FROM `sas_photos` ".
      "WHERE `id_photo`<'".$photo->id."' AND `incomplet`='1' $filter ".
      "ORDER BY `id_photo` DESC " .
      "LIMIT 1");
else
  $req = new requete($site->db, "SELECT * FROM `sas_photos` ".
      "WHERE `incomplet`='1' $filter ".
      "ORDER BY `id_photo` DESC " .
      "LIMIT 1");

if ( $req->lines == 1 )
{
  $photo->_load($req->get_row());


  $cat = new catphoto($site->db);
  $catpr = new catphoto($site->db);
  $cat->load_by_id($photo->id_catph);
  $path = $cat->get_html_link()." / ".$photo->get_html_link();
  $catpr->load_by_id($cat->id_catph_parent);
  while ( $catpr->is_valid() )
  {
    $path = $catpr->get_html_link()." / ".$path;
    $catpr->load_by_id($catpr->id_catph_parent);
  }
  $cts->add_title(2,$path);


  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`, " .
    "  COALESCE(CONCAT(' (',`utl_etu_utbm`.`surnom_utbm`,')'),'')) as `nom_utilisateur` " .
    "FROM `sas_personnes_photos` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`sas_personnes_photos`.`id_utilisateur` " .
    "LEFT JOIN `utl_etu_utbm` ON `utilisateurs`.`id_utilisateur` = `utl_etu_utbm`.`id_utilisateur` " .
    "WHERE `sas_personnes_photos`.`id_photo`='".$photo->id."' " .
    "ORDER BY `nom_utilisateur`");

  $imgcts = new contents();

  if ( $photo->type_media == MEDIA_VIDEOFLV )
    $imgcts->add(new flvideo($photo->id,"sas2/images.php?/".$photo->id.".flv"));
  else
    $imgcts->add(new image($photo->id,"images.php?/".$photo->id.".diapo.jpg"));

  $cts->add($imgcts,false,true,"sasimg");

/*
  if ( $req->lines==0 )
  {
    $frm = new form("setfull",$page,false,"POST","Liste complète");
    $frm->add_hidden("id_photo",$photo->id);
    $frm->add_hidden("action","complete");
    $frm->add_hidden("complet",1);
    $frm->add_info("Il n'y a personne sur cette photo (de reconaissable).");
    $frm->add_submit("valid","Oui/Suivant");
    $subcts->add($frm,true);
  }
*/
  $frm = new form("peoples",$page."&id_photo=$photo->id",false,"POST","Compléter la photo");
  //$frm->add_hidden("id_photo",$photo->id);
  $frm->add_hidden("action","complete");

  $sfrm = new subform("people","Personnes sur la photo");
  while ( list($id,$nom) = $req->get_row() )
    $sfrm->add_checkbox("yet|$id",$nom,true);
  for ($i=0;$i<7;$i++)
    $sfrm->add_user_fieldv2("id_utilisateur[$i]","");
  $sfrm->add_checkbox("complet","Liste complète",$photo->incomplet?false:true);
  $frm->addsub($sfrm);

  $sfrm = new subform("meta","Meta-informations");
  $sfrm->add_text_field("titre","Titre",$photo->titre);
  $sfrm->add_text_field("tags","Tags (séparteur: virgule)",$photo->get_tags());
  $sfrm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",$photo->meta_id_asso,true);
  $sfrm->add_entity_select("id_asso_photographe", "Photographe", $site->db, "asso",$photo->id_asso_photographe,true);
  $frm->addsub($sfrm);

  $sfrm = new subform("access","Droits d'accés");
  $sfrm->set_subform_checked("restrict", ($photo->droits_acces & 1) ? "none" : "group");
  $ssfrm = new subformoption("restrict","none","Accès non restreint");
  $sfrm->addsub($ssfrm,true);
  $ssfrm = new subformoption("restrict","group","Limiter l'accés au groupe");
  $ssfrm->add_entity_select( "id_group", "Groupe", $site->db, "group", $photo->id_groupe );
  $sfrm->addsub($ssfrm,true);
  $frm->addsub($sfrm);

  $frm->add_submit("fin","Valider/Suivant");
  $site->add_box("auto_right_peoples",$frm);

  $cts->puts("<div class=\"clearboth\"></div>");

}
else
{
  $cts->add_paragraph("Merci de votre aide, vous êtes arrivés à la fin :).");

  $cts->add_paragraph("<a href=\"./\">Retour au SAS</a>");
  if ( $cat->is_valid() )
    $cts->add_paragraph("Retour à ".$cat->get_html_link());
}
$site->add_contents($cts);
$site->end_page ();
?>
