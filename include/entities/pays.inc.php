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

class pays extends stdentity
{

  var $nom;
  var $indtel;

  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `loc_pays`
				WHERE `id_pays` = '" .
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
    $this->id = $row['id_pays'];
    $this->nom = $row['nom_pays'];
    $this->indtel = $row['indtel_pays'];
  }

  function create ( $nom, $indtel )
  {
    $this->nom = $nom;
    $this->indtel = $indtel;

    $req = new insert ($this->dbrw,
            "loc_pays", array(
              "nom_pays"=>$this->nom,
              "indtel_pays"=>$this->indtel
            ));

		if ( $req )
		{
			$this->id = $req->get_id();
		  return true;
		}

		$this->id = null;
    return false;
  }

  function update ( $nom, $indtel )
  {
    $this->nom = $nom;
    $this->indtel = $indtel;

    $req = new update ($this->dbrw,
            "loc_pays", array(
              "nom_pays"=>$this->nom,
              "indtel_pays"=>$this->indtel
            ),
            array("id_pays"=>$this->id) );
  }

  function delete ( )
  {
    new delete($this->dbrw,"loc_pays",array("id_pays"=>$this->id));
    $this->id = null;
  }

}

?>
