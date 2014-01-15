<?
/** @file
 *  Rendu d'un trajet.
 *
 */

/* Copyright 2006, 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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

session_start ();

$topdir = "../";

include($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/imgloc.inc.php");
require_once($topdir. "include/entities/pays.inc.php");
require_once($topdir. "include/entities/ville.inc.php");
require_once($topdir. "include/entities/lieu.inc.php");


$db = new mysqlae ();

$coords = array();


$villes[0] = $_SESSION['trajet']['start'];

if (is_array($_SESSION['trajet']['etapes']))
{
  foreach ($_SESSION['trajet']['etapes'] as $etape)
    $villes[] = $etape;
}

$villes[] = $_SESSION['trajet']['stop'];


$img = new imgloc(800, IMGLOC_COUNTRY, $db, new pgsqlae());


if (count($villes))
{
  foreach($villes as $ville)
    $img->add_step_by_idville($ville, false);
}

$img->add_context();

$img = $img->generate_img();

require_once ($topdir . "include/watermark.inc.php");
$wm_img = new img_watermark ($img->imgres);

$wm_img->output();

?>
