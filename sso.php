<?php
/* Copyright 2004-2006
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

$topdir="";
require_once($topdir."include/site.inc.php");
$site = new site();


/**********************
 * Liste des paramètres à passer :
 * $_POST["api_key"] la clé d'identification du client
 * $_POST["mode"] le mode de login (e-mail UTBM(0), Assidu(1) ou id utilisateur(2))
 * $_POST["username"] l'identifiant utilisateur (prénom.nom ou id)
 * $_POST["password"] le mot de passe de l'utilisateur
 */


function valid_key($key, $site)
{
  $valid = new requete($site->db,"SELECT `key`
                     FROM `sso_api_keys`
                     WHERE `key` = '".mysql_real_escape_string($key)."'");

  if ( $valid->lines != 1)
  {
    return FALSE;
  }
  else
    return TRUE;
}

function endswithmail($haystack)
{
    return substr($haystack, -strlen('@utbm.fr')) == '@utbm.fr';
}

if ( !isset($_POST["api_key"])
     || !isset($_POST["mode"])
     || !isset($_POST["username"])
     || !isset($_POST["password"])
   )
{
  echo -1;
  exit();
}
if ( !valid_key($_POST["api_key"], $site) )
{
  echo -1;
  exit();
}

$username = $_POST["username"];

if ( strtolower($_POST["mode"]) == "utbm" )
    $site->user->load_by_email(endswithmail($username) ? $username : $username."@utbm.fr");
elseif ( strtolower($_POST["mode"]) == "assidu" )
    $site->user->load_by_email($username."@assidu-utbm.fr");
elseif ( strtolower($_POST["mode"]) == "id" )
    $site->user->load_by_id($username);
else
    $site->user->load_by_email(endswithmail($username) ? $username : $username."@utbm.fr");

if ( $site->user->id != -1 && $site->user->hash == "valid" && $site->user->is_password($_POST["password"]) )
  echo 1;
else
  echo 0;

exit();
?>
