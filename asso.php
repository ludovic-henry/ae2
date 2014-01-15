<?php
/* Copyright 2006
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
/** Affiche les informations publiques sur une association.
 * @see asso/asso.php
 */

$topdir = "./";
include($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/taglist.inc.php");

require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/page.inc.php");

$site = new site ();
$asso = new asso($site->db,$site->dbrw);

if ( ($_REQUEST['action'] == "addasso") && $site->user->is_in_group("gestion_ae") )
{
  $asso_parent = new asso($site->db);

  if ( !$_REQUEST['nom']  || !$_REQUEST['nom_unix'] )
  {
    $Error = "Un ou plusieurs champs sont incomplets.";
  }
  else
  {
    if ( $_REQUEST['asso_parent'] )
    {
      $asso_parent->load_by_id($_REQUEST['asso_parent']);
      if ( $asso_parent->id < 1 )
        $asso_parent->id = null;
    }
    else
      $asso_parent->id = null;

    if ( $GLOBALS["is_using_ssl"] )
      $asso->add_asso($_REQUEST['nom'],$_REQUEST['nom_unix'],$asso_parent->id,$_REQUEST["adresse"],$_REQUEST['email'],$_REQUEST['siteweb'],$_REQUEST['login_email'],$_REQUEST['passwd_email'],isset($_REQUEST['distinct_benevole']));
    else
      $asso->add_asso($_REQUEST['nom'],$_REQUEST['nom_unix'],$asso_parent->id,$_REQUEST["adresse"],$_REQUEST['email'],$_REQUEST['siteweb'],null,null,isset($_REQUEST['distinct_benevole']));
  }
}
else if ( isset($_REQUEST["id_asso"]) )
{
  $asso_parent = new asso($site->db);
  $asso->load_by_id($_REQUEST["id_asso"]);
  if ( $asso->id < 1 )
  {
    $site->error_not_found("presentation");
    exit();
  }

  // Correction du vocabulaire
  if ( !is_null($asso->id_parent) )
  {
    $GLOBALS['ROLEASSO'][ROLEASSO_PRESIDENT] = "Responsable";
    $GLOBALS['ROLEASSO'][ROLEASSO_VICEPRESIDENT] = "Vice-responsable";
  }
  else
  {
    $GLOBALS['ROLEASSO'][ROLEASSO_PRESIDENT] = "Président";
    $GLOBALS['ROLEASSO'][ROLEASSO_VICEPRESIDENT] = "Vice-président";
  }

  if ( $site->user->is_in_group("gestion_ae") || $asso->is_member_role( $site->user->id, ROLEASSO_VICEPRESIDENT ) )
  {

    if ( $_REQUEST['action'] == "applyedit" )
    {
      if ( $site->user->is_in_group("gestion_ae") && ( !$_REQUEST['nom']  || !$_REQUEST['nom_unix'] ) )
      {
        $Error = "Un ou plusieurs champs sont incomplets.";
        $_REQUEST['page'] = "edit";
      }
      elseif ( !preg_match("#^([a-z0-9][a-z0-9\.]+[a-z0-9])$#i",strtolower($_REQUEST['nom_unix'])) )
      {
        $Error = "Le nom Unix ne doit comporter que des caractères alpha-numériques et des points (jamais à la fin), et doit faire au moins trois caractères";
        $_REQUEST['page'] = "edit";
      }
      else
      {
        if ( $site->user->is_in_group("gestion_ae") )
        {
          $asso_parent->load_by_id($_REQUEST['asso_parent']);

          if ( $GLOBALS["is_using_ssl"] )
            $asso->update_asso($_REQUEST['nom'],$_REQUEST['nom_unix'],$asso_parent->id,$_REQUEST["adresse"],$_REQUEST['email'],$_REQUEST['siteweb'],$_REQUEST['login_email'],$_REQUEST['passwd_email'],isset($_REQUEST['distinct_benevole']), isset($_REQUEST['hidden']));
          else
            $asso->update_asso($_REQUEST['nom'],$_REQUEST['nom_unix'],$asso_parent->id,$_REQUEST["adresse"],$_REQUEST['email'],$_REQUEST['siteweb'],null,null,isset($_REQUEST['distinct_benevole']), isset($_REQUEST['hidden']));
        }
        elseif ( $GLOBALS["is_using_ssl"] )
          $asso->update_asso($asso->nom,$asso->nom_unix,$asso_parent->id,$_REQUEST["adresse"],$_REQUEST['email'],$_REQUEST['siteweb'],$_REQUEST['login_email'],$_REQUEST['passwd_email'],isset($_REQUEST['distinct_benevole']));
        else
          $asso->update_asso($asso->nom,$asso->nom_unix,$asso_parent->id,$_REQUEST["adresse"],$_REQUEST['email'],$_REQUEST['siteweb'],null,null,isset($_REQUEST['distinct_benevole']));


        $asso->set_tags($_REQUEST['tags']);

      }
    }
    else if ( is_dir("/var/www/ae2/data/img") && $_REQUEST['action'] == "setlogo"  )
    {
      if ( is_uploaded_file($_FILES['logofile']['tmp_name']) )
      {
        $src = $_FILES['logofile']['tmp_name'];

        $dest_small ="/var/www/ae2/data/img/logos/".$asso->nom_unix.".small.png";
        $dest_icon = "/var/www/ae2/data/img/logos/".$asso->nom_unix.".icon.png";
        $dest_full = "/var/www/ae2/data/img/logos/".$asso->nom_unix.".jpg";

        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 80x80 $dest_small"));
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -resize 48x48 -size 48x48 xc:transparent +swap -gravity center -composite $dest_icon"));
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -background white $dest_full"));
      }
      else
        $ErreurLogo = "Erreur lors de l'upload";

      $_REQUEST['page'] = "edit";
    }

  }

  if ( ($_REQUEST['page'] == "edit" ) && ($site->user->is_in_group("gestion_ae") || $asso->is_member_role($site->user->id, ROLEASSO_VICEPRESIDENT)))
  {
    $site->start_page("presentation",$asso->nom);
    $cts = new contents($asso->get_html_path());
    $cts->add(new tabshead($asso->get_tabs($site->user),"info"));

    $frm = new form("editasso","asso.php?id_asso=".$asso->id,true,"POST","Edition");
    $frm->add_hidden("action","applyedit");

    if ( $Error )
      $frm->error($Error);
    if ( $site->user->is_in_group("gestion_ae") )
    {
      $frm->add_text_field("nom","Nom de l'association",$asso->nom,true);
      $frm->add_entity_select("asso_parent", "Association parent", $site->db, "asso",$asso->id_parent,true);
    }
    if ( $site->user->is_in_group("root") )
    {
      $frm->add_info("Le nom Unix est une donnée sensible utilisé pour le fonctionements de plusieurs services comme les mailing-lists, à ne modifier que si vous savez ce que vous faites !");
      $frm->add_text_field("nom_unix","Nom 'unix' (lettres et chiffres sans espaces)",$asso->nom_unix,true);
    }

    $frm->add_text_area("adresse","Adresse postale",$asso->adresse_postale);

    $frm->add_text_field("email","Email",$asso->email);
    $frm->add_text_field("siteweb","Site web",$asso->siteweb);
    $frm->add_text_field("tags","Tags (séparateur: virgule)",$asso->get_tags());

    $frm->add_checkbox("distinct_benevole","Activer la mailing liste bénévoles",$asso->distinct_benevole);

    if ( $site->user->is_in_group("root") )
        $frm->add_checkbox("hidden","Masquer le club (club fermé)",$asso->hidden);

    if ( $GLOBALS["is_using_ssl"] )
    {
      $frm->add_text_field("login_email","Login mail utbm",$asso->login_email);
      $frm->add_password_field("passwd_email","Mot de passe",$asso->passwd_email);
    }
    else
      $frm->add_info("Pour pouvoir définir la boite email utbm et son mot de passe, passez en connexion sécurisée (HTTPS).");

    $frm->add_submit("applyedit","Enregistrer");
    $cts->add($frm,true);

    $frm = new form("setlogo","asso.php?id_asso=".$asso->id,true,"POST","Logo");
    $frm->add_hidden("action","setlogo");
    if ( $ErreurLogo )
      $frm->error($ErreurLogo);
    if ( file_exists($topdir."data/img/logos/".$asso->nom_unix.".small.png") )
      $frm->add_info("<img src=\"".$topdir."data/img/logos/".$asso->nom_unix.".small.png\" />");
    $frm->add_info("Le logo doit être de grande taille, avec un fond transparent et au format PNG");
    $frm->add_info("Il peut être nécessaire de régénérer le cache de votre navigateur après l'envoi pour visualiser le changement");
    $frm->add_file_field("logofile","Fichier PNG");
    $frm->add_submit("valid","Enregistrer");
    $cts->add($frm,true);

    $site->add_contents($cts);
    $site->end_page();
    exit();
  }
  $site->add_css("css/doku.css");
  $site->start_page("presentation",$asso->nom);

  $cts = new contents($asso->get_html_path());
  if ( $site->user->is_in_group("moderateur_site") || $asso->is_member_role( $site->user->id, ROLEASSO_MEMBREBUREAU ) || $site->user->is_in_group("root") )
    $cts->set_toolbox(new toolbox(array("article.php?page=edit&name=activites:".$asso->nom_unix=>"Editer Présentation","asso.php?page=edit&id_asso=".$asso->id=>"Editer")));

  $cts->add(new tabshead($asso->get_tabs($site->user),"info"));


  if ( $_REQUEST["action"] == "selfenroll" && !is_null($asso->id_parent) )
  {
    $site->allow_only_logged_users("presentation");

    if ( $asso->is_member($site->user->id) )
    {
      $cts->add_title(2,"Inscription enregistrée");
      $cts->add_paragraph("Votre inscription était déjà enregistré, vous receverez déjà par e-mail les nouvelles de ".$asso->nom);
    }
    else
    {
      $asso->add_actual_member ( $site->user->id, time(), ROLEASSO_MEMBRE, "" );
      $cts->add_title(2,"Inscription enregistrée");
      $cts->add_paragraph("Votre inscription a été enregistré, vous receverez désormais par e-mail les nouvelles de ".$asso->nom);
    }
  }


  $page = new page($site->db);
  $page->load_by_pagename("activites:".$asso->nom_unix);

  if ($asso->hidden)
    $cts->add_paragraph("Club supprimé", "error");

  if (!$asso->hidden || $site->user->is_in_group("root"))
  {
    if ( $page->id > 0 )
    {
      $cts->add_title(2,"Pr&eacute;sentation");
      $cts->add($page->get_contents());

    }
    elseif ( $site->user->is_in_group("moderateur_site") )
      $cts->add_paragraph("<a href=\"article.php?page=new&amp;name=activites:".$asso->nom_unix."\">Creer l'article de pr&eacute;sentation</a>");

    if ( $site->user->is_in_group("root") )
      $req = new requete($site->db,
        "SELECT `id_asso`, `nom_asso`, `nom_unix_asso` " .
        "FROM `asso` WHERE `id_asso_parent`='".$asso->id."' " .
        "ORDER BY `nom_asso`");
    else
      $req = new requete($site->db,
        "SELECT `id_asso`, `nom_asso`, `nom_unix_asso` " .
        "FROM `asso` WHERE `id_asso_parent`='".$asso->id."' " .
        "AND `hidden`='0' ".
        "ORDER BY `nom_asso`");
    if ( $req->lines > 0 )
    {
      require_once($topdir."include/cts/gallery.inc.php");

      $site->add_css("css/asso.css");

      $vocable = "Activités";
      if ( $asso->id == 1 )
        $vocable = "Pôles";

      $gal = new gallery($vocable,"clubsgal");
      while ( $row = $req->get_row() )
      {
        $img = "/data/img/logos/".$row['nom_unix_asso'].".small.png";

        if ( !file_exists("/var/www/ae2".$img) )
        {
          $gal->add_item(
            "<a href=\"asso.php?id_asso=".$row['id_asso']."\">&nbsp;<img src=\"images/icons/128/asso.png\" alt=\"\" class=\"nope\" />&nbsp;</a>",
            "<a href=\"asso.php?id_asso=".$row['id_asso']."\">".$row['nom_asso']."</a>" );
        }
        else
          $gal->add_item(
            "<a href=\"asso.php?id_asso=".$row['id_asso']."\">&nbsp;<img src=\"$img\" alt=\"\" />&nbsp;</a>",
            "<a href=\"asso.php?id_asso=".$row['id_asso']."\">".$row['nom_asso']."</a>" );
      }
      $cts->add($gal,true);

    }

    $links = array();

    if ( $asso->email )
      $links[] = "<b>Contact</b> : <a href=\"mailto:".$asso->email."\">".$asso->email."</a>";

    if ( $asso->siteweb )
      $links[] = "<b>Site web</b> : <a href=\"".$asso->siteweb."\">".$asso->siteweb."</a>";

    if ( is_null($asso->id_parent) )
      $extracond .= "`asso_membre`.`role` > '".ROLEASSO_MEMBREACTIF."' ";
    else
      $extracond .= "`asso_membre`.`role` > '".ROLEASSO_TRESORIER."' ";

    $req = new requete($site->db,
      "SELECT COUNT(*) " .
      "FROM `asso_membre` " .
      "WHERE `asso_membre`.`date_fin` IS NULL " .
      "AND `asso_membre`.`id_asso`='".$asso->id."' " .
      "AND ".$extracond);

    list($respcnt) = $req->get_row();

    if ( $respcnt > 0 )
    {
      if ( is_null($asso->id_parent) )
        $links[] = "<b>Bureau</b> : <a href=\"asso/membres.php?id_asso=".$asso->id."\">Voir les membres du bureau</a>";
      else
        $links[] = "<b>Responsable</b> : <a href=\"asso/membres.php?id_asso=".$asso->id."\">Voir le(s) responsable(s)</a>";
    }

    $links[] = "<b>Historique</b> : <a href=\"asso/history.php?id_asso=".$asso->id."\">Voir résumé</a>";

    if ( count($links) > 0)
      $cts->add(new itemlist("",false,$links),true);

    $cts->puts("<div class=\"clearboth\"></div>");

    if ( $asso->is_mailing_allowed() && !is_null($asso->id_parent) && (!$site->user->is_valid() || !$asso->is_member($site->user->id)) )
    {
      $cts->add_title(2,"Inscrivez vous pour en savoir plus");

      $cts->add_paragraph("Inscrivez vous pour recevoir les nouvelles de ".$asso->nom." par e-mail et participer aux discussions, c'est simple et rapide : <a href=\"asso.php?id_asso=".$asso->id."&amp;action=selfenroll\">cliquez ici</a>");
    }

    $cts->add(new taglist($asso),true);
  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

require_once($topdir. "include/cts/tree.inc.php");

$site->start_page("presentation","Associations");

if ( $site->user->is_in_group("root") )
    $req = new requete($site->db,
        "SELECT " .
        "`asso1`.*, " .
        "`asso2`.`id_asso` as `id_asso_parent` " .
        "FROM `asso` AS `asso1`" .
        "LEFT JOIN `asso` AS `asso2` ON `asso1`.`id_asso_parent`=`asso2`.`id_asso`" .
        "ORDER BY `asso2`.`id_asso`,`asso1`.`nom_asso` ");
else
    $req = new requete($site->db,
        "SELECT " .
        "`asso1`.*, " .
        "`asso2`.`id_asso` as `id_asso_parent` " .
        "FROM `asso` AS `asso1`" .
        "LEFT JOIN `asso` AS `asso2` ON `asso1`.`id_asso_parent`=`asso2`.`id_asso`" .
        "WHERE `asso1`.`hidden`='0' ".
        "ORDER BY `asso2`.`id_asso`,`asso1`.`nom_asso` ");

$site->add_contents(new treects ( "Associations", $req, 0, "id_asso", "id_asso_parent", "nom_asso" ));

if ( $site->user->is_in_group("root") )
{
  $frm = new form("newasso","asso.php",true,"POST","Ajouter une association");

  if ( isset($Error) && $Error )
    $frm->error($Error);

  $frm->add_hidden("action","addasso");
  $frm->add_text_field("nom","Nom de l'association","",true);
  $frm->add_text_field("nom_unix","Nom 'unix' (lettres et chiffres sans espaces)","",true);
  $frm->add_entity_select("asso_parent", "Association parent", $site->db, "asso",0,true);
  $frm->add_text_area("adresse","Adresse postale");

  $frm->add_text_field("email","Email",$asso->email);
  $frm->add_text_field("siteweb","Site web",$asso->siteweb);

  $frm->add_checkbox("distinct_benevole","Activer la mailing liste bénévoles",$asso->distinct_benevole);


  if ( $GLOBALS["is_using_ssl"] )
  {
    $frm->add_text_field("login_email","Login mail utbm",$asso->login_email);
    $frm->add_password_field("passwd_email","Mot de passe",$asso->passwd_email);
  }
  else
    $frm->add_info("Pour pouvoir définir la boite email utbm et son mot de passe, passez en connexion sécurisée (HTTPS).");


  $frm->add_submit("addasso","Ajouter");

  $site->add_contents($frm);
}



$site->end_page();
?>
