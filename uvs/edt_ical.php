<?php
/** @file
 *
 * @brief Export iCal des emplois du temps.
 *
 */

/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

exit;

$topdir = "../";

include($topdir. "include/site.inc.php");
require_once ($topdir . "include/entities/edt.inc.php");

$db = new mysqlae();
$edt = new edt($db);

$id_user = intval($_REQUEST['id']);

isset($_REQUEST['semestre']) ? $semestre = $_REQUEST['semestre'] : $semestre = (date("m") > 6 ? "A" : "P") . date("y");


$edt->load_by_etu_semestre($id_user, $semestre);

header("Content-Type: text/calendar; charset=utf-8");
header("Content-Disposition: filename=edt.ics");


echo "BEGIN:VCALENDAR\n";
echo "VERSION:2.0\n";
echo "CALSCALE:GREGORIAN\n";
echo "METHOD:PUBLISH\n";
echo "X-WR-CALNAME:Emploi du temps\n";
echo "X-WR-TIMEZONE:Europe/Paris\n";
echo "BEGIN:VTIMEZONE
TZID:Europe/Paris
X-LIC-LOCATION:Europe/Paris
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100d
TZNAME:CET
DTSTART:19701025T030000
END:STANDARD
END:VTIMEZONE";

$invday = array_flip($jour);

/* strtotime() ne parle qu'anglais ... */
$days = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
$shortdays = array("MO", "TU", "WE", "TH", "FR", "SA", "SU");


foreach ($edt->edt_arr as $seance)
{
  $numj = $invday[$seance['jour_seance']];

  /* Automne : premier [jour de la semaine] trouvé apres le premier septembre -> mi janvier */
  if ($semestre[0] == "A")
    {
      $thisyear = date("Y");
      $nextyear = date("Y", strtotime("next year"));

      $start = date("Ymd", strtotime("next " . $days[$numj], strtotime($thisyear . date("-09-01"))));
      $until = date("Ymd", strtotime("last " . $days[$numj], strtotime($nextyear . date("-01-16"))));
    }
  /* printemps, let's say mi février -> fin juin */
  else
    {
      $start = date("Ymd", strtotime("next " . $days[$numj], strtotime(date("Y-02-15"))));
      $until = date("Ymd", strtotime("last " . $days[$numj], strtotime(date("Y-07-01"))));
    }
  $start .= "T";
  $end = $start;

  $start .= str_replace("h", "", $seance['hr_deb_seance']) . "00";
  $end   .= str_replace("h", "", $seance['hr_fin_seance']) . "00";

  $until .= "T" .str_replace("h", "", $seance['hr_fin_seance']) . "00" ."Z";


  switch($seance['semaine_seance'])
    {
    case 'AB':
      $freq = 1;
      break;
    case 'B':
      /* semaine B - on commence la semaine d'apres */
      if ($start[7] == 1)
	$start[7] = 8;
      else
	{
	  $start[6] = 2;
	  $start[7] = 2;
	}
      break;
    default:
      $freq = 2;
    }
  /* on bourrine sur la sortie standard */
  echo "BEGIN:VEVENT
DTSTART;TZID=Europe/Paris:$start
DTEND;TZID=Europe/Paris:$end
RRULE:FREQ=WEEKLY;UNTIL=$until;INTERVAL=$freq;WKST=MO;BYDAY=".$shortdays[$numj]."
CLASS:PUBLIC
DESCRIPTION:
LOCATION:".$seance['salle_seance']."
SEQUENCE:1
STATUS:CONFIRMED
SUMMARY:".$seance['nom_uv'] . " " .$seance['type_seance']."
TRANSP:OPAQUE
END:VEVENT\n";
}
echo "END:VCALENDAR\n";

?>
