<?php

/* Copyright 2007
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
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
require_once($topdir . "include/graph.inc.php");

$values=explode(";",utf8_decode($_REQUEST["values"]));
if(empty($values))
  exit();

$val=array();
foreach($values as $value)
{
  $value=explode("|", $value, 2);
  if(count($value)==2)
    $val[$value[0]]=$value[1];
  else
    exit();
}

if ( $_REQUEST["action"] == "cam" )
{
  $graph=new camembert(600,400,array(),2,0,0,0,0,0,0,10,150);
  arsort($val);
}
elseif ( $_REQUEST["action"] == "bar" )
  $graph=new histogram2(600,400,array(),2,0,0,0,0,0,0,10,150);
else
  exit();

foreach($val as $key=>$value)
  $graph->data($value, $key);

$graph->png_render();
exit();

?>
