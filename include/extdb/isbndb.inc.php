<?php

/**
 * @file Base de donnés externe : ISBNDB.COM.
 * Base de donnés de livres.
 */

/* Copyright 2006
 * - Julien Etelain <julien CHEZ pmad POINT net>
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

require_once("xml.inc.php");

function isbn_get_infos( $isbn )
{

	$cts = file_get_contents("http://isbndb.com/api/books.xml?access_key=28SNOW6C&index1=isbn&value1=$isbn");

	if ( $cts )
	{
		$xml = new u007xml($cts);
		if ( $xml->arrOutput[0]["childrens"][0]["attributes"]["TOTAL_RESULTS"] != 1 )
			return -2;

		$res["isbn"]=$xml->arrOutput[0]["childrens"][0]["childrens"][0]["attributes"]["ISBN"];
		$res["title"]=$xml->arrOutput[0]["childrens"][0]["childrens"][0]["childrens"][0]["nodevalue"];
		$res["longtitle"]=$xml->arrOutput[0]["childrens"][0]["childrens"][0]["childrens"][1]["nodevalue"];
		$res["author"]=$xml->arrOutput[0]["childrens"][0]["childrens"][0]["childrens"][2]["nodevalue"];
		$res["editor"]=$xml->arrOutput[0]["childrens"][0]["childrens"][0]["childrens"][3]["nodevalue"];
		return $res;
	}
	return -3;
}

function isbn_get_infos_from_ean13 ( $cbar )
{
	$cap=NULL;
	if ( ereg("978([0-9]{9})([0-9])",$cbar,$cap) )
	{
		$poids = array(10,9,8,7,6,5,4,3,2);
		for($i=0;$i<9;$i++)
			$t += $cap[1]{$i} * $poids[$i];
		$l = ($t%11);

		if ( $l == 1 )
			$l = "X";
		elseif ( $l !=0 )
			$l = 11-$l;
		return isbn_get_infos($cap[1].$l);
	}
	return -1;
}


?>
