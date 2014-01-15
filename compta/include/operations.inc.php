<?php
/* Copyright 2005,2006, 2007
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
 * Modes de paiements possibles pour une opération de compta.
 */
$modes_operation = array(2=>"Espèces",1=>"Chèque",3=>"Virement",4=>"Carte Bancaire");

/**
 * Une opération de compta [dans un classeur de compta].
 *
 * Une opération doit être liée au moins à un tiers. Cela représente la personne
 * qui a été crédité (si l'opération est un débit), ou qui a été  débité (si il
 * s'agit d'un débit). Un tiers peut être :
 * - un utlisateur
 * - une association
 * - une entreprise
 * - un compte association (pour les mouvements interne)
 * Un général un seul doit être définit, hormis si un utilisateur a servi
 * d'intermédiaire, alors il doit être définit en plus du tiers "réel".
 *
 * Une opération peut être liée à un ou plusieurs fichiers de la partie fichier.
 * (pour stocker des justificatifs).
 *
 * @ingroup compta
 * @see classeur
 * @see operation_comptable
 * @see operation_club
 * @see compta_libelle
 */
class operation extends stdentity
{
	/** Id du classeur dans le quel se trouve cette opération */
	var $id_classeur;
	/** Numéro d'ordre de l'opération.
	  * Attention: il ne doit pas y avoir d'interruption dans la numérotation.
	  */
	var $num;
	/** Id de l'étiquette associée (libelle). Facultatif : peut être null.
	 * Permet de creer des catégories d'opérations au sein d'un classeur pour
	 * suivre par exemples des catégories budgetaires
	 */
	var $id_libelle;

	/** Id du type d'opération simplifié (obgligatoire */
	var $id_opclb;

	/** Id du type comptable (facultatif), peut être null. */
	var $id_opstd;

	/** Opération liée, pour les opération jumelles (de compte à compte en interne)*/
	var $id_op_liee;

	/* bénéficiaire : asso, entreprise ou compte bancaire*/

	/** Id de l'utilisateur tiers (crédité/débité) ou qui a servi d'intermédiaire. Facultatif. */
	var $id_utilisateur;
	/** Id de l'association tiers (crédité/débité). Facultatif. */
	var $id_asso;
	/** Id de l'entreprise tiers (crédité/débité). Facultatif. */
	var $id_ent;
	/** Id du compte association tiers (crédité/débité). Facultatif. */
	var $id_cptasso;

	/* informations sur l'opération */
	/** Montant de l'opération en centimes */
	var $montant;
	/** Date de l'opération (timestamp) */
	var $date;
	/** Commentaire sue l'opération */
	var $commentaire;
	/** Marquage effectué (1: effctue, 0: non effectué) */
	var $effectue;
	/** Mode de paiement de l'opération
	 * @see $modes_operation
	 */
	var $mode;
	/** Si le mode est par chèque, le numéro du chèque */
	var $num_cheque;



	function load_by_id ( $id_op )
	{
		$req = new requete ($this->db, "SELECT *
							FROM `cpta_operation`
							WHERE id_op='".intval($id_op)."'");

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
    $this->id = $row['id_op'];
    $this->id_classeur = $row['id_classeur'];
    $this->num = $row['num_op'];
    $this->id_opclb = $row['id_opclb'];
    $this->id_opstd = $row['id_opstd'];
    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_op_liee = $row['id_op_liee'];
    $this->id_asso = $row['id_asso'];
    $this->id_ent = $row['id_ent'];
    $this->id_cptasso = $row['id_cptasso'];
    $this->montant = $row['montant_op'];
    $this->date = strtotime($row['date_op']);
    $this->commentaire = $row['commentaire_op'];
    $this->effectue = $row['op_effctue'];

    $this->mode = $row['mode_op'];
    $this->num_cheque = $row['num_cheque_op'];
    $this->id_libelle = $row['id_libelle'];
	}


	function add_op ( $id_classeur,
					$id_opclb, $id_opstd,
					$id_utilisateur,
					$id_asso, $id_ent, $id_cptasso,
					$montant, $date, $commentaire, $effectue,
					$mode, $num_cheque,
					$id_libelle = null
					)
	{

		$this->id_classeur = $id_classeur;
		$this->id_opclb = $id_opclb;
		$this->id_opstd = $id_opstd;
		$this->id_utilisateur = $id_utilisateur;
		$this->id_asso = $id_asso;
		$this->id_ent = $id_ent?$id_ent:null;
		$this->id_cptasso = $id_cptasso;
		$this->montant = $montant;
		$this->date = $date;
		$this->commentaire = $commentaire;
		$this->effectue = $effectue;
		$this->mode = $mode;
		$this->num_cheque = $num_cheque;
		$this->id_libelle = $id_libelle;

		$sql = new requete ( $this->db, "SELECT MAX(`num_op`) FROM `cpta_operation` " .
				"WHERE `id_classeur`='".intval($this->id_classeur)."'" );

		if ( $sql->lines == 1 )
			list($pnum) = $sql->get_row();
		else
			$pnum = 0;

		$this->num = $pnum + 1;

		$sql = new insert ($this->dbrw,
			"cpta_operation",
			array(
				"id_classeur" => $this->id_classeur,
				"id_opclb" => $this->id_opclb,
				"id_opstd" => $this->id_opstd,
				"id_utilisateur" => $this->id_utilisateur,
				"id_asso" => $this->id_asso,
				"id_ent" => $this->id_ent,
				"id_cptasso" => $this->id_cptasso,
				"num_op" => $this->num,
				"montant_op" => $this->montant,
				"date_op" => date("Y-m-d",$this->date),
				"commentaire_op"=>$this->commentaire,
				"op_effctue" => $this->effectue,
				"mode_op" => $this->mode,
				"num_cheque_op" => $this->num_cheque,
				"id_libelle"=>$this->id_libelle

				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = -1;

	}

	/**
	 * @private
	 */
	function _link ( $id_op )
	{
		$this->id_op_liee = $id_op;
		$req = new update($this->dbrw,
					"cpta_operation",
					array("id_op_liee"=>$id_op),
					array("id_op"=>$this->id));
	}

	/**
	 * Lie une opération avec une autre (opérations jumelles)
	 * @param $op instance deoperation à liér
	 */
	function link_op ( $op )
	{
		if ( $op->id < 1 || $this->id < 1 ) return;
		$op->_link($this->id)	;
		$this->_link($op->id);
	}

	/**
	 * Supprime l'opération (et l'opération jumelle)
	 */
	function delete ( )
	{
		if ( $this->id_op_liee )
			new delete($this->dbrw,"cpta_operation",array("id_op"=>$this->id_op_liee));

		new delete($this->dbrw, "cpta_operation", array("id_op"=>$this->id));
		new delete($this->dbrw, "cpta_operation_files",array("id_op"=>$this->id));
    new update($this->dbrw, "cpta_facture",array("id_op"=>null),array("id_op"=>$this->id));
	}

	/**
	 * Marque comme faite l'opération (et l'opération jumelle)
	 * @param $done fait(1) ou non fait(0)
	 */
	function mark_done ( $done = 1 )
	{
		if ( $this->id_op_liee )
		{
			$req = new update($this->dbrw,
						"cpta_operation",
						array("op_effctue"=>$done),
						array("id_op"=>$this->id_op_liee));
		}
		$req = new update($this->dbrw,
					"cpta_operation",
					array("op_effctue"=>$done),
					array("id_op"=>$this->id));
		$this->effectue=$done;
	}

	function save ( $id_opclb, $id_opstd,
					$id_utilisateur,
					$id_asso, $id_ent, $id_cptasso,
					$montant, $date, $commentaire, $effectue,
					$mode, $num_cheque,
					$id_libelle = null
					)
	{

		$this->id_opclb = $id_opclb;
		$this->id_opstd = $id_opstd;
		$this->id_utilisateur = $id_utilisateur;
		$this->id_asso = $id_asso;
		$this->id_ent = $id_ent;
		$this->id_cptasso = $id_cptasso;
		$this->montant = $montant;
		$this->date = $date;
		$this->commentaire = $commentaire;
		$this->effectue = $effectue;

		$this->mode = $mode;
		$this->num_cheque = $num_cheque;

		$this->id_libelle = $id_libelle;

		$sql = new update ($this->dbrw,
			"cpta_operation",
			array(
				"id_opclb" => $this->id_opclb,
				"id_opstd" => $this->id_opstd,
				"id_utilisateur" => $this->id_utilisateur,
				"id_asso" => $this->id_asso,
				"id_ent" => $this->id_ent,
				"id_cptasso" => $this->id_cptasso,
				"montant_op" => $this->montant,
				"date_op" => date("Y-m-d",$this->date),
				"commentaire_op"=>$this->commentaire,
				"op_effctue" => $this->effectue,
				"mode_op" => $this->mode,
				"num_cheque_op" => $this->num_cheque,
				"id_libelle"=>$this->id_libelle

				),
			array(
				"id_op" => $this->id
				)
			);

		if ( $this->id_op_liee ) // On met à jour l'opération liée
		{
			$req = new update($this->dbrw,
						"cpta_operation",
						array(
							"montant_op" => $this->montant,
							"date_op" => date("Y-m-d",$this->date),
							"mode_op" => $this->mode,
							"num_cheque_op" => $this->num_cheque,
							"op_effctue" => $this->effectue
						),
						array("id_op"=>$this->id_op_liee));
		}

	}

	/**
	 * Définit l'étiquette (libelle)  associée à cette opération.
	 * @param $id_libelle Id de l'étiquette. Peut être null.
	 * @see compta_libelle
	 */
	function set_libelle($id_libelle)
	{
		$this->id_libelle = $id_libelle;

		$sql = new update ($this->dbrw,
			"cpta_operation",
      array("id_libelle" => $this->id_libelle),
			array("id_op" => $this->id)
			);
	}

	/**
	 * Récupère la liste des ids des fichiers associés à cette opération.
	 * @return la liste des ids des fichiers
	 * @see dfile
	 * @see operation::get_files
	 */
	function get_files_ids()
	{
    $list = array();
    $req = new requete($this->db,"SELECT id_file FROM cpta_operation_files WHERE id_op='".intval($this->id)."'");
	  while ( list($id) = $req->get_row() )
	    $list[$id] = $id;
	  return $list;
	}

	/**
	 * Récupère la liste des fichiers associés à cette opération.
	 * @return la liste des fichiers sous forme d'instances de dfile
	 * @see dfile
	 */
	function get_files()
	{
	  global $topdir;
    require_once($topdir . "include/entities/files.inc.php");
	  $list = $this->get_files_ids();
	  $files = array();
	  foreach ( $list as $id )
	  {
	    $file = new dfile($this->db,$this->dbrw);
	    if ( $file->load_by_id($id) )
	      $files[] = $file;
	  }
	  return $files;
	}

	/**
	 * Définit la liste des fichiers associées à cett opération
	 * @param $files liste d'instances de dfile
	 * @see dfile
	 */
	function set_files ( &$files )
	{
	  $actual = $this->get_files_ids();

	  foreach ( $files as $file )
	  {
	    if ( !isset($actual[$file->id]) )
	      new insert($this->dbrw,"cpta_operation_files",array("id_op"=>$this->id,"id_file"=>$file->id));
	    else
	      unset($actual[$file->id]);
	  }

	  foreach ( $actual as $id )
	    new delete($this->dbrw,"cpta_operation_files",array("id_op"=>$this->id,"id_file"=>$id));

	}


}


?>
