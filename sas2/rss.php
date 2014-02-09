<?php
/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
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
$topdir="../";
require_once("include/sas.inc.php");

$site = new sas();

$cat = new catphoto($site->db);
$photo = new photo($site->db);

$cat->load_by_id($_REQUEST["id_catph"]);

header("Content-Type: text/xml; charset=utf-8");

if ( !$cat->is_valid() )
  exit();

if ( !$cat->is_right($site->user,DROIT_LECTURE) )
  exit();

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "	<channel>\n";


if ( is_null($cat->id_photo) )
  echo "			<url>http://ae.utbm.fr/images/misc/sas-default.png</url>\n";
else

echo "			<title>".htmlspecialchars($cat->nom)."</title>\n";



$sqlph = $cat->get_photos ( $cat->id, $site->user, $site->user->get_groups_csv(), "sas_photos.*");

while ( $row = $sqlph->get_row() )
{
  $photo->_load($row);

  $img_vignette = "http://ae.utbm.fr/sas2/images.php?/".$photo->id.".vignette.jpg";
  $img = "http://ae.utbm.fr/sas2/images.php?/".$photo->id.".jpg";
  $title = $photo->id;
  $link = "http://ae.utbm.fr/sas2/?id_photo=".$photo->id;

  $description="<p><a href=\"$link\" title=\"$title\"><img src=\"$img_vignette\" alt=\"$title\" /></a></p> ";

  echo "		<item>\n";
  echo "			<pubDate>".gmdate("D, j M Y G:i:s T",$photo->date_ajout)."</pubDate>\n";

  if ( !is_null($photo->date_prise_vue) )
    echo "			<dc:date.Taken>".gmstrftime("%Y-%m-%dT%H:%M:%S%P+00:00",$photo->date_prise_vue)."</dc:date.Taken>\n";

}

echo "	</channel>\n";
echo "</rss>\n";

?>