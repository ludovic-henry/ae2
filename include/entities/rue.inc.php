<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
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

/**
 * @file
 */


/**
 * Type de rue
 * @ingroup pg2
 * @author Julien Etelain
 */
class typerue extends stdentity
{
  /** Nom du type de rue */
  var $nom;

  /**
   * Charge un élément par son id
   * @param $id Id de l'élément
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_typerue`
				WHERE `id_typerue` = '".mysql_real_escape_string($id)."'
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
    $this->id = $row['id_typerue'];
    $this->nom = $row['nom_typerue'];
  }

  function create ( $nom )
  {
    $this->nom = $nom;

    $req = new insert ( $this->dbrw, "pg_typerue",
      array(
      "nom_typerue" => $this->nom
      ) );

    if ( !$req->is_success() )
    {
      $this->id = null;
      return false;
    }

	  $this->id = $req->get_id();
    return true;
  }

  function update ( $nom )
  {
    $this->nom = $nom;

    new update ( $this->dbrw, "pg_typerue",
      array(
      "nom_typerue" => $this->nom
      ),
      array("id_typerue"=>$this->id) );
  }

  function delete ()
  {
    new delete($this->dbrw, "pg_typerue",array("id_typerue"=>$this->id));
    new delete($this->dbrw, "pg_rue",array("id_typerue"=>$this->id));
    $this->id=null;
  }
  function prefer_list()
  {
    return true;
  }

}

/**
 * Rue
 * @see typerue
 * @see ville
 * @ingroup pg2
 * @author Julien Etelain
 */
class rue extends stdentity
{
  /** Nom de la rue */
  var $nom;
  /** Complément pour situer sur plan */
  var $complement;
  /** Type de rue (rue, boulevard, RN, ...)
    * @see typerue */
  var $id_typerue;
  /** Ville dans la quelle se situe la rue
    * @see ville */
  var $id_ville;
  /** Dans le cas de "centres", rue de l'entrée principale */
  var $id_rue_entree;
  /** Dans le cas de "centres", numéro de rue de l'entrée principale */
  var $num_entree;

  /**
   * Charge un élément par son id
   * @param $id Id de l'élément
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_rue`
				WHERE `id_rue` = '".mysql_real_escape_string($id)."'
				LIMIT 1");

    if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
  }

  function load_or_create ( $id_typerue, $id_ville, $nom )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_rue`
				WHERE `id_typerue` = '".mysql_real_escape_string($id_typerue)."'
				AND `id_ville` = '".mysql_real_escape_string($id_ville)."'
				AND `nom_rue` = '".mysql_real_escape_string($nom)."'
				LIMIT 1");

    if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return;
		}

		$this->create ( $nom, "", $id_typerue, $id_ville );
  }



  function _load ( $row )
  {
    $this->id = $row['id_rue'];
    $this->nom = $row['nom_rue'];
    $this->id_typerue = $row['id_typerue'];
    $this->id_ville = $row['id_ville'];
    $this->id_rue_entree = $row['id_rue_entree'];
    $this->num_entree = $row['num_entree_rue'];
    $this->complement = $row['complement_rue'];
  }

  function create ( $nom, $complement, $id_typerue, $id_ville, $id_rue_entree=null, $num_entree=null )
  {
    $this->nom = $nom;
    $this->id_typerue = $id_typerue;
    $this->id_ville = $id_ville;
    $this->id_rue_entree = $id_rue_entree;
    $this->num_entree = $num_entree;
    $this->complement = $complement;

    $req = new insert ( $this->dbrw, "pg_rue",
      array(
      "nom_rue" => $this->nom,
      "id_typerue" => $this->id_typerue,
      "id_ville" => $this->id_ville,
      "id_rue_entree" => $this->id_rue_entree,
      "num_entree_rue" => $this->num_entree,
      "complement_rue"=> $this->complement
      ) );

    if ( !$req->is_success() )
    {
      $this->id = null;
      return false;
    }

	  $this->id = $req->get_id();
    return true;
  }

  function update ( $nom, $complement, $id_typerue, $id_ville, $id_rue_entree=null, $num_entree=null )
  {
    $this->nom = $nom;
    $this->id_typerue = $id_typerue;
    $this->id_ville = $id_ville;
    $this->id_rue_entree = $id_rue_entree;
    $this->num_entree = $num_entree;
    $this->complement = $complement;

    new update ( $this->dbrw, "pg_rue",
      array(
      "nom_rue" => $this->nom,
      "id_typerue" => $this->id_typerue,
      "id_ville" => $this->id_ville,
      "id_rue_entree" => $this->id_rue_entree,
      "num_entree_rue" => $this->num_entree,
      "complement_rue"=> $this->complement
      ),
      array("id_rue"=>$this->id) );
  }

  function delete ()
  {
    new delete($this->dbrw, "pg_rue",array("id_rue"=>$this->id));
    $this->id=null;
  }

}


?>
