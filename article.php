<?php

/* Copyright 2006,2007
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

$topdir="./";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/page.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$conf['maxtoclevel']=4;
$conf['maxseclevel']=6;

$site = new site ();
$page = new page ($site->db,$site->dbrw);
$site->add_css("css/articles.css");
$site->add_css("css/doku.css");

if ( $site->user->is_valid()  && $site->user->is_in_group("moderateur_site") )
{
  $page->id_utilisateur = 0;
  $page->id_groupe = 8;
  $page->id_groupe_admin = 8;
  $page->droits_acces = 0x311;

  if ( $_REQUEST['action'] == "new" )
  {
    if ( !$_REQUEST["name"] || !preg_match("#^([a-z0-9\-_:\.]+)$#",$_REQUEST["name"]) )
      $Erreur = "Nom invalide";
    elseif ( !$_REQUEST["title"] || !$_REQUEST["texte"] )
      $Erreur = "Veuillez préciser un titre et/ou un contenu";
    elseif ( $page->load_by_pagename($_REQUEST["name"]) )
      $Erreur = "Cette page existe déjà";
    else
    {
      $page->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);
      $page->add($site->user, $_REQUEST["name"], $_REQUEST["title"], $_REQUEST['texte'], $_REQUEST['section']);
    }
  }

  if ( $_REQUEST['page'] == "new" || isset($Erreur) )
  {
    foreach ($site->tab_array as $entry)
      $sections[$entry[0]] = $entry[2];

    $site->start_page("accueil","Nouveau");
    $frm = new form("newarticle","article.php",true,"POST","Nouvelle page");
    if ( isset($Erreur) )
      $frm->error($Erreur);
    $frm->add_hidden("action","new");
    $frm->add_text_field("name","Nom",$_REQUEST["name"],true);
    $frm->add_text_field("title","Titre","",true);
    //$frm->add_entity_select("groupid","Groupe",$site->db,"group" );
    $frm->add_select_field("section","Section",$sections,"presentation");

    $frm->add_rights_field($page,false,$page->is_admin($site->user),"pages");
    $frm->add_dokuwiki_toolbar('texte');
    $frm->add_text_area("texte","Contenu","",80,20,true);

    $frm->add_submit("save","Ajouter");
    $site->add_contents($frm);
    $site->add_contents(new wikihelp());
    $site->end_page();
    exit();
  }

}

if ( isset($_REQUEST["name"]) && ereg("^activites-(.*)$",$_REQUEST["name"],$regs )) // LEGACY SUPPORT
{
  $asso = new asso($site->db);
  $asso->load_by_unix_name($regs[1]);
  if ( $asso->id > 0 )
  {
    header("Location: asso.php?id_asso=".$asso->id);
    exit();
  }
}

if ( ereg("^activites:(.*)$", $page->nom,$regs ))
{
    echo 'Unix name :'.$regs[1].'\n';
  $asso = new asso($site->db);
  $asso->load_by_unix_name($regs[1]);
  if ( $asso->id > 0 )
  {
    header("Location: asso.php?id_asso=".$asso->id);
    exit();
  }
}

if ( isset($_REQUEST["name"]) )
{
  if ( $_REQUEST["name"]{0} == ":" )
    $page->load_by_pagename(substr($_REQUEST["name"],1));
  else
    $page->load_by_pagename($_REQUEST["name"]);
}

if ( !$page->is_valid() )
{
  $site->start_page("accueil","Erreur");

  $err = new error("Page inconnue","Merci de vérifier le lien que vous avez emprunté");

  if ( $site->user->is_in_group("moderateur_site") )
    $err->set_toolbox(new toolbox(array("article.php?page=new&name=".
                                        $_REQUEST["name"] => "Creer la page")));

  $site->add_contents($err);
  $site->end_page();

  exit();
}

if ( !$page->is_right($site->user,DROIT_LECTURE) )
  $site->error_forbidden();

$section = "presentation";
if ( $page->section )
  $section = $page->section;

if( $section == "presentation" )
  $site->add_css("css/presentation.css");
  $tabs = array(
    array("presentation","article.php?name=presentation","Présentation"),
    array("services","article.php?name=presentation:services","Services quotidiens"),
    array("carteae","article.php?name=presentation:carteae","La carte AE"),
    array("siteae","article.php?name=presentation:siteae","Le site AE"),
    array("siteae","article.php?name=presentation:siteae","Activités et clubs"),
    array("siteae","article.php?name=presentation:siteae","Responsables des clubs"),
    );

if ( $page->is_right($site->user,DROIT_ECRITURE) )
{
  if ( $_REQUEST['action'] == "save" )
    {
      $page->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);
      $page->save( $site->user, $_REQUEST['title'], $_REQUEST['texte'], $_REQUEST['section'] );
      $section = $page->section;
    }
  if ( $_REQUEST['page'] == "edit" )
    {
      foreach ($site->tab_array as $entry)
        $sections[$entry[0]] = $entry[2];
      $site->start_page($section,"Edition :".$page->titre);
      $frm = new form("editarticle","article.php?name=".$page->nom,true,"POST","Edition : ".$page->nom);
      $frm->add_hidden("action","save");
      $frm->add_text_field("title","Titre",$page->titre,true);
      //$frm->add_entity_select("groupid","Groupe",$site->db,"group",$page->id_groupe );
      $frm->add_select_field("section","Section",$sections,$page->section);

      $frm->add_rights_field($page,false,$page->is_admin($site->user),"pages");
      $frm->add_dokuwiki_toolbar('texte');
      $frm->add_text_area("texte","Contenu",$page->texte,80,20,true);
      $frm->add_submit("save","Enregistrer");
      $site->add_contents($frm);
      $site->add_contents(new wikihelp());
      $site->end_page();
      exit();
    }
  $can_edit = true;
}
else
$can_edit = false;


if ( $page->nom == "services" /*|| $page->nom == "planning"*/ )
{
  $site->set_side_boxes("left",array("calendrier","connexion"));
  $site->add_box("calendrier",new calendar($site->db));
  $site->add_box("connexion", $site->get_connection_contents());
}


$site->start_page($section,$page->titre);
$cts = $page->get_contents();

if ( count($cts->wiki->index["childs"]) )
{
  function make_index($itm)
    {
      if ( is_null( $itm["title"] ) )
        $lst = new itemlist("Index");
      else
        $lst = new itemlist("<a href=\"#".$itm["ancre"]."\">".$itm["title"]."</a>");

      foreach($itm["childs"] as $sitm)
        {
          if (count($sitm["childs"]) )
            $lst->add(make_index(&$sitm));
          else
            $lst->add("<a href=\"#".$sitm["ancre"]."\">".$sitm["title"]."</a>");
        }
      return $lst;
    }

  $site->sides["left"] = array_merge (array("wikiindex"),$site->sides["left"]);
  $site->add_box("wikiindex", make_index($cts->wiki->index));
}

if ( $can_edit )
  $cts->set_toolbox(new toolbox(array("article.php?page=edit&name=".$page->nom=>"Editer","article.php?page=new"=>"Ajouter une page")));



$site->add_contents($cts);
$site->end_page();

?>
