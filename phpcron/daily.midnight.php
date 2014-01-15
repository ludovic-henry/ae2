<?php
/* Copyright 2006-2012
 * - Antoine Ténart <antoine dot tenart at utbm dot fr>
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

if (!isset ($argc))
  exit ();

$_SERVER['SCRIPT_FILENAME']='/var/www/ae2/phpcron';

$topdir = $_SERVER['SCRIPT_FILENAME'].'/../';
require_once ($topdir.'include/site.inc.php');

$site = new site ();

echo '==== '.date ('d/m/Y').' ====\n';


require_once ($topdir.'include/entities/news.inc.php');

nouvelle::expire_cache_content ();

?>
