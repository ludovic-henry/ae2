<?php

/* Copyright 2007
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";
require_once($topdir. "include/site.inc.php");
$site = new site ();

if (!$site->user->is_in_group ("gestion_ae") && !$site->user->is_in_group ("portaetif"))
  $site->error_forbidden();

$site->set_side_boxes("left",array());
$site->set_side_boxes("right",array());
$cts = new contents("Portaetif");
$cts->add_paragraph("<a href=\"parrainages.php\">parrainages</a>");
$cts->add_paragraph("<a href=\"gala.php\">gala</a>");

$site->add_contents($cts);
$site->end_page();

?>
