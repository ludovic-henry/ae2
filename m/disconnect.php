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
 * Disconnect user
 */

$topdir = "../";

require_once($topdir. "include/site.inc.php");


$site = new site();
$site->set_mobile(true);

/* See ya ! */
$req = new delete($site->dbrw, "site_sessions", array("id_session"=>$_COOKIE['AE2_SESS_ID']) );

/* Some cakes's stuff */
setcookie ("AE2_SESS_ID", "", time() - 3600, "/", "ae.utbm.fr", 0);
unset($_COOKIE['AE2_SESS_ID']);
unset($_SESSION['session_redirect']);

/* Go back home */
$loc = "/";
if($GLOBALS["taiste"])
  $loc .= "taiste/";

header("Location: ".$loc."m/");  /* You again ! */


/* Do not cross. */
$site->end_page();

?>
