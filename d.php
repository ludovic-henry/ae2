<?php

/* Copyright 2006,2007
 *
 * - Maxime Petazzoni < sam at bulix dot org >
 * - Laurent Colnat < laurent dot colnat at utbm dot fr >
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

/**
 * @file Navigateur des dossiers virtuel
 * @see include/entities/files.inc.php
 * @see include/entities/folder.inc.php
 */

$topdir="./";
require_once($topdir."include/site.inc.php");
require_once($topdir."include/entities/asso.inc.php");
require_once($topdir."include/entities/files.inc.php");
require_once($topdir."include/entities/folder.inc.php");
require_once($topdir."include/cts/taglist.inc.php");

$site = new site();
$site->add_css("css/d.css");
$file = new dfile($site->db, $site->dbrw);
$folder = new dfolder($site->db, $site->dbrw);
$asso_folder = new asso($site->db);

//session_write_close(); // on n'a plus besoin de la session, liberons le semaphore...

if(!is_dir("/var/www/ae2/data/files"))
  $site->fatal_partial("fichiers");

$section="fichiers";

if ( isset($_REQUEST["id_file"]))
{
  if ( isset($_REQUEST["rev"]))
    $file->load_by_id_and_rev($_REQUEST["id_file"],$_REQUEST["rev"]);
  else
    $file->load_by_id($_REQUEST["id_file"]);
  if ( $file->is_valid() )
  {
    if ( !$file->is_right($site->user,DROIT_LECTURE) )
      $site->error_forbidden($section,"group",$file->id_groupe);

    $folder->load_by_id($file->id_folder);
  }
  else
    $site->error_not_found($section);

}

if( ($_REQUEST['action'] == "accepter" || $_REQUEST['action'] == "refuser") && $site->user->is_in_group("moderateur_site") ) {
  if( $file->id > 0 ) {
    if($_REQUEST['action'] == "accepter")
      $file->set_modere();
    else
      $file->delete_file_rev();

    unset($file);

    $file = new dfile($site->db, $site->dbrw);
    $file->load_by_id($_REQUEST["id_file"]);

    if( !$file->is_valid() )
      $site->error_not_found($section);
  }
}

// "Exception"
if ( $_REQUEST["action"] == "download" && $file->is_valid() )
{
  if ( $_REQUEST["download"] == "thumb" )
  {
    $filename = $file->get_thumb_filename();
    if ( ! file_exists($filename) )
    {
      $icon = $file->get_icon_name();
      $site->return_simplefile( "icon128".$icon, "image/png", $topdir."images/icons/128/".$icon );
    }
    elseif( $file->mime_type=="image/png" )
      $site->return_simplefile( "dthumb".$file->id, "image/png", $filename );
    else
      $site->return_simplefile( "dthumb".$file->id, "image/jpeg", $filename );
    exit();
  }
  elseif ( $_REQUEST["download"] == "preview" )
  {
    $filename = $file->get_screensize_filename();
    if ( ! file_exists($filename) )
    {
      $icon = $file->get_icon_name();
      $site->return_simplefile( "icon128".$icon, "image/png", $topdir."images/icons/128/".$icon );
    }
    elseif( $file->mime_type=="image/png" )
      $site->return_simplefile( "dpreview".$file->id, "image/png", $filename );
    else
      $site->return_simplefile( "dpreview".$file->id, "image/jpeg", $filename );
    exit();
  }
  $file->increment_download();
  $filename = $file->get_real_filename();

  header("Content-Disposition: filename=".$file->nom_fichier);

  if ( file_exists($filename) )
    $site->return_simplefile( $file->nom_fichier, $file->mime_type, $filename );

  exit();
}

if ( isset($_REQUEST["id_folder"]) && !( isset($_REQUEST["id_file"]) && $file->is_valid() ) )
  $folder->load_by_id($_REQUEST["id_folder"]);

if ( !$folder->is_valid() )
{
  $file->id = null;
  if ( isset($_REQUEST["id_asso"]) ) // On veut le dossier racine d'une association
  {
    $asso = new asso($site->db);
    $asso->load_by_id($_REQUEST["id_asso"]);
    $asso_folder->load_by_id($_REQUEST["id_asso"]);
    if ( $asso_folder->is_valid() && (!$asso->hidden||$site->user->is_in_group("root")) ) // L'association existe, chouette
    {
      $folder->load_root_by_asso($asso_folder->id);
      if ( !$folder->is_valid() ) // Le dossier racine n'existe pas... on va le creer :)
      {
        $folder->id_groupe_admin = $asso_folder->get_bureau_group_id(); // asso-bureau
        $folder->id_groupe = $asso_folder->get_membres_group_id(); // asso-membre
        $folder->droits_acces = 0xDDD;
        $folder->id_utilisateur = null;
        $folder->add_folder ( $section, null, null, $asso_folder->id );
      }
    }
    elseif( !$asso->hidden||$site->user->is_in_group("root") )
      $folder->load_by_id(1);
  }
  else
    $folder->load_by_id(1);
}

if ( !$folder->is_right($site->user,DROIT_LECTURE) )
  $site->error_forbidden($section,"group",$folder->id_groupe);

if( $_REQUEST["action"] == "emptyclpbrd" ) {
  unset( $_SESSION["d_clipboard"] );
}

if( isset($_REQUEST["undo_cut_file"]) ) {
    unset( $_SESSION["d_clipboard"]["I".$_REQUEST['undo_cut_file']] );

    if( empty($_SESSION["d_clipboard"]) )
      unset($_SESSION["d_clipboard"]);
}

if( isset($_REQUEST["undo_cut_folder"]) ) {
    unset( $_SESSION["d_clipboard"]["O".$_REQUEST['undo_cut_folder']] );

    if( empty($_SESSION["d_clipboard"]) )
      unset($_SESSION["d_clipboard"]);
}

if ( $_REQUEST["action"] == "cut" )
{

  if ( $file->is_valid() && $file->is_right($site->user,DROIT_ECRITURE) )
  {
    $_SESSION["d_clipboard"]["I".$file->id] = $file->id;
        $file->id=null;
  }
  elseif ( $folder->id_folder_parent && $folder->is_right($site->user,DROIT_ECRITURE) ) // la racine ne peut pas être coupée
  {
    $_SESSION["d_clipboard"]["O".$folder->id] = $folder->id;
    $folder->load_by_id($folder->id_folder_parent);
  }
}
elseif ( $file->is_valid() && $_REQUEST["action"] == "delete" )
{
  if ( $file->is_right($site->user,DROIT_ECRITURE)
       && $site->is_sure($section, 'Suppression du fichier '.$file->get_display_name()))
  {
    _log($site->dbrw,'suppression fichier','Suppression du fichier '.$file->get_display_name(),'fichier',$site->user);
    $file->delete_file();
    $file->id=null;
  }
}
elseif ( $folder->is_valid() && $_REQUEST["action"] == "delete" )
{
  if ( $folder->is_right($site->user,DROIT_ECRITURE) )
    if ( $site->is_sure ( "","Suppression du dossier ".$folder->get_display_name(),"folder".$folder->id, 3 ) )
    {
      _log($site->dbrw,'suppression dossier','Suppression du dossier '.$folder->get_display_name(),'fichier',$site->user);
      $folder->delete_folder();
      $folder->load_by_id($folder->id_folder_parent);
      if ( !$folder->is_valid() )
        $folder->load_by_id(1);
      if ( !$folder->is_right($site->user,DROIT_LECTURE) )
        $site->error_forbidden($section,"group",$folder->id_groupe);
    }
}


if ( $_REQUEST["action"] == "davmount" )
{
  /*
   * Support RFC 4709
   * http://www.ietf.org/rfc/rfc4709.txt
   */

  $rpath = rawurlencode($folder->nom_fichier);

  $pfolder = new dfolder($site->db);
  $pfolder->load_by_id($folder->id_folder_parent);
  while ( $pfolder->is_valid() )
  {
    $rpath = rawurlencode($pfolder->nom_fichier)."/".$rpath;
    $pfolder->load_by_id($pfolder->id_folder_parent);
  }

  if ( !$site->user->is_valid() )
    $url = "http://".$_SERVER["HTTP_HOST"].dirname($_SERVER["SCRIPT_NAME"])."/webdav.php/";
  else
    $url = "https://".$_SERVER["HTTP_HOST"].dirname($_SERVER["SCRIPT_NAME"])."/webdav.php/";

  header("Content-Type: application/davmount+xml");
  header("Cache-Control: private");
  header("Content-Disposition: filename=".$folder->id.".davmount");

  echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  echo "<dm:mount xmlns:dm=\"http://purl.org/NET/webdav/mount\">\n";
  echo "  <dm:url>".htmlspecialchars($url,ENT_NOQUOTES,"UTF-8")."</dm:url>\n";
  echo "  <dm:open>".htmlspecialchars($rpath,ENT_NOQUOTES,"UTF-8")."/</dm:open>\n";

  if ( $site->user->is_valid()
       /*&& !preg_match('/^\/var\/www\/ae\/www\/(taiste|taiste21)\//', $_SERVER['SCRIPT_FILENAME'])*/ )
    echo "  <dm:username>".htmlspecialchars($site->user->email,ENT_NOQUOTES,"UTF-8")."</dm:username>\n";

  echo "</dm:mount>\n";

  exit();
}

if ( $file->is_valid() )
  $path = $folder->get_html_link()." / ".$file->get_html_link();
else
  $path = $folder->get_html_link();

$id_asso = $folder->id_asso;

$pfolder = new dfolder($site->db);
$pfolder->load_by_id($folder->id_folder_parent);

while ( $pfolder->is_valid() )
{
  $id_asso = $pfolder->id_asso;
  $path = $pfolder->get_html_link()." / $path";
  $pfolder->load_by_id($pfolder->id_folder_parent);
}

if ( $id_asso )
{
  $asso_folder->load_by_id($id_asso);
  /*if ( $asso_folder->is_valid() )
    $path = $asso_folder->get_html_link()." / $path";*/
  $section="presentation";
}

if ( $_REQUEST["action"] == "addfolder" && $folder->is_right($site->user,DROIT_AJOUTCAT) )
{
  $file->id=null;
  if ( !$_REQUEST["nom"] )
  {
    $_REQUEST["page"] = "newfolder";
    $ErreurAjout="Veuillez préciser un nom pour le dossier";
  }
  else
  {
    $asso = new asso($site->db);
    $asso->load_by_id($_REQUEST["id_asso"]);

    $nfolder = new dfolder($site->db,$site->dbrw);
    $nfolder->herit($folder);
    $nfolder->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);

    $nfolder->add_folder ( $_REQUEST["nom"], $folder->id, $_REQUEST["description"], $asso->id );

    $folder = $nfolder;
    $path .= " / ".$folder->get_html_link();

    if (isset($_REQUEST['automodere']))
      if ($site->user->is_in_group ("moderateur_site") && $_REQUEST['automodere'])
        $folder->set_modere ();
  }
}
elseif ( $_REQUEST["action"] == "addfile" && $folder->is_right($site->user,DROIT_AJOUTITEM) )
{
  if ( !$_REQUEST["nom"] )
  {
    $_REQUEST["page"] = "newfolder";
    $ErreurAjout="Veuillez préciser un nom pour le fichier.";
  }
  elseif( !is_uploaded_file($_FILES['file']['tmp_name']) || ($_FILES['file']['error'] != UPLOAD_ERR_OK ) )
  {
    $_REQUEST["page"] = "newfolder";
    $ErreurAjout="Erreur lors du transfert.";
  }
  else
  {
    $asso = new asso($site->db);
    $asso->load_by_id($_REQUEST["id_asso"]);
    $file->herit($folder);
    $file->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);
    $file->add_file ( $_FILES["file"], $_REQUEST["nom"], $folder->id, $_REQUEST["description"],$asso->id );
    $file->set_tags($_REQUEST["tags"]);

    if (isset($_REQUEST['automodere']))
      if ($site->user->is_in_group ("moderateur_site") && $_REQUEST['automodere'])
        $file->set_modere ();
  }
}


if ( $file->is_valid() )
{
  if ( $_REQUEST["action"] == "cancelborrow" && $file->is_right($site->user,DROIT_ECRITURE) )
  {
    $file->unlock($site->user);
    $Notice = "Emprunt annulé";
  }
  elseif ( $_REQUEST["action"] == "returnfile" && $file->is_right($site->user,DROIT_ECRITURE) )
  {
    //ErreurReturn

    if ( $file->is_locked($site->user) )
      $ErreurReturn="Impossible de restituer le fichier";
    elseif( !is_uploaded_file($_FILES['file']['tmp_name']) || ($_FILES['file']['error'] != UPLOAD_ERR_OK ) )
      $ErreurReturn="Erreur lors du transfert";
    else
    {
      $file->new_revision ( $_FILES["file"], $site->user, $_REQUEST["comment"] );
      $file->unlock($site->user);
      $Notice = "Fichier restitué";
    }

  }
  elseif ( $_REQUEST["action"] == "borrow" && $file->is_right($site->user,DROIT_ECRITURE) )
  {
    if ( $file->is_locked($site->user) )
      $Notice="Impossible d'emprunter le fichier";
    else
    {
      $file->lock($site->user);
      $Notice = "Fichier emprunté";
      header("Location: d.php?id_file=".$file->id."&action=download");
    }
  }
  elseif ( $_REQUEST["action"] == "save" && $file->is_right($site->user,DROIT_ECRITURE) )
  {
    if ( $_REQUEST["nom"] )
    {
      $asso = new asso($site->db);
      $asso->load_by_id($_REQUEST["id_asso"]);
      $file->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);
      $file->update_file( $_REQUEST["nom"], $_REQUEST["description"],$asso->id );
      $file->set_tags($_REQUEST["tags"]);
    }
  }
  elseif ( $_REQUEST["action"] == "edit" && $file->is_right($site->user,DROIT_ECRITURE) )
  {
    $site->start_page($section,"Fichiers");
    $cts = new contents($path." / Editer");

    $frm = new form("savefile","d.php?id_file=".$file->id);
    $frm->add_hidden("action","save");
    $frm->add_text_field("nom","Nom",$file->titre,true);
    $frm->add_text_field("tags","Tags (séparateur: virgule)",$file->get_tags());
    $frm->add_text_area("description","Description",$file->description);

    $frm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",$file->id_asso,true);
    $frm->add_rights_field($file,false,$file->is_admin($site->user),"files");
    $frm->add_submit("valid","Enregistrer");

    $cts->add($frm);


    $site->add_contents($cts);
    $site->end_page();
    exit();
  }

  $user = new utilisateur($site->db);
  $user->load_by_id($file->id_utilisateur);

  $site->start_page($section,"Fichiers");


  if ( $asso_folder->is_valid() )
  {
    $cts = new contents($asso_folder->nom);
    $cts->add(new tabshead($asso_folder->get_tabs($site->user),"files"));
    $cts->add_title(1,$path);
  }
  else
    $cts = new contents($path);

  if( !$file->is_moderated() ) {
    $mod_text = '<p class="alertunmod">Attention : fichier non modéré.</p>';

    if( $site->user->is_in_group("moderateur_site") ) {
      $mod_text .= "<br /><br /><b><a href=\"d.php?id_file=".$file->id."&amp;action=accepter\">Accepter ce fichier</a></b>";
      $mod_text .= "<br /><b><a href=\"d.php?id_file=".$file->id."&amp;action=refuser\">Supprimer / revenir à la version précédente</a></b>";
    }

    $cts->add_paragraph($mod_text);
  }

  if ( isset($Notice) )
    $cts->add_paragraph("<b>$Notice</b>");

    $actions = array();

  $filename = $file->get_thumb_filename();
  if ( !file_exists($filename) )
    $thumburl = $topdir."images/icons/128/".$file->get_icon_name();
  else
  {
    $thumburl = $file->get_thumb_filename();
    $actions[] = "<a href=\"d.php?id_file=".$file->id."&amp;action=download&amp;download=preview\">Aperçu</a>";
  }

  $cts->add(new image("Miniature",$thumburl,"imgright"));
  $cts->add( new wikicontents ("Description",$file->description),true );

  $actions[] = "<a href=\"d.php?id_file=".$file->id."&amp;action=download\">T&eacute;l&eacute;charger</a>";

  if ( $file->is_right($site->user,DROIT_ECRITURE) )
  {
    $actions[] = "<a href=\"d.php?id_file=".$file->id."&amp;action=edit\">Editer</a>";
    $actions[] = "<a href=\"d.php?id_file=".$file->id."&amp;action=delete\">Supprimer</a>";
  }


  $cts->add(new itemlist(false,false,$actions));

  if ($file->mime_type == "audio/mpeg" )
  {
    require_once($topdir."include/cts/player.inc.php");
    $cts->add(new mp3player("Ecouter","../../d.php?id_file=".$file->id."&action=download"),true);
  }

  $cts->add(new itemlist("Informations",false,
      array(
        "ID: ".$file->id,
        "Taille: ".$file->taille." Octets",
        "Type: ".$file->mime_type,
        "Date d'ajout: ".date("d/m/Y",$file->date_ajout),
        "Nom r&eacute;el: ".$file->nom_fichier,
        "Nombre de t&eacute;l&eacute;chargements: ".$file->nb_telechargement,
        "Propos&eacute; par : ". $user->get_html_link()
      )),true);


  if ( $file->is_right($site->user,DROIT_ECRITURE) )
  {
    $lock = $file->get_lock();

    if ( $lock )
    {
      if ( $lock['id_utilisateur'] == $site->user->id )
      {
        $cts->add_title(2,"Restituer le fichier");

        $frm = new form("addfile","d.php?id_file=".$file->id);
        $frm->allow_only_one_usage();
        $frm->add_hidden("action","returnfile");
        if ( $ErreurReturn )
          $frm->error($ErreurReturn);
        $frm->add_file_field("file","Fichier",true);
        $frm->add_text_area("comment","Commentaire","");
        $frm->add_submit("valid","Restituer");

        $cts->add($frm);

        $cts->add_paragraph("<a href=\"d.php?id_file=".$file->id."&amp;action=cancelborrow\">Annuler l'emprunt</a>");
      }
      else
      {
        $cts->add_title(2,"Fichier emprunté");
        $user = new utilisateur($site->db);
        $user->load_by_id($lock['id_utilisateur']);
        $cts->add_paragraph(
          "Emprunté par ".$user->get_html_link().
          " depuis le ".date("d/m/Y H:i",strtotime($lock['time_file_lock'])));
      }
    }
    else
    {
      $cts->add_title(2,"Emprunter le fichier");
      $cts->add_paragraph("<a href=\"d.php?id_file=".$file->id."&amp;action=borrow\">Emprunter et télécharger le fichier</a>");
    }
  }

  $cts->add_title(2,"Historique");

  $req = new requete($site->db,"SELECT ".
  "id_rev_file, date_rev_file, comment_rev_file, ".
  "COALESCE(CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`), alias_utl) AS `nom_utilisateur` ".
  "FROM d_file_rev ".
  "INNER JOIN utilisateurs ON ( d_file_rev.id_utilisateur_rev_file=utilisateurs.id_utilisateur) ".
  "WHERE id_file='".$file->id."' ".
  "ORDER BY date_rev_file DESC");

  $list = new itemlist(false,"wikihist");
  while ( $row = $req->get_row() )
  {
    $list->add(
      "<span class=\"wdate\">".date("Y/m/d H:i",strtotime($row['date_rev_file']))."</span> ".
      "<a class=\"wpage\" href=\"?id_file=".$file->id."&amp;rev=".$row['id_rev_file']."&amp;action=download\">Révision ".$row['id_rev_file']."</a> ".
      "- <span class=\"wuser\">".htmlentities($row['nom_utilisateur'],ENT_NOQUOTES,"UTF-8")."</span> ".
      "<span class=\"wlog\">".htmlentities($row['comment_rev_file'],ENT_NOQUOTES,"UTF-8")."</span>");
  }
  $cts->add($list);

  $cts->add_title(2,"Références");

  $req = new requete($site->db,"SELECT fullpath_wiki, title_rev ".
    "FROM wiki_ref_file ".
    "INNER JOIN wiki ON ( wiki.id_wiki=wiki_ref_file.id_wiki) ".
    "INNER JOIN `wiki_rev` ON (".
          "`wiki`.`id_wiki`=`wiki_rev`.`id_wiki` ".
           "AND `wiki`.`id_rev_last`=`wiki_rev`.`id_rev` ) ".
    "WHERE wiki_ref_file.id_file='".$file->id."' ".
    "ORDER BY fullpath_wiki");

  if ( $req->lines )
  {
    $cts->add_title(3,"Pages wiki2");
    $list = new itemlist(null,"wikirefpages");
    while ( $row = $req->get_row() )
    {
      $list->add(
        "<a class=\"wpage\" href=\"wiki2/?name=".$row['fullpath_wiki']."\">".
        ($row['fullpath_wiki']?$row['fullpath_wiki']:"(racine)")."</a> ".
        " : <span class=\"wtitle\">".htmlentities($row['title_rev'],ENT_NOQUOTES,"UTF-8")."</span> ");
    }
    $cts->add($list);
  }

  $req = new requete($site->db,"SELECT nvl_nouvelles.id_nouvelle, titre_nvl ".
    "FROM nvl_nouvelles_files ".
    "INNER JOIN nvl_nouvelles USING(id_nouvelle) ".
    "WHERE nvl_nouvelles_files.id_file='".$file->id."' ".
    "ORDER BY titre_nvl");

  if ( $req->lines )
  {
    $cts->add_title(3,"Nouvelles");
    $list = new itemlist(null,"newsrefs");
    while ( $row = $req->get_row() )
    {
      $list->add(
        "<a href=\"news.php?id_nouvelle=".$row['id_nouvelle']."\">".htmlentities($row['titre_nvl'],ENT_NOQUOTES,"UTF-8")."</a>");
    }
    $cts->add($list);
  }

  $cts->add_title(3,"Tags");
  $cts->add(new taglist($file));


  $site->add_contents($cts);
  $site->end_page();
  exit();
}
if ( $_REQUEST["action"] == "save" && $folder->is_right($site->user,DROIT_ECRITURE) )
{
  if ( $_REQUEST["nom"] )
  {
    $asso = new asso($site->db);
    $asso->load_by_id($_REQUEST["id_asso"]);
    $folder->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);
    if ($site->user->is_in_group ("root"))
      $folder->set_auto_moderated ($_REQUEST['auto_moderated']);
    $folder->update_folder ( $_REQUEST["nom"],$_REQUEST["description"], $asso->id );
  }

}
elseif ( $_REQUEST["action"] == "edit" && $folder->is_right($site->user,DROIT_ECRITURE) )
{
  $site->start_page($section,"Fichiers");
  $cts = new contents($path." / Editer");
  $frm = new form("savefolder","d.php?id_folder=".$folder->id);
  $frm->add_hidden("action","save");
  $frm->add_text_field("nom","Nom",$folder->titre,true);
  $frm->add_text_area("description","Description",$folder->description);
  $frm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",$folder->id_asso,true);
  $frm->add_rights_field($folder,true,$folder->is_admin($site->user),"files");
  if ($site->user->is_in_group("root"))
    $frm->add_checkbox("auto_moderated", "<b>Les fichiers ajoutés à ce dossier ne nécessitent pas de modération. ATTENTION, DANGEREUX !</b>", $folder->auto_moderated);
  $frm->add_submit("valid","Enregistrer");
  $cts->add($frm);
  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( $_REQUEST["page"] == "newfolder" && $folder->is_right($site->user,DROIT_AJOUTCAT) )
{
  $site->start_page($section,"Fichiers");
  $cts = new contents($path." / Ajouter un dossier");

  $frm = new form("addfolder","d.php?id_folder=".$folder->id);
  $frm->allow_only_one_usage();
  $frm->add_hidden("action","addfolder");
  if ( $ErreurAjout )
    $frm->error($ErreurAjout);
  $frm->add_text_field("nom","Nom","",true);
  $frm->add_text_area("description","Description","");
  $frm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",false,true);
  $frm->add_rights_field($folder,true,$folder->is_admin($site->user),"files");
  if ($site->user->is_in_group("moderateur_site")) $frm->add_checkbox("automodere", "<b>Auto-modération</b>");
  $frm->add_submit("valid","Ajouter");

  $cts->add($frm);
  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( $_REQUEST["page"] == "newfile" && $folder->is_right($site->user,DROIT_AJOUTITEM) )
{
  $folder->droits_acces |= 0x200;
  $site->start_page($section,"Fichiers");
  $cts = new contents($path." / Ajouter un fichier");

  $frm = new form("addfile","d.php?id_folder=".$folder->id);
  $frm->allow_only_one_usage();
  $frm->add_hidden("action","addfile");
  if ( $ErreurAjout )
    $frm->error($ErreurAjout);
  $frm->add_file_field("file","Fichier",true);
  $frm->add_text_field("nom","Nom","",true);
  $frm->add_text_field("tags","Tags (séparateur: virgule)","");
  $frm->add_text_area("description","Description","");
  $_asso=false;
  if(!is_null($folder->id_asso))
    $_asso=$folder->id_asso;
  $frm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",$_asso,true);
  $frm->add_rights_field($folder,false,$folder->is_admin($site->user),"files");
  if ($site->user->is_in_group("moderateur_site")) $frm->add_checkbox("automodere", "<b>Auto-modération</b>");
  $frm->add_submit("valid","Ajouter");

  $cts->add($frm);
  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( isset($_SESSION["d_clipboard"]) && $_REQUEST["action"] == "paste" )
{
  $inffile = new dfile($site->db,$site->dbrw);
  $inffolder = new dfolder($site->db,$site->dbrw);

  foreach( $_SESSION["d_clipboard"] as $aid => $id )
  {
    if ( $aid{0} == 'I' )
    {
      if ( $folder->is_right($site->user,DROIT_AJOUTITEM) )
      {
        $inffile->load_by_id($id);
        $inffile->move_to($folder->id, null, $site->user->is_in_group ("moderateur_site"));
      }
    }
    elseif ( $folder->is_right($site->user,DROIT_AJOUTCAT) )
    {
      $inffolder->load_by_id($id);
      $inffolder->move_to($folder->id, null, $site->user->is_in_group ("moderateu    r_site"));
    }
  }

  unset($_SESSION["d_clipboard"]);
}

require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/cts/gallery.inc.php");


$site->start_page($section,"Fichiers");

if ( isset($_SESSION["d_clipboard"]) )
{
  $inffile = new dfile($site->db);
  $inffolder = new dfolder($site->db);

  $cts = new contents("Presse papier");

  if ( $folder->is_right($site->user,DROIT_AJOUTITEM) || $folder->is_right($site->user,DROIT_AJOUTCAT) )
    $cts->add_paragraph("<a href=\"d.php?id_folder=".$folder->id."&amp;action=paste\">Deplacer ici</a>");

  $cts->add_paragraph("<a href=\"d.php?id_folder=".$folder->id."&amp;action=emptyclpbrd\">Vider le presse papier</a>");

  $lst = new itemlist("Contenu");

  foreach( $_SESSION["d_clipboard"] as $aid => $id )
  {
    if ( $aid{0} == 'I' )
    {
      $inffile->load_by_id($id);
      $clip_txt = $inffile->get_html_link();
      $clip_txt .= " | <a href=\"d.php?id_folder=".$folder->id."&amp;undo_cut_file=".$id."\">retirer du presse papier</a>";
    }
    else
    {
      $inffolder->load_by_id($id);
      $clip_txt=$inffolder->get_html_link();
      $clip_txt .= " | <a href=\"d.php?id_folder=".$folder->id."&amp;undo_cut_folder=".$id."\">retirer du presse papier</a>";
    }
    $lst->add($clip_txt);
  }

  $cts->add($lst,true);

  $site->add_contents($cts);
}




if ( $asso_folder->is_valid() )
{
  $cts = new contents($asso_folder->get_html_path());
  $cts->add(new tabshead($asso_folder->get_tabs($site->user),"files"));
  $cts->add_title(1,$path);
}
else
  $cts = new contents($path);

if ( $folder->is_right($site->user,DROIT_ECRITURE) )
  $cts->set_toolbox(new toolbox(array(
"d.php?id_folder=".$folder->id."&action=edit"=>"Editer",
"d.php?id_folder=".$folder->id."&action=delete"=>"Supprimer",
"d.php?id_folder=".$folder->id."&action=cut"=>"Couper",
)));


if ( $folder->description)
  $cts->add( new wikicontents ("Description",$folder->description),true );


$gal = new gallery("Fichiers et dossiers","aedrive",false,"d.php?id_folder_parent=".$folder->id,array("download"=>"Télécharger","info"=>"Details","edit"=>"Editer","delete"=>"Supprimer"));

$sub1 = $folder->get_folders ( $site->user);
$fd = new dfolder($site->db);
while ( $row = $sub1->get_row() )
{
  $acts = false;
  $fd->_load($row);
  if ( $fd->is_right($site->user,DROIT_ECRITURE) )
    $acts = array("edit","delete","cut");

  $desc  =$fd->description;
  if ( strlen($desc) > 72 )
    $desc = substr($desc,0,72)."...";

  $gal->add_item ( "<img src=\"images/icons/128/folder.png\" alt=\"dossier\" />","<a href=\"d.php?id_folder=".$fd->id."\" class=\"itmttl\">".$fd->titre."</a><br/><span class=\"itmdsc\">".$desc."</span>", "id_folder=".$fd->id, $acts, "folder" );

}

$sub2 = $folder->get_files ( $site->user);
$fd = new dfile($site->db);
while ( $row = $sub2->get_row() )
{
  $acts = array("download","info");
  $fd->_load($row);
  if ( $fd->is_right($site->user,DROIT_ECRITURE) )
  {
    $acts[] ="edit";
    $acts[] ="delete";
    $acts[] ="cut";
  }

  if ( !file_exists($fd->get_thumb_filename()) )
    $img = $topdir."images/icons/128/".$fd->get_icon_name();
  else
    $img = "d.php?id_file=".$fd->id."&amp;action=download&amp;download=thumb";

  $desc  =$fd->description;
  if ( strlen($desc) > 72 )
    $desc = substr($desc,0,72)."...";

  $gal->add_item ( "<img src=\"$img\" alt=\"fichier\" />","<a href=\"d.php?id_file=".$fd->id."\" class=\"itmttl\">".$fd->titre."</a><br/><span class=\"itmdsc\">".$desc."</span>", "id_file=".$fd->id, $acts, "file" );

}
$cts->add($gal,true);

if ( $folder->is_right($site->user,DROIT_AJOUTCAT) )
  $cts->add_paragraph("<a href=\"d.php?id_folder=".$folder->id."&amp;page=newfolder\">Ajouter un dossier</a>");

if ( $folder->is_right($site->user,DROIT_AJOUTITEM) )
  $cts->add_paragraph("<a href=\"d.php?id_folder=".$folder->id."&amp;page=newfile\">Ajouter un fichier</a>");

$cts->add_paragraph("<a href=\"d.php?id_folder=".$folder->id."&amp;action=davmount\">Voir ce dossier avec votre client WebDAV</a>");

$site->add_contents($cts);
$site->end_page();

?>
