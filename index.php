<?php

/** @file
 *
 * @brief La page principale avec l'affichage des 10 dernières new
 * modérées.
 *
 */

/* Copyright 2004,2006,2007
 * - Alexandre Belloni <alexandre POINT belloni CHEZ utbm POINT fr>
 * - Thomas Petazzoni <thomas POINT petazzoni CHEZ enix POINT org>
 * - Julien Etelain <julien CHEZ pmad POINT net>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "./";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/newsflow.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/entities/news.inc.php");

$site = new site ();
$site->add_rss("Toute l'actualité de l'association des étudiants","rss.php");
$site->add_css("css/doku.css");
$site->add_js("js/superflux.js");

$site->start_page("accueil","Bienvenue");

if ( !$site->user->is_valid() )
{
  require_once($topdir. "include/entities/page.inc.php");
  $page = new page ($site->db);
  $page->load_by_pagename("info:welcome");
  $site->add_contents($page->get_contents());
}

$site->add_contents(new newsfront($site->db));

$site->end_page();

?>
