<?php

/* Copyright 2008, 2011
 * - Remy BURNEY < rburney <point> utbm <at> gmail <dot> com >
 * - Mathieu Briand < briandmathieu at hyprua dot org >
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

$topdir='../../';
require_once($topdir .'include/site.inc.php');
require_once($topdir .'include/entities/forum.inc.php');
$site = new site();

if ( !$site->user->is_in_group('root')
     && !$site->user->is_in_group('moderateur_forum')
   )
  $site->error_forbidden("forum",'group',7);

$site->start_page('forum','Administration du forum');
$cts = new contents("Administration");
$tabs = array(array('','forum2/admin/index.php','Accueil'),
              array('users','forum2/admin/users.php','Utilisateurs'),
              array('addforums','forum2/admin/add_forum.php','Ajout de forum'),
              array('modrecent','forum2/admin/mod_recent.php','Historique de modération'),
             );
$cts->add(new tabshead($tabs,'addforums'));

$forum = new forum($site->db,$site->dbrw);

if( $_REQUEST["action"]=="new")
{
  $forum->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin'],false);
  if ($forum->create($_REQUEST['titre'], $_REQUEST['description'], isset($_REQUEST['categorie']), $_REQUEST['id_forum_parent'], $_REQUEST['id_asso'], $_REQUEST['ordre']))
  {
    $cts->add_title(2,"Nouveau forum");
    $cts->add_paragraph("Nouveau forum créé : <a href\"../index.php?id_forum=".$forum->id."\">".$_REQUEST['titre']."</a>.");
  }
  else
  {
    $cts->add_title(2,"Nouveau forum");
    $cts->add(new error("Impossible de créer le nouveau forum."));
  }
}
else
{
  $values_forum = array(null=>"(Aucun)");
  $sql = "SELECT id_forum, titre_forum FROM frm_forum ORDER BY titre_forum";
  $req = new requete($site->db, $sql);
  while( list($value,$name) = $req->get_row()){
    $values_forum[$value] = $name;
  }

  $cts->add_title(2,"Nouveau forum");
  $frm = new form("newfrm","?page=new&type=frm",true);
  $frm->add_hidden("action","new");
  $frm->add_text_field("titre","Titre","");
  $frm->add_text_field("ordre","Numéro d'ordre",0);
  $frm->add_select_field("id_forum_parent",
                       "Forum parent",
                       $values_forum,
                       "","", true);
  $frm->add_entity_select("id_asso", "Association/Club lié", $site->db, "asso",$news->id_asso,true);
  $frm->add_checkbox ( "categorie", "Catégorie", false );
  $frm->add_text_area("description","Description","");
  $frm->add_rights_field($forum,false,$forum->is_admin($site->user));
  $frm->add_submit("newfrm","Ajouter");
  $cts->add($frm);
}

$site->add_contents($cts);
$site->end_page();

?>
