<?php
/* Copyright 2005
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

$topdir = "./";

require_once($topdir. "include/site.inc.php");

$site = new site ();

if(!isset($_REQUEST['domain']))
  $site->allow_only_logged_users();

switch ($_REQUEST["domain"])
{
  case "utbm" :
    $site->user->load_by_email($_REQUEST["username"]."@utbm.fr");
  break;
  case "assidu" :
    $site->user->load_by_email($_REQUEST["username"]."@assidu-utbm.fr");
  break;
  case "id" :
    $site->user->load_by_id($_REQUEST["username"]);
  break;
  case "autre" :
    $site->user->load_by_email($_REQUEST["username"]);
  break;
  case "alias" :
    $site->user->load_by_alias($_REQUEST["username"]);
  break;
  case "carteae":
    $site->user->load_by_carteae($_REQUEST["username"], true, false);
  break;
  default :
    $site->user->load_by_email($_REQUEST["username"]."@utbm.fr");
  break;
}

if ( !$site->user->is_valid() )
{
  if(!isset($_REQUEST["mobile"])) header("Location: article.php?name=site:wrongpassworduser");
  else                            header("Location: m/");  /* TODO */
  exit();
}

if ( $site->user->hash != "valid" )
{
  if(!isset($_REQUEST["mobile"])) header("Location: article.php?name=site:activate");
  else                            header("Location: m/");  /* TODO */
  exit();
}

if ( !$site->user->is_password($_POST["password"]) )
{
  if(!isset($_REQUEST["mobile"])) header("Location: article.php?name=site:wrongpassworduser");
  else                            header("Location: m/");  /* TODO */
  exit();
}

$forever=false;

if ( isset($_REQUEST["personnal_computer"]) )
  $forever=true;

$site->connect_user($forever);

$page = $topdir;

/*
 * Le passage de la redirection se fait via la variable de session pour eviter
 * toute redirection non controlée.
 */
if ( $_SESSION['session_redirect']
    && !strpos($_SESSION['session_redirect'],'connect.php'))
{
  $page = $_SESSION['session_redirect'];
  unset($_SESSION['session_redirect']);

  if(strpos($page, "site:wrongpassworduser") || strpos($page, "site:activate"))
    $page = $topdir;
}

if(!isset($_REQUEST["mobile"])) header("Location: $page");
else                            header("Location: m/");

?>
