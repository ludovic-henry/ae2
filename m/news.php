<?php
/* Copyright 2011
 * - Antoine Tenart < antoine dot tenart at gmail dot com >
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
 * Display news for the mobile version
 */

$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/news.inc.php");
require_once($topdir. "include/entities/asso.inc.php");


$site = new site();
$site->set_mobile(true);
$site->add_css("themes/mobile/css/news.css");

$cts = new contents();
$cts->add_title(1, "News", "mob_title");
$site->add_contents($cts);

$news = new nouvelle($site->db, $site->dbrw);

if ( isset($_REQUEST["id_nouvelle"]) )
  $news->load_by_id($_REQUEST["id_nouvelle"]);

if($news->id > 0 && $news->modere) {
  $site->start_page("accueil", $news->titre);
  $site->add_contents($news->get_contents());
}

/* Do not cross. */
$site->end_page();

?>
