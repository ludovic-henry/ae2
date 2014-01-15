<?php

/* Copyright 2007
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
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

$log_file = "/var/www/ae/log.txt";
function log_add ($table,$values)
{
  global $log_file;
  if(!empty($table) && !empty($values))
  {
    if($handle = fopen($log_file,'a'))
    {
      $row=false;
      $_values=array();
      $log = "INSERT INTO `".$table."` (";
      foreach($values as $key=>$value)
      {
        if (!$row)
        {
          $log .="`id_utilisateur_fillot`";
          $row=true;
        }
        else
          $log .=", `id_utilisateur_fillot`";
        $_values[]=$value;
      }
      $row=false;
      $log.=") VALUES (";
      foreach($_values as $value)
      {
        if(!$row)
        {
          $log .="'".$value."'";
          $row=true;
        }
        else
          $log .=", '".$value."'";
      }
      $log.=");\n";
      if(fwrite($handle, $log) === FALSE)
        return false;
      fclose($handle);
    }
    return true;
  }
  else
    return false;
}

?>
