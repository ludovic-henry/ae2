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
require_once($topdir."include/cts/gallery.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/cts/sas.inc.php");
require_once($topdir."include/cts/video.inc.php");
require_once($topdir."include/cts/react.inc.php");

require_once($topdir. "include/entities/page.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$site = new sas();
$site->add_css("css/doku.css");
$site->allow_only_logged_users("sas");

$photo = new photo($site->db,$site->dbrw);
$cat = new catphoto($site->db,$site->dbrw);
$metacat = new catphoto($site->db);
$catpr = new catphoto($site->db);
$asso = new asso($site->db);
$phasso = new asso($site->db);
$ptasso = new asso($site->db);
if( isset($_REQUEST["modeincomplet"]) && !isset($_REQUEST["action"]))
{
  $req = new requete($site->db,"SELECT sas_photos.id_photo as id FROM sas_personnes_photos 
				JOIN sas_photos 
				ON sas_photos.id_photo = sas_personnes_photos.id_photo 
				WHERE incomplet = 1 
				AND sas_personnes_photos.id_utilisateur = '".$site->user->id."' 
				".(isset($_REQUEST["id_photo"])?(" AND sas_photos.id_photo > '".intval($_REQUEST["id_photo"])."'"):"")." 
				ORDER BY sas_photos.id_photo
				LIMIT 1");
  if($req->lines > 0)
  {
    list( $id_photo ) = $req->get_row();
    
    $photo->load_by_id($id_photo);
    if ( !$photo->is_valid() )
	exit();
    $cat->load_by_id($photo->id_catph);
    
  }
  else
  {
    $cts = new contents("Fin");
    $cts->add_paragraph("Merci de votre aide, vous êtes arrivés à la fin :).");
    $site->add_contents($cts);

    $site->end_page ();
    exit();
  }
}
elseif ( isset($_REQUEST["id_photo"]))
{
  $photo->load_by_id($_REQUEST["id_photo"]);
  if ( !$photo->is_valid() )
    $cat->load_by_id(1);
  else
    $cat->load_by_id($photo->id_catph);
}
if ( isset($_REQUEST["id_catph"]))
  $cat->load_by_id($_REQUEST["id_catph"]);
elseif ( !$cat->is_valid() )
  $cat->load_by_id(1);

if ( isset($_REQUEST["meta_id_catph"]))
  $metacat->load_by_id($_REQUEST["meta_id_catph"]);

if ( !$cat->is_valid() && !$cat->load_by_id(1) )
{
  header("Location: ../index.php");
  exit();
}

if ( !$cat->is_right($site->user,DROIT_LECTURE) && !($photo->is_valid() && $photo->is_on_photo($site->user->id)))
  $site->error_forbidden("sas","group",$cat->id_groupe);


if ( $metacat->is_valid() && !$metacat->is_right($site->user,DROIT_LECTURE) )
  $metacat->id=null;

if ( $photo->is_valid() && !$photo->is_right($site->user,DROIT_LECTURE) )
  $site->error_forbidden("matmatronch","group",$photo->id_groupe);

$site->add_css("css/sas.css");



$grps = $site->user->get_groups_csv();

if ( $_REQUEST["action"] == "cut" )
{
  if ( $photo->is_valid() && $photo->is_right($site->user,DROIT_ECRITURE) )
  {

    $sqlph = $cat->get_photos ( $cat->id, $site->user, $grps, "sas_photos.id_photo");
    $count=0;
    while ( list($id) = $sqlph->get_row() )
    {
      if ( $id == $photo->id )
        $idx = $count;
      $count++;
    }

    $_REQUEST["page"] = ($idx) / SAS_NPP;

    if ( !isset($_SESSION["sas_clipboard"]["photos"]) )
      $_SESSION["sas_clipboard"]["photos"]  = array();

    $_SESSION["sas_clipboard"]["photos"][$photo->id] = $photo->id;
    $photo->id=null;
  }
  elseif ( $cat->id_catph_parent && $cat->is_right($site->user,DROIT_ECRITURE) ) // la racine ne peut pas être coupée
  {
   if ( !isset($_SESSION["sas_clipboard"]["categories"]) )
      $_SESSION["sas_clipboard"]["categories"]  = array();

    $_SESSION["sas_clipboard"]["categories"][$cat->id] = $cat->id;
    $cat->load_by_id($cat->id_catph_parent);
  }
}
elseif ( $_REQUEST["action"] == "addphoto" && $GLOBALS["svalid_call"] )
{
  $_REQUEST["view"] = "add";

  if ( !is_uploaded_file($_FILES['file']['tmp_name']) ||
    ($_FILES['file']['error'] != UPLOAD_ERR_OK) )
    $ErreurUpload = "Erreur lors du transfert du fichier.";
  elseif ( !$cat->is_right($site->user,DROIT_AJOUTITEM) )
    $ErreurUpload = "Vous n'avez pas les droits requis.";
  else
  {

    $phasso->load_by_id($_REQUEST["id_asso"]);
    $ptasso->load_by_id($_REQUEST["id_asso_photographe"]);

    $photo->herit($cat,false);
    $photo->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin'],false);
    $licence = new licence($site->db);
    if($_REQUEST['auteur']==1) {
      $auteur=$site->user->id;
      $licence->load_by_id($_REQUEST['id_licence']);
    }
    else
    {
      $licence->id = null;
      $auteur = new utilisateur($site->db);
      if($auteur->load_by_id($_REQUEST['photographe'])){
        if(!is_null($auteur->id_licence_default_sas))
          $licence->load_by_id($auteur->id_licence_default_sas);
        $auteur=$auteur->id;
      }
      else
        $auteur=null;
    }
    $photo->add_photo (
      $_FILES['file']['tmp_name'],
      $cat->id,
      $_REQUEST['comment'],
      $auteur,
      $_REQUEST['personne'],
      $phasso->id,
      $_REQUEST["titre"],
      $ptasso->id,
      $licence->id
      );

  }
}
elseif ( $_REQUEST["action"] == "delete" && $photo->is_valid() && !$_REQUEST["id_utilisateur"] )
{
  if ( $photo->is_right($site->user,DROIT_ECRITURE) )
  {

    $sqlph = $cat->get_photos ( $cat->id, $site->user, $grps, "sas_photos.id_photo");
    $count=0;
    while ( list($id) = $sqlph->get_row() )
    {
      if ( $id == $photo->id )
        $idx = $count;
      $count++;
    }

    if ( $idx == 0 )
      $_REQUEST["page"] = 0;
    else
      $_REQUEST["page"] = ($idx-1) / SAS_NPP;

    $photo->remove_photo();
    $photo->id=null;
  }
}
elseif ( $_REQUEST["action"] == "sethome" && $photo->is_valid() && $cat->is_valid() )
{
  if ( $cat->is_right($site->user,DROIT_ECRITURE) )
    if ( $photo->droits_acquis )
    {
      $sqlph = $cat->get_photos ( $cat->id, $site->user, $grps, "sas_photos.id_photo");
      $count=0;
      while ( list($id) = $sqlph->get_row() )
      {
        if ( $id == $photo->id )
          $idx = $count;
        $count++;
      }

      $_REQUEST["page"] = ($idx) / SAS_NPP;

      $cat->set_photo($photo->id);
      $photo->id=null;
    }

}
elseif ( $_REQUEST["action"] == "delete" && $cat->is_valid() && !$_REQUEST["id_photo"] )
{
  if ( $cat->is_right($site->user,DROIT_ECRITURE) )
  {
    if ( $site->is_sure ( "","Suppression de la catégorie ".$cat->nom,"ctph".$cat->id, 3 ) )
    {
      $cat->remove_cat($site->user);
      $cat->load_by_id($cat->id_catph_parent);
    }
  }
}
if ($photo->is_valid() )
  $path =   $cat->get_html_link()." / ".$photo->get_html_link();
else
  $path =   $cat->get_html_link();

if ( $metacat->is_valid() )
{
  $catpr->load_by_id($metacat->id);
  $cat->set_meta($metacat);
  $self="./?meta_id_catph=".$metacat->id."&";
  $selfhtml="./?meta_id_catph=".$metacat->id."&amp;";
  $path = str_replace("/?","/?meta_id_catph=".$metacat->id."&amp;",$path);
}
else
{
  $catpr->load_by_id($cat->id_catph_parent);
  $self="./?";
  $selfhtml="./?";
}

$root_asso_id = null;

while ( $catpr->is_valid() )
{
  if ( is_null($root_asso_id) && $catpr->meta_mode == CATPH_MODE_META_ASSO )
    $root_asso_id = $catpr->meta_id_asso;

  $path =   $catpr->get_html_link()." / ".$path;
  $catpr->load_by_id($catpr->id_catph_parent);
}


/*
 * Photos
 */

if ( $_REQUEST["action"] == "rertraitphoto" && $photo->is_valid() && $photo->is_on_photo($site->user->id) )
{
  if ( $_REQUEST["mesure"] == "retrait" )
  {
    $photo->remove_photo();
    $photo->id=null;
  }
  elseif ( $_REQUEST["mesure"] == "notonphoto" )
  {
    $photo->remove_personne($site->user->id);
  }
  else
  {
    $photo->donne_accord($user->id);
    require_once($topdir."include/entities/group.inc.php");
    $groups = enumerates_groups($site->db);
    $groupss = array_keys ( $groups, $_REQUEST["mesure"]);
    if ( count($groupss) > 0 )
    {
      $photo->id_groupe = $groupss[0];
      $photo->droits_acces=0x310;
      $photo->save_rights();
    }
  }
}

if ( $photo->is_valid() )
{
  $sqlph = $cat->get_photos ( $cat->id, $site->user, $grps, "sas_photos.id_photo");
  $count=0;
  while ( list($id) = $sqlph->get_row() )
  {
    if ( $id == $photo->id ) $idx = $count;
    $photos[] = $id;
    $count++;
  }

  $can_write = $photo->is_right($site->user,DROIT_ECRITURE);
  $can_comment = $can_write; //|| $photo->is_on_photo($site->user->id);

  if ( $photo->meta_id_asso )
    $phasso->load_by_id($photo->meta_id_asso);

  if ( $photo->id_asso_photographe )
    $ptasso->load_by_id($photo->id_asso_photographe);


  if ( ($_REQUEST["action"] == "addpersonne") && $can_write)
  {
    $utl = new utilisateur($site->db);
    $utl->load_by_id($_REQUEST["id_utilisateur"]);
    if ( $utl->is_valid() )
    {
      $photo->add_personne($utl,true, $site->user->id);
      $Message="Personne ajout&eacute;e : ".$utl->get_html_link();
    }
    else
      $ErrorPersonne="Personne inconnue...";

  }
  elseif ( ($_REQUEST["action"] == "setfull") && $can_write)
  {
    $photo->set_incomplet(false);
    $Message="La liste des personnes a été marquée comme complète.";
  }
  elseif ( ($_REQUEST["action"] == "rotate90") && $can_write)
  {
    $photo->rotate(+90);
  }
  elseif ( ($_REQUEST["action"] == "rotate-90") && $can_write)
  {
    $photo->rotate(-90);
  }
  elseif ( ($_REQUEST["action"] == "delete") && $can_write)
  {
    $photo->remove_personne($_REQUEST["id_utilisateur"]);
    $Message="Personne supprimée.";
  }
  elseif ( ($_REQUEST["action"] == "updatephoto") && $can_write)
  {
    $phasso->load_by_id($_REQUEST["id_asso"]);
    $ptasso->load_by_id($_REQUEST["id_asso_photographe"]);
    $licence=$photo->id_licence;
    if($site->user->id==$photo->id_utilisateur_photographe)
    {
      $licence = new licence($site->db);
      if($licence->load_by_id($_REQUEST['id_licence']))
        $licence=$licence->id;
    }
    $userinfo = new utilisateur($site->db);
    $userinfo->load_by_id($_REQUEST["id_utilisateur_photographe"]);

    $old = htmlentities($photo->get_display_name(),ENT_COMPAT,"UTF-8");

    $photo->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin'],false);
    $photo->update_photo(
      $_REQUEST["date"],
      $_REQUEST["comment"],
      $userinfo->id,
      $phasso->id,
      $_REQUEST["titre"],
      $ptasso->id,
      $licence
      );

    $photo->set_incomplet(isset($_REQUEST["incomplet"]));

    $photo->set_tags($_REQUEST["tags"]);

    // petit hack pour éviter de recalculer tout le chemin
    $new = htmlentities($photo->get_display_name(),ENT_COMPAT,"UTF-8");
    $path = str_replace($old,$new,$path);

  }
  elseif ( ($_REQUEST["action"] == "setcomment") && $can_comment)
  {
    $photo->set_comment ( $_REQUEST["commentaire"], $can_write);
  }
  elseif ( $_REQUEST["action"] == "suggestpersonne" )
  {
    $utl = new utilisateur($site->db);
    $utl->load_by_id($_REQUEST["id_utilisateur"]);
    if ( $utl->is_valid() )
    {
      $photo->add_personne($utl,false, $site->user->id);
      $Message="Personne ajout&eacute;e comme suggestion : ".$utl->get_html_link();;
    }
    else
      $ErrorSuggest="Personne inconnue...";

  }
  elseif ( $_REQUEST["action"] == "suggestcomplet" )
  {
    $photo->set_incomplet(false,true);
    $Message="La liste des personnes a été suggérée comme complète.";
  }
  elseif ( $_REQUEST["action"] == "setweekly" &&  $site->user->is_in_group ("moderateur_site") )
  {
    copy($photo->get_abs_path().$photo->id.".jpg",
         "/var/www/ae2/data/com/weekly_photo.jpg");

    copy($photo->get_abs_path().$photo->id.".diapo.jpg",
         "/var/www/ae2/data/com/weekly_photo-diapo.jpg");

    copy($photo->get_abs_path().$photo->id.".vignette.jpg",
         "/var/www/ae2/data/com/weekly_photo-small.jpg");

    new update ($site->dbrw,"site_boites",
      array ("contenu_boite" => $cat->nom),
      array ("nom_boite" => "Weekly_Photo"));
  }

  if ( ($_REQUEST["page"] == "edit" || $_REQUEST["action"] == "edit") && $can_write )
  {
    $userinfo = new utilisateur($site->db);
    $userinfo->load_by_id($photo->id_utilisateur_photographe);

    $site->start_page("sas","Stock à Souvenirs");

    $cts = new contents($path." / Editer");

    $frm = new form("updatephoto","./?id_photo=".$photo->id);
    $frm->add_hidden("action","updatephoto");
    $frm->add_datetime_field("date","Date et heure de prise de vue",$photo->date_prise_vue);
    $frm->add_text_field("titre","Titre",$photo->titre);
    $frm->add_text_field("tags","Tags (séparateur: virgule)",$photo->get_tags());
    $frm->add_text_area("comment","Commentaire",$photo->commentaire);
    $frm->add_checkbox("incomplet","Liste des personnes incomplète",$photo->incomplet);
    $frm->add_entity_select ( "id_asso", "Association/Club lié", $site->db, "asso",$photo->meta_id_asso,true);
    $frm->add_entity_select ( "id_asso_photographe", "Photographe (club)", $site->db, "asso",$photo->id_asso_photographe,true);
    $frm->add_entity_smartselect ( "id_utilisateur_photographe", "Photographe", $userinfo, true );

    if($site->user->id==$photo->id_utilisateur_photographe)
    {
      $licence = new licence($site->db);
      if($licence->load_by_id($photo->id_licence))
        $licence=$photo->id_licence;
      elseif(!is_null($site->user->id_licence_default_sas))
        $licence=$site->user->id_licence_default_sas;
      $frm->add_entity_select('id_licence','Choix de la licence',$site->db,'licence',$licence,false,array(),'\'id_licence\' ASC');
    }

    $frm->add_rights_field($photo,false,$photo->is_admin($site->user));
    $frm->add_submit("valid","Enregistrer");

    $cts->add($frm);

    $site->add_contents($cts);

    $site->end_page ();
    exit();
  }
  elseif ( $_REQUEST["page"] == "askdelete" )
  {
    $site->start_page("sas","Stock à Souvenirs");

    $cts = new contents($path." / Demande de retrait");

    if ( $photo->is_on_photo($site->user->id) )
    {

      $frm = new form("droitphoto","./?id_photo=".$photo->id."&id_catph=".$cat->id,false,"POST","Votre souhait");
      $frm->add_hidden("action","rertraitphoto");
      $frm->add_hidden("id_photo",$photo->id);

      $frm->add_radiobox_field (  "mesure","",array("utbm"=>"Limiter l'accés aux personnes de l'UTBM."),"ok");

      if ( $site->user->promo_utbm )
        $frm->add_radiobox_field (  "mesure","",array(sprintf("promo%02d",$site->user->promo_utbm)=>"Limiter l'accés à la promo ".$site->user->promo_utbm).".","ok");

      $frm->add_radiobox_field (  "mesure","",array("retrait"=>"Retrait du SAS de la photo. (Attention, ceci est irreversible)."),"ok");
      $frm->add_radiobox_field (  "mesure","",array("notonphoto"=>"Je ne suis pas sur cette photo."),"ok");


      $frm->add_submit("set","Valider");

      $cts->add($frm,true);
    }
    else
      $cts->add_paragraph("Vous n'avez pas été identifitié sur la photo, pour en demander le retrait contactez l'AE");

    $site->add_contents($cts);

    $site->end_page ();
    exit();
  }

  if ( $_REQUEST["fetch"] == "script" )
  {
    echo "window.history.pushState(null, document.title, '?id_photo=".$photo->id."');";
    echo "openInContents( 'cts1', './', '".$exdata."id_photo=".$photo->id."&fetch=photocts');";
    if ( $_REQUEST["diaporama"] > 0 && ( $idx != $count-1 ) )
    {
      echo "cache5.src=\"images.php?/".$photos[$idx+1].".diapo.jpg\";\n";
      echo "setTimeout(\"evalCommand('./', '".$exdata."id_photo=".$photos[$idx+1]."&fetch=script&diaporama=".intval($_REQUEST["diaporama"])."')\", ".intval($_REQUEST["diaporama"]).");";
    }
    exit();
  }

  $cts = new sasphoto ( $path, "./", $cat, $photo, $site->user, $Message, $metacat );

  if ( $_REQUEST["diaporama"] > 0 && ( $idx != $count-1 ) )
  {
    $cts->puts("<script>" .
        "cache1= new Image(); cache1.src=\"".$topdir."images/to_prev.png\";".
        "cache2= new Image(); cache2.src=\"".$topdir."images/to_next.png\";".
        "cache3= new Image(); cache3.src=\"".$topdir."images/icons/16/catph.png\";".
        "cache4= new Image(); cache4.src=\"".$topdir."images/icons/16/photo.png\";".
        "cache5= new Image(); cache5.src=\"images.php?/".$photos[$idx+1].".diapo.jpg\";".
        "cache6= new Image(); cache6.src=\"".$topdir."images/user.png\";".
        "cache7= new Image(); cache7.src=\"".$topdir."images/actions/delete.png\";");
    $cts->puts("setTimeout(\"evalCommand('./', '".$exdata."id_photo=".$photos[$idx+1]."&fetch=script&diaporama=".intval($_REQUEST["diaporama"])."')\", ".intval($_REQUEST["diaporama"]).");");
    $cts->puts("</script>");
  }

  if ( $_REQUEST["fetch"] == "photocts" )
  {
    echo "<h1>".$cts->title."</h1>\n";
    echo $cts->html_render();
    exit();
  }

  $site->start_page("sas","Stock à Souvenirs",true);
  $site->add_contents($cts);
  $site->end_page ();

  exit();
}


if ( $_REQUEST["action"] == "addsubcat" && $cat->is_right($site->user,DROIT_AJOUTCAT) && $GLOBALS["svalid_call"] )
{
  $ErreurAjout=null;

  if ( !$_REQUEST["nom"] )
    $ErreurAjout = "Veuillez précisez un nom";
  elseif ( ($_REQUEST["__rights_add"] & DROIT_AJOUTITEM ) && ($_REQUEST["debut"] > $_REQUEST["fin"] || $_REQUEST["debut"] <= 0) )
    $ErreurAjout = "Dates non valides";
  else
  {
    if ( !$_REQUEST["debut"] )
    {
      $_REQUEST["debut"] = null;
      $_REQUEST["fin"] = null;
    }

    $ncat = new catphoto($site->db,$site->dbrw);
    $ncat->herit($cat,true);
    $ncat->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin'],true);
    $ncat->add_catphoto($cat->id,$_REQUEST["nom"],$_REQUEST["debut"],$_REQUEST["fin"],$_REQUEST["id_asso"],$_REQUEST["mode"],$_REQUEST["id_lieu"]);
    $path .= " / ".$ncat->get_html_link();
    $cat = $ncat;
  }

  if ( $ErreurAjout )
    $_REQUEST["page"] = "subcat";
}
elseif ( $_REQUEST["action"] == "editcat" && $cat->is_right($site->user,DROIT_ECRITURE) && $GLOBALS["svalid_call"] )
{
  if ( !$_REQUEST["nom"] )
    $ErreurEdition = "Veuillez précisez un nom";
  elseif ( ($_REQUEST["__rights_add"] & DROIT_AJOUTITEM ) && ($_REQUEST["debut"] > $_REQUEST["fin"] || $_REQUEST["debut"] <= 0) )
    $ErreurEdition = "Dates non valides";
  else
  {
    if ( !$_REQUEST["debut"] )
    {
      $_REQUEST["debut"] = null;
      $_REQUEST["fin"] = null;
    }

    $cat->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin'],true);
    if ($cat->is_admin($site->user) && $site->user->is_in_group('gestion_ae')) {
      if ($_REQUEST['recursive']) {
        $pht = null;
        $req = $cat->get_photos ($cat->id, $site->user, null);

        while ($pht = $req->get_row ()) {
          $photo->_load ($pht);
          $photo->set_rights($site->user, $_REQUEST['rights'], $_REQUEST['rights_id_group'], $_REQUEST['rights_id_group_admin']);
          $photo->save_rights();
        }
      }
    }

    $photo->load_by_id($_REQUEST["id_photo_index"]);

    $cat->update_catphoto($site->user,$cat->id_catph_parent,$_REQUEST["nom"],$_REQUEST["debut"],$_REQUEST["fin"],$_REQUEST["id_asso"],$_REQUEST["mode"],$_REQUEST["id_lieu"]);

    $cat->set_photo($photo->id);
    $path =   $cat->get_html_link();
    $catpr->load_by_id($cat->id_catph_parent);
    while ( $catpr->is_valid() )
    {
      $path =   $catpr->get_html_link()." / ".$path;
      $catpr->load_by_id($catpr->id_catph_parent);
    }
  }
  if ( $ErreurEdition )
    $_REQUEST["page"] = "edit";
}

if ( ( $_REQUEST["page"] == "edit" || $_REQUEST["action"] == "edit") && $cat->is_right($site->user,DROIT_ECRITURE) )
{
  $site->start_page("sas","Stock à Souvenirs");
  $cts = new contents($path." / Editer");


  $frm = new form("editcat","./?id_catph=".$cat->id);
  $frm->allow_only_one_usage();
  $frm->add_hidden("action","editcat");
  if ( $ErreurEdition )
    $frm->error($ErreurEdition);
  $frm->add_text_field("nom","Nom",$cat->nom,true);
  $frm->add_text_field("id_photo_index","N° de la photo de la miniature",$cat->id_photo,true);
  $frm->add_datetime_field("debut","Date et heure de début",$cat->date_debut,true);
  $frm->add_datetime_field("fin","Date et heure de fin",$cat->date_fin,true);
  $frm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",$cat->meta_id_asso,true);
  $frm->add_entity_select("id_lieu", "Lieu", $site->db, "lieu",$cat->id_lieu,true);
  $frm->add_select_field("mode","Mode",$GLOBALS['catph_modes'],$cat->meta_mode);
  if ($cat->is_admin($site->user) && $site->user->is_in_group('gestion_ae'))
    $frm->add_checkbox("recursive", "Appliquer récursivement les droits", false);
  $frm->add_rights_field($cat,true,$cat->is_admin($site->user));
  $frm->add_submit("valid","Enregistrer");

  $cts->add($frm);

  $site->add_contents($cts);
  $site->end_page ();
  exit();
}

if ( $_REQUEST["page"] == "subcat" && $cat->is_right($site->user,DROIT_AJOUTCAT) )
{
  $site->start_page("sas","Stock à Souvenirs");
  $cts = new contents($path." / Nouvelle sous-catégorie");

  $cts->add_paragraph("Remarque: la nouvelle catégorie sera visible des autres utilisateurs dès qu'elle sera modérée.");

  $frm = new form("addsubcat","./?id_catph=".$cat->id);
  $frm->allow_only_one_usage();
  $frm->add_hidden("action","addsubcat");
  if ( $ErreurAjout )
    $frm->error($ErreurAjout);
  $frm->add_text_field("nom","Nom","",true);
  $frm->add_datetime_field("debut","Date et heure de début",-1,true);
  $frm->add_datetime_field("fin","Date et heure de fin",-1,true);
  $frm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",false,true);
  $frm->add_entity_select("id_lieu", "Lieu", $site->db, "lieu",false,true);

  $frm->add_select_field("mode","Mode",$GLOBALS['catph_modes'],CATPH_MODE_NORMAL);
  $frm->add_rights_field($cat,true,$cat->is_admin($site->user));
  $frm->add_submit("valid","Ajouter");

  $cts->add($frm);

  $site->add_contents($cts);
  $site->end_page ();
  exit();
}
elseif ( $_REQUEST["action"] == "paste" && isset($_SESSION["sas_clipboard"]) )
{
  $infphoto = new photo($site->db,$site->dbrw);
  $infcat = new catphoto($site->db,$site->dbrw);

  if ( $cat->is_right($site->user,DROIT_AJOUTITEM) && isset($_SESSION["sas_clipboard"]["photos"]) && count($_SESSION["sas_clipboard"]["photos"])>0 )
  {
    foreach( $_SESSION["sas_clipboard"]["photos"] as $id )
    {
      $infphoto->load_by_id($id);
      $infphoto->move_to($cat->id);
    }
    unset($_SESSION["sas_clipboard"]["photos"]);
  }

  if ( $cat->is_right($site->user,DROIT_AJOUTCAT) && isset($_SESSION["sas_clipboard"]["categories"]) && count($_SESSION["sas_clipboard"]["categories"])>0 )
  {
    foreach( $_SESSION["sas_clipboard"]["categories"] as $id )
    {
      $infcat->load_by_id($id);
      $infcat->move_to($cat->id);
    }
    unset($_SESSION["sas_clipboard"]["categories"]);
  }

  if ( !isset($_SESSION["sas_clipboard"]["categories"]) && !isset($_SESSION["sas_clipboard"]["photos"]) )
    unset($_SESSION["sas_clipboard"]);
}
elseif ( $_REQUEST["action"] == "emptyclpbrd" && isset($_SESSION["sas_clipboard"]) )
{
  unset($_SESSION["sas_clipboard"]);
}
elseif( isset($_REQUEST["undo_cut_pic"]) ) {
  unset( $_SESSION["sas_clipboard"]["photos"][$_REQUEST["undo_cut_pic"]] );

  if( empty($_SESSION["sas_clipboard"]["photos"]) && empty($_SESSION["sas_clipboard"]["categories"]) )
    unset( $_SESSION["sas_clipboard"] );
}
elseif( isset($_REQUEST["undo_cut_cat"]) ) {
  unset( $_SESSION["sas_clipboard"]["categories"][$_REQUEST["undo_cut_cat"]] );

  if( empty($_SESSION["sas_clipboard"]["photos"]) && empty($_SESSION["sas_clipboard"]["categories"]) )
    unset( $_SESSION["sas_clipboard"] );
}

/*
 * Listing catégories
 */
$site->start_page("sas","Stock à Souvenirs");

function cats_produde_gallery ( $sqlct)
{
  global $topdir,$site;
  $scat=new catphoto($site->db);
  $gal = new gallery(false,"cats",false,false,"id_catph",array("edit"=>"Editer","delete"=>"Supprimer"));
  while ( $row = $sqlct->get_row() )
  {
    $img = $topdir."images/misc/sas-default.png";
    if ( $row['id_photo'] )
      $img = "images.php?/".$row['id_photo'].".vignette.jpg";

    $scat->_load($row);
    $acts=false;
    if ( $scat->is_right($site->user,DROIT_ECRITURE) )
      $acts = array("delete","edit");

    $gal->add_item(
        "<a href=\"./?id_catph=".$row['id_catph']."\"><img src=\"$img\" alt=\"".$row['nom_catph']."\" /></a>",
        "<a href=\"./?id_catph=".$row['id_catph']."\">".$row['nom_catph']."</a> (".$scat->get_short_semestre().")",
        $row['id_catph'],
        $acts);
  }
  return $gal;
}




if ( $cat->id == 1 )
{
  $page = new page ($site->db);
  $page->load_by_pagename("info:sas");

  if ( !$page->is_valid() )
    $site->add_contents(new contents("Bienvenue dans le Stock à Souvenirs (SAS)"));
  else
    $site->add_contents($page->get_contents());

  $cts = new contents("Ajouts r&eacute;cents");

  $cts->add(cats_produde_gallery($cat->get_recent_photos_categories($site->user,$grps)));

  $site->add_contents($cts);

}

if ( isset($_SESSION["sas_clipboard"]) )
{
  $infphoto = new photo($site->db);
  $infcat = new catphoto($site->db);

  $cts = new contents("Presse papier");

  if ( $cat->is_right($site->user,DROIT_AJOUTITEM) || $cat->is_right($site->user,DROIT_AJOUTCAT) )
    $cts->add_paragraph("<a href=\"index.php?id_catph=".$cat->id."&amp;action=paste\">Deplacer ici</a><br/><br/>");

  $cts->add_paragraph("<a href=\"index.php?id_catph=".$cat->id."&amp;action=emptyclpbrd\">Vider le presse papier</a>");

  $lst = new itemlist("Contenu");

  if ( isset($_SESSION["sas_clipboard"]["photos"]) && count($_SESSION["sas_clipboard"]["photos"])>0 )
  {
    foreach( $_SESSION["sas_clipboard"]["photos"] as $id )
    {
      $infphoto->load_by_id($id);
      $clip_txt = $infphoto->get_html_link();
      $clip_txt .= " | <a href=\"index.php?id_catph=".$cat->id."&amp;undo_cut_pic=".$id."\">retirer du presse papier</a>";

      $lst->add($clip_txt);
    }
  }

  if ( isset($_SESSION["sas_clipboard"]["categories"]) && count($_SESSION["sas_clipboard"]["categories"])>0 )
  {
    foreach( $_SESSION["sas_clipboard"]["categories"] as $id )
    {
      $infcat->load_by_id($id);
      $clip_txt = $infcat->get_html_link();
      $clip_txt .= " | <a href=\"index.php?id_catph=".$cat->id."&amp;undo_cut_cat=".$id."\">retirer du presse papier</a>";
      $lst->add($clip_txt);
    }
  }

  $cts->add($lst,true);

  $site->add_contents($cts);
}


if ( $metacat->is_valid() || $cat->meta_mode == CATPH_MODE_META_ASSO || !is_null($root_asso_id) )
{

  if ( $cat->meta_mode == CATPH_MODE_META_ASSO )
    $asso->load_by_id($cat->meta_id_asso);
  else if ( !is_null($root_asso_id) )
    $asso->load_by_id($root_asso_id);
  else
    $asso->load_by_id($metacat->meta_id_asso);

  $cts = new contents($asso->get_html_path());
  $site->start_page("presentation","Photos"); // Et oui, on peut le refaire quand on veut !

  $cts->add(new tabshead($asso->get_tabs($site->user),"photos"));
  $cts->add_title(1,$path);
}
else
  $cts = new contents($path);

if ( $cat->id == 1 )
  $cts->add_paragraph("<a href=\"search.php\">Recherche</a>");

// Sous-catégories
if ( $cat->is_right($site->user,DROIT_AJOUTCAT) )
  $cts->add_paragraph("<a href=\"./?id_catph=".$cat->id."&amp;page=subcat\">Ajouter une catégorie dans ".$cat->nom."</a>");

if ( !is_null($cat->date_debut) )
  $cts->add(new reactonforum ( $site->db, $site->user, $cat->nom, array("id_catph"=>$cat->id), $cat->meta_id_asso, true ));

$cts->add(new sascategory ( "./", $cat, $site->user ));
// --> voir include/cts/sas.inc.php


// Photos
$sqlcntph = $cat->get_photos ( $cat->id, $site->user, $grps, "COUNT(*)");

list($nb) = $sqlcntph->get_row();

if ( $nb>0 )
  $site->add_rss("Gallerie: ".$cat->nom,"rss.php?id_catph=".$cat->id);

if ( $nb>0 || $cat->is_right($site->user,DROIT_AJOUTITEM) )
{

  if ( $nb>0)
  {
    $req = new requete($site->db, "SELECT COUNT(*) FROM `sas_photos` ".
      "WHERE `incomplet`='1' AND `id_catph`='".intval($cat->id)."' AND (`droits_acces_ph` & 0x100) AND `id_utilisateur` ='".$site->user->id."'");
    list($nbtcus)=$req->get_row();

    if ( $cat->is_admin($site->user) )
    {
      $req = new requete($site->db, "SELECT COUNT(*) FROM `sas_photos` ".
        "WHERE `incomplet`='1' AND `id_catph`='".intval($cat->id)."' AND `id_groupe_admin` ='".$cat->id_groupe_admin."'");
      list($nbtcad)=$req->get_row();
    }
    else
      $nbtcad=0;
  }
  else
  {
    $nbtcad=0;
    $nbtcus=0;
  }
  if ( $site->user->is_in_group("sas_admin") && intval($cat->id) == 1)
  {
      $req = new requete($site->db, "SELECT COUNT(*) FROM `sas_photos` ".
        "WHERE `incomplet`='1'");
      list($nbtcad)=$req->get_row();
  }
  if( intval($cat->id) == 1 )
  {
    $req = new requete($site->db, "SELECT COUNT(*) FROM `sas_photos` ".
      "WHERE `incomplet`='1' AND (`droits_acces_ph` & 0x100) AND `id_utilisateur` ='".$site->user->id."'");
    list($nbtcus)=$req->get_row();
    
  }

  $tabs = array(array("","sas2/".$self."id_catph=".$cat->id, "photos - $nb"),
          array("diaporama","sas2/".$self."view=diaporama&id_catph=".$cat->id,"diaporama"),
          array("tools","sas2/".$self."view=tools&id_catph=".$cat->id,($nbtcad>0||$nbtcus>0)?"<b>outils !!</b>":"outils"),
          array("stats","sas2/".$self."view=stats&id_catph=".$cat->id,"statistiques"));

  if ($cat->is_right($site->user,DROIT_AJOUTITEM) )
    $tabs[] =array("add","sas2/".$self."view=add&id_catph=".$cat->id,"Ajouter");

  $cts->add(new tabshead($tabs,$_REQUEST["view"]));
}

if ( $_REQUEST["view"] == "tools" )
{


  if ( $nbtcus > 0 )
    $cts->add_paragraph("<a href=\"complete.php?mode=userphoto&id_catph=".$cat->id."\">Identification des personnes sur mes photos ($nbtcus)</a>");

  if ( $cat->is_admin($site->user) )
  {

    $cts->add_paragraph("<a href=\"complete.php?mode=adminzone&id_catph=".$cat->id."\">Identification des personnes sur les photos ($nbtcad)</a>");

  }
}
elseif ( $_REQUEST["view"] == "stats" )
{



}
elseif ( $_REQUEST["view"] == "add" && $cat->is_right($site->user,DROIT_AJOUTITEM) )
{

  if ( $metacat->is_valid() )
    $asso->load_by_id($metacat->meta_id_asso);

  $cts->add_paragraph("<br/>Si vous voulez ajouter de nombreuses photos, " .
      "nous vous conseillons d'utiliser le logiciel UBPT Transfert qui " .
      "vous permet d'envoyer plusieurs photos en même temps de façon automatisée.<br/> " .
      "<a href=\"../article.php?name=sas:transfert\">Télécharger UBPT Transfert</a> (Disponible pour Windows, Mac OS X et Linux)");

  $cts->add_paragraph("Après avoir ajout&eactute; vos photos, il faut <b>IMPERATIVEMENT renseigner les noms des personnes</b> " .
      "se trouvant sur les photos pour que ces dernières puissent être visibles de tous.<br/>");

  $cts->add_paragraph("Remarque: L'ajout de vidéos n'est possible qu'avec UBPT Transfert <b>version 2.2</b><br/>");


  $frm = new form("setfull",$self."id_catph=".$cat->id);
  $frm->add_hidden("action","addphoto");
  $frm->allow_only_one_usage();
  if ( $ErreurUpload )
    $frm->error($ErreurUpload);
  $frm->add_file_field("file","Fichier",true);
  $frm->add_text_field("titre","Titre",$photo->titre);
  $frm->add_text_area("comment","Commentaire");
  $frm->add_checkbox("personne","Il n'y a personne sur la photo (reconaissable)");
  $frm->add_entity_select ( "id_asso", "Association/Club lié", $site->db, "asso",$asso->id,true);
  $frm->add_entity_select ( "id_asso_photographe", "Photographe", $site->db, "asso",null,true);

  $frm->add_rights_field($cat,false,$cat->is_admin($site->user));
  $sfrm = new form("auteur",null,null,null,"Je certifie être le photographe");
  $sfrm->add_entity_select('id_licence','Choix de la licence',$site->db,'licence',false,false,array(),'\'id_licence\' ASC');
  $frm->add($sfrm,false,true, true, 1, false, true);
  $sfrm = new form("auteur",null,null,null,"Je ne suis pas le photographe");
  $sfrm->add_user_fieldv2('photographe','Photographe');
  $frm->add($sfrm,false,true, false, 0, false, true);
  $frm->add_submit("valid","Ajouter");

  $cts->add($frm);
}
elseif ( $_REQUEST["view"] == "diaporama" && $nb > 0 )
{
  $sqlph = $cat->get_photos ( $cat->id, $site->user, $grps, "sas_photos.id_photo", " LIMIT 1");
  list($id_photo)=$sqlph->get_row();


  $cts->add_paragraph("<br/>Selectionnez l'interval entre deux photos, et lancez le diaporama.<br/>");

  $frm = new form("diaporama",$self."id_photo=".$id_photo);
  $frm->add_select_field("diaporama","Interval",array(1000=>"1 seconde",3000=>"3 secondes",5000=>"5 secondes"),3000);
  $frm->add_submit("valid","Lancer");

  $cts->add($frm);
}
elseif ( $nb )
{
  $can_sethome = $cat->is_right($site->user,DROIT_ECRITURE);

  $page = intval($_REQUEST["page"]);
  $npp=SAS_NPP;
  $st=$page*$npp;

  $sqlph = $cat->get_photos ( $cat->id, $site->user, $grps, "sas_photos.*", " LIMIT $st,$npp");

  $gal = new gallery(false,"photos","phlist",$self."id_catph=".$cat->id,"id_photo",array("delete"=>"Supprimer","edit"=>"Editer","cut"=>"Couper","sethome"=>"Definir comme photo de présentation"));
  while ( $row = $sqlph->get_row() )
  {
    $photo->_load($row);
    $img = "images.php?/".$photo->id.".vignette.jpg";
    $acts=array();
    if ( $photo->is_right($site->user,DROIT_ECRITURE) )
      $acts = array("delete","edit","cut");
    if ( $can_sethome && $photo->droits_acquis)
      $acts[]="sethome";

    if ( $row['type_media_ph'] == 1 )
      $gal->add_item("<a href=\"".$selfhtml."id_photo=".$photo->id."\"><img src=\"$img\" alt=\"Photo\">".
          "<img src=\"".$wwwtopdir."images/icons/32/multimedia.png\" alt=\"Video\" class=\"ovideo\" /></a>","",$photo->id, $acts );
    else
      $gal->add_item("<a href=\"".$selfhtml."id_photo=".$photo->id."\"><img src=\"$img\" alt=\"Photo\"></a>","",$photo->id, $acts );
  }
  $cts->add($gal);

  if ( $nb > $npp )
  {
    $tabs = array();
    $i=0;
    while ( $i < $nb )
    {
      $n = $i/$npp;
      $tabs[]=array($n,"sas2/".$self."id_catph=".$cat->id."&page=".$n,$n+1 );
      $i+=$npp;
    }
    $cts->add(new tabshead($tabs, $page, "_bottom"));
  }
}

$site->add_contents($cts);

$site->end_page ();


?>
