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
 * Lecteur de fichier MP3
 *
 * Permet d'afficher un petit lecteur de fichiers MP3.
 * Utilise dweplayer http://www.alsacreations.fr/mp3-dewplayer.html sous licence
 * Creative Commons http://creativecommons.org/licenses/by-nd/2.0/fr/.
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class mp3player extends stdcontents
{
	var $src;
	var $class;
	/**
	 * Contruit le lecteur MP3
	 * @param $title Titre du contenu
	 * @param $src URL relatif depuis $wwwtopdir."images/flash" vers le fichier mp3
	 */
	function mp3player ( $title, $src)
	{
		$this->title = $title;
		$this->src = $src;
	}

	function html_render ()
	{
	  global $wwwtopdir;

		return
"<object type=\"application/x-shockwave-flash\" data=\"".$wwwtopdir."images/flash/dewplayer.swf?showtime=1&amp;mp3=".rawurlencode($this->src)."\" width=\"200\" height=\"20\">".
"<param name=\"movie\" value=\"".$wwwtopdir."images/flash/dewplayer.swf?showtime=1&amp;mp3=".rawurlencode($this->src)."\" />"."<param name=\"wmode\" value=\"transparent\" />"."</object>";
	}

}



?>
