<?php

/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
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

$site = new site();

if ( preg_match('/^\/var\/www\/ae\/www\/taiste\//', $_SERVER['SCRIPT_FILENAME']) )
  $infofile = $topdir."var/cache/stream";
else
  $infofile = $topdir."var/cache/stream-prod";


if ( file_exists($infofile) )
  $GLOBALS["streaminfo"] = unserialize(file_get_contents($infofile));
else
  $GLOBALS["streaminfo"] = array();

if ( !$GLOBALS["is_using_ssl"] )
{
  echo "sorry, please use ssl\n";
  exit();
}

$valid = new requete($site->db,
  "SELECT `key` ".
  "FROM `sso_api_keys` ".
  "WHERE `key` = '".mysql_real_escape_string($_REQUEST["key"])."'");

if ( $valid->lines != 1 )
{
  echo "sorry, wrong key\n";
  exit();
}

$allowed=array("ogg","mp3","title","artist","message");
$updated = array();

foreach ( $allowed as $key )
{
  if ( isset($_REQUEST[$key]) )
  {
    if(!empty($_REQUEST[$key])) {
      $GLOBALS["streaminfo"][$key] = $_REQUEST[$key];
    } elseif(isset($GLOBALS["streaminfo"][$key])) {
      unset($GLOBALS["streaminfo"][$key]);
    }
    $updated[] = $key;
  }
}

echo "thank you. updated: ".implode(", ",$updated)."\n";

$GLOBALS["streaminfo"]["updated"] = time();

file_put_contents($infofile,serialize($GLOBALS["streaminfo"]));


?>
