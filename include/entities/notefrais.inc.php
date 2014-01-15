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
 * Classe permettant l'edition de notes de frais
 *
 *
 * @see classeur
 * @ingroup compta
 * @author Julien Etelain
 */
class notefrais extends stdentity
{

	/** Classeur virtuel de compta où la note est rangée (NULL autorisé) */
	var $id_classeur;

	/** Association imputée */
	var $id_asso;

	/** Bénévole emettant la note de frais */
	var $id_utilisateur;

	/** Date d'emission de la note de frais */
	var $date;

	/** Commentaire */
	var $commentaire;

	/** Total (en centimes) (calculé, pour optimisation) */
	var $total;

  /** Avance (en centimes) */
	var $avance;

	/** Total à payer (calculé, pour optimisation) */
	var $total_payer;

	/** Validé */
  var $valide;

	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `cpta_notefrais`
				WHERE `id_notefrais` = '" . mysql_real_escape_string($id) . "'
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
		$this->id = $row['id_notefrais'];
		$this->id_classeur = $row['id_classeur'];
		$this->id_asso = $row['id_asso'];
		$this->id_utilisateur = $row['id_utilisateur'];
		$this->date = strtotime($row['date_notefrais']);
		$this->commentaire = $row['commentaire_notefrais'];
		$this->total = $row['total_notefrais'];
		$this->avance = $row['avance_notefrais'];
		$this->total_payer = $row['total_payer_notefrais'];
		$this->valide = $row['valide_notefrais'];
	}

  function create ( $id_classeur, $id_asso, $id_utilisateur, $commentaire, $avance  )
  {
    $this->id_classeur = $id_classeur;
    $this->id_asso = $id_asso;
    $this->id_utilisateur = $id_utilisateur;
    $this->date = time();
    $this->commentaire = $commentaire;
    $this->avance = $avance;
    $this->total = $avance;
    $this->total_payer = 0;
    $this->valide = 0;

    $req = new insert ($this->dbrw,
            "cpta_notefrais", array(
              "id_classeur"=>$this->id_classeur,
              "id_asso"=>$this->id_asso,
              "id_utilisateur"=>$this->id_utilisateur,
              "commentaire_notefrais"=>$this->commentaire,
              "date_notefrais"=>date("Y-m-d H:i:s",$this->date),
              "total_notefrais"=>$this->total,
              "avance_notefrais"=>$this->avance,
              "total_payer_notefrais"=>$this->total_payer,
              "valide_notefrais"=>$this->valide
            ));

		if ( $req )
		{
			$this->id = $req->get_id();
		  return true;
		}

		$this->id = null;
    return false;
  }

  function update ( $id_asso, $commentaire, $avance  )
  {
    $this->id_asso = $id_asso;
    $this->date = time();
    $this->commentaire = $commentaire;
    $this->avance = $avance;
    $this->total_payer = $this->total-$this->avance;

    $req = new update ($this->dbrw,
            "cpta_notefrais", array(
              "id_asso"=>$this->id_asso,
              "commentaire_notefrais"=>$this->commentaire,
              "date_notefrais"=>date("Y-m-d H:i:s",$this->date),
              "avance_notefrais"=>$this->avance,
              "total_payer_notefrais"=>$this->total_payer
            ),
            array("id_notefrais"=>$this->id));

  }

  function set_valide ()
  {
    $this->valide = 1;
    $req = new update ($this->dbrw,"cpta_notefrais", array("valide_notefrais"=>$this->valide), array("id_notefrais"=>$this->id));
  }

  function set_classeur ( $id_classeur )
  {
    $this->id_classeur = $id_classeur;
    $req = new update ($this->dbrw,"cpta_notefrais", array("id_classeur"=>$this->id_classeur), array("id_notefrais"=>$this->id));
  }

  function delete ()
  {
    new delete ($this->dbrw,"cpta_notefrais", array("id_notefrais"=>$this->id));
    new delete ($this->dbrw,"cpta_notefrais_ligne", array("id_notefrais"=>$this->id));
    $this->id = null;
  }


	/**
	 * Ajoute une ligne à la note de frais
	 * @param $designation Designation
	 * @param $prix Prix
	 */
  function create_line ( $designation, $prix )
	{
    $req = new insert ($this->dbrw,
            "cpta_notefrais_ligne", array(
              "id_notefrais"=>$this->id,
              "designation_ligne_notefrais"=>$designation,
              "prix_ligne_notefrais"=>$prix
            ));
    $this->update_fields();
	}

	function delete_line ( $num )
	{
    $req = new delete ($this->dbrw,
            "cpta_notefrais_ligne", array(
              "id_notefrais"=>$this->id,
              "num_notefrais_ligne"=>$num
            ));
    $this->update_fields();
	}

	function delete_all_lines ( )
	{
    $req = new delete ($this->dbrw,
            "cpta_notefrais_ligne", array(
              "id_notefrais"=>$this->id
            ));
    $this->update_fields();
	}

  function update_fields()
  {
		$req = new requete($this->db, "SELECT SUM(prix_ligne_notefrais) FROM `cpta_notefrais_ligne`
				WHERE `id_notefrais` = '" . mysql_real_escape_string($this->id) . "'");

    list($this->total) = $req->get_row();

    $this->total_payer = $this->total-$this->avance;

    $req = new update ($this->dbrw,
            "cpta_notefrais",
            array(
              "total_notefrais"=>$this->total,
              "total_payer_notefrais"=>$this->total_payer
            ),
            array("id_notefrais"=>$this->id));
  }

	function get_lines()
	{
	  $lines = array();
		$req = new requete($this->db, "SELECT * FROM `cpta_notefrais_ligne`
				WHERE `id_notefrais` = '" . mysql_real_escape_string($this->id) . "'");
		while ( $row = $req->get_row() )
		  $lines[]=$row;

	  return $lines;
	}


}



?>
