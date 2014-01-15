<?php

/* Copyright 2008
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

if ( !$site->user->is_in_group("compta_admin") )
  $site->error_forbidden("accueil");

$site->start_page("accueil","Tâches courantes des trésoriers AE");

$cts = new contents("Tâches courantes des trésoriers AE");

$board = new board();

$sublist = new itemlist("Comptabilité de l'AE","boxlist");
$sublist->add("<a href=\"".$topdir."entreprise.php\">Carnet d'adresses</a>");
$sublist->add("<a href=\"".$topdir."compta/\">Comptabilité</a>");
$sublist->add("<a href=\"".$topdir."comptoir/admin.php\">Comptoirs AE</a>");
$sublist->add("<a href=\"".$topdir."compta/eticket.php\">Gestion E-tickets</a>");
$board->add($sublist,true);

$sublist = new itemlist ("Opérations diverses", "boxlist");
$sublist->add ("<a href=\"".$topdir."ae/fermeture_comptes.php\">Clôture des comptes des non cotisants de plus de 2 ans</a>");
$board->add ($sublist, true);

$cts->add($board);

$site->add_contents($cts);

$site->end_page();

?>
