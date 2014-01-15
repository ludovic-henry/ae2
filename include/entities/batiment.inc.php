<?php

/** @file
 * @defgroup inventaire Inventaire/Reservation salles/Reservation matériel
 * @{
 */

/**
 * Classe gérant les batiments
 */
class batiment extends stdentity
{
	/** @see sitebat */
	var $id_site;
	var $nom;
	var $fumeur;
	var $convention;
	var $notes;


	/** Charge un batiment en fonction de son id
	 * $this->id est égal à -1 en cas d'erreur
	 * @param $id id de la fonction
	 */
	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `sl_batiment`
				WHERE `id_batiment` = '" . mysql_real_escape_string($id) . "'
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
		$this->id			= $row['id_batiment'];
		$this->id_site		= $row['id_site'];
		$this->nom			= $row['nom_bat'];
		$this->fumeur		= $row['bat_fumeur'];
		$this->convention	= $row['convention_bat'];
		$this->notes			= $row['notes_bat'];
	}

	/** Ajoute un batiment et le charge dans l'instance
	 * @param $id_site Id du site sur le quel se trouve le batiment
	 * @param $nom Nom du batiment
	 * @param $fumeur (Booléen) Fumeur ou non
	 * @param $convention (Booléen) Convention de locaux requise
	 * @param $notes Notes (libres)
	 */
	function add ( $id_site, $nom, $fumeur, $convention, $notes )
	{
		$this->id_site		= $id_site;
		$this->nom			= $nom;
		$this->fumeur		= is_null($fumeur)?false:$fumeur;
		$this->convention	= is_null($convention)?false:$convention;
		$this->notes			= $notes;

		$sql = new insert ($this->dbrw,
			"sl_batiment",
			array(
				"id_site" => $this->id_site,
				"nom_bat" => $this->nom,
				"bat_fumeur" => $this->fumeur,
				"convention_bat" => $this->convention,
				"notes_bat" => $this->notes
				)
			);

		if ( $sql )
			$this->id = $sql->get_id();
		else
			$this->id = null;

	}

}

?>
