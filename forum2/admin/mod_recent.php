<?php

/* Copyright 2011
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
$cts->add(new tabshead($tabs,'modrecent'));

if (isset($_REQUEST['showall']))
  $cts->add_paragraph("<a href=\"mod_recent.php\">Ne pas afficher les auto-modérations</a>");
else
  $cts->add_paragraph("<a href=\"?showall\">Afficher les auto-modérations</a>");

$req = new requete ($site->db, "SELECT `frm_modere_info` . * , ".
  "COALESCE( `frm_sujet`.`titre_sujet` , `frm_message`.`titre_message` ) titre_sujet , ".
  "CONCAT( `utilisateurs`.`prenom_utl` , ' ', `utilisateurs`.`nom_utl` ) AS `nom_utilisateur` ".
  "FROM `frm_modere_info` ".
  "LEFT JOIN utilisateurs USING ( id_utilisateur ) ".
  "LEFT JOIN frm_message USING ( id_message ) ".
  "LEFT JOIN frm_sujet USING ( id_sujet ) ".
  "WHERE DATEDIFF( NOW( ) , `modere_date` ) < '30' ".
  (isset($_REQUEST['showall']) ? "" : " AND `modere_action` IN ('DELETE', 'UNDELETE', 'EDIT', 'DELETEFIRST')").
  "ORDER BY modere_date DESC;");

$tbl = new sqltable("modrecent",
  "Actions de modtération récentes", $req, "../",
  "id_message",
  array("nom_utilisateur"=>"Utilisateur","modere_action"=>"Action","modere_date"=>"Date","titre_sujet"=>"Sujet"),
  array("view"=>"Voir le message"),
  array(),
  array("modere_action"=>array('DELETE'=>"Message supprimé", 'UNDELETE'=>"Message rétabli", 'EDIT'=>"Message modifié", 'AUTODELETE'=>"Message supprimé par l'utilisateur", 'AUTOEDIT'=>"Message modifié par l'utilisateur", 'DELETEFIRST'=>"Sujet supprimé")),
  true, true, array(), "#msg");

$cts->add($tbl);

$site->add_contents($cts);
$site->end_page();

?>
