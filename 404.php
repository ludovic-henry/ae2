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

/** @file
 *
 * @brief Page d'erreur HTTP 404
 */

Header("HTTP/1.0 404 Not Found");

include($topdir. "include/site.inc.php");
include($topdir. "include/entities/page.inc.php");

$site = new site ();

$site->start_page("none","Erreur 404");
$site->add_contents(new error("Page inconnue (404)","Merci de vérifier le lien que vous avez emprunté"));
$site->end_page();

?>
