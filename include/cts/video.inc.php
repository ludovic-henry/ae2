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
 * @file
 */

/**
 * Lecteur de fichiers FLV (flash 6)
 *
 * Permet d'afficher un lecteur de fichiers FLV
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class flvideo extends stdcontents
{

	var $src;
	var $class;

	/**
	 * Contruit le lecteur FLV
	 * @param $title Titre du contenu
	 * @param $src URL relatif depuis $wwwtopdir vers le fichier flv
	 */
	function flvideo ( $title, $src)
	{
		$this->title = $title;
		$this->src = $src;
	}

	function html_render ()
	{
	  global $wwwtopdir;

		return
"<object type=\"application/x-shockwave-flash\" data=\"".$wwwtopdir."images/flash/flvplayer.swf\" width=\"400\" height=\"300\">".
"<param name=\"movie\" value=\"".$wwwtopdir."images/flash/flvplayer.swf\" />"."<param name=\"FlashVars\" value=\"flv=".$wwwtopdir."sas2/".$this->src."\" />"."<param name=\"wmode\" value=\"transparent\" />"."</object>";
	}

}



?>
