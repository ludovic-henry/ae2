<?php
/* Copyright 2004-2006
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 *
 * Ce fichier fait partie du site de l'Association des Ã©tudiants de
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

$topdir="../";
require_once($topdir."include/site.inc.php");

class sso_auth
{
  var $valid=0;
  function sso_auth($key="")
  {
    $site = new site();
    $valid = new requete($site->db,"SELECT `key`
                       FROM `sso_api_keys`
		       WHERE `key` = '".mysql_real_escape_string($key)."'");
    if ( $valid->lines == 1)
      $this->valid=1;
  }
}
