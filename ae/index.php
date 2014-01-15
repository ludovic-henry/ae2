<?php

/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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

if ( !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("accueil","group","gestion_ae");

$site->start_page("accueil","Tâches courantes AE");

$cts = new contents("Tâches courantes AE");

$board = new board();

$sublist = new itemlist("Pret matériel");
$sublist->add("<a href=\"../emprunt.php\">Reserver du matériel</a>");
$sublist->add("<a href=\"../emprunt.php?page=retrait\">Preter du matériel</a> (retrait immédiat)");
$sublist->add("<a href=\"modereemp.php?view=togo\">Retrait matériel</a>");
$sublist->add("<a href=\"modereemp.php\">Modération des emprunts de matériel</a>");
$sublist->add("<a href=\"../emprunt.php?page=retour\">Retour de matériel</a>");
$board->add($sublist,true);

$sublist = new itemlist("Carte AE");
$sublist->add("<a href=\"cartesae.php?view=retrait\">Retrait carte AE (+ cadeau)</a>");
$sublist->add("<a href=\"cartesae.php?view=bureau\">Arrivée cartes AE au bureau</a>");
$sublist->add("<a href=\"cartesae.php\">Impression cartes AE</a>");
$board->add($sublist,true);

$board->clear();

$sublist = new itemlist("Salles");
$sublist->add("<a href=\"".$topdir."salle.php?page=reservation\">Reserver une salle</a>");
$sublist->add("<a href=\"".$topdir."ae/modereres.php\">Modération des reservations de salle</a>");
$board->add($sublist,true);

$sublist = new itemlist("Cotisations");
$sublist->add("<a href=\"cotisations.php#newstudent\">Nouvelle cotisation</a>");
$sublist->add("<a href=\"cotisations.php\">Renouvellement cotisation/consultation</a> (Possible aussi depuis la fiche utilisateur)");
$sublist->add("<a href=\"cotizlast.php\">Dernières cotisations réalisées</a>");
$board->add($sublist,true);

$board->clear();

$sublist = new itemlist("Inventaire");
$sublist->add("<a href=\"".$topdir."objtype.php\">Types d'objet</a>");
$sublist->add("<a href=\"".$topdir."sitebat.php\">Batiments/Salles</a>");
$board->add($sublist,true);


$sublist = new itemlist("Elections");
$sublist->add("<a href=\"elections.php\">Organiser une election</a>");
$sublist->add("<a href=\"elections.php\">Modifier/Consulter une election</a>");
$board->add($sublist,true);

$board->clear();

$sublist = new itemlist("Divers");
$sublist->add("<a href=\"".$topdir."ae/infotodo.php?action=nouveau\">Reporter un bug / demander une fonctionnalité</a>");
$sublist->add("<a href=\"".$topdir."activites.php?view=list\">Listing des responsables et des trésoriers des activités</a>");
$sublist->add("<a href=\"".$topdir."asso/sendfax.php?id_asso=1\">Envoyer des fax</a>");
$sublist->add("<a href=\"".$topdir."group.php\">Gestion des groupes</a>");
$sublist->add("<a href=\"".$topdir."ae/partenariats.php\">Avantages partenariats en attente</a>");
$board->add($sublist,true);

$cts->add($board);

$site->add_contents($cts);

$site->end_page();

?>
