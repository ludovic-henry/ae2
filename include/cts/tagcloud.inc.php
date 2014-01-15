<?
/* Copyright 2007
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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
 * Affiche un nuage de tags
 *
 * @author Manuel Vonthron
 * @ingroup display_cts
 */
class tagcloud extends stdcontents
{
   /**
   * Classe générant des nuages de tags
   * @param $values ben le tableau de valeurs quoi : array($name => $qty)
   * @param $link_title titre associé au tag, utiliser '{name}' et '{qty}' comme pour les valeurs du tableau
   * @param $link_to lien associé au tag ('{name}' et '{qty}' utilisable)
   * @param $min_size taille en % du plus petit tag
   * @param $max_size taille en % du plus gros tag
   */

  function tagcloud(
    $values,
    $title = null,
    $link_title = false,
    $link_to = false,
    $min_size = 60,
    $max_size = 200,
    $ids=null)
  {
    $this->title = $title;

    $min_qty = min( array_values($values) );
    $range = max( array_values($values) ) - $min_qty ;
    if ( $range == 0 )
      $range = 1;
    $step_size = ($max_size - $min_size) / $range;
    $id=null;
    foreach($values as $name => $qty)
    {
      if($qty == 0)
        continue;
      $size = ceil( $min_size + ($qty - $min_qty) * $step_size );

      if ( !is_null($ids) )
        $id = $ids[$name];

      if ($link_to)
        $link = str_replace(array("{name}", "{qty}", "{id}"), array($name, $qty,$id), $link_to);
      else
        $link = "#";

      $this->buffer .= "<a href=\"$link\" style=\"font-size:".$size."%\"";

      if($link_title)
      {
        $title = str_replace(array("{name}", "{qty}"), array($name, $qty), $link_title);
        $this->buffer .= " title=\"$title\"";
      }

      $this->buffer .= ">$name</a>";
      $this->buffer .= " ";

    }
  }
}

?>
