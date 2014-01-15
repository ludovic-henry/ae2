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
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/entities/news.inc.php");
require_once($topdir."include/entities/lieu.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir."include/entities/files.inc.php");

if ( !$site->is_user_admin() )
{
  exit();
}

if ( !is_null($site->asso->id_parent) )
{
  $GLOBALS['ROLEASSO'][ROLEASSO_PRESIDENT] = "Responsable";
  $GLOBALS['ROLEASSO'][ROLEASSO_VICEPRESIDENT] = "Vice-responsable";
}
else
{
  $GLOBALS['ROLEASSO'][ROLEASSO_PRESIDENT] = "Président";
  $GLOBALS['ROLEASSO'][ROLEASSO_VICEPRESIDENT] = "Vice-président";
}

$req = new requete($site->db,
    "SELECT fullpath_wiki, title_rev FROM `wiki`
    LEFT JOIN `wiki_rev` ON (wiki.id_wiki = wiki_rev.id_wiki AND wiki.id_rev_last = wiki_rev.id_rev)
    WHERE `fullpath_wiki` LIKE 'articles:" . mysql_real_escape_string(CMS_PREFIX) . "%'
    AND `fullpath_wiki` NOT LIKE 'articles:" . mysql_real_escape_string(CMS_PREFIX) . "boxes:%'"
    );
$pages = array();
while ( $row = $req->get_row() )
  $pages[substr($row['fullpath_wiki'],strlen("articles:".CMS_PREFIX))] = $row['title_rev'];

if ( !isset($pages["home"]) )
  $pages["home"] = "Accueil";

if ( $_REQUEST["action"] == "addonglet" )
{
  $name = null;

  if ( !$_REQUEST["title"] )
    $ErreurAddOnglet = "Veuillez choisir un titre";
  elseif ( $_REQUEST["typepage"] == "article" )
  {
    $lien = "index.php?name=".$_REQUEST["nom_page"];
    $name = $_REQUEST["nom_page"];

    $page = new page ($site->db,$site->dbrw);
    $page->load_by_pagename(CMS_PREFIX.$_REQUEST["nom_page"]);
    $page->save($site->user,$page->title, $page->texte, CMS_PREFIX.$name );
  }
  elseif ( $_REQUEST["typepage"] == "crearticle" && $_REQUEST["name"] != "accueil" )
  {
    if ( !$_REQUEST["name"] || !preg_match("#^([a-z0-9\-_:]+)$#",$_REQUEST["name"]) )
      $ErreurAddOnglet = "Nom invalide";
    else
    {
      $lien = "index.php?name=".$_REQUEST["name"];
      $name = $_REQUEST["name"];

      $page = new page ($site->db,$site->dbrw);
      $page->load_by_pagename(CMS_PREFIX.$name);

      if ( !$page->is_valid() )
      {
        $page->id_utilisateur = $site->user->id;
        $page->id_groupe = $site->asso->get_membres_group_id();
        $page->id_groupe_admin = $site->asso->get_bureau_group_id();
        $page->droits_acces = 0x311;
        $page->add($site->user,CMS_PREFIX.$name, $_REQUEST["title"], "", CMS_PREFIX.$name);
      }
      else
        $page->save($site->user,$page->title, $page->texte, CMS_PREFIX.$name );
    }
  }
  elseif ( $_REQUEST["typepage"] == "aedrive" )
  {
    $lien = "d.php";
    $name = "fichiers";
  }
  elseif ( $_REQUEST["typepage"] == "sas2" )
  {
    $lien = "photos.php";
    $name = "sas";
  }
  elseif ( $_REQUEST["typepage"] == "contact" )
  {
    $lien = "contact.php";
    $name = "contact";
  }
  elseif ( $_REQUEST["typepage"] == "membres" )
  {
    $lien = "membres.php";
    $name = "membres";
  }
  elseif ( $_REQUEST["typepage"] == "blog" )
  {
    require_once("include/blog.inc.php");
    $blog = new blog($site->db,$site->dbrw);
    $lien = "blog.php";
    $name = "blog";
    if(defined('CMS_ALTERNATE'))
      $blog->create($site->asso,CMS_ALTERNATE);
    else
      $blog->create($site->asso);
    if( !$blog->is_valid() )
      $name = null;
  }
  elseif ( $_REQUEST["typepage"] == "pull" && $site->asso->id == 110) {
    $lien = "inscriptions.php";
    $name = "inscriptions";
  }
  else
    $name = null;

  if ( !is_null($name) )
  {
    $site->tab_array[] = array(CMS_PREFIX.$name,$lien,$_REQUEST["title"]);
    $site->save_conf();
  }
}
elseif ( $_REQUEST["action"] == "addbox" )
{
  if ( empty($site->config["boxes.names"]) )
    $boxes = array();
  else
    $boxes = explode(",",$site->config["boxes.names"]);

  $name = null;

  if ( $_REQUEST["typebox"] == "custom" )
  {
    if ( !$_REQUEST["name"] || !preg_match("#^([a-z0-9\-_:]+)$#",$_REQUEST["name"]) )
      $ErreurAddBox = "Nom invalide";
    else
    {
      $name = $_REQUEST["name"];
      $page = new page ($site->db,$site->dbrw);
      $page->load_by_pagename(CMS_PREFIX."boxes:".$name);
      if ( !$page->is_valid() )
      {
        $page->id_utilisateur = $site->user->id;
        $page->id_groupe = $site->asso->get_membres_group_id();
        $page->id_groupe_admin = $site->asso->get_bureau_group_id();
        $page->droits_acces = 0x311;
        $page->add($site->user,CMS_PREFIX."boxes:".$name, $_REQUEST["title"], "", CMS_PREFIX."accueil");
      }
      else
        $page->save($site->user,$_REQUEST["title"], $page->texte, CMS_PREFIX."accueil" );
    }
  }
  else//if ( $_REQUEST["typebox"] == "calendrier" )
    $name = "calendrier";

  if ( !is_null($name) )
  {
    if ( !in_array($name,$boxes) )
    $boxes[] = $name;

    $site->config["boxes.names"] = implode(",",$boxes);
    $site->save_conf();
  }
}
elseif ( $_REQUEST["action"] == "setconfig" )
{
  $site->config["membres.upto"] = intval($_REQUEST["membres_upto"]);
  $site->config["membres.allowjoinus"] = isset($_REQUEST["membres_allowjoinus"])?1:0;
  $site->config["home.news"] = isset($_REQUEST["home_news"])?1:0;
  $site->config["home.excludenewssiteae"] = isset($_REQUEST["home_excludenewssiteae"])?1:0;

  $site->save_conf();
}
elseif ( $_REQUEST["action"] == "delete" && isset($_REQUEST["nom_onglet"]) )
{
  if ( $_REQUEST["nom_onglet"] != CMS_PREFIX."accueil" )
  {

    foreach ( $site->tab_array as $key => $row )
    {
      if ( $_REQUEST["nom_onglet"] == $row[0] )
        unset($site->tab_array[$key]);
    }
    $site->save_conf();
  }
}
elseif ( $_REQUEST["action"] == "delete" && isset($_REQUEST["box_name"]) )
{
  if (isset($site->config["boxes.specific.".$_REQUEST["box_name"]]))
  {
    unset($site->config["boxes.specific.".$_REQUEST["box_name"]]);

    if ( empty($site->config["boxes.specific"]) )
      $boxes = array();
    else
      $boxes = explode(",",$site->config["boxes.specific"]);

    foreach ( $boxes as $key => $name )
    {
      if ( $_REQUEST["box_name"] == $name )
        unset($boxes[$key]);
    }

    $site->config["boxes.specific"] = implode(",",$boxes);
  }
  else
  {
    if ( empty($site->config["boxes.names"]) )
      $boxes = array();
    else
      $boxes = explode(",",$site->config["boxes.names"]);

    foreach ( $boxes as $key => $name )
    {
      if ( $_REQUEST["box_name"] == $name )
        unset($boxes[$key]);
    }

    $site->config["boxes.names"] = implode(",",$boxes);
  }

  $site->save_conf();

}
elseif ( $_REQUEST["action"] == "setboxsections"  )
{
  $sections = array();

  foreach( $_REQUEST["sections"]  as $nom => $set )
    $sections[]=$nom;

  $site->config["boxes.sections"] = implode(",",$sections);
  $site->save_conf();
}
elseif ( $_REQUEST["action"] == "up" && isset($_REQUEST["nom_onglet"]) )
{
  $prevkey=null;

  foreach ( $site->tab_array as $key => $row )
  {
    if ( $_REQUEST["nom_onglet"] == $row[0] )
    {
      if ( !is_null($prevkey) )
      {
        $tmp = $site->tab_array[$key];
        $site->tab_array[$key] = $site->tab_array[$prevkey];
        $site->tab_array[$prevkey] = $tmp;
      }
    }
    $prevkey = $key;
  }
  $site->save_conf();
}
elseif ( $_REQUEST["action"] == "down" && isset($_REQUEST["nom_onglet"]) )
{
  $prevkey=null;
  foreach ( $site->tab_array as $key => $row )
  {
    if ( $_REQUEST["nom_onglet"] == $row[0] )
      $prevkey = $key;
    elseif ( !is_null($prevkey) )
    {
      $tmp = $site->tab_array[$key];
      $site->tab_array[$key] = $site->tab_array[$prevkey];
      $site->tab_array[$prevkey] = $tmp;
      $prevkey=null;
    }
  }
  $site->save_conf();
}
elseif ( $_REQUEST["action"] == "up" && isset($_REQUEST["box_name"]) )
{
  if ( empty($site->config["boxes.names"]) )
    $boxes = array();
  else
    $boxes = explode(",",$site->config["boxes.names"]);

  $prevkey=null;

  foreach ( $boxes as $key => $name )
  {
    if ( $_REQUEST["box_name"] == $name )
    {
      if ( !is_null($prevkey) )
      {
        $tmp = $boxes[$key];
        $boxes[$key] = $boxes[$prevkey];
        $boxes[$prevkey] = $tmp;
      }
    }
    $prevkey = $key;
  }

  $site->config["boxes.names"] = implode(",",$boxes);
  $site->save_conf();
}
elseif ( $_REQUEST["action"] == "down" && isset($_REQUEST["box_name"]) )
{
  if ( empty($site->config["boxes.names"]) )
    $boxes = array();
  else
    $boxes = explode(",",$site->config["boxes.names"]);

  $prevkey=null;
  foreach ( $boxes as $key => $name )
  {
    if ( $_REQUEST["box_name"] == $name )
      $prevkey = $key;
    elseif ( !is_null($prevkey) )
    {
      $tmp = $boxes[$key];
      $boxes[$key] = $boxes[$prevkey];
      $boxes[$prevkey] = $tmp;
    }
  }

  $site->config["boxes.names"] = implode(",",$boxes);
  $site->save_conf();
}
elseif ( $_REQUEST["action"] == "edit" )
{
  $page = new page ($site->db,$site->dbrw);
  $page->load_by_pagename(CMS_PREFIX."boxes:".$_REQUEST["box_name"]);
  if ($page->is_valid() )
  {
    $site->start_page(CMS_PREFIX."config","Edition boite :".$page->titre);
    $frm = new form("editarticle","configurecms.php?view=boxes",true,"POST","Edition : ".$page->nom);
    $frm->add_hidden("action","save");
    $frm->add_hidden("box_name",$_REQUEST["box_name"]);
    $frm->add_text_field("title","Titre",$page->titre,true);
    $frm->add_rights_field($page,false,true,"pages");
    $frm->add_text_area("texte","Contenu",$page->texte,80,20,true);

    $subfrm = new subform("setboxsections","Sections où les boites seront affichées");

    if (isset($site->config["boxes.specific.".$_REQUEST["box_name"]]))
      $boxes_sections = explode(",",$site->config["boxes.specific.".$_REQUEST["box_name"]]);
    else
      $boxes_sections = array();

    foreach ( $site->tab_array as $row )
    {
      $nom = $row[0];
      $titre = $row[2];
      $subfrm->add_checkbox("sections[$nom]","$titre",in_array($nom,$boxes_sections));
    }

    $frm->addsub( $subfrm, false, true );
    $frm->add_info("Laisser tout décoché pour utiliser les réglages globaux");

    $frm->add_submit("save","Enregistrer");
    $site->add_contents($frm);
    $site->add_contents(new wikihelp());
    $site->end_page();
    exit();
  }
}
elseif ( $_REQUEST["action"] == "save" )
{
  $page = new page ($site->db,$site->dbrw);
  $page->load_by_pagename(CMS_PREFIX."boxes:".$_REQUEST["box_name"]);

  if ($page->is_valid() )
  {
    $page->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin']);
    $page->save($site->user, $_REQUEST['title'], $_REQUEST['texte'], CMS_PREFIX."accueil" );
  }

  if ( empty($site->config["boxes.names"]) )
    $boxes = array();
  else
    $boxes = explode(",",$site->config["boxes.names"]);

  if ( empty($site->config["boxes.specific"]) )
    $boxes_specific = array();
  else
    $boxes_specific = explode(",",$site->config["boxes.specific"]);

  if (empty($_REQUEST["sections"]))
  {
    if (! in_array($_REQUEST["box_name"], $boxes))
      $boxes[] = $_REQUEST["box_name"];
    foreach ( $boxes_specific as $key => $name )
      if ( $name == $_REQUEST["box_name"] )
        unset($boxes_specific[$key]);
    if (isset($site->config["boxes.specific.".$_REQUEST["box_name"]]))
      unset($site->config["boxes.specific.".$_REQUEST["box_name"]]);
  }
  else
  {
    if (! in_array($_REQUEST["box_name"], $boxes_specific))
      $boxes_specific[] = $_REQUEST["box_name"];
    foreach ( $boxes as $key => $name )
      if ( $name == $_REQUEST["box_name"] )
        unset($boxes[$key]);

    $sections = array();
    foreach( $_REQUEST["sections"] as $name => $set )
      $sections[]=$name;
    $site->config["boxes.specific.".$_REQUEST["box_name"]] = implode(",",$sections);
  }

  $site->config["boxes.names"] = implode(",",$boxes);
  $site->config["boxes.specific"] = implode(",",$boxes_specific);
  $site->save_conf();
}
elseif( $_REQUEST["action"] == "setcss" )
{
  $site->config["css.base"] = $_REQUEST["css_base"];
  $site->save_conf();
  file_put_contents($basedir."/specific/custom.css",$_REQUEST["data"]);
}
elseif( $_REQUEST["action"] == "setfooter" && isset($site->config['footer']))
{
  $site->config['footer'] = trim($_REQUEST['footer']);
  $site->save_conf();
  require_once($topdir."include/cts/cached.inc.php");
  $path = CMS_ID_ASSO;
  if(defined('CMS_ALTERNATE'))
    $path.="_".CMS_ALTERNATE;
  $cache = new cachedcontents("aecmsfooter_".$path);
  $cache->expire();
  $cache->set_contents(new contents('',doku2xhtml($site->config['footer'])));
  $cache=$cache->get_cache();
}
elseif ( $_REQUEST["action"] == "delete" && isset($_REQUEST["filename"]) )
{
  $dir = $basedir."/specific/img/";

  $filename = $dir.preg_replace("`([^a-zA-Z0-9_\\-\\.])`", "", basename($_REQUEST["filename"]));

  unlink($filename);
}
elseif ( $_REQUEST["action"] == "addimgfile" )
{
  if( is_uploaded_file($_FILES['file']['tmp_name']) && ($_FILES['file']['error'] == UPLOAD_ERR_OK ) )
  {
    $dir = $basedir."/specific/img/";

    if (!is_dir($dir))
      mkdir($dir);

    $filename = $dir.preg_replace("`([^a-zA-Z0-9_\\-\\.])`", "", basename($_FILES['file']['name']));

    move_uploaded_file ( $_FILES['file']['tmp_name'], $filename );
  }
}



$req = new requete($site->db,
    "SELECT fullpath_wiki, title_rev FROM `wiki`
    LEFT JOIN `wiki_rev` ON (wiki.id_wiki = wiki_rev.id_wiki AND wiki.id_rev_last = wiki_rev.id_rev)
    WHERE `fullpath_wiki` LIKE 'articles:" . mysql_real_escape_string(CMS_PREFIX) . "boxes:%'"
    );
$pages_boxes = array();
while ( $row = $req->get_row() )
  $pages_boxes[substr($row['fullpath_wiki'],strlen("articles:".CMS_PREFIX))] = $row['title_rev'];

$site->start_page ( CMS_PREFIX."config", "Configuration de AECMS" );

$cts = new contents("Configuration de AECMS");

$dejafait = array();
$onglets_noms = array();

$liste_onglets = array();
foreach ( $site->tab_array as $row )
{
  if ( $row[0] != CMS_PREFIX."config" )
  {
    $dejafait[substr($row[0],strlen(CMS_PREFIX))] = true;

    if ( ereg("^index\.php\?name=(.*)$",$row[1],$regs) )
    {
      $lien = "Page: ".$pages[$regs[1]];
      unset($pages[$regs[1]]);
    }
    elseif ( $row[1] == "photos.php" )
      $lien = "Gallerie photos";
    elseif ( $row[1] == "d.php" )
      $lien = "Espace fichiers";
    elseif ( $row[1] == "membres.php" )
      $lien = "Membres";
    elseif ( $row[1] == "contact.php" )
      $lien = "Contact";
    elseif ( $row[1] == "blog.php" )
      $lien = "Blog";
    elseif ( $row[1] == "index.php" )
      $lien = "Page: ".$pages["home"];
    else
      $lien = "Lien spécial (".$row[1].")";

    $liste_onglets[] = array("nom_onglet"=>$row[0],"titre_onglet"=>stripslashes($row[2]),"lien_onglet"=>$lien);
    $onglets_noms[$row[0]] = $row[2];
  }
}


$tabs = array(
        array("","$aecmsname/configurecms.php", "Onglets"),
        array("boxes","$aecmsname/configurecms.php?view=boxes","Boites"),
        array("options","$aecmsname/configurecms.php?view=options","Options"),
        array("css","$aecmsname/configurecms.php?view=css","Style"),
        array("news","$aecmsname/configurecms.php?view=news","Nouvelles")
        );
if(isset($site->config['footer']))
  $tabs[]=array("footer","$aecmsname/configurecms.php?view=footer","Footer");
$cts->add(new tabshead($tabs,$_REQUEST["view"]));

if ( $_REQUEST["view"] == "" )
{
  $cts->add_title(2,"Onglets");


  $cts->add( new sqltable ( "onglets", "Onglets", $liste_onglets,
  "configurecms.php", "nom_onglet", array("titre_onglet"=>"Titre","lien_onglet"=>"Lien"),
  array("delete"=>"Supprimer","up"=>"Vers le haut","down"=>"Vers le bas"), array() ));

  $cts->add_title(2,"Nouvel onglet");

  $frm = new form("newonglet","configurecms.php",false,"POST","Nouvel onglet");


  $frm->add_hidden("action","addonglet");
  if ( $ErreurAddOnglet )
    $frm->error($ErreurAddOnglet);

  $frm->add_text_field("title","Titre","",true);

  unset($pages["home"]);

  if ( count($pages) > 0 )
  {
    $sfrm = new form("typepage",null,null,null,"Page existante");
    $sfrm->add_select_field("nom_page","Page",$pages);
    $frm->add($sfrm,false,true,true,"article",false,true);
  }

  $sfrm = new form("typepage",null,null,null,"Nouvelle page");
  $sfrm->add_text_field("name","Code (nom)","",true);
  $frm->add($sfrm,false,true,true,"crearticle",false,true);

  if ( !isset($dejafait["fichiers"]) )
  {
    $sfrm = new form("typepage",null,null,null,"Espace fichiers (aedrive)");
    $frm->add($sfrm,false,true,false,"aedrive",false,true);
  }
  if ( !isset($dejafait["sas"]) )
  {
    $sfrm = new form("typepage",null,null,null,"Gallerie photos (sas2)");
    $frm->add($sfrm,false,true,false,"sas2",false,true);
  }
  if ( !isset($dejafait["contact"]) )
  {
    $sfrm = new form("typepage",null,null,null,"Contact");
    $frm->add($sfrm,false,true,false,"contact",false,true);
  }
  if ( !isset($dejafait["membres"]) )
  {
    $sfrm = new form("typepage",null,null,null,"Membres");
    $frm->add($sfrm,false,true,false,"membres",false,true);
  }
  if ( !isset($dejafait["blog"]) )
  {
    $sfrm = new form("typepage",null,null,null,"Blog");
    $frm->add($sfrm,false,true,false,"blog",false,true);
  }
  if ( !isset($dejafait["inscriptions"]) && $site->asso->id == 110)
  {
    $sfrm = new form("typepage",null,null,null,"Inscriptions PULL");
    $frm->add($sfrm,false,true,false,"pull",false,true);
  }
  $frm->add_submit("save","Ajouter");
  $cts->add ( $frm );

}
else if ( $_REQUEST["view"] == "boxes" )
{
  $cts->add_title(2,"Boites");

  // Boxes
  if ( empty($site->config["boxes.names"]) )
    $boxes = array();
  else
    $boxes = explode(",",$site->config["boxes.names"]);

  if ( isset($site->config["boxes.specific"]) && (! empty($site->config["boxes.specific"])))
    $boxes = array_merge($boxes, explode(",",$site->config["boxes.specific"]));

  $boxes_sections = explode(",",$site->config["boxes.sections"]);

  $boxes_list = array();
  foreach ( $boxes as $name )
  {
    if ( $name == "calendrier" )
    {
      $title = "Calendrier";
      $type="Calendrier";
    }
    else
    {
      $title = $pages_boxes["boxes:".$name];
      $type="Personnalisée";
    }
    $boxes_list[] = array("box_name"=>$name,"box_title"=>$title,"box_type"=>$type);
  }

  $cts->add( new sqltable ( "boxes", "Boites", $boxes_list,
  "configurecms.php?view=boxes", "box_name", array("box_title"=>"Titre","box_type"=>"Type"),
  array("delete"=>"Supprimer","edit"=>"Editer","up"=>"Vers le haut","down"=>"Vers le bas"), array() ));

  $cts->add_title(2,"Nouvelle boite");

  $frm = new form("newbox","configurecms.php?view=boxes",false,"POST","Nouvelle boite");
  $frm->add_hidden("action","addbox");
  if ( $ErreurAddBox )
    $frm->error($ErreurAddBox);

  $sfrm = new form("typebox",null,null,null,"Personnalisée");
  $sfrm->add_text_field("name","Code (nom)","",true);
  $sfrm->add_text_field("title","Titre","",true);
  $frm->add($sfrm,false,true,true,"custom",false,true);

  if ( !in_array("calendrier",$boxes) )
  {
    $sfrm = new form("typebox",null,null,null,"Calendrier");
    $frm->add($sfrm,false,true,false,"calendrier",false,true);
  }
  $frm->add_submit("save","Ajouter");
  $cts->add ( $frm );

  $cts->add_title(2,"Sections où les boites seront affichées");

  $frm = new form("setboxsections","configurecms.php?view=boxes",false,"POST","Sections où les boites seront affichées");
  $frm->add_hidden("action","setboxsections");

  foreach ( $onglets_noms as $nom => $titre )
    $frm->add_checkbox("sections[$nom]","$titre",in_array($nom,$boxes_sections));

  $frm->add_submit("save","Enregistrer");
  $cts->add ( $frm );

}
else if ( $_REQUEST["view"] == "options" )
{
  $cts->add_title(2,"Paramètrage");

  $frm = new form("setconfig","configurecms.php?view=options",true,"POST","Options");
  $frm->add_hidden("action","setconfig");

  $sfrm = new form("typebox",null,null,null,"Section membres");

  unset($GLOBALS['ROLEASSO'][ROLEASSO_MEMBRE]);
  unset($GLOBALS['ROLEASSO'][ROLEASSO_MEMBREACTIF]);

  $sfrm->add_select_field("membres_upto","Membres, liste jusqu'au niveau",$GLOBALS['ROLEASSO'], $site->config["membres.upto"]);
  $sfrm->add_checkbox("membres_allowjoinus","Membres, afficher le formulaire \"Rejoignez-nous\"",$site->config["membres.allowjoinus"]);
  $frm->add($sfrm);

  $sfrm = new form("typebox",null,null,null,"Page accueil");
  $sfrm->add_checkbox("home_news","Afficher les nouvelles",$site->config["home.news"]);
  $sfrm->add_checkbox("home_excludenewssiteae","Afficher seulement les nouvelles spécifiques à AECMS",$site->config["home.excludenewssiteae"]);


  //excludenewssiteae
  $frm->add($sfrm);



  $frm->add_submit("save","Enregistrer");
  $cts->add($frm);

}
else if ( $_REQUEST["view"] == "css" )
{
/*$tabs = array(
        array("","configurecms.php?view=css", "Version simplifiée"),
        array("avance","configurecms.php?view=css&version=avance","Version avancée"),
        );

$cts->add(new tabshead($tabs,$_REQUEST["version"]));*/

$base_styles = array("base.css"=>"Site AE","base-blackie.css"=>"Blackie","base-verticalie.css"=>"Verticalie");

  if ( file_exists($basedir."/specific/custom.css") )
    $custom = file_get_contents($basedir."/specific/custom.css");
  else
    $custom = "";
  $cts->add_title(2,"Feuille de style");
  $frm = new form("setcss","configurecms.php?view=css",true,"POST","CSS");
  $frm->add_hidden("action","setcss");
  $frm->add_select_field("css_base","Style de base",$base_styles, $site->config["css.base"]);
  $frm->add_text_area("data","Code CSS personalisé",$custom,80,20);
  $frm->add_submit("save","Enregistrer");
  $cts->add($frm);

  $cts->add_title(2,"Images pour la feuille de style personalisée");

  $files=array();

  $dir = $basedir."/specific/img/";

  if (is_dir($dir))
  {    if ($dh = opendir($dir))
    {      while (($file = readdir($dh)) !== false)
      {
        if ( is_file($dir.$file) )
          $files[]=array("filename"=>$file,"useincss"=>"img/".$file);
      }      closedir($dh);    }  }

  $cts->add( new sqltable ( "cssimg", "Images", $files,
  "configurecms.php?view=css", "filename", array("filename"=>"Fichier","useincss"=>"Nom à utiliser dans le code CSS"),
  array("delete"=>"Supprimer"), array() ));

  $cts->add_title(2,"Ajouter une image pour la feuille de style personalisée");

  $frm = new form("addfile","configurecms.php?view=css");
  $frm->allow_only_one_usage();
  $frm->add_hidden("action","addimgfile");
  $frm->add_file_field("file","Fichier",true);
  $frm->add_submit("add","Ajouter");
  $cts->add($frm);
}
else if ( $_REQUEST["view"] == "footer" && isset($site->config['footer']))
{
  $cts->add_title(2,"Footer");
  $frm = new form("setfooter","configurecms.php?view=footer",true,"POST","CSS");
  $frm->add_hidden("action","setfooter");
  $frm->add_dokuwiki_toolbar("footer");
  $frm->add_text_area("footer",
                      "Footer",
                      $site->config['footer'],
                      80,
                      20);
  $frm->add_submit("save","Enregistrer");
  $cts->add($frm);
}
else if( $_REQUEST["view"] == "news" )
{


  $news = new nouvelle($site->db,$site->dbrw);
  $lieu = new lieu($site->db);

  /* suppression de la nouvelle via la sqltable */
  if ((isset($_REQUEST['id_nouvelle']))
      && ($_REQUEST['action'] == "delete"))
    {

      $news = new nouvelle ($site->db, $site->dbrw);
      $id = intval($_REQUEST['id_nouvelle']);
      $news->load_by_id ($id);
      $news->delete ();
      $cts->add("<p>Suppression de la nouvelle eff&eacute;ctu&eacute;e avec succ&egrave;s</p>");
    }

  /* modification de la nouvelle via formulaire */
  if ((isset($_REQUEST['id_nouvelle']))
      && ($_REQUEST["action"] == "save"))
    {
      $modere = false;
      $id_lieu = intval($_REQUEST['id_lieu']);
      $lieu->load_by_id($id_lieu);
      $news->load_by_id($_REQUEST["id_nouvelle"]);


      if ( $_REQUEST["title"] && $_REQUEST["content"] )
  {
    $news->save_news($site->asso->id,
         $_REQUEST['title'],
         $_REQUEST['resume'],
         $_REQUEST['content'],
         false,
         null,
         $_REQUEST["type"],
         $lieu->id,
         !isset($_REQUEST['non_asso_seule']) ? NEWS_CANAL_AECMS : NEWS_CANAL_SITE);
    $news->set_tags($_REQUEST["tags"]);
  }
    }


  /* formulaire de modification de la nouvelle */
  if ((isset($_REQUEST['id_nouvelle']))
      && ($_REQUEST['action'] == "edit"))
    {
      $news = new nouvelle ($site->db);
      $id = intval($_REQUEST['id_nouvelle']);
      $news->load_by_id ($id);

      // affichage de la nouvelle
      $frm = new form ("editnews","configurecms.php?view=news",false,"POST","Edition d'une nouvelle");
      $frm->add_hidden("action","save");
      $frm->add_hidden("id_nouvelle",$news->id);
      $frm->add_select_field ("type",
            "Type de nouvelle",
            array(NEWS_TYPE_APPEL => "Appel/concours",
            NEWS_TYPE_EVENT => "Événement ponctuel",
            NEWS_TYPE_HEBDO => "Séance hebdomadaire",
            NEWS_TYPE_NOTICE => "Info/resultat")
            ,$news->type);

      $frm->add_text_field("title", "Titre",$news->titre,true);
      $frm->add_checkbox ( "non_asso_seule", "Publier aussi sur le site de l'AE (sera soumis à modération)", $news->id_canal==NEWS_CANAL_SITE);
      $frm->add_entity_select("id_lieu", "Lieu", $site->db, "lieu",$news->id_lieu,true);
      $frm->add_text_field("tags", "Tags",$news->get_tags());
      $frm->add_text_area ("resume","Resume",$news->resume);
      $frm->add_dokuwiki_toolbar('content');
      $frm->add_text_area ("content", "Contenu",$news->contenu,80,10,true);

      $frm->add_submit("valid","Enregistrer");
      $cts->add($frm);
    }


  /* affichage de la liste des nouvelles */
  $req = new requete($site->db,
         "SELECT `nvl_nouvelles`.*,
                      CONCAT(`utilisateurs`.`prenom_utl`,
                             ' ',
                             `utilisateurs`.`nom_utl`) AS `nom_prenom`
                      FROM `nvl_nouvelles`, `utilisateurs`
                      WHERE `nvl_nouvelles`.`modere_nvl`='1'
                      AND `nvl_nouvelles`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`
                      AND `nvl_nouvelles`.`id_canal`='".NEWS_CANAL_AECMS."'
                      AND `nvl_nouvelles`.`id_asso`='".$site->asso->id."'
                      ORDER BY `nvl_nouvelles`.`date_nvl`
                      DESC");



  // génération de la liste de nouvelles
  $tabl = new sqltable ("news_list",
      "Liste des nouvelles",
      $req,
      "configurecms.php?view=news",
      "id_nouvelle",
      array ("titre_nvl" => "Titre",
             "nom_prenom" => "auteur",
             "date_nvl" => "Date"),
      array ("edit"=>"Modifier",
             "delete"=>"Supprimer"),
      array (),
      array ());



  $cts->add($tabl,true);
  $cts->add_title(2,"Ajouter une nouvelle");
  // pour eviter d'ajouter 400 ligne de code ici ca sera juste un lien
  $cts->add(new itemlist("Ajouter une nouvelle",false,array(
    "<a href=\"news.php\">Ajouter une nouvelle</a>"
  )));

} // fin onglet administration des nouvelles



$cts->add_title(2,"Outils");

$cts->add(new itemlist("Outils",false,array(
  "<a href=\"index.php?page=new\">Creer une nouvelle page</a>",
  "<a href=\"form.php?form=new\">Creer un formulaire</a>"
)));


$site->add_contents($cts);

$site->end_page();

?>
