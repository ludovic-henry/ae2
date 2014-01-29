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


$topdir = "./";
include($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/tree.inc.php");

$site = new site ();

if ( isset($_REQUEST['id_utilisateur']) )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);
  if ( $user->id < 0 )
  {
    $site->error_not_found("matmatronch");
    exit();
  }
  $can_edit = ( $user->id==$site->user->id || $site->user->is_in_group("gestion_ae") );

  if ( $user->id != $site->user->id && !$site->user->utbm && !$site->user->ae )
    $site->error_forbidden("services","reserved");
}
else
{
  $user = &$site->user;
  $can_edit = true;
}

$site->start_page("services","Famille");

// On charge tout, ça évite un nombre déraisonable de requête
// si la base passe à 20000 entrés, il faudra trouver une autre solution
$req = new requete($site->db,
    "SELECT " .
    "`parrains`.`id_utilisateur` AS `id_utilisateur_parent`, " .
    "`utilisateurs`.`id_utilisateur` AS `id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur` " .
    "FROM `utilisateurs` " .
    "LEFT JOIN `parrains` ON `utilisateurs`.`id_utilisateur`=`parrains`.`id_utilisateur_fillot` ");


$site->add_contents(new treects ( "Famille UTBM", $req, $user->id, "id_utilisateur", "id_utilisateur_parent", "nom_utilisateur" ));

$site->add_contents(new contents ("Arbre g&eacute;n&eacute;alogique",
          "<p>Un arbre g&eacute;n&eacute;alogique peut &ecirc;tre g&eacute;n&eacute;r&eacute; ".
          "en <a href=/matmatronch/index.php/famille/".$user->id.">cliquant ici</a></p>"));

$site->end_page();

?>
