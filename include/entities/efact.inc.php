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
 */
/**
 * @file
 */

/**
 * Classe permettant l'edition de factures.
 *
 * Permet d'établir des facturer des tiers, se range dans un classeur de compta.
 *
 * @see classeur
 * @ingroup compta
 * @author Julien Etelain
 */
class efact extends stdentity
{

	/** Classeur virtuel de compta où la facture est rangée */
	var $id_classeur;

	/** Raison sociale de la personne facturée */
	var $nom_facture;

	/** Adresse (siège social) de la personne factuée */
	var $adresse_facture;

	/** Date d'emission de la facture */
	var $date;

	/** Titre de la facture */
	var $titre;

	/** Montant total de la facture (calculé, pour optimisation) */
  var $montant;

  /** Operation de compta liée (peut être NULL) */
  var $id_op;


  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `cpta_facture`
				WHERE `id_efact` = '" .
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

  function load_by_id_op ( $id_op )
  {
    $req = new requete($this->db, "SELECT * FROM `cpta_facture`
				WHERE `id_op` = '" .
		       mysql_real_escape_string($id_op) . "'
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
    $this->id = $row['id_efact'];
    $this->id_classeur = $row['id_classeur'];
    $this->nom_facture = $row['nom_facture'];
    $this->adresse_facture = $row['adresse_facture'];
    $this->date = strtotime($row['date_facture']);
    $this->titre = $row['titre_facture'];
    $this->montant = $row['montant_facture'];
    $this->id_op = $row['id_op'];
  }

  function create ( $id_classeur, $nom_facture, $adresse_facture, $date, $titre, $id_op=null )
  {

    $this->id_classeur = $id_classeur;
    $this->nom_facture = $nom_facture;
    $this->adresse_facture = $adresse_facture;
    $this->date = $date;
    $this->titre = $titre;
    $this->montant = 0;
    $this->id_op = $id_op;

    $req = new insert ($this->dbrw,
            "cpta_facture", array(
              "id_classeur"=>$this->id_classeur,
              "nom_facture"=>$this->nom_facture,
              "adresse_facture"=>$this->adresse_facture,
              "date_facture"=>date("Y-m-d",$this->date),
              "titre_facture"=>$this->titre,
              "montant_facture"=>$this->montant,
              "id_op"=>$this->id_op
            ));

		if ( $req )
		{
			$this->id = $req->get_id();
		  return true;
		}

		$this->id = null;
    return false;
  }

  function update ( $id_classeur, $nom_facture, $adresse_facture, $date, $titre, $id_op=null )
  {
    $this->id_classeur = $id_classeur;
    $this->nom_facture = $nom_facture;
    $this->adresse_facture = $adresse_facture;
    $this->date = $date;
    $this->titre = $titre;
    $this->id_op = $id_op;

    $req = new update ($this->dbrw,
            "cpta_facture", array(
              "id_classeur"=>$this->id_classeur,
              "nom_facture"=>$this->nom_facture,
              "adresse_facture"=>$this->adresse_facture,
              "date_facture"=>date("Y-m-d",$this->date),
              "titre_facture"=>$this->titre,
              "id_op"=>$this->id_op
            ),
            array("id_efact"=>$this->id) );
  }

  function set_op ( $id_op=null )
  {

    $this->id_op = $id_op;

    $req = new update ($this->dbrw,
            "cpta_facture", array(
              "id_op"=>$this->id_op
            ),
            array("id_efact"=>$this->id) );
  }

	/**
	 * Ajoute une ligne à la facture
	 * @param $prix_unit Prix unitaire
	 * @param $quantite Quantité
	 * @param $designation Designation
	 */
  function create_line ( $prix_unit, $quantite, $designation )
	{
    $req = new insert ($this->dbrw,
            "cpta_facture_ligne", array(
              "prix_unit_ligne_efact"=>$prix_unit,
              "quantite_ligne_efact"=>$quantite,
              "designation_ligne_efact"=>$designation,
              "id_efact"=>$this->id
            ));
    $this->_update_montant();
	}

	function update_line ( $num, $prix_unit, $quantite, $designation )
	{
    $req = new update ($this->dbrw,
            "cpta_facture_ligne", array(
              "prix_unit_ligne_efact"=>$prix_unit,
              "quantite_ligne_efact"=>$quantite,
              "designation_ligne_efact"=>$designation),
            array("id_efact"=>$this->id,"num_ligne_efact"=>$num));
    $this->_update_montant();
	}

	function delete_line ( $num )
	{
    $req = new delete ($this->dbrw,"cpta_facture_ligne",array("id_efact"=>$this->id,"num_ligne_efact"=>$num));
    $this->_update_montant();
	}

	function get_line  ( $num )
	{
    $req = new requete($this->db, "SELECT * FROM `cpta_facture_ligne`
				WHERE `id_efact` = '".mysql_real_escape_string($this->id)."' AND `num_ligne_efact` = '".mysql_real_escape_string($num)."'
				LIMIT 1");

	  return $req->get_row();
	}


	function _update_montant()
	{
	  $req = new requete($this->dbrw,
	  "UPDATE cpta_facture ".
	  "SET montant_facture=".
	  "( ".
	    "SELECT SUM(prix_unit_ligne_efact*quantite_ligne_efact) ".
	    "FROM cpta_facture_ligne ".
	    "WHERE id_efact='".mysql_real_escape_string($this->id)."'".
	  ") ".
	  "WHERE id_efact='".mysql_real_escape_string($this->id)."'");

	  if ( !is_null($this->id_op) )
	  {
	    $this->load_by_id($this->id);
		  $sql = new update ($this->dbrw,"cpta_operation",array("montant_op" => $this->montant),array("id_op" => $this->id_op));
	  }
	}

	function delete()
	{
    new delete ($this->dbrw,"cpta_facture_ligne",array("id_efact"=>$this->id));
    new delete ($this->dbrw,"cpta_facture",array("id_efact"=>$this->id));
	}



}



?>
