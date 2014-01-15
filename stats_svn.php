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

$topdir="./";

include($topdir."include/graph.inc.php");

$cam=new camembert(600,380,array(),2,0,0,0,0,0,0,10,150);

$svn=exec("/usr/share/php5/exec/svn_stats.sh");

$svn=explode("|",$svn,-1);

$stats=array();
for($i=0;$i<count($svn);$i++)
{
  if(!empty($svn[$i]))
  {
    $tmp=explode(" ",$svn[$i]);
    $j=$tmp[0];
    $stats[$j]=$tmp[1];
  }
}
arsort($stats);

foreach($stats as $author => $commits)
  $cam->data($commits,$author);

$cam->png_render();

exit();

?>
