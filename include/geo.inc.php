<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des 0tudiants de
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
 * Fonctions de calcul geographique
 * Toute latitude positive => Nord, negative => Sud
 * Toute longiture positive => Est, negative => Ouest
 * @see geopoint
 */

/**
 * Converti une latitude/longitude exprimé en radians en degrés
 * @param $rad La valeur en radians
 * @return un texte representatnt la valeur en degrés (NN°NN'NN")
 * @ingroup display_cts_formsupport
 */
function geo_radians_to_degrees ( $rad )
{
  $degrees = $rad*360/2/M_PI;
  $deg = floor($degrees);
  $minutes = ($degrees-$deg)*60;
  $min = floor($minutes);
  $sec = round(($minutes-$min)*60,2);
  return $deg."°".$min."'".$sec."\"";
}

/**
 * Converti une latitude/longitude exprimé en degrés en radians
 * @param $deg Texte representant la valeur en degrés (NN°NN'NN" ou NN,NNNN)
 * @return la valeur en radians
 * @ingroup display_cts_formsupport
 */
function geo_degrees_to_radians ( $deg )
{
  if ( ereg("^([0-9]+)°([0-9]+)'([0-9,\.]+)\"(E|N|S|O|W)$",$deg,$regs) )
  {
    $res = ((((str_replace(",",".",$regs[3])/60)+$regs[2])/60)+$regs[1])*2*M_PI/360;

    if ( $regs[4] == "O" || $regs[4] == "S" || $regs[4] == "W" )
      return -1*$res;

    return $res;
  }
  elseif ( ereg("^([0-9]+)°([0-9]+)'([0-9,\.]+)\"$",$deg,$regs) )
    return ((((str_replace(",",".",$regs[3])/60)+$regs[2])/60)+$regs[1])*2*M_PI/360;
  elseif ( ereg("^([0-9]+)([,\.])([0-9]+)([ENSOW]?)$",$deg,$regs) )
  {
    $res = floatval($regs[1].".".$regs[3]);

    if ( $regs[4] == "O" || $regs[4] == "S" || $regs[4] == "W" )
      return -1*$res;

    return $res;
  }
  return NULL;
}


?>
