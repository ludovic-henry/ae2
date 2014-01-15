<?php
/* Copyright 2008
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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
 * Permet d'afficher d'afficher une barre de progression
 * Idéale pour voir l'avancement d'un projet
 *
 * @author Simon Lopez
 * @ingroup display_cts
 */
class progressbar extends stdcontents
{
  var $class;
  var $percent;

  /**
   * Construit une barre de progression
   * @param $percent Avancement en pourcentage
   * @param $title Titre
   * @param $class Classe CSS complémentaire pour l'ensemble
   */
  function progressbar ( $percent, $title=null, $class=null )
  {
    $this->title   = $title;
    $this->class   = $class;
    if($percent>100)
      $percent=100;
    $this->percent = $percent;
  }

  function html_render ()
  {
    if ( !is_null($this->class) )
      $this->buffer = "<div class=\"progressbar ".$this->class."\">\n";
    else
      $this->buffer = "<div class=\"progressbar\">\n";
    $this->buffer.= "<div class=\"progressbar_prog\" style=\"width:".str_replace(',','.',$this->percent)."%;\">\n";
    $this->buffer.= "</div><div class=\"progressbar_value\">".$this->percent."%</div>\n";
    $this->buffer.= "</div>\n";
    return $this->buffer;
  }

}



?>
