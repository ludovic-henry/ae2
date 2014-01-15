<?php
/* Copyright 2007
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
include($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/tag.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/files.inc.php");
require_once($topdir. "include/cts/tagcloud.inc.php");
require_once($topdir. "sas2/include/cat.inc.php");
require_once($topdir. "sas2/include/photo.inc.php");
require_once($topdir."include/cts/gallery.inc.php");

$site = new site ();

$tag = new tag ($site->db,$site->dbrw);

if ( isset($_REQUEST["id_tag"]) )
  $tag->load_by_id($_REQUEST["id_tag"]);

if ( $tag->is_valid() )
{
  $site->start_page("presentation",$tag->nom);
  $cts = new contents("<a href=\"tag.php\">Tags</a> / ".$tag->nom);

  $site->add_css("css/sas.css");
  // photo
  $cat = new catphoto($site->db);
  $cat->load_by_id(1);
  $photos = $cat->get_photos_search ( $site->user, "id_tag='".$tag->id."'", "LEFT JOIN sas_photos_tag USING(id_photo)", "sas_photos.*", "LIMIT 6");

  if ( $photos->lines > 0 )
  {
    $photo = new photo($site->db);

    $gal = new gallery("Photo(s)","photos","phlist");
    while ( $row = $photos->get_row() )
    {
      $photo->_load($row);
      $img = "sas2/images.php?/".$photo->id.".vignette.jpg";

      if ( $row['type_media_ph'] == 1 )
        $gal->add_item("<a href=\"sas2/?id_photo=".$photo->id."\"><img src=\"$img\" alt=\"Photo\">".
            "<img src=\"".$wwwtopdir."images/icons/32/multimedia.png\" alt=\"Video\" class=\"ovideo\" /></a>");
      else
        $gal->add_item("<a href=\"sas2/?id_photo=".$photo->id."\"><img src=\"$img\" alt=\"Photo\"></a>");
    }
    $cts->add($gal,true);

    $cts->add_paragraph("<a href=\"sas2/search.php?action=search&amp;tags=".$tag->nom."\">Toutes les photos</a>");
  }

  // fichier
  $req = new requete($site->db,
    "SELECT d_file.* FROM d_file_tag INNER JOIN d_file USING(id_file) WHERE id_tag='".$tag->id."' ORDER BY titre_file");
  if ( $req->lines > 0 )
  {
    $dfile = new dfile($site->db);
    $lst = new itemlist("Fichier(s)");
    while ( $row = $req->get_row() )
    {
      $dfile->_load($row);
      $lst->add($dfile->get_html_link());
    }
    $cts->add($lst,true);
  }

  // asso et club
  $req = new requete($site->db,
    "SELECT asso.* FROM asso_tag INNER JOIN asso USING(id_asso) WHERE id_tag='".$tag->id."' ORDER BY nom_asso");
  if ( $req->lines > 0 )
  {
    $asso = new asso($site->db);
    $lst = new itemlist("Association(s) et activité(s)");
    while ( $row = $req->get_row() )
    {
      $asso->_load($row);
      $lst->add($asso->get_html_link());
    }
    $cts->add($lst,true);
  }

  $site->add_contents($cts);
  $site->end_page ();
  exit();
}

$site->start_page("presentation","Tags");

$cts = new contents("Tags");

$req = new requete($site->db,"SELECT id_tag, nom_tag, nombre_tag FROM tag WHERE `modere_tag` = '1' ORDER BY nom_tag");

$values=array();
$ids=array();

while ( list($id,$name,$qty) = $req->get_row() )
{
  $values[$name] = $qty;
  $ids[$name]=$id;
}

$cts->add(new tagcloud($values, null, false, "tag.php?id_tag={id}", 60, 200, $ids));

$site->add_contents($cts);
$site->end_page ();

?>
