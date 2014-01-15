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
$photo->id=-1;
$filter="";
$page = "modere.php?";
$error=0;
$phasso = new asso($site->db);
$ptasso = new asso($site->db);

// Permet de restreindre le travail à une catégorie
$cat = new catphoto($site->db,$site->dbrw);
if ( isset($_REQUEST["id_catph"]))
{
  $cat->load_by_id($_REQUEST["id_catph"]);
  if ( $cat->id > 0 )
  {
    $page .= "id_catph=".$cat->id."&";
    $filter = " AND `id_catph`='".intval($cat->id)."'";
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
  $filter .= " AND `id_groupe_admin` ='".$id_groupe_admin."'";
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


  if ( $photo->id > 0 && $photo->is_right($site->user,DROIT_ECRITURE))
  {
    if ( isset($_REQUEST["delete"]))
    {
      $photo->remove_photo();
    }
    else
    {
      if(isset($_REQUEST["id_utilisateur"]))
      {
	      $incomplet=!isset($_REQUEST["complet"]);

	      $req = new requete($site->db,"SELECT `id_utilisateur`,`modere_phutl` FROM `sas_personnes_photos` WHERE `id_photo`='".$photo->id."'");
	      while ( list($id,$modere) = $req->get_row() )
	      {
		if ( !isset($_REQUEST["yet|$id"]) )
		  $photo->remove_personne($id);
		elseif ( $modere == 0 )
		  $photo->modere_personne($id, $site->user->id);
	      }


	      $utl = new utilisateur($site->db);
	      foreach( $_REQUEST["id_utilisateur"] as $id )
	      {
		if ( !empty($id ) )
		{
		  $utl->load_by_id($id);
		  if ( $utl->id > 0 )
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

	      $photo->set_modere(true,$site->user->id);

	      if ( $_REQUEST["restrict"] == "limittogroup" )
	      {
		$photo->droits_acces = 0x310;
		$photo->id_groupe = $_REQUEST["id_group"];
	      }
	      else
		$photo->droits_acces = 0x311;

	      $phasso->load_by_id($_REQUEST["id_asso"]);
	      $ptasso->load_by_id($_REQUEST["id_asso_photographe"]);

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
	else
		$error=1;
	
    }
  }

}
elseif ( $_REQUEST["action"] == "moderecat" )
{
  $catmod = new catphoto($site->db,$site->dbrw);
  $catmod->load_by_id($_REQUEST["id_catph_modere"]);
  if ($catmod->id > 0 && $catmod->is_right($site->user,DROIT_ECRITURE) )
  {
    if ( isset($_REQUEST["delete"]))
    {
      $catmod->remove_cat($site);
    }
    else
    {
      $catmod->set_modere();
    }
  }
}

$req = new requete($site->db, "SELECT * FROM `sas_cat_photos` ".
      "WHERE `modere_catph`='0' $filter ".
      "ORDER BY `id_catph` " .
      "LIMIT 1");

if ( $req->lines == 1 )
{
  $catpr = new catphoto($site->db);
  $cat->_load($req->get_row());
  $path =   $cat->get_html_link();
  $catpr->load_by_id($cat->id_catph_parent);
  while ( $catpr->id > 0 )
  {
    $path =   $catpr->get_html_link()." / ".$path;
    $catpr->load_by_id($catpr->id_catph_parent);
  }

  $frm = new form("moderecat",$page,false,"POST",$path);
  $frm->add_hidden("id_catph_modere",$cat->id);
  $frm->add_hidden("action","moderecat");
  $frm->add_submit("modere","Accepter");
  $frm->add_submit("delete","Supprimer");
  $cts->add($frm,true);
  $site->add_contents($cts);
  $site->end_page ();
  exit();
}

if ( $error > 0 )
  $req = new requete($site->db, "SELECT * FROM `sas_photos` ".
      "WHERE `id_photo`='".$photo->id."' $filter ".
      "ORDER BY `id_photo` " .
      "LIMIT 1");
else
  $req = new requete($site->db, "SELECT * FROM `sas_photos` ".
      "WHERE `id_photo`>'".$photo->id."' AND `modere_ph`='0' $filter ".
      "ORDER BY `id_photo` " .
      "LIMIT 1");

if ( $req->lines == 1 )
{
  $photo->_load($req->get_row());

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



  $frm = new form("pasaccord",$page,false,"POST","Photo non acceptable");
  $frm->add_hidden("id_photo",$photo->id);
  $frm->add_hidden("action","modere");
  $frm->add_submit("delete","Supprimer");
  $site->add_box("auto_right_pasaccord",$frm);

  $frm = new form("accord",$page,false,"POST","Photo acceptable: informations");
  $frm->add_hidden("id_photo",$photo->id);
  $frm->add_hidden("action","modere");

  $sfrm = new form("valid",null,null,null,"Validation");
  $sfrm->add_submit("modere","Accepter");
    $frm->add($sfrm);


  $sfrm = new form("people",null,null,null,"Personnes sur la photo");
  while ( list($id,$nom) = $req->get_row() )
    $sfrm->add_checkbox("yet|$id",$nom,true);
  for ($i=0;$i<7;$i++)
    $sfrm->add_user_fieldv2("id_utilisateur[$i]","");
  $sfrm->add_checkbox("complet","Liste complète",$photo->incomplet?false:true);
    $frm->add($sfrm);

  $sfrm = new form("meta",null,null,null,"Meta-informations");
  $sfrm->add_text_field("titre","Titre",$photo->titre);
  $sfrm->add_text_field("tags","Tags (séparteur: virgule)",$photo->get_tags());
  $sfrm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",$photo->meta_id_asso,true);
  $sfrm->add_entity_select("id_asso_photographe", "Photographe", $site->db, "asso",$photo->id_asso_photographe,true);
    $frm->add($sfrm);

  $sfrm = new form("access",null,null,null,"Droits d'accés");
  $ssfrm = new form("restrict",null,null,null,"Accès non restreint");
    $sfrm->add($ssfrm,false,true,($photo->droits_acces & 1),"none",false,true);
  $ssfrm = new form("restrict",null,null,null,"Limiter l'accés au groupe");
    $ssfrm->add_entity_select( "id_group", "Groupe", $site->db, "group", $photo->id_groupe );
    $sfrm->add($ssfrm,false,true,!($photo->droits_acces & 1),"limittogroup",false,true);
    $frm->add($sfrm);

  $site->add_box("auto_right_accord",$frm);

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
