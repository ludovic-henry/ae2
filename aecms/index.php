<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
 * Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once("include/site.inc.php");
require_once($topdir."include/entities/news.inc.php");

$page = new page ($site->db,$site->dbrw);

$section = CMS_PREFIX."accueil";

if ( $site->is_user_admin() )
{
  // Droits par défaut
  $page->id_utilisateur = null;
  $page->id_groupe = $site->asso->get_membres_group_id();
  $page->id_groupe_admin = $site->asso->get_bureau_group_id();
  $page->droits_acces = 0x311;

  if ( $_REQUEST['action'] == "new" )
  {
    if ( !$_REQUEST["name"] || !preg_match("#^([a-z0-9\-_:]+)$#",$_REQUEST["name"]) )
      $Erreur = "Nom invalide";
    elseif ( !$_REQUEST["title"] || !$_REQUEST["texte"] )
      $Erreur = "Veuillez préciser un titre et/ou un contenu";
    elseif ( $page->load_by_pagename(CMS_PREFIX.$_REQUEST["name"]) )
      $Erreur = "Cette page existe déjà";
    else
    {
      $page->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);
      $page->add($site->user,CMS_PREFIX.$_REQUEST["name"], $_REQUEST["title"], $_REQUEST['texte'], $_REQUEST['section']);
    }
  }

  /*if ( $_REQUEST['action'] == "delete" )
  {
    if ( $page->load_by_pagename(CMS_PREFIX.$_REQUEST["name"]) )
    {
      if ( $site->is_sure ( "","Suppression de la page ".$page->titre,"page".$page->nom, 1 ) )
      {
        $page->del();
        $_REQUEST['page'] = "new";
      }
    }
  }*/

  if ( $_REQUEST['page'] == "new" || isset($Erreur) )
  {
    foreach ($site->tab_array as $entry)
      $sections[$entry[0]] = $entry[2];

    $site->start_page("none","Nouveau");
    $frm = new form("newarticle","index.php",true,"POST","Nouvelle page");
    $frm->add_hidden("action","new");
    if ( isset($Erreur) )
      $frm->error($Erreur);
    $frm->add_text_field("name","Nom",$_REQUEST["name"],true);
    $frm->add_text_field("title","Titre","",true);
    $frm->add_select_field("section","Section",$sections,"presentation");
    $frm->add_rights_field($page,false,$page->is_admin($site->user),"pages");
    $frm->add_dokuwiki_toolbar('texte',$site->asso->id,null,true);
    $frm->add_text_area("texte","Contenu","",80,20,true);
    $frm->add_submit("save","Ajouter");
    $site->add_contents($frm);
    $site->add_contents(new wikihelp());
    $site->end_page();
    exit();
  }
}

$noedit=false;

if ( isset($_REQUEST["name"]) )
{
  if ( $_REQUEST["name"]{0} == ":" )
  {
    $page->load_by_pagename(substr($_REQUEST["name"],1));
    $noedit=true;
  }
  else
    $page->load_by_pagename(CMS_PREFIX.$_REQUEST["name"]);
}
else
  $page->load_by_pagename(CMS_PREFIX."home");

if ( !$page->is_valid() )
{
  $site->start_page ( $section, "Erreur" );

  $cts = new contents("Page inconnue");

  $cts->add_paragraph("Merci de vérifier le lien que vous avez emprunté.","error");

  if ( $site->is_user_admin() )
  {
    if ( isset($_REQUEST["name"]) )
      $cts->add_paragraph("<a href=\"index.php?page=new&amp;name=".rawurlencode($_REQUEST["name"])."\">Créer la page</a>");
    else
      $cts->add_paragraph("<a href=\"index.php?page=new&amp;name=home\">Créer la page</a>");
  }

  $site->add_contents($cts);

  $site->end_page();
  exit();
}

if ( $page->section )
  $section = $page->section;

if ( !$page->is_right($site->user,DROIT_LECTURE) )
{
  if(!$site->user->is_valid())
    $site->allow_only_logged_users();
  if($site->user->is_valid() && !$page->is_right($site->user,DROIT_LECTURE))
  {
    $site->start_page ( $section, "Erreur" );

    $err = new error("Accès restreint","Vous n'avez pas le droit d'accéder à cette page.");
    $site->add_contents($err);

    $site->end_page();
    exit();
  }
}



if ( ($page->is_right($site->user,DROIT_ECRITURE) || $site->is_user_admin()) && !$noedit )
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
    $frm = new form("editarticle","index.php?name=".substr($page->nom,strlen(CMS_PREFIX)),true,"POST","Edition : ".$page->nom);
    $frm->add_hidden("action","save");
    $frm->add_text_field("title","Titre",$page->titre,true);
    $frm->add_select_field("section","Section",$sections,$page->section);
    $frm->add_rights_field($page,false,$page->is_admin($site->user) || $site->asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU),"pages");
    $frm->add_dokuwiki_toolbar('texte',$site->asso->id,null,true);
    $frm->add_text_area("texte","Contenu",$page->texte,80,20,true);
    $frm->add_submit("save","Enregistrer");
    $site->add_contents($frm);
    $site->add_contents(new wikihelp());
    $site->end_page();
    exit();
  }
  $can_edit=true;
}
else
  $can_edit=false;

$site->start_page ( $section, $page->titre);

$cts = $page->get_contents();

$site->add_contents($cts);

if ( $can_edit )
  $cts->set_toolbox(new toolbox(array("index.php?page=edit&name=".substr($page->nom,strlen(CMS_PREFIX))=>"Editer"/*,
                                      "index.php?action=delete&name=".substr($page->nom,strlen(CMS_PREFIX))=>"Supprimer"*/)));

if ( $page->nom == CMS_PREFIX."home" && $site->config["home.news"] == 1 )
{
  $site->add_rss("L'actualité de ".$site->asso->nom,"rss.php");

  if ( !is_null($site->asso->id_parent) )
    $site->add_rss("Toute l'actualité de l'association des étudiants","/rss.php");

  $newscount = 0;

  if ( $site->config["home.excludenewssiteae"] == 1 )
  $req = new requete($site->db,"SELECT COUNT(*) FROM nvl_nouvelles WHERE id_asso='".mysql_real_escape_string($site->asso->id)."' AND `modere_nvl`='1' AND id_canal='".NEWS_CANAL_AECMS."'");
  else
  $req = new requete($site->db,"SELECT COUNT(*) FROM nvl_nouvelles WHERE id_asso='".mysql_real_escape_string($site->asso->id)."' AND `modere_nvl`='1'");
  list($newscount) = $req->get_row();



  $page=0;
  $npp=10;

  $pagescount = ceil($newscount/$npp);

  if ( isset($_REQUEST["npage"]) )
    $page = intval($_REQUEST["npage"]);

  if ( $page < 0 )
    $page=0;
  elseif ( $page >= $pagescount )
    $page = $pagescount-1;

  $st = $page*$npp;

  if ( $site->config["home.excludenewssiteae"] == 1 )
  $req = new requete($site->db,"SELECT * FROM nvl_nouvelles WHERE id_asso='".mysql_real_escape_string($site->asso->id)."' AND `modere_nvl`='1' AND id_canal='".NEWS_CANAL_AECMS."' ".
  "ORDER BY date_nvl DESC ".
  "LIMIT $st,$npp");
  else
  $req = new requete($site->db,"SELECT * FROM nvl_nouvelles WHERE id_asso='".mysql_real_escape_string($site->asso->id)."' AND `modere_nvl`='1' ".
  "ORDER BY date_nvl DESC ".
  "LIMIT $st,$npp");

  $news = new nouvelle($site->db);

  while ( $row = $req->get_row() )
  {
    $news->_load($row);

    $cts = $news->get_contents_nobrand_flow();
    $cts->cssclass="article anews";
    if ( $can_edit )
      $cts->set_toolbox(new toolbox(array("configurecms.php?view=news&action=edit&id_nouvelle=".$news->id=>"Editer")));
    $site->add_contents($cts);
  }

  $cts = new contents();

  for($p=0;$p<$pagescount;$p++)
    $cts->puts("<a href=\"index.php?npage=$p\">".($p+1)."</a> ");

  $cts->cssclass="apages";

  $site->add_contents($cts);
}


$site->end_page();

?>
