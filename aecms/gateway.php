<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
 * Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once("include/site.inc.php");

if( $_REQUEST['module']=="tinycal" )
{
  $cal = new tinycalendar($site->db);
  $cal->set_target($_REQUEST['target']);
  $cal->set_type($_REQUEST['type']);
  $cal->set_ext_topdir($_REQUEST['topdir']);
  echo $cal->html_render();
  exit();
}

if ( $_REQUEST['class'] == "calendar" )
  $cts = new calendar($site->db,$site->asso->id);
else
  $cts = new contents();

echo $cts->html_render();

?>
