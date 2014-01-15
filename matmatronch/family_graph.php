<?php

/** @file
 *
 * @brief Fichier generant un png d'arbre genealogique
 *
 */

/* Copyright 2006
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

/* on garde juste les infos d'utilisateur pour faire de l'ACL */
$topdir = "./../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir. "include/cts/gallery.inc.php");
require_once($topdir. "include/globals.inc.php");

require_once ($topdir. "include/genealogie.inc.php");

$site = new site ();

if ( !$site->user->is_valid() )
{
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: image/gif");
    header("Content-Disposition: inline; filename=".
    basename("/images/na.gif"));
    readfile("/var/www/ae/www/images/na.gif");
    exit ();
}

$id = mysql_real_escape_string($_REQUEST['id']);

$gene = new genealogie ();
$gene->generate_filiation_utl ($id, $site->db);
$gene->generate ();

$gene->destroy ();
?>
