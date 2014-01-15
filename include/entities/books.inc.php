<?php
/* Copyright 2006
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
 * Gestion bibliothèque
 */
require_once($topdir."include/entities/objet.inc.php");

/**
 * @defgroup biblio Bibliothèqe
 * @ingroup inventaire
 */

/**
 * Editeur de livres, de BDs et/ou de jeux
 * @ingroup biblio
 * @author Julien Etelain
 */
class editeur extends stdentity
{
	var $nom;

	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `bk_editeur`
				WHERE `id_editeur` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
	}

  function load_or_create( $nom )
  {
		$req = new requete($this->db, "SELECT * FROM `bk_editeur`
				WHERE `nom_editeur` = '" . mysql_real_escape_string($nom) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return;
		}

    $this->add_editeur($nom);
  }

	function _load ( $row )
	{
		$this->id = $row['id_editeur'];
		$this->nom = $row['nom_editeur'];
	}

	function add_editeur ( $nom )
	{
		$this->nom = $nom;

		$sql = new insert ($this->dbrw,
			"bk_editeur",
			array(
				"nom_editeur" => $this->nom
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

	function save_editeur ( $nom )
	{
		$this->nom = $nom;

		$sql = new update ($this->dbrw,
			"bk_editeur",
			array(
				"nom_editeur" => $this->nom
				),
			array("id_editeur"=>$this->id)
			);

	}

}

/**
 * Série de livres, de BDs ou de jeux
 * @ingroup biblio
 * @author Julien Etelain
 */
class serie extends stdentity
{
	var $nom;

	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `bk_serie`
				WHERE `id_serie` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
	}

	function load_or_create ( $nom )
	{
		$req = new requete($this->db, "SELECT * FROM `bk_serie`
				WHERE `nom_serie` = '" . mysql_real_escape_string($nom) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return;
		}

		$this->add_serie($nom);
	}

	function _load ( $row )
	{
		$this->id = $row['id_serie'];
		$this->nom = $row['nom_serie'];
	}

	function add_serie ( $nom )
	{
		$this->nom = $nom;

		$sql = new insert ($this->dbrw,
			"bk_serie",
			array(
				"nom_serie" => $this->nom
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

	function save_serie ( $nom )
	{
		$this->nom = $nom;

		$sql = new update ($this->dbrw,
			"bk_serie",
			array(
				"nom_serie" => $this->nom
				),
			array("id_serie"=>$this->id)
			);

	}
}

/**
 * Auteur de livres, de BDs ou de jeux
 * @ingroup biblio
 * @author Julien Etelain
 */
class auteur extends stdentity
{
	var $nom;

	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `bk_auteur`
				WHERE `id_auteur` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
	}

	function load_or_create ( $nom )
	{
		$req = new requete($this->db, "SELECT * FROM `bk_auteur`
				WHERE `nom_auteur` = '" . mysql_real_escape_string($nom) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return;
		}

		$this->add_auteur($nom);
	}

	function _load ( $row )
	{
		$this->id = $row['id_auteur'];
		$this->nom = $row['nom_auteur'];
	}

	function add_auteur ( $nom )
	{
		$this->nom = $nom;

		$sql = new insert ($this->dbrw,
			"bk_auteur",
			array(
				"nom_auteur" => $this->nom
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

	function save_auteur ( $nom )
	{
		$this->nom = $nom;

		$sql = new update ($this->dbrw,
			"bk_auteur",
			array(
				"nom_auteur" => $this->nom
				),
			array("id_auteur"=>$this->id)
			);

	}
}

/**
 * Livre de la bibliothèque
 * @ingroup biblio
 * @author Julien Etelain
 */
class livre extends objet
{
	/** Id de la série */
	var $id_serie;
	/** Id de l'éditeur */
	var $id_editeur;
	/** Numéro dans la série */
	var $num_livre;

	var $isbn;

  /** Charge un livre en fonction de son id
	 * $this->id est égal à -1 en cas d'erreur
	 * @param $id id de la fonction
	 */
	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT `inv_objet`.*, `bk_book`.* FROM `inv_objet`
				INNER JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet`
				WHERE `inv_objet`.`id_objet` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
	}

	/** Charge un livre en fonction de son code barre
	 * $this->id est égal à -1 en cas d'erreur
	 * @param $id id de la fonction
	 */
	function load_by_cbar ( $cbar )
	{
		$req = new requete($this->db, "SELECT `inv_objet`.*, `bk_book`.* FROM `inv_objet`
				INNER JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet`
				WHERE `inv_objet`.`cbar_objet` = '" . mysql_real_escape_string($cbar) . "'
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
		$this->id_serie = $row['id_serie'];
		$this->id_editeur = $row['id_editeur'];
		$this->num_livre = $row['num_livre'];
		$this->isbn = $row['isbn_livre'];
		$this->_is_book = true;
		parent::_load($row);
	}


	function add_book ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$code_objtype, $num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes,
				$id_serie, $id_editeur,$num_livre, $isbn="" )
	{

		parent::add($id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$code_objtype, $num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes );

		$this->id_serie = $id_serie;
		$this->id_editeur = $id_editeur;
		$this->num_livre = $num_livre;
		$this->isbn = $isbn;
		$this->_is_book = true;

		if ( $this->is_valid() )
		{
			$sql = new insert ($this->dbrw,
				"bk_book",
				array(
					"id_objet" => $this->id,
					"id_serie" => $this->id_serie,
					"id_editeur" => $this->id_editeur,
					"num_livre" => $this->num_livre,
					"isbn_livre" => $this->isbn
					)
				);
		}
	}

	function save_book ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes,$cbar,
				$id_serie, $id_editeur,$num_livre, $isbn=""  )
	{

		$this->save_objet ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes,$cbar );

		$this->id_serie = $id_serie;

		$this->id_editeur = $id_editeur;
		$this->num_livre = $num_livre;

		$sql = new update ($this->dbrw,
			"bk_book",
			array(
				"id_serie" => $this->id_serie,
				"id_editeur" => $this->id_editeur,
				"num_livre" => $this->num_livre,
				"isbn_livre" => $this->isbn
				),
			array("id_objet" => $this->id)
			);

	}

	function add_auteur ( $id_auteur )
	{
		$sql = new insert ($this->dbrw,
				"bk_livre_auteur",
				array(
					"id_objet" => $this->id,
					"id_auteur" => $id_auteur,
					)
				);

	}

	function remove_auteur ( $id_auteur )
	{
		$sql = new delete ($this->dbrw,
				"bk_livre_auteur",
				array(
					"id_objet" => $this->id,
					"id_auteur" => $id_auteur,
					)
				);
	}

}


?>
