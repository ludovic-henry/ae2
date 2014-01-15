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
 */

/**
 * Budget à placer dans un classeur de compta
 * @ingroup compta
 */
class budget extends stdentity
{
	var $id_classeur;
	var $nom;
	var $total;
	var $date;
	var $valide;
	var $termine;

	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `cpta_budget`
				WHERE `id_budget` = '" . mysql_real_escape_string($id) . "'
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
		$this->id = $row['id_budget'];
		$this->id_classeur = $row['id_classeur'];
		$this->nom = $row['nom_budget'];
		$this->total = $row['total_budget'];
		$this->date = strtotime($row['date_budget']);
		$this->valide = $row['valide_budget'];
		$this->projets = $row['projets_budget'];
		$this->termine = $row['termine_budget'];
	}

	function new_budget ( $id_classeur, $nom, $projets="" )
	{

		$this->id_classeur = $id_classeur;
		$this->nom = $nom;
		$this->date = time();
		$this->valide = 0;
		$this->total = 0;
		$this->projets = $projets;
		$this->termine = 0;

		$sql = new insert ($this->dbrw,
			"cpta_budget",
			array(
				"id_classeur" => $this->id_classeur,
				"nom_budget" => $this->nom,
				"total_budget" => $this->total,
				"date_budget"=> date("Y-m-d H:i",$this->date),
				"valide_budget"=> $this->valide,
				"projets_budget" => $this->projets,
				"termine_budget" => $this->termine
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

	function update ( $nom, $projets )
	{
		$this->nom = $nom;
		$this->date = time();
		$this->projets = $projets;

		$sql = new update ($this->dbrw,
			"cpta_budget",
			array(
				"nom_budget" => $this->nom,
				"date_budget"=> date("Y-m-d H:i",$this->date),
				"projets_budget" => $this->projets
				),
			array(
			  "id_budget"=>$this->id
			  )
			);
	}

	function set_termine ( )
	{
		$this->termine = 1;

		$sql = new update ($this->dbrw,
			"cpta_budget",
			array(
				"termine_budget" => $this->termine
				),
			array(
			  "id_budget"=>$this->id
			  )
			);
	}

	function set_valide ( )
	{
	  $this->valide = 1;

		$sql = new update ($this->dbrw,
			"cpta_budget",
			array(
				"valide_budget" => $this->valide
				),
			array(
			  "id_budget"=>$this->id
			  )
			);
	}

	function add_line ( $id_opclb, $montant, $description )
	{
		$sql = new insert ($this->dbrw,
			"cpta_ligne_budget",
			array(
				"id_budget" => $this->id,
				"id_opclb" => $id_opclb,
				"montant_ligne" => $montant,
				"description_ligne"=> $description
				)
			);
	}

	function get_line ( $num )
	{
	  $req = new requete($this->db,"SELECT * FROM cpta_ligne_budget WHERE id_budget='".mysql_real_escape_string($this->id)."' AND num_lignebudget='".mysql_real_escape_string($num)."'");

	  if ( $req->lines != 1 )
	    return null;

	  return $req->get_row();
	}


	function update_line ( $num, $id_opclb, $montant, $description )
	{
		$sql = new update($this->dbrw,
			"cpta_ligne_budget",
			array(
				"id_opclb" => $id_opclb,
				"montant_ligne" => $montant,
				"description_ligne"=> $description
				),
			array(
				"id_budget" => $this->id,
				"num_lignebudget" => $num
				));
	}

	function remove_line ( $num )
	{
		$sql = new delete($this->dbrw,
			"cpta_ligne_budget",
			array(
				"id_budget" => $this->id,
				"num_lignebudget" => $num
				));
	}

}





?>
