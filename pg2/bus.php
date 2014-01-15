<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des Etudiants de
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

$topdir="../";
require_once("include/site.inc.php");
require_once($topdir."include/entities/bus.inc.php");
require_once($topdir."include/entities/ville.inc.php");
require_once($topdir."include/cts/gmap.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");

$site = new pgsite();

$reseaubus = new reseaubus($site->db,$site->dbrw);
$lignebus = new lignebus($site->db,$site->dbrw);
$arretbus = new arretbus($site->db,$site->dbrw);
$ville = new ville($site->db);

if ( isset($_REQUEST["id_arretbus"]) )
  $arretbus->load_by_id($_REQUEST["id_arretbus"]);
elseif ( isset($_REQUEST["id_geopoint"]) )
  $arretbus->load_by_id($_REQUEST["id_geopoint"]);

if ( isset($_REQUEST["id_lignebus"]) )
{
  if ( $lignebus->load_by_id($_REQUEST["id_lignebus"]) )
    $reseaubus->load_by_id($lignebus->id_reseaubus);
}
elseif ( isset($_REQUEST["id_reseaubus"]) )
  $reseaubus->load_by_id($_REQUEST["id_reseaubus"]);

if ( $site->is_admin() && isset($_REQUEST["action"]) )
{
  if ( $_REQUEST["action"] == "createreseaubus" )
  {
    $reseaubusparent = new reseaubus($site->db);
    $reseaubusparent->load_by_id($_REQUEST["id_reseaubus_parent"]);
    $reseaubus->create ( $_REQUEST["nom"], $_REQUEST["siteweb"], $reseaubusparent->id );
  }
  elseif ( $_REQUEST["action"] == "createarretbus" )
  {
    $ville->load_by_id($_REQUEST["id_ville"]);
    $arretbus->create ( $ville->id,  $_REQUEST["nom"], $_REQUEST["lat"], $_REQUEST["long"], $_REQUEST["eloi"] );
  }
  elseif ( $_REQUEST["action"] == "createlignebus" && $reseaubus->is_valid() )
  {
    $problems=0;
    $arrets=array();

    $ErrorLigne="";

    $data = explode("\n",$_REQUEST["arrets"]);
    foreach ( $data as $e )
    {
      list($nom,$ville) = explode(";",$e,2);
      $rows = $arretbus->find_arret($nom,$ville);
      if ( count($rows) != 1 )
      {
        if ( count($rows)==0 )
          $ErrorLigne .= "Arret \"$e\" inconnu, ";
        else
          $ErrorLigne .= "Plusieurs arrets pour \"$e\" précisez ville, ";
        $problems++;
      }
      else
        $arrets[] = current($rows);
    }

    if ( $problems == 0 )
    {
      $lignebus->load_by_id($_REQUEST["id_lignebus_parent"]);
      $lignebus->create ( $_REQUEST["nom"], $reseaubus->id, $_REQUEST["couleur"], $lignebus->id );
      $i=0;
      foreach ( $arrets as $arret )
        $lignebus->add_arret($arret["id_geopoint"],$i++);
    }
  }
}

// Gènère le chemin affiché
if ( $reseaubus->is_valid() )
{
  $path = $reseaubus->get_html_link();

  $reseaubusparent = new reseaubus($site->db);
  $reseaubusparent->id_reseaubus_parent = $reseaubus->id_reseaubus_parent;

  while ( !is_null($reseaubusparent->id_reseaubus_parent)
    && $reseaubusparent->load_by_id($reseaubusparent->id_reseaubus_parent) )
    $path = $reseaubusparent->get_html_link()." / ".$path;

  $path = "<a href=\"bus.php\">Reseaux de bus</a> / ".$path;
}
else
  $path = "<a href=\"bus.php\">Reseaux de bus</a>";

// Affichage
if ( $arretbus->is_valid() )
{
  $path .= " / ".$arretbus->get_html_link();

  $site->start_page("pgbus","Arret ".$arretbus->nom." - Reseaux de bus");
  $site->add_alternate_geopoint($arretbus);
  $cts = new contents($path);



  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( $lignebus->is_valid() )
{
  $path .= " / ".$lignebus->get_html_link();
  $site->start_page("pgbus","Ligne ".$lignebus->nom." - Reseaux de bus");
  $cts = new contents($path);

  $gmap = new gmap("lignebus");
  $gmap->add_path ( $lignebus->nom, $lignebus->get_path(), $lignebus->couleur );
  $cts->add($gmap);


  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( $reseaubus->is_valid() )
{
  $site->start_page("pgbus","Reseaux de bus");
  $cts = new contents($path);

  $req = new requete($site->db,"SELECT pg_lignebus.*, pg_reseaubus.nom_reseaubus FROM ".
  "pg_lignebus ".
  "INNER JOIN pg_reseaubus ON (pg_reseaubus.id_reseaubus=pg_lignebus.id_reseaubus) ".
  "WHERE pg_reseaubus.id_reseaubus = '".mysql_real_escape_string($reseaubus->id)."' ".
  "OR pg_reseaubus.id_reseaubus_parent = '".mysql_real_escape_string($reseaubus->id)."' ".
  "ORDER BY pg_lignebus.nom_lignebus");

  $gmap = new gmap("reseaubus");
  while ( $req->get_row() )
  {
    $lignebus->_load();
    $gmap->add_path ( $lignebus->nom, $lignebus->get_path(), $lignebus->couleur );
  }
  $cts->add($gmap);

  $req->go_first();

  $tbl = new sqltable(
    "listlignes",
    "Lignes de bus", $req, "bus.php",
    "id_lignebus",
    array("nom_lignebus"=>"Nom de la ligne"),
    array("info"=>"Informations / Horraires"), array(), array( )
    );
  $cts->add($tbl,true);

  if ( $site->is_admin() )
  {
    $frm = new form("createlignebus","bus.php",true,"POST","Ajouter une ligne de bus");
    if ( $ErrorLigne )
      $frm->error($ErrorLigne);
    $frm->add_hidden("action","createlignebus");
    $frm->add_text_field("nom","Nom de la ligne");
    $frm->add_color_field("couleur","rgb","Couleur");
    $frm->add_entity_smartselect("id_reseaubus","Reseau de Bus",$reseaubus);
    $frm->add_entity_smartselect("id_lignebus_parent","Ligne de bus parent",$lignebus,true);
    $frm->add_text_area("arrets","Nom des arrets (1 par ligne)","Arret1; Ville si précisison nécessaire\nArret2\nArret3; Ville\nArret4\nArret5");
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("pgbus","Reseaux de bus");

$cts = new contents($path);

$req = new requete($site->db,"SELECT * FROM pg_reseaubus WHERE id_reseaubus_parent IS NULL ORDER BY nom_reseaubus");
$list = new itemlist("Reseaux de bus");
while ( $row = $req->get_row() )
{
  $reseaubus->_load($row);
  $list->add($reseaubus->get_html_link());
}
$cts->add($list,true);

if ( $site->is_admin() )
{
  $reseaubus->id=null;
  $frm = new form("createreseaubus","bus.php",false,"POST","Ajouter un reseau de bus");
  $frm->add_hidden("action","createreseaubus");
  $frm->add_text_field("nom","Nom du réseau");
  $frm->add_text_field("siteweb","Site web","http://");
  $frm->add_entity_smartselect("id_reseaubus_parent","Reseau parent",$reseaubus,true);
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);

  $frm = new form("createarretbus","bus.php",false,"POST","Ajouter un arret de bus");
  $frm->add_hidden("action","createarretbus");
  $frm->add_text_field("nom","Nom");
  $frm->add_entity_smartselect("id_ville","Ville",$ville);
  $frm->add_geo_field("lat","Latitude","lat");
  $frm->add_geo_field("long","Longitude","long");
  $frm->add_text_field("eloi","Eloignement");
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);

}

$site->add_contents($cts);
$site->end_page();

?>
