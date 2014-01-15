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

$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir."include/cts/board.inc.php");


$site = new site ();
$site->allow_only_logged_users("accueil");

$today = date("Y-m-d");
$site->start_page("accueil","Ma boite à outils");
$cts = new contents("Ma boite à outils");
$board = new board();



// infos et réservations
$sublist = new itemlist("Infos et réservations");
$req = new requete($site->db,"SELECT  " .
  "COUNT(*) " .
  "FROM sl_reservation " .
  "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
  "WHERE sl_reservation.id_utilisateur='".$site->user->id."' AND " .
  "sl_reservation.date_debut_salres >= '$today' AND " .
  "((sl_reservation.date_accord_res IS NULL) OR " .
  "(sl_salle.convention_salle=1 AND sl_reservation.convention_salres=0)) " );
list($nb) = $req->get_row();
if ( $nb )
  $sublist->add("<a href=\"".$topdir."user/reservations.php\"><b>Mes reservations de salles : $nb en attente</b></a>");
else
  $sublist->add("<a href=\"".$topdir."user/reservations.php\">Mes reservations de salles</a>");

$req = new requete($site->db,"SELECT COUNT(*) " .
  "FROM inv_emprunt " .
  "WHERE id_utilisateur='".$site->user->id."' AND etat_emprunt<=1");
list($nb) = $req->get_row();

if ( $nb )
  $sublist->add("<a href=\"".$topdir."user/emprunts.php\"><b>Mes emprunts de matériel : $nb en attente</b></a>");
else
  $sublist->add("<a href=\"".$topdir."user/emprunts.php\">Mes emprunts de matériel</a>");

$sublist->add("<a href=\"".$topdir."news.php\">Proposer une nouvelle</a>");
$sublist->add("<a href=\"".$topdir."affiches.php\">Proposer une affiche</a>");
$sublist->add("<a href=\"".$topdir."salle.php?page=reservation\">Reserver une salle</a>");
$sublist->add("<a href=\"".$topdir."emprunt.php\">Reserver du matériel</a>");
$board->add($sublist,true);


/* Comptoirs */
$req = new requete($site->db,
   "SELECT id_comptoir,nom_cpt " .
   "FROM cpt_comptoir " .
   "WHERE id_groupe_vendeur IN (".$site->user->get_groups_csv().") AND type_cpt = '2' " .
   "AND archive != '1' " .
   "ORDER BY nom_cpt");
if ($req->lines > 0)
{
  $sublist = new itemlist("Comptoirs","boxlist");
  while(list($id,$nom)=$req->get_row())
    $sublist->add("<a href=\"".$topdir."comptoir/bureau.php?id_comptoir=$id\">Comptoir : ".$nom."</a>");
  $board->add($sublist,true);
}

// assos
$req = new requete($site->db,
        "SELECT `asso`.`id_asso`, " .
        "`asso`.`nom_asso` ".
        "FROM `asso_membre` " .
        "INNER JOIN `asso` ON `asso`.`id_asso`=`asso_membre`.`id_asso` " .
        "WHERE `asso_membre`.`role` > 1 AND `asso_membre`.`date_fin` IS NULL " .
        "AND `asso_membre`.`id_utilisateur`='".$site->user->id."' " .
        "AND `asso`.`id_asso` != '1' " .
        "ORDER BY asso.`nom_asso`");
if ( $req->lines > 0 )
{
  if( $site->user->is_in_group("root") || $site->user->is_in_group("moderateur_site"))
    $sublist = new itemlist("Gestion assos/clubs","boxlist");
  elseif( $req->lines == 0 )
    $sublist = new itemlist("Gestion assos/club","boxlist");
  else
    $sublist = new itemlist("Gestion assos/clubs","boxlist");

  if( $site->user->is_in_group("root") )
    $sublist->add("<a href=\"".$topdir."rootplace/index.php\">Équipe informatique</a>");
  if($site->user->is_in_group("moderateur_site"))
    $sublist->add("<a href=\"".$topdir."ae/com.php\">Équipe com</a>");

  while ( list($id,$nom) = $req->get_row() )
    $sublist->add("<a href=\"".$topdir."asso.php?id_asso=$id\">$nom</a>");

  $board->add($sublist,true);
}
elseif($site->user->is_in_group("root") || $site->user->is_in_group("moderateur_site"))
{
  if($site->user->is_in_group("root") && $site->user->is_in_group("moderateur_site"))
    $sublist = new itemlist("Gestion assos/clubs","boxlist");
  else
    $sublist = new itemlist("Gestion assos/club","boxlist");
  if($site->user->is_in_group("root"))
    $sublist->add("<a href=\"".$topdir."rootplace/index.php\">Équipe informatique</a>");
  if($site->user->is_in_group("moderateur_site"))
    $sublist->add("<a href=\"".$topdir."ae/com.php\">Équipe com</a>");
  $board->add($sublist,true);
}

//Autre
$req = new requete($site->db,"SELECT `id_depot` FROM `svn_member_depot` WHERE `id_utilisateur`='".$site->user->id."'");
if($req->lines != 0)
{
  $sublist = new itemlist("Autre","boxlist");
  $sublist->add("<a href=\"".$topdir."user/svn.php\">Mes SVN</a>");
  $board->add($sublist,true);
}

$cts->add($board);
$site->add_contents($cts);
$site->end_page();

?>
