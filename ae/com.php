<?php

/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
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
 * along with site program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir."include/cts/board.inc.php");


$site = new site ();

if ( !$site->user->is_in_group("moderateur_site") )
  $site->error_forbidden("accueil");

$site->start_page("accueil","Tâches courantes Com` AE");

$cts = new contents("Tâches courantes de la Com` AE");

$board = new board();

$sublist = new itemlist("Contenu paramétrable");
$sublist->add("<a href=\"site.php\">Textes paramétrables</a>");
$sublist->add("<a href=\"weekly_upload.php\">Planning/Photo de la semaine</a>");
$sublist->add("<a href=\"../article.php?name=info:welcome\">Texte d'accueil pour les non connectés</a>");

$board->add($sublist,true);

$sublist = new itemlist("Modération");

$req = new requete($site->db,"SELECT COUNT(*) FROM `nvl_nouvelles`  WHERE `modere_nvl`='0' ");
list($nbnews) = $req->get_row();

$req = new requete($site->db,"SELECT COUNT(*) FROM `d_file`  WHERE `modere_file`='0' ");
list($nbfichiers) = $req->get_row();
$req = new requete($site->db,"SELECT COUNT(*) FROM `d_folder`  WHERE `modere_folder`='0' ");
list($nbdossiers) = $req->get_row();
$nbfichiers+=$nbdossiers;

$req = new requete($site->db,"SELECT COUNT(*) FROM `planet_flux`  WHERE `modere`='0' ");
list($nbflux) = $req->get_row();
$req = new requete($site->db,"SELECT COUNT(*) FROM `planet_tags`  WHERE `modere`='0' ");
list($nbtags) = $req->get_row();
$nbflux+=$nbtags;

$req = new requete($site->db,"SELECT COUNT(*) FROM `aff_affiches`  WHERE `modere_aff`='0' ");
list($nbaffiches) = $req->get_row();

if ( $nbnews > 0 )
  $sublist->add("<a href=\"moderenews.php\"><b>Modération des nouvelles ($nbnews)</b></a>");
else
  $sublist->add("<a href=\"moderenews.php\">Modération des nouvelles (Aucune)</a>");

if ( $nbfichiers > 0 )
  $sublist->add("<a href=\"moderedrive.php\"><b>Modération des fichiers et dossiers ($nbfichiers)</b></a>");
else
  $sublist->add("<a href=\"moderedrive.php\">Modération des fichiers et dossiers (Aucun)</a>");

if ( $nbflux > 0 )
  $sublist->add("<a href=\"".$topdir."planet/index.php?view=modere\"><b>Modération des flux ($nbflux)</b></a>");
else
  $sublist->add("<a href=\"".$topdir."planet/index.php?view=modere\">Modération des flux (Aucun)</a>");

if ( $nbaffiches > 0 )
  $sublist->add("<a href=\"modereaffiches.php\"><b>Modération des affiches ($nbaffiches)</b></a>");
else
  $sublist->add("<a href=\"modereaffiches.php\">Modération des affiches (Aucune)</a>");

$board->add($sublist,true);

$sublist = new itemlist("Divers");
$sublist->add("<a href=\"sondage.php\">Sondages</a>");
$sublist->add("<a href=\"weekmail.php\">Weekmail</a>");
$sublist->add("<a href=\"".$topdir."affiches.php\">Proposer une affiche</a>");
$sublist->add("<a href=\"".$topdir."affiches.php?page=list\">Affiches actuelles ou à venir</a>");
$sublist->add("<a href=\"".$topdir."news.php?id_asso=1\">Proposer une nouvelle</a>");
$sublist->add("<a href=\"".$topdir."asso/campagne.php?id_asso=1\">Organiser une campagne</a>");
$board->add($sublist,true);

$cts->add($board);

$site->add_contents($cts);

$site->end_page();

?>
