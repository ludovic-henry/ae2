<?php
/* Copyright 2006
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

/**
 * @file
 */

/**
 * Affiche un historique sous forme d'une frise chronologique
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class history extends stdcontents
{

  var $_class;

  var $history;

  var $begin;
  var $end;

  function history($title=false,$class=false)
  {

    $this->title = $title;
    $this->_class = $class;

    $this->begin = null;
    $this->end = null;
    $this->history = array();
  }

  /**
   * Ajoute un élèment à l'historique
   * @param $timestamp Timestamp unix de la date de l'élèment
   * @param $element Element (stdcontents ou texte)
   */
  function add_element ( $timestamp, $data, $info )
  {
    // enlève heure, minutes et secondes
    $timestamp =  mktime(0, 0, 0, date("m",$timestamp), date("d",$timestamp), date("Y",$timestamp));

    if ( is_null($this->begin) )
    {
      $this->begin = $timestamp;
      $this->end = $timestamp;
    }
    else if ( $timestamp < $this->begin )
      $this->begin = $timestamp;
    else if ( $timestamp > $this->end )
      $this->end = $timestamp;

    $element = array($data,$info);

    if ( !isset($this->history[$timestamp]) )
      $this->history[$timestamp] = array($element);
    else
      $this->history[$timestamp][] = $element;

  }

  function html_render ()
  {
    ksort($this->history);

    $this->buffer .= "<div class=\"history".($this->_class?" ".$this->_class:"")."\">\n";
    $n = 0;
    foreach ( $this->history as $date => $elements )
    {

      $this->buffer .= "<div class=\"date side$n\">\n";

      $this->buffer .= "<div class=\"timebar\"></div>\n";

      $this->buffer .= "<div class=\"dcts\">\n";
      $this->buffer .= "<h2>".date("d/m/Y",$date)."</h2>\n";
      $this->buffer .= "<div class=\"elements\">\n";

      foreach ( $elements as $element )
      {
        $this->buffer .= "<div class=\"element\">\n";

        $this->buffer .= "<div class=\"data\">\n";
        if ( is_object($element[0]) )
          $this->buffer .= $element[0]->html_render()."\n";
        else
          $this->buffer .= $element[0]."\n";
        $this->buffer .= "</div>\n";

        if ( $element[1] )
        {
          $this->buffer .= "<div class=\"info\">\n";
          $this->buffer .= $element[1]."\n";
          $this->buffer .= "</div>\n";
        }

        $this->buffer .= "</div>\n";
      }
      $this->buffer .= "<div class=\"clearboth\"></div></div>\n";
      $this->buffer .= "</div>\n";
      $this->buffer .= "<div class=\"clearboth\"></div></div>\n";
      $n = ($n+1)%2;
    }

    $this->buffer .= "<div class=\"clearlast\"></div></div>\n";

    return $this->buffer;
  }
}


?>
