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

class ville extends stdentity
{

  var $nom;
  var $id_pays;
  var $cpostal;
  var $lat;
  var $long;
  var $eloi;

  var $pgdb;

  function ville($db, $dbrw = null, $pgdb = null)
  {
    $this->stdentity ($db, $dbrw);
    $this->pgdb = $pgdb;
  }

  function load_by_id ($id)
  {
    $req = new requete($this->db, "SELECT * FROM `loc_ville`
				WHERE `id_ville` = '" .
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

  /* redéfinition du can_enumerate, spécifique aux recherches de lieux */
  function can_enumerate() { return true; }

  function load_by_pgid($id)
  {
    if (! $this->pgdb)
      return false;

    $req = new pgrequete($this->pgdb,
			 "SELECT
                                   AsText(TRANSFORM(the_geom, 4030)) AS coords
                                   , id_loc AS id_ville
                                   , name_loc AS nom_ville
                          FROM
                                   worldloc
                          WHERE
                                   id_loc = ".intval($id));

    $rs = $req->get_all_rows();
    $rs = $rs[0];

    $rs['coords'] = str_replace("POINT(", "", $rs['coords']);
    $rs['coords'] = str_replace(")", "", $rs['coords']);
    list($rs['long_ville'], $rs['lat_ville']) = explode(' ', $rs['coords']);

    $this->_load($rs);


  }


  function _load ( $row )
  {
    $this->id = $row['id_ville'];
    $this->nom = $row['nom_ville'];
    $this->id_pays = $row['id_pays'];
    $this->cpostal = $row['cpostal_ville'];
    $this->lat = $row['lat_ville'];
    $this->long = $row['long_ville'];
    $this->eloi = $row['eloi_ville'];
  }

  function create ( $id_pays, $nom, $cpostal, $lat, $long, $eloi )
  {
    $this->id_pays = $id_pays;
    $this->cpostal = $cpostal;
    $this->nom = $nom;
    $this->lat = $lat;
    $this->long = $long;
    $this->eloi = $eloi;

    $req = new insert ($this->dbrw,
            "loc_ville", array(
              "nom_ville"=>$this->nom,
              "id_pays"=>$this->id_pays,
              "cpostal_ville"=>$this->cpostal,
              "lat_ville"=>sprintf("%.12F",$this->lat),
              "long_ville"=>sprintf("%.12F",$this->long),
              "eloi_ville"=>sprintf("%.12F",$this->eloi)
            ));

		if ( $req )
		{
			$this->id = $req->get_id();
		  return true;
		}

		$this->id = null;
    return false;
  }

  function update ( $id_pays, $nom, $cpostal, $lat, $long, $eloi )
  {
    $this->id_pays = $id_pays;
    $this->cpostal = $cpostal;
    $this->nom = $nom;
    $this->lat = $lat;
    $this->long = $long;
    $this->eloi = $eloi;

    $req = new update ($this->dbrw,
            "loc_ville", array(
              "nom_ville"=>$this->nom,
              "id_pays"=>$this->id_pays,
              "cpostal_ville"=>$this->cpostal,
              "lat_ville"=>sprintf("%.12F",$this->lat),
              "long_ville"=>sprintf("%.12F",$this->long),
              "eloi_ville"=>sprintf("%.12F",$this->eloi)
            ),
            array("id_ville"=>$this->id) );
  }

  function delete ( )
  {
    new delete($this->dbrw,"loc_ville",array("id_ville"=>$this->id));
    $this->id = null;
  }


  /**
   * Version otpmimisée de fsearch
   *
   */
  function fsearch ( $pattern, $limit=5, $conds = null, $full=false )
  {
    if ( $limit < 10 )
      $limit = 10;

    if ( ereg("^([0-9][0-9AB]*) ?(.*)$",$pattern,$match) )
    {
      // Recherche par code postal
      $cp = mysql_escape_string($match[1]);
      $pattern = mysql_escape_joker_string($match[2]);

      if ( empty($pattern) )
      {
        $sql = "SELECT `id_ville`,`nom_ville`, `nom_pays`, `cpostal_ville` ".
          "FROM `loc_ville` INNER JOIN `loc_pays` USING (`id_pays`) ".
          "WHERE `cpostal_ville` LIKE '$cp%'";
      }
      else
      {
        $sql = "SELECT `id_ville`,`nom_ville`, `nom_pays`, `cpostal_ville` ".
          "FROM `loc_ville` INNER JOIN `loc_pays` USING (`id_pays`) ".
          "WHERE `cpostal_ville`='$cp' AND `nom_ville` LIKE '$pattern%'";
      }
    }
    else
    {
      $pattern = mysql_escape_joker_string($pattern);

      $sql = "SELECT `id_ville`,`nom_ville`, `nom_pays` ".
        "FROM `loc_ville` INNER JOIN `loc_pays` USING (`id_pays`) ".
        "WHERE `nom_ville` LIKE '$pattern%'";
    }

    if ( !is_null($conds) && count($conds) > 0 )
    {
      foreach ($conds as $key => $value)
      {
        $sql .= " AND ";
        if ( is_null($value) )
          $sql .= "(`" . $key . "` is NULL)";
        else
          $sql .= "(`" . $key . "`='" . mysql_escape_string($value) . "')";
      }
    }

    if ( isset($cp) && empty($pattern) )
      $sql .= " ORDER BY cpostal_ville, nom_ville";
    else
      $sql .= " ORDER BY 1";

    if ( !is_null($limit) && $limit > 0 )
      $sql .= " LIMIT ".$limit;

    $req = new requete($this->db,$sql);

    if ( !$req || $req->errno != 0 )
      return null;

    $values=array();

    while ( $row = $req->get_row() )
    {
      if ( isset($row["cpostal_ville"]) )
        $values[$row[0]] = $row["cpostal_ville"]." ".$row[1] . " (" . $row[2] . ")";
      else
        $values[$row[0]] = $row[1] . " (" . $row[2] . ")";
    }
    return $values;
  }


}



?>
