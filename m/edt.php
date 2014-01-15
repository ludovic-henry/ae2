<?php
/* Copyright 2012
 * - Antoine Tenart < antoine dot tenart at utbm dot com >
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

/**
 * edt for the mobile version
 */

$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "pedagogie/include/pedag_user.inc.php");

$site = new site();
$site->set_mobile(true);

$user = new pedag_user($site->db, $site->dbrw);
$user->load_by_id($site->user->id);

$site->start_page("Emploi du temps", "Emploi du temps");

$cts = new contents();
$cts->add_title(1, "Emploi du temps", "mob_title");

$semestre = SEMESTER_NOW;

$cts->add_paragraph("<center><img src=\"".$wwwtopdir."pedagogie/edt.php?semestre=".$semestre."&action=print&id_utilisateur=".
      $user->id."\" alt=\"Emploi du temps ".$semestre."\" /></center>");


$site->add_contents($cts);


/* Do not cross. */
$site->end_page();

?>
