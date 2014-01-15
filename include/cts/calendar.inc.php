<?php

/** @file
 *
 * @brief Classe de gestion du calendrier.
 *
 */

/* Copyright 2004-2007
 * - Maxime Petazzoni <maxime POINT petazzoni CHEZ bulix POINT org>
 * - Alexandre Belloni <alexandre POINT belloni CHEZ utbm POINT fr>
 * - Thomas Petazzoni <thomas POINT petazzoni CHEZ enix POINT org>
 * - Julien Etelain < julien at pmad dot net >
 * - Manuel Vonthron <manuel DOT vonthron AT acadis DOT org>
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

require_once($topdir."include/entities/news.inc.php");

/**
 * Permet d'afficher un calendrier de nouvelles
 *
 * Classe issue en partie de la v1 du site (d'où les auteurs ;) )
 *
 * @author Maxime Petazzoni
 * @author Alexandre Belloni
 * @author Thomas Petazzoni
 * @author Julien Etelain
 * @ingroup display_cts
 */
class calendar extends stdcontents
{
  var $db, $date, $events;

  var $weekdays = array ("Lu", "Ma", "Me", "Je", "Ve", "Sa", "Di");

  var $months = array ("Janvier", "F&eacute;vrier", "Mars", "Avril",
  "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre",
  "Octobre", "Novembre", "D&eacute;cembre");

  var $id_asso;


  /** Constructeur de la classe
  */
  function calendar (&$db,$id_asso=null,$subclass='',$id_box='sbox_body_calendrier')
  {
    $this->db = $db;
    $this->subclass=$subclass;
    /* Si les paramètres temporels sont donnés, on les utilise */
    if ($_GET['caldate'] != "")
      $this->date = strtotime($_GET['caldate']);

    /* Sinon, on prend le timestamp courant */
    else
      $this->date = time();

    $this->events = "";
    $this->title = "Calendrier";
    $this->id_asso = $id_asso;
    $this->id_box=$id_box;

  }

  /** Affichage du calendrier
  */
  function html_render ()
  {
    global $topdir,$wwwtopdir,$timing;

    /* On extrait le jour, le mois, l'année et le nombre du jours du
    * mois courant a partir du timestamp */
    $day = date("j", $this->date);
    $month = date("n", $this->date);
    $year = date("Y", $this->date);
    $days = date("t", $this->date);

    //
    if ( $topdir == $wwwtopdir )
      $this->buffer = "<p class=\"ical\"><a href=\"".$wwwtopdir."article.php?name=ical\"><img src=\"".$wwwtopdir."images/icons/16/ical.png\" alt=\"iCalendar\" /></a></p>";
    else
      $this->buffer = "";
    if(!empty($this->subclass))
      $this->buffer .= "<div class=\"calendarhead ".$this->subclass."\">\n";
    else
      $this->buffer .= "<div class=\"calendarhead\">\n";
    $this->buffer .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\">\n";

    $prevmonth = $month - 1;
    $nextmonth = $month + 1;

    if ($prevmonth < 10)
      $prevmonth = "0" . $prevmonth;

    if ($nextmonth < 10)
      $nextmonth = "0" . $nextmonth;

    $prevdate = $year . "-" . $prevmonth . "-" . $day;
    $nextdate = $year . "-" . $nextmonth . "-" . $day;

    if ($month == 1)
      $prevdate = $year-1 . "-" . "12" . "-" . $day;

    if ($month == 12)
      $nextdate = $year+1 . "-" . "1"  . "-" . $day;

    $dmois = mktime(6, 0, 1, $month, 1, $year);
    $fmois = mktime(6, 0, 1, $month+1, 1, $year);
    $sql = "SELECT `nvl_dates`.date_debut_eve, ".
      "`nvl_dates`.date_fin_eve, ".
      "`nvl_nouvelles`.type_nvl, ".
      "`nvl_nouvelles`.titre_nvl ".
      "FROM `nvl_dates` " .
      "INNER JOIN `nvl_nouvelles` on `nvl_nouvelles`.`id_nouvelle`=`nvl_dates`.`id_nouvelle`" .
      "WHERE  modere_nvl='1' ".
      "AND `nvl_dates`.`date_debut_eve` <= '".date("Y-m-d H:i:s",$fmois)."' " .
      "AND `nvl_dates`.`date_fin_eve` >= '".date("Y-m-d H:i:s",$dmois)."'  ";

    if ( is_null($this->id_asso) )
      $sql .= "AND id_canal='".NEWS_CANAL_SITE."' ";
    else
      $sql .= "AND id_asso='".mysql_real_escape_string($this->id_asso)."' ";
  //date_debut_eve,date_fin_eve


    $events=array();
    $req = new requete($this->db,$sql);
    while ( $row = $req->get_row() )
    {
      $debut = floor((strtotime($row['date_debut_eve'])-$dmois)/(24*3600))+1;
      $fin = floor((strtotime($row['date_fin_eve'])-$dmois)/(24*3600))+1;
      for($i=$debut;$i<=$fin;$i++)
        $events[$i][] = $row;
    }
    unset($req);

    $subclass='';
    if(!empty($this->subclass))
      $subclass='&amp;subclass='.$this->subclass;

    $this->buffer .= "<tr>\n";
    $this->buffer .= "<td class=\"month\"><a href=\"?caldate=$prevdate\" onclick=\"return !openInContents('".$this->id_box."','".$wwwtopdir."gateway.php','class=calendar&amp;caldate=$prevdate&amp;topdir=$wwwtopdir&amp;id_box=".$this->id_box."$subclass');\">&laquo;</a></td>\n";
    $this->buffer .= "<td class=\"month\" colspan=\"5\">" . $this->months[$month-1] . " " . $year . "</td>\n";
    $this->buffer .= "<td class=\"month\"><a href=\"?caldate=$nextdate\" onclick=\"return !openInContents('".$this->id_box."','".$wwwtopdir."gateway.php','class=calendar&amp;caldate=$nextdate&amp;topdir=$wwwtopdir&amp;id_box=".$this->id_box."$subclass');\">&raquo;</a></td>\n";
    $this->buffer .= "</tr>\n";

    /* Affichage des jours de la semaine */
    $this->buffer .= "<tr>";
    foreach ($this->weekdays as $day)
      $this->buffer .= "<td class=\"weekday\">$day</td>";
    $this->buffer .= "</tr>\n";

    $this->buffer .= "</table>\n";
    $this->buffer .= "</div>\n";

    /* Partie principale du calendrier : les jours du mois */
    if(!empty($this->subclass))
      $this->buffer .= "<div class=\"calendar ".$this->subclass."\">\n";
    else
      $this->buffer .= "<div class=\"calendar\">\n";
    $this->buffer .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\">\n";

    /* On cherche le premier jour du mois dans la semaine */
    $first = date("w", mktime(0, 0, 0, $month, 1, $year)) - 1;

    if($first < 0)
    $first += 7;

    $current_day = 0;

    /* La première semaine */
    $this->buffer .= "<tr>";
    for ($i=0 ; $i<$first ; $i++)
      $this->buffer .= "<td class=\"day\"></td>";
    for ($i=0 ; $i< 7-$first ; $i++)
      $this->day ($year, $month, ++$current_day, $events[$current_day]);
    $this->buffer .= "</tr>\n";

    /* Les autres jours du mois */
    while (($days - $current_day > 0) &&
    ($days - $current_day > 6))
    {
      $this->buffer .= "<tr>";
      for ($i=0 ; $i < 7 ; $i++)
        $this->day ($year, $month, ++$current_day, $events[$current_day]);
      $this->buffer .= "</tr>\n";
    }

    /* last week */
    $this->buffer .= "<tr>";

    for ($i=$current_day ; $i < $days ; $i++)
      $this->day ($year, $month, $i+1, $events[$i+1]);

    for ($j=0 ; $j < 7-$i ; $j++)
      $this->buffer .= "<td class=\"day\"></td>";

    $this->buffer .= "</tr>\n";
    $this->buffer .= "</table>\n";

    /* On affiche les evenements */
    $this->buffer .= $this->events;
    $this->buffer .= "</div>\n";

    return $this->buffer;
  }
  /** Affichage d'un jour.
  *
  * @param year L'année
  * @param month Le mois
  * @param day Le jour
  */
  function day ($year, $month, $day, $events=array())
  {
    global $topdir,$wwwtopdir,$timing;

    $style = "day";

    $date = $this->sql_date(mktime(0, 0, 0, $month, $day, $year));

    if ( count($events) > 0 )
    {
      $idx=3;

      foreach( $events as $ev)
      {
        if ( $ev["type_nvl"] == 1 )
          $idx = 1;
        elseif ( $ev["type_nvl"] == 2 && $idx == 3 )
          $idx = 2;
      }

      $style .= " event".$idx;

      if ( $idx != 3 )
      {
        $this->events .= "<dl class=\"event\" id=\"de".$day."\">";
        foreach( $events as $ev)
        {
          $this->event_add ($ev,$date);
          $js = " onmouseover=\"sot('de".$day."');\"";
          $js .= " onmouseout=\"ho('de".$day."');\"";
        }
        $this->events .= "</dl>\n";
      }
    }

    /* Si le jour demandé est aujourd'hui, on active la case */
    if ($date == $this->sql_date (time()))
    $style .= " active";

    /* On affiche la case */
    if ( count($events) > 0 )
      $this->buffer .= "<td class=\"".$style."\"$js>".
       "<a href=\"" . $wwwtopdir . "events.php?day=" . $date . "\">" . $day . "</a></td>";
    else
      $this->buffer .= "<td class=\"".$style."\"$js>" . $day . "</td>";
  }


  function event_add ($ev,$date)
  {
    $start = split(" ", $ev['date_debut_eve']);
    $dstart = $start[0];
    $start = split(":", $start[1]);
    $end = split(" ", $ev['date_fin_eve']);
    $dend = $end[0];

    $end = split(":", $end[1]);

    if ( $ev["type_nvl"] == 1 )
      $idx = 1;
    elseif ( $ev["type_nvl"] == 2 )
      $idx = 2;
    else
      return;

    $this->events .= "<dt class=\"e$idx\">" . htmlentities($ev['titre_nvl'], ENT_QUOTES, "UTF-8") . "</dt>";
    $this->events .= "<dd class=\"e$idx\">";

    if ( $dstart == $date && $dend == $date )
      $this->events .= "De ".$start[0] . ":" . $start[1] . " à " . $end[0] . ":" . $end[1];
    else if ( $dstart == $date )
      $this->events .= "A partir de ".$start[0] . ":" . $start[1];
    else if ( $dend == $date )
      $this->events .= "Jusqu'à ".$end[0] . ":" . $end[1];

    $this->events .= "</dd>";
  }

  /** Créé une date de type SQL à partir d'un timestamp
  *
  * @param time Un timestamp
  *
  * @return La date au format SQL YYYY-MM-DD
  */
  function sql_date ($time)
  {
    return strftime("%Y-%m-%d", $time);
  }
}

/**
 * Permet d'afficher un calendrier pour selectionner une date
 *
 * En général, on utilise les fonctions de form pour "utiliser" cette classe.
 *
 * @author Manuel Vonthron
 * @ingroup display_cts
 * @see form::add_date_field
 * @see form::add_datetime_field
 */
class tinycalendar extends calendar
{
  var $target;
  var $type;
  var $ext_topdir;

  function set_target($target)
  {
    $this->target = $target;
  }

  function set_type($type = "date")
  {
    $this->type = $type;
  }

  function set_ext_topdir($topdir)
  {
    $this->ext_topdir = $topdir;
  }


  function html_render()
  {

    global $topdir,$wwwtopdir;

    /* On extrait le jour, le mois, l'année et le nombre du jours du
    * mois courant a partir du timestamp */
    $day = date("j", $this->date);
    $month = date("n", $this->date);
    $year = date("Y", $this->date);
    $days = date("t", $this->date);

/*    $this->buffer = "<div class=\"close\" onclick=\"closecal();\" >X</div>"; // pour l'instant on laisse tomber */
    $this->buffer .= "<div class=\"calendarhead tinycalhead\">\n";
    $this->buffer .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\">\n";
    $this->buffer .= "<div class=\"closecal\" onclick=\"closecal('$this->target');\">&nbsp;</div>";

    $prevmonth = $month - 1;
    $nextmonth = $month + 1;

    if ($prevmonth < 10)
      $prevmonth = "0" . $prevmonth;

    if ($nextmonth < 10)
      $nextmonth = "0" . $nextmonth;

    $prevdate = $year . "-" . $prevmonth . "-" . $day;
    $nextdate = $year . "-" . $nextmonth . "-" . $day;

    if ($month == 1)
      $prevdate = $year-1 . "-" . "12" . "-" . $day;

    if ($month == 12)
      $nextdate = $year+1 . "-" . "1"  . "-" . $day;

    $prevyear = $year-1 . "-" . $month . "-" . $day;
    $nextyear = $year+1 . "-" . $month  . "-" . $day;

    $this->buffer .= "<tr>\n";
    $this->buffer .= "<td class=\"month\">";
    $this->buffer .= "<a href=\"?caldate=$prevdate\" onclick=\"return !openInContents('".$this->target."_calendar','".$this->ext_topdir."gateway.php','module=tinycal&amp;target=".$this->target."&amp;type=".$this->type."&amp;topdir=".$this->ext_topdir."&amp;caldate=$prevyear');\">&laquo;</a><br />";
    $this->buffer .= "<a href=\"?caldate=$prevdate\" onclick=\"return !openInContents('".$this->target."_calendar','".$this->ext_topdir."gateway.php','module=tinycal&amp;target=".$this->target."&amp;type=".$this->type."&amp;topdir=".$this->ext_topdir."&amp;caldate=$prevdate');\">&laquo;</a>";
    $this->buffer .= "</td>\n";
    $this->buffer .= "<td class=\"month\" colspan=\"5\">" . $year . "<br />" . $this->months[$month-1] . "</td>\n";
    $this->buffer .= "<td class=\"month\">";
    $this->buffer .= "<a href=\"?caldate=$nextdate\" onclick=\"return !openInContents('".$this->target."_calendar','".$this->ext_topdir."gateway.php','module=tinycal&amp;target=".$this->target."&amp;type=".$this->type."&amp;topdir=".$this->ext_topdir."&amp;caldate=$nextyear');\">&raquo;</a><br />";
    $this->buffer .= "<a href=\"?caldate=$nextdate\" onclick=\"return !openInContents('".$this->target."_calendar','".$this->ext_topdir."gateway.php','module=tinycal&amp;target=".$this->target."&amp;type=".$this->type."&amp;topdir=".$this->ext_topdir."&amp;caldate=$nextdate');\">&raquo;</a>";

    $this->buffer .= "</td>\n";
    $this->buffer .= "</tr>\n";

    /* Affichage des jours de la semaine */
    $this->buffer .= "<tr>";
    foreach ($this->weekdays as $day)
      $this->buffer .= "<td class=\"weekday\">$day</td>";
    $this->buffer .= "</tr>\n";

    $this->buffer .= "</table>\n";
    $this->buffer .= "</div>\n";

    /* Partie principale du calendrier : les jours du mois */
    $this->buffer .= "<div class=\"calendar tinycal\">\n";
    $this->buffer .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\">\n";

    /* On cherche le premier jour du mois dans la semaine */
    $first = date("w", mktime(0, 0, 0, $month, 1, $year)) - 1;

    if($first < 0)
    $first += 7;

    $current_day = 0;

    /* La première semaine */
    $this->buffer .= "<tr>";
    for ($i=0 ; $i<$first ; $i++)
      $this->buffer .= "<td class=\"day\"></td>";
    for ($i=0 ; $i< 7-$first ; $i++)
      $this->day ($year, $month, ++$current_day);
    $this->buffer .= "</tr>\n";

    /* Les autres jours du mois */
    while (($days - $current_day > 0) &&
    ($days - $current_day > 6))
    {
      $this->buffer .= "<tr>";
      for ($i=0 ; $i < 7 ; $i++)
        $this->day ($year, $month, ++$current_day);
      $this->buffer .= "</tr>\n";
    }

    /* last week */
    $this->buffer .= "<tr>";

    for ($i=$current_day ; $i < $days ; $i++)
      $this->day ($year, $month, ++$current_day);

    for ($j=0 ; $j < 7-$i ; $j++)
      $this->buffer .= "<td class=\"day\"></td>";

    $this->buffer .= "</tr>\n";
    $this->buffer .= "</table>\n";
    $this->buffer .= "<div class=\"tinycal_endbox\"></div>\n";
    $this->buffer .= "</div>\n";

    return $this->buffer;
  }

function day ($year, $month, $day)
  {
    global $topdir,$wwwtopdir;


    $style = "tinycal_day";

    /* on construit une date mysql */
    $date = $this->sql_date(mktime(0, 0, 0, $month, $day, $year));

    /* le jour suivant */
    $date2 = $this->sql_date(mktime(0, 0, 0, $month, $day + 1, $year));


    /* Si le jour demandé est aujourd'hui, on active la case */
    if ($date == $this->sql_date (time()))
    $style .= " active";

    if($this->type == "datetime")
      $extra = "' 20:00'";
    else
      $extra = " ''";


    $js = "onclick=\"return_val('$this->target', $day + '/' + $month + '/' + $year + $extra);\"";

    $this->buffer .= "<td class=\"$style\" $js >" . $day . "</td>";
  }

}

?>
