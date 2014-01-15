<?php
/** @file
 *
 * @brief Connexion et obtention d'informations sur des applications
 * Web2.0 distantes (flickr, facebook, ...)
 *
 */

/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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


require_once($topdir . "include/extdb/xml.inc.php");


$flickr_api_key = "FLICKR_API_KEY";

class flickr_info
{
  var $user;
  var $flickr_id;


  function flickr_info(&$user, $user_id)
  {
    global $flickr_api_key;
    $this->user = $user;

    $xmlcts = file_get_contents("http://api.flickr.com/services/rest/".
				"?method=flickr.people.findByUsername".
				"&api_key=".$flickr_api_key.
				"&username=" . $user_id);

    $xml = new u007xml($xmlcts);

    $this->flickr_id = $xml->arrOutput[0]['childrens'][0]['attributes']['ID'];

  }

  function get_cts_latest_photos($nb = 5)
  {
    if (!$this->user)
      return false;
    if (!$this->flickr_id)
      return false;

    global $flickr_api_key;

    $photoscts = file_get_contents("http://api.flickr.com/services/rest/".
				   "?method=flickr.people.getPublicPhotos".
				   "&api_key=".$flickr_api_key.
				   "&user_id=" . $this->flickr_id.
				   "&per_page=".$nb);

    $xml = new u007xml($photoscts);




    $cts = new contents("Les dernières photographies flickr de " .
			$this->user->prenom . " " .
			$this->user->nom);

    if (count($xml->arrOutput[0]['childrens'][0]['childrens']) > 0)
      {
	foreach ($xml->arrOutput[0]['childrens'][0]['childrens'] as &$photo)
	  {
	    $imgurl = "<img alt=\"".$photo['attributes']['TITLE']."\" ".
	      " src=\"http://farm".$photo['attributes']['FARM'].
	      ".static.flickr.com/".
	      $photo['attributes']['SERVER']."/".$photo['attributes']['ID'].
	      "_".$photo['attributes']['SECRET']."_m.jpg\" />";
	    $cts->puts($imgurl);
	  }
      }
	return $cts;
  }
}





?>
