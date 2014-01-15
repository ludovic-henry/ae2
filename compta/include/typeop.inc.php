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
 * Type des opérations de la compta
 */

$types_mouvements = array (1 => "Credit",-1 => "Debit",0 => "Pas de mouvement de fonds");
$types_mouvements_reel = array (1 => "Credit",-1 => "Debit");

/**
 * Type d'opération selon le plan comptable
 * @ingroup compta
 */
class operation_comptable extends stdentity
{
  /** Code du plan comptable du type d'opération */
	var $code;
	/** Nom du type d'opération */
	var $libelle;
	/** Mouvement du type d'opération
	 * @see $types_mouvements
	 */
	var $type_mouvement;


	/** Charge le type d'opération comptable par son id
	 * @param $id Id du type d'opération comptable
	 */
	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `cpta_op_plcptl`
				WHERE `id_opstd` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;

	}

	function load_by_code ( $code )
	{
		$req = new requete($this->db, "SELECT * FROM `cpta_op_plcptl`
				WHERE `code_plan` = '" . mysql_real_escape_string($code) . "'
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
		$this->id = $row['id_opstd'];
		$this->code = $row['code_plan'];
		$this->libelle = $row['libelle_plan'];
		$this->type_mouvement = $row['type_mouvement'];
	}



}

/**
 * Type d'opération simplifié pour les clubs (relatif au compte association)
 * @ingroup compta
 */
class operation_club extends stdentity
{
  /** Id de l'activité/association associé, null si cette opération est communes
    * à l'ensemble des activités
    */
	var $id_asso;
	/** Id du type d'operation comptable associé, peut être null
	 * @see operation_comptable
	 */
	var $id_opstd;
	/** Nom du type d'opération */
	var $libelle;
	/** Mouvement du type d'opération
	 * @see $types_mouvements_reel
	 */
	var $type_mouvement;

	/** Charge le type d'opération simplifié par son id
	 * @param $id Id du type d'opération
	 */
	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `cpta_op_clb`
				WHERE `id_opclb` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;

	}

	function load_or_create ( $id_asso, $code_plan, $libelle=null )
	{
		$opstd = new operation_comptable($this->db);
		$opstd->load_by_code($code_plan);

		if ( $libelle )
			$req = new requete($this->db, "SELECT * FROM `cpta_op_clb`
				WHERE `id_asso` = '" . mysql_real_escape_string($id_asso) . "' " .
				"AND `id_opstd` = '" . mysql_real_escape_string($opstd->id) . "' " .
				"AND `libelle_opclb` LIKE '".mysql_real_escape_string($libelle)."'
				LIMIT 1");
		else
			$req = new requete($this->db, "SELECT * FROM `cpta_op_clb`
				WHERE `id_asso` = '" . mysql_real_escape_string($id_asso) . "' " .
				"AND `id_opstd` = '" . mysql_real_escape_string($opstd->id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return;
		}

		if ( !$libelle )
			$libelle = $opstd->libelle;

		$this->new_op_pstd ( $id_asso, $opstd->id, $libelle, $opstd->type_mouvement );
	}

	function _load ( $row )
	{
		$this->id = $row['id_opclb'];
		$this->id_asso = $row['id_asso'];
		$this->id_opstd = $row['id_opstd'];
		$this->libelle = $row['libelle_opclb'];
		$this->type_mouvement = $row['type_mouvement'];
	}

	/** Rattache un type d'opération confome au plan comptable au type d'opératiobn simplifié
	 * @param $id_opstd Id du type d'opération comptable
	 */
	function attach ( $id_opstd )
	{
		new update($this->dbrw,
					"cpta_operation",
					array("id_opstd"=>$id_opstd),
					array("id_opclb"=>$this->id));

		new update($this->dbrw,
					"cpta_op_clb",
					array("id_opstd"=>$id_opstd),
					array("id_opclb"=>$this->id));
	}

	/** Ajoute un nouveau type d'opération sur le compte asso
	 * @param $id_asso Id de l'association
	 * @param $libelle Libelle de l'opération
	 * @param $type_mouvement Type de mouvement
	 */
	function new_op ( $id_asso, $libelle, $type_mouvement )
	{
		$this->id_asso = $id_asso;
		$this->libelle = $libelle;
		$this->type_mouvement = $type_mouvement;

		$sql = new insert ($this->dbrw,
			"cpta_op_clb",
			array(
				"id_asso" => $this->id_asso,
				"libelle_opclb" => $this->libelle,
				"type_mouvement" => $this->type_mouvement
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;


	}

	/** Ajoute un nouveau type d'opération sur le compte asso connaissant l'opération plan comptable
	 * @param $id_asso Id de l'association
	 * @param $id_opstd Id de l'opération comptable
	 * @param $libelle Libelle de l'opération
	 * @param $type_mouvement Type de mouvement (conforme à l'opération du plan comptable)
	 */
	function new_op_pstd ( $id_asso, $id_opstd, $libelle, $type_mouvement )
	{
		$this->id_asso = $id_asso;
		$this->id_opstd = $id_opstd;
		$this->libelle = $libelle;
		$this->type_mouvement = $type_mouvement;

		$sql = new insert ($this->dbrw,
			"cpta_op_clb",
			array(
				"id_asso" => $this->id_asso,
				"id_opstd" => $this->id_opstd,
				"libelle_opclb" => $this->libelle,
				"type_mouvement" => $this->type_mouvement
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;


	}

	/** Remplace le type d'opération par un autre (et supprime ce type)
	 * @param $op Instance de operation_club
	 */
	function replace_and_remove ( &$op )
	{
		new update($this->dbrw,
					"cpta_operation",
					array("id_opstd"=>$op->id_opstd,"id_opclb"=>$op->id),
					array("id_opclb"=>$this->id));

		new update($this->dbrw,
					"cpta_ligne_budget",
					array("id_opclb"=>$op->id),
					array("id_opclb"=>$this->id));

		new delete($this->dbrw,
					"cpta_op_clb",
					array("id_opclb"=>$this->id));
	}

	/** Ajoute un nouveau type d'opération sur le compte asso connaissant l'opération plan comptable
	 * @param $id_asso Id de l'association
	 * @param $id_opstd Id de l'opération comptable
	 * @param $libelle Libelle de l'opération
	 * @param $type_mouvement Type de mouvement (conforme à l'opération du plan comptable)
	 */
	function save ( $id_asso, $id_opstd, $libelle, $type_mouvement )
	{
		$this->id_asso = $id_asso;
		$this->id_opstd = $id_opstd;
		$this->libelle = $libelle;
		$this->type_mouvement = $type_mouvement;

		new update ($this->dbrw,
			"cpta_op_clb",
			array(
				"id_asso" => $this->id_asso,
				"id_opstd" => $this->id_opstd,
				"libelle_opclb" => $this->libelle,
				"type_mouvement" => $this->type_mouvement
				),
			array(
				"id_opclb" => $this->id
				)
			);

		new update($this->dbrw,
					"cpta_operation",
					array("id_opstd"=>$this->id_opstd),
					array("id_opclb"=>$this->id));
	}
}


?>
