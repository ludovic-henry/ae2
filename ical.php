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

$GLOBALS['nosession'] = true;

$topdir = "./";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/ical.inc.php");
require_once($topdir. "include/entities/news.inc.php");

$site = new site();

$cal = new icalendar();

$events = new requete ($site->db,
  "SELECT `nvl_dates`.*,`nvl_nouvelles`.*, geopoint.* FROM `nvl_dates` " .
  "INNER JOIN `nvl_nouvelles` on `nvl_nouvelles`.`id_nouvelle`=`nvl_dates`.`id_nouvelle` " .
  "LEFT JOIN geopoint ON (geopoint.id_geopoint=nvl_nouvelles.id_lieu) " .
  "WHERE `date_fin_eve` >= '" . date("Y-m-d",time()-(60*60*24*30)) ." 00:00:00' ".
  "AND `nvl_nouvelles`.`modere_nvl` > 0 ".
  "AND id_canal='".NEWS_CANAL_SITE."'");

while ($row = $events->get_row ())
{
  $st = strtotime($row['date_debut_eve']);
  $end = strtotime($row['date_fin_eve']);

  if ( $row["type_nvl"] == 3 || ($end-$st) > (60*60*24) )
    $dateonly = true;
  else
    $dateonly = false;

  $cal->add_event ( "http://ae.utbm.fr/news.php?id_nouvelle=".$row["id_nouvelle"]."&date=".$row["date_debut_eve"], $row['titre_nvl'], $row['resume_nvl'], $st, $end, $dateonly, "http://ae.utbm.fr/news.php?id_nouvelle=".$row["id_nouvelle"], $row["nom_geopoint"], $row["lat_geopoint"], $row["long_geopoint"] );
}


$cal->render();

?>
