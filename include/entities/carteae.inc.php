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
 * Gestion des cartes AE
 */

define("CETAT_ATTENTE",0);
define("CETAT_EN_PRODUCTION",2);
define("CETAT_IMPRIMEE",4);
define("CETAT_AU_BUREAU_AE",6);
define("CETAT_CIRCULATION",8);
define("CETAT_EXPIRE",10);
define("CETAT_PERDUE",12);
define("CETAT_VOLEE",14);

$EtatsCarteAE = array (
	CETAT_ATTENTE       => "Attente de fabrication",
	CETAT_EN_PRODUCTION => "En fabrication",
	CETAT_IMPRIMEE      => "Fabriquée, en cours d'acheminement",
	CETAT_AU_BUREAU_AE  => "Au bureau AE (GM,TC: Sévenans; EDIM : Montbéliard; IMAP,GI,GESC,Autres: Belfort)",
	CETAT_CIRCULATION   => "En circulation",
	CETAT_EXPIRE        => "Expirée",
	CETAT_PERDUE        => "Perdue",
	CETAT_VOLEE         => "Volée"
	);

/**
 * Une carte AE
 * @author Julien Etelain
 * @see cotisation
 */
class carteae extends stdentity
{
  /** Id de la cotisation rattachée à cette carte */
	var $id_cotisation;
	/** Etat de la carte
	 * @see $EtatsCarteAE
	 */
	var $etat_vie_carte;
	/** Date d'expiration de la carte (timestamp),
	 * corresponds à la fin de la cotisation associée
	 */
	var $date_expiration;
	/** Lettre complémentaire au numéro de la carte pour éviter les erreurs de saisie */
  var $cle;


	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `ae_carte`
				WHERE `id_carte_ae` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
		{
			$this->_load($req->get_row());
			return true;
		}

		$this->id = null;
		return false;
	}

	/** Charge une carte en fonction de son code barre
	 * $this->id est égal à null en cas d'erreur
	 * @param $num code barre de la carte, ou numéro + clé, ou numéro
   * @return true en cas de succès, false sinon
	 */
	function load_by_cbarre ( $num )
	{
	  if ( ereg("^([0-9]+)([a-zA-Z]{1})$", $num, $regs) )
	  {
	    if ( !$this->load_by_id($regs[1]) )
	      return false;

	    if ( strtoupper($this->cle) != strtoupper($regs[2]) ) // Verifie la cle de contrôle
	    {
	      $this->id=null;
	      $this->id_cotisation=null;
	      return false;
	    }
	    return true;
	  }
	  elseif ( ereg("^([0-9]+) ([a-zA-Z\\-]{1,6})\\.([a-zA-Z\\-]{1,6})$", $num, $regs) )
	  {
	    return $this->load_by_id($regs[1]);
	  }

	  return $this->load_by_id(intval($num));
	}

	/** Charge une carte en fonction de l'id de son propriétaire
	 * $this->id est égal à null en cas d'erreur
	 * @param $id id de l'utilisateur
	 * @return true en cas de succès, false sinon
	 */
	function load_by_utilisateur ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `ae_carte` " .
				"INNER JOIN `ae_cotisations` ON `ae_cotisations`.`id_cotisation`=`ae_carte`.`id_cotisation` " .
				"WHERE `ae_cotisations`.`id_utilisateur` = '" . mysql_real_escape_string($id) . "' " .
				"AND `ae_carte`.`etat_vie_carte_ae`<=".CETAT_EXPIRE." " .
				"ORDER BY `ae_carte`.`date_expiration` DESC ".
				"LIMIT 1");

		if ( $req->lines == 1 )
			$this->_load($req->get_row());
		else
			$this->id = null;
	}

	/**
	 * Détermine si la carte chargée est valide
	 * @return true si la carte est valide, false sinon
	 */
	function is_validcard()
	{
		return !is_null($this->id) && ($this->etat_vie_carte == CETAT_CIRCULATION && $this->date_expiration >= date('Y-m-d'));
	}

	function _load ( $row )
	{
		$this->id				= $row['id_carte_ae'];
		$this->id_cotisation		= $row['id_cotisation'];
		$this->etat_vie_carte		= $row['etat_vie_carte_ae'];
		$this->date_expiration	= $row['date_expiration'];
		$this->cle	= $row['cle_carteae'];
	}

  /**
   * Crée une nouvelle carte AE
   * @param $id_cotisation Id de la cotisantion
   * @param $expire Date d'expiration (timestamp)
   */
	function add ( $id_cotisation, $expire )
	{
		$this->id_cotisation = $id_cotisation;
		$this->etat_vie_carte = CETAT_ATTENTE;
		$this->date_expiration = $expire;
    $this->cle = chr(ord('A')+rand(0,25));

		$sql = new insert ($this->dbrw,
			"ae_carte",
			array(
				"id_cotisation" => $this->id_cotisation,
				"etat_vie_carte_ae" => $this->etat_vie_carte,
				"date_expiration" => date("Y-m-d",$this->date_expiration),
				"cle_carteae" => $this->cle
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

  /**
   * Prolonge le carte en l'association à une autre cotisation.
   * @param $id_cotisation Id de la nouvelle cotisantion
   * @param $expire Nouvelle date d'expiration (timestamp)
   */
	function prolongate ( $id_cotisation, $expire )
	{
		$this->id_cotisation = $id_cotisation;
		$this->etat_vie_carte = CETAT_ATTENTE;
		$this->date_expiration = $expire;

		$sql = new update ($this->dbrw,
			"ae_carte",
			array(
				"id_cotisation" => $this->id_cotisation,
				"etat_vie_carte_ae" => $this->etat_vie_carte,
				"date_expiration" => date("Y-m-d",$this->date_expiration)
				),
			array(
				"id_carte_ae"=>$this->id
				)
			);

	}

  /**
   * Modifie l'etat de la carte
   * @param $state Nouvel etat de la carte
	 * @return true en cas de succès, false sinon
   */
	function set_state ( $state )
	{

		$this->etat_vie_carte = $state;

		$verif_state = new requete($this->db,"SELECT * FROM `ae_carte` WHERE `id_carte_ae` = '".intval($this->id)."' AND `etat_vie_carte_ae` = '".intval($state)."' LIMIT 1");

		if ($verif_state->lines == 0)
		{
		$sql = new update ($this->dbrw,
			"ae_carte",
			array(
				"etat_vie_carte_ae" => $this->etat_vie_carte,
				),
			array(
				"id_carte_ae"=>$this->id
				)
			);
			return TRUE;
		}
		else
			return FALSE;

	}


}

?>
