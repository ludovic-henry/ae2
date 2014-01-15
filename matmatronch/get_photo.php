<?php

/** @file
 *
 * @brief Fichier recevant un email en GET et retournant une de nos photos mat'matronch.
 *
 * pour les besoins du bds essentiellement ...
 *
 */
/* Copyright 2006
 * - Laurent COLNAT <laurent POINT colnat CHEZ utbm POINT fr>
 * - Simon LOPEZ < simon POINT lopez CHEZ ayolo POINT org>
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

$topdir = "./../";

require_once($topdir . "include/external_client.inc.php");

if ($_REQUEST['email'])
{
	if ($_REQUEST['sso'])
	{
	  require_once($topdir . "include/sso.inc.php");
	  $valid_key = new sso_auth($_REQUEST['sso']);
	  if ($valid_key->valid==1)
	    $ext_client = new external_client(1);
	  else
	    $ext_client = new external_client();
	}
	else
	  $ext_client = new external_client();

	$user = $ext_client->load_user_by_email($_REQUEST['email'],$_REQUEST['ae_user']?1:0);

	$ext_client->show_user_photo($user,$_REQUEST['type']?$_REQUEST['type']:1);

}

?>
