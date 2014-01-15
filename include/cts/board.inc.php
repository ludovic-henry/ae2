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

/**
 * Permet d'afficher des contents sur deux colonnes
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class board extends stdcontents
{
  var $boardclass;

  /**
   * Construit un board
   * @param $title Titre
   * @param $class Classe CSS complémentaire pour l'ensemble
   */
  function board ( $title=null, $class=null )
  {
    $this->title = $title;
    $this->boardclass = $class;
  }

  /**
   * Ajoute in contents dans le board
   * @param $cts contents à ajouté
   * @param $title Affiche ou non le titre du contents
   * @param $class classe CSS complémentaire pour le conteneur du contents
   */
  function add ( &$cts, $title=false, $class=null )
  {
    if ( is_null($class) )
      $this->buffer .= "<div class=\"panel\">\n";
    else
      $this->buffer .= "<div class=\"panel $class\">\n";

    if ( $title )
      $this->buffer .= "<h2>".$cts->title."</h2>\n";

    $this->buffer .= "<div class=\"panelcts\">\n";
    $this->buffer .= $cts->html_render()."\n";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";
  }

  function clear()
  {
    $this->buffer .= "<div class=\"clearboth\"></div>\n";
  }

  function html_render ()
  {
    if ( !is_null($this->boardclass) )
      return "<div class=\"board ".$this->boardclass."\">".$this->buffer."<div class=\"clearboth\"></div></div>";
    else
      return "<div class=\"board\">".$this->buffer."<div class=\"clearboth\"></div></div>";
  }

}



?>
