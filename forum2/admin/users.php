<?php

/* Copyright 2008
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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
require_once($topdir .'include/cts/sqltable.inc.php');
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
$cts->add(new tabshead($tabs,'users'));

if($_REQUEST['action']=='ban')
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST['id_utilisateur']);
  if($user->is_valid())
  {
    _log($site->dbrw,'Ajout d\'un utilisateur au groupe ban_forum','Ajout de l\'utilisateur '.$user->nom.' '.$user->prenom.' (id : '.$user->id.') au groupe ban_forum (id : 39)','Groupes',$site->user);
    $user->add_to_group(39);
    $cts->add_paragraph($user->get_display_name().' a bien été banni du forum');
  }
}
elseif($_REQUEST['action']=='unban')
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST['id_utilisateur']);
  if($user->is_valid())
  {
    _log($site->dbrw,'Retrait d\'un utilisateur du groupe ban_forum','Retrait de l\'utilisateur '.$user->get_display_name().' (id : '.$user->id.') du groupe ban_forum (id : 39)','Groupes',$site->user);
    $user->add_to_group(39);
    $cts->add_paragraph($user->get_display_name().' a bien été "débanni" du forum');
  }
}
elseif($_REQUEST['action']=='unbans')
{
  $user = new utilisateur($site->db,$site->dbrw);
  foreach($_REQUEST['id_utilisateurs'] as $id_utilisateur )
  {
    $user->load_by_id($id_utilisateur);
    if($user->is_valid())
    {
      _log($site->dbrw,'Retrait d\'un utilisateur du groupe ban_forum','Retrait de l\'utilisateur '.$user->get_display_name().' (id : '.$user->id.') du groupe ban_forum (id : 39)','Groupes',$site->user);
      $user->add_to_group(39);
      $cts->add_paragraph($user->get_display_name().' a bien été "débanni" du forum');
    }
  }
}

$frm = new form('add','users.php',false,'POST','Bannir un utilisateur');
$frm->add_hidden('action','ban');
$frm->add_user_fieldv2('id_utilisateur','Utilisateur');
$frm->add_submit('valid','Ajouter');
$cts->add($frm,true);
$sql='SELECT `utilisateurs`.`id_utilisateur`, '.
     'CONCAT(`utilisateurs`.`prenom_utl`,\' \',`utilisateurs`.`nom_utl`) as `nom_utilisateur` '.
     'FROM `utl_groupe`, `utilisateurs` '.
     'WHERE `utilisateurs`.`id_utilisateur` = `utl_groupe`.`id_utilisateur` '.
     'AND `utl_groupe`.`id_groupe` = 39 '.
     'ORDER BY `utilisateurs`.`nom_utl`,`utilisateurs`.`prenom_utl`';
$req = new requete($site->db,$sql);
$tbl = new sqltable('bannis',
                    'Utilisateurs bannis du forum',
                    $req,
                    'users.php',
                    'id_utilisateur',
                    array('nom_utilisateur'=>'Utilisateur'),
                    array('unban'=>'Enlever le ban'),
                    array('unbans'=>'Enlever le ban'));
$cts->add($tbl);
$site->add_contents($cts);
$site->end_page();

?>
