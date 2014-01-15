<?php
/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des Ãtudiants de
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

$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/entities/tag.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("moderateur_site"))
  $site->error_forbidden("accueil","group",8);

if ( $_REQUEST["action"] == "modere" )
{
  $tag = new tag($site->db,$site->dbrw);
  foreach ( $_REQUEST["id_tags"] as $id )
  {
    $tag->load_by_id($id);
    $tag->set_modere(true);
  }
}
elseif ( $_REQUEST["action"] == "delete" )
{
  $tag = new tag($site->db,$site->dbrw);
  foreach ( $_REQUEST["id_tags"] as $id )
  {
    $tag->load_by_id($id);
    $tag->delete();
  }
}

$site->start_page ("accueil", "Modération des tags");

$cts = new contents("Modération des tags");

$req = new requete($site->db,"SELECT * FROM tag WHERE modere_tag='0' ORDER BY nom_tag");


$tbl = new sqltable ("moderetag_list",
    "",
    $req,
    "moderetag.php",
    "id_tag",
    array ("nom_tag" => "Tag"),
    array (),
    array ("modere"=>"Accepter","delete" => "Refuser"),
    array ());

$cts->add ($tbl);
$site->add_contents ($cts);

$site->end_page ();

?>
