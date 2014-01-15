<?php
/*
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

 /**
 * @file
 */

/*
class lieu extends stdentity
{

  var $id_ville;
  var $id_lieu_parent;
  var $nom;
  var $lat;
  var $long;
  var $eloi;

  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `loc_lieu`
				WHERE `id_lieu` = '" .
		       mysql_real_escape_string($id) . "'
				LIMIT 1");

    if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
  }

  function _load ( $row )
  {
    $this->id = $row['id_lieu'];
    $this->id_ville = $row['id_ville'];
    $this->id_lieu_parent = $row['id_lieu_parent'];
    $this->nom = $row['nom_lieu'];
    $this->lat = $row['lat_lieu'];
    $this->long = $row['long_lieu'];
    $this->eloi = $row['eloi_lieu'];
  }

  function create ( $id_ville, $id_lieu_parent, $nom, $lat, $long, $eloi )
  {

    if ( strpos($nom,",") !== false ) // La vigule est réservée pour décrire un lieu imbriqué de façon précise (ex: salle, batiment)
      return false;

    $this->id_ville = $id_ville;
    $this->id_lieu_parent = $id_lieu_parent;
    $this->nom = $nom;
    $this->lat = $lat;
    $this->long = $long;
    $this->eloi = $eloi;

    $req = new insert ($this->dbrw,
            "loc_lieu", array(
              "id_ville"=>$this->id_ville,
              "id_lieu_parent"=>$this->id_lieu_parent,
              "nom_lieu"=>$this->nom,
              "lat_lieu"=>sprintf("%.12F",$this->lat),
              "long_lieu"=>sprintf("%.12F",$this->long),
              "eloi_lieu"=>sprintf("%.12F",$this->eloi)
            ));

		if ( $req )
		{
			$this->id = $req->get_id();
		  return true;
		}

		$this->id = null;
    return false;
  }

  function update ( $id_ville, $id_lieu_parent, $nom, $lat, $long, $eloi )
  {
    $this->id_ville = $id_ville;
    $this->id_lieu_parent = $id_lieu_parent;
    $this->nom = $nom;
    $this->lat = $lat;
    $this->long = $long;
    $this->eloi = $eloi;

    $req = new update ($this->dbrw,
            "loc_lieu", array(
              "id_ville"=>$this->id_ville,
              "id_lieu_parent"=>$this->id_lieu_parent,
              "nom_lieu"=>$this->nom,
              "lat_lieu"=>sprintf("%.12F",$this->lat),
              "long_lieu"=>sprintf("%.12F",$this->long),
              "eloi_lieu"=>sprintf("%.12F",$this->eloi)
            ),
            array("id_lieu"=>$this->id) );
  }

  function delete ( )
  {
    new delete($this->dbrw,"loc_lieu",array("id_lieu"=>$this->id));
    $this->id = null;
  }

}*/

require_once($topdir."include/entities/geopoint.inc.php");

/**
 * Lieu geolocalisé.
 * Les lieux sont des geopoint particuliers, dans le sens, ou tout geopoint,
 * quelque soit son type peut être, en plus, un lieu.
 *
 * ATTENTION: dans le cas de jointure avec loc_lieu, il faut faire un jointure
 * "facultative" (LEFT JOIN) avec loc_lieu (id_lieu=id_lieu) et une jointure
 * "normale" (INNER JOIN) avec geopoint (id_lieu=id_geopoint). Si la connaissance
 * de id_lieu_parent n'est pas requise, seul la jointure avec geopoint est requise.
 *
 * @see geopoint
 */
class lieu extends geopoint
{
  var $id_lieu_parent;

  /**
   * Charge un lieu, ou un geopoint en tant que lieu, dans l'instance.
   * @param $id Id du lieu ou de geopoint
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `geopoint`
        LEFT JOIN `loc_lieu` ON ( loc_lieu.id_lieu = geopoint.id_geopoint )
				WHERE `id_lieu` = '".mysql_real_escape_string($id)."'
				LIMIT 1");

    if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
  }

  function _load ( $row )
  {
    $this->geopoint_load($row);
    $this->id_lieu_parent = $row['id_lieu_parent'];
  }

  /**
   * Creer un nouveau lieu
   * @param $id_ville Id de la ville dans le quel le lieu se trouve (null si aucun)
   * @param $id_lieu_parent Id du lieu parent (null si aucun)
   * @param $nom Nom du point
   * @param $lat Latitude
   * @param $long Longitude
   * @param $eloi Eloignement
   * @return true si crée, false sinon
   */
  function create ( $id_ville, $id_lieu_parent, $nom, $lat, $long, $eloi )
  {
    if ( strpos($nom,",") !== false )
      return false;

    if ( !$this->geopoint_create ( $nom, $lat, $long, $eloi, $id_ville ) )
      return false;

    $this->id_lieu_parent = $id_lieu_parent;
    $req = new insert ($this->dbrw,
            "loc_lieu", array(
              "id_lieu"=>$this->id,
              "id_lieu_parent"=>$this->id_lieu_parent
            ));

    return true;
  }

  /**
   * Met à jour les informations relatives au lieu
   * @param $id_ville Id de la ville dans le quel le lieu se trouve (null si aucun)
   * @param $id_lieu_parent Id du lieu parent (null si aucun)
   * @param $nom Nom du point
   * @param $lat Latitude
   * @param $long Longitude
   * @param $eloi Eloignement
   */
  function update ( $id_ville, $id_lieu_parent, $nom, $lat, $long, $eloi )
  {
    $this->id_lieu_parent = $id_lieu_parent;
    $this->geopoint_update ( $nom, $lat, $long, $eloi, $id_ville );
    $req = new update ($this->dbrw,
            "loc_lieu", array(
              "id_lieu_parent"=>$this->id_lieu_parent
            ),
            array("id_lieu"=>$this->id) );
  }

  /**
   * Supprime le lieu, s'il s'agit bien d'un lieu, sinon supprime simplement
   * les informations spécifiques au type lieu.
   */
  function delete ( )
  {
    new delete($this->dbrw,"loc_lieu",array("id_lieu"=>$this->id));

    if ( $this->type == "lieu" )
      $this->geopoint_delete();
  }

}

/* fonctions globales sur le positionnement */

/*
 * @brief Fonction de récupération de la description géographique
 * des contours des départements francais au format KML.
 *
 * @param pgdb, une ressource de connexion à une base postgres
 * @param numdept, le numéro du département
 *
 * @return une description KML, une chaine vide sinon
 */
function get_kml_dept($pgdb, $numdept)
{
  global $topdir;
  require_once($topdir . "include/pgsqlae.inc.php");

  $numdept = pg_escape_string($numdept);

  $req = new pgrequete($pgdb, "SELECT
                                        AsKml(the_geom) AS kmldept
                               FROM
                                        deptfr
                               WHERE
                                        code_dept = '".$numdept."'
                               LIMIT    1");
  $rows = $req->get_all_rows();

  return $rows[0]['kmldept'];
}

?>
