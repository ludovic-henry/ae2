<?php
/* Copyright 2005,2006,2007
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

/**
 * @file
 *
 */

/**
 * Entreprise en relation avec l'AE
 * @author Julien Etelain
 * @ingroup compta
 */
class entreprise extends stdentity
{
	var $nom;
	var $rue;
	var $id_ville;
	var $telephone;
	var $email;
	var $fax;
  var $siteweb;


	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `entreprise`
				WHERE `id_ent` = '" . mysql_real_escape_string($id) . "'
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
		$this->id		= $row['id_ent'];
		$this->id_ville		= $row['id_ville'];
		$this->nom		= $row['nom_entreprise'];
		$this->rue		= $row['rue_entreprise'];
		$this->telephone	= $row['telephone_entreprise'];
		$this->email		= $row['email_entreprise'];
		$this->fax		= $row['fax_entreprise'];
		$this->siteweb		= $row['siteweb_entreprise'];
	}

	function add ( $nom,$rue,$id_ville,$telephone,$email,$fax,$siteweb)
	{
		if ( !$this->dbrw ) return; // Exits if "Read Only" mode

		$this->nom = $nom;
		$this->rue = $rue;
		$this->id_ville = $id_ville;
		$this->telephone = $telephone;
		$this->email = $email;
		$this->fax = $fax;
		$this->siteweb = $siteweb;

		$sql = new insert ($this->dbrw,
			"entreprise",
			array(
				"nom_entreprise" => $this->nom,
				"rue_entreprise" => $this->rue,
				"id_ville" => $this->id_ville,
				"telephone_entreprise" => $this->telephone,
				"email_entreprise" => $this->email,
				"fax_entreprise" => $this->fax,
				"siteweb_entreprise"=> $this->siteweb
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

	function remove ( )
	{
		if ( !$this->dbrw ) return; // Exits if "Read Only" mode

		$sql = new delete ($this->dbrw,
			"entreprise",
			array(
				"id_ent" => $this->id
				)
			);
	}

	function save ( $nom,$rue,$id_ville,$telephone,$email,$fax,$siteweb)
	{


		$this->nom = $nom;
		$this->rue = $rue;
		$this->id_ville = $id_ville;
		$this->telephone = $telephone;
		$this->email = $email;
		$this->fax = $fax;
		$this->siteweb = $siteweb;

		$sql = new update ($this->dbrw,
			"entreprise",
			array(
				"nom_entreprise" => $this->nom,
				"rue_entreprise" => $this->rue,
				"id_ville" => $this->id_ville,
				"telephone_entreprise" => $this->telephone,
				"email_entreprise" => $this->email,
				"fax_entreprise" => $this->fax,
				"siteweb_entreprise"=> $this->siteweb
				),
			array (
				"id_ent" => $this->id
				)
			);


	}



  function _fsearch ( $sqlpattern, $limit=5, $count=false, $conds = null )
  {
    $class = get_class($this);

    if ( !$sqlpattern )
      return null;

    if ( $count )
    {
		  $sql = "SELECT COUNT(*) ";
      $limit=null;
    }
    else
		  $sql = "SELECT `id_ent`,`nom_entreprise` ";

    $sql .= "FROM `entreprise` ".
      "WHERE `nom_entreprise` REGEXP '$sqlpattern'";

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

    $sql .= " ORDER BY 1";

    if ( !is_null($limit) && $limit > 0 )
      $sql .= " LIMIT ".$limit;

		$req = new requete($this->db,$sql);

    if ( $count )
    {
      list($nb) = $req->get_row();
      return $nb;
    }

    if ( !$req || $req->errno != 0 )
      return null;

    $values=array();

		while ( $row = $req->get_row() )
		  $values[$row[0]] = $row[1];

    return $values;
  }

	function join ( $id_ent )
	{
    if ( $this->id == $id_ent )
      return;

		$sql = new update($this->dbrw,
			"contact_entreprise",
			array("id_ent" => $this->id),
			array("id_ent" => $id_ent));

		$sql = new update($this->dbrw,
			"commentaire_entreprise",
			array("id_ent" => $this->id),
			array("id_ent" => $id_ent));

		$sql = new update($this->dbrw,
			"cpta_operation",
			array("id_ent" => $this->id),
			array("id_ent" => $id_ent));

		$sql = new delete($this->dbrw,"entreprise_secteur",array("id_ent" => $id_ent));

		$sql = new delete($this->dbrw,"entreprise",array("id_ent" => $id_ent));
	}


	function add_secteur ( $id_secteur )
	{
		$sql = new insert ($this->dbrw,
			"entreprise_secteur",
			array(
				"id_ent" => $this->id,
				"id_secteur" => $id_secteur
				)
			);
	}

	function remove_secteur ( $id_secteur )
	{
		$sql = new delete ($this->dbrw,
			"entreprise_secteur",
			array(
				"id_ent" => $this->id,
				"id_secteur" => $id_secteur
				)
			);
	}

}

/**
 * Contact dans une entreprise
 * @author Julien Etelain
 * @ingroup compta
 */
class contact_entreprise extends stdentity
{
	var $id_ent;
	var $nom;
	var $telephone;
	var $service;
	var $email;
	var $fax;

	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `contact_entreprise`
				WHERE `id_contact` = '" . mysql_real_escape_string($id) . "'
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
		$this->id		= $row['id_contact'];
		$this->id_ent	= $row['id_ent'];
		$this->nom		= $row['nom_contact'];
		$this->telephone	= $row['telephone_contact'];
		$this->service	= $row['service_contact'];
		$this->email		= $row['email_contact'];
		$this->fax		= $row['fax_contact'];
	}

	function add ( $id_ent, $nom, $telephone, $service, $email, $fax )
	{
		$this->id_ent	= $id_ent;
		$this->nom		= $nom;
		$this->telephone	= $telephone;
		$this->service	= $service;
		$this->email		= $email;
		$this->fax		= $fax;


		$sql = new insert ($this->dbrw,
			"contact_entreprise",
			array(
				"id_ent" => $this->id_ent,
				"nom_contact" => $this->nom,
				"telephone_contact" => $this->telephone,
				"service_contact" => $this->service,
				"email_contact" => $this->email,
				"fax_contact" => $this->fax
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

	function remove ( )
	{
		if ( !$this->dbrw ) return; // Exits if "Read Only" mode

		$sql = new delete ($this->dbrw,
			"contact_entreprise",
			array(
				"id_contact" => $this->id
				)
			);
	}

}

/**
 * Commentaire sur une entreprise
 * @author Julien Etelain
 * @ingroup compta
 */
class commentaire_entreprise extends stdentity
{
	var $id_ent;
	var $id_utilisateur;
	var $id_contact;
	var $date;
	var $commentaire;


	/** Charge un commentaire en fonction de son id
	 * $this->id est égal à -1 en cas d'erreur
	 * @param $id id de la fonction
	 */
	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `commentaire_entreprise`
				WHERE `id_com_ent` = '" . mysql_real_escape_string($id) . "'
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
		$this->id			= $row['id_com_ent'];
		$this->id_ent		= $row['id_ent'];
		$this->id_utilisateur	= $row['id_utilisateur'];
		$this->id_contact	= $row['id_contact'];
		$this->date			= $row['date_com_ent'];
		$this->commentaire	= $row['commentaire_ent'];
	}

	function add ( $id_utilisateur, $id_ent, $id_contact, $commentaire )
	{
		if ( !$id_contact )
			$id_contact = NULL;

		$this->id_ent		= $id_ent;
		$this->id_utilisateur	= $id_utilisateur;
		$this->id_contact	= $id_contact;
		$this->date 			= time();
		$this->commentaire	= $commentaire;

		$sql = new insert ($this->dbrw,
			"commentaire_entreprise",
			array(
				"id_ent" => $this->id_ent,
				"id_utilisateur" => $this->id_utilisateur,
				"id_contact" => $this->id_contact,
				"date_com_ent" => date("Y-m-d",$this->date),
				"commentaire_ent" => $this->commentaire
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

	function remove ( )
	{
		if ( !$this->dbrw ) return; // Exits if "Read Only" mode

		$sql = new delete ($this->dbrw,
			"commentaire_entreprise",
			array(
				"id_com_ent" => $this->id
				)
			);
	}
}

/**
 * Secteur d'activité pour les entreprises
 *
 * @author Julien Etelain
 * @ingroup compta
 */
class secteur extends stdentity
{

	var $nom;

	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `secteur`
				WHERE `id_secteur` = '" . mysql_real_escape_string($id) . "'
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
		$this->id			= $row['id_secteur'];
		$this->nom		= $row['nom_secteur'];
	}

	function add ( $nom )
	{
		$this->nom		= $nom;

		$sql = new insert ($this->dbrw,
			"secteur",
			array(
				"nom_secteur" => $this->nom
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}
  function can_fsearch ( )
  {
    return false;
  }
}



?>
