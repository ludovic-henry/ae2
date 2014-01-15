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
$cts->add(new tabshead($tabs,''));

$cts->add_paragraph('Administration du forum. Dans cette section vous pourrez '.
                    'administrer le forum ae. Vous pourrez bannir ou "débannir" '.
                    'un utilisateur, en spécifiant un motifs, voir, modifier, créer '.
                    'des forums. Le reste de l\'administration s\'effectue '.
                    'directement sur le forum.');
$site->add_contents($cts);
$site->end_page();

?>
