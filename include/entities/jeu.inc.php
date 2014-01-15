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
 * 02111-1307, USA.
 */

require_once($topdir."include/entities/objet.inc.php");

/**
 * Jeu de la bilbiothèqe / de l'inventaire
 *
 * (utilisé nottament pour le troll penché)
 * @see objet
 * @ingroup biblio
 * @author Julien Etelain
 */
class jeu extends objet
{
	/** Id de la série (pour les jeux de rôle) */
	var $id_serie;
  /** Etat du jeu */
  var $etat;
  /** Nb de joueurs (champ texte) */
  var $nb_joueurs;
  /** Durée moyenne d'une partie (champ texte) */
  var $duree;
  /** Langue du jeu (champ texte) */
  var $langue;
  /** Difficultée du jeu (champ texte) */
  var $difficulte;


	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT `inv_objet`.*, `inv_jeu`.* FROM `inv_objet`
				INNER JOIN `inv_jeu` ON `inv_jeu`.`id_objet`=`inv_objet`.`id_objet`
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
	 * $this->id est égal à null en cas d'erreur
	 * @param $id id de la fonction
	 */
	function load_by_cbar ( $cbar )
	{
		$req = new requete($this->db, "SELECT `inv_objet`.*, `inv_jeu`.* FROM `inv_objet`
				INNER JOIN `inv_jeu` ON `inv_jeu`.`id_objet`=`inv_objet`.`id_objet`
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

		$this->etat = $row['etat_jeu'];
		$this->nb_joueurs = $row['nb_joueurs_jeu'];
		$this->duree = $row['duree_jeu'];
		$this->langue = $row['langue_jeu'];
		$this->difficulte = $row['difficulte_jeu'];
		$this->_is_jeu = true;

		parent::_load($row);
	}

	/**
	 * Ajoute un jeu à l'inventaire
	 *
	 * @see objet::add
	 */
	function add_jeu ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$code_objtype, $num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes,
				$id_serie, $etat,$nb_joueurs,$duree,$langue,$difficulte )
	{

		parent::add($id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$code_objtype, $num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes );

		$this->id_serie = $id_serie;

		$this->etat = $etat;
		$this->nb_joueurs = $nb_joueurs;
		$this->duree = $duree;
		$this->langue = $langue;
		$this->difficulte = $difficulte;
		$this->_is_jeu = true;

		if ( $this->is_valid() )
		{
			$sql = new insert ($this->dbrw,
				"inv_jeu",
				array(
					"id_objet" => $this->id,
					"id_serie" => $this->id_serie,
          'etat_jeu' => $this->etat,
          'nb_joueurs_jeu' => $this->nb_joueurs,
          'duree_jeu' => $this->duree,
          'langue_jeu' => $this->langue,
          'difficulte_jeu' => $this->difficulte
					)
				);
		}
	}

	/**
	 * Modifie les informations sur le jeu
	 *
	 * @see objet::save_objet
	 */
	function save_jeu ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes,$cbar,
				$id_serie, $etat,$nb_joueurs,$duree,$langue,$difficulte  )
	{

		$this->save_objet ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $nom,
				$num_serie, $prix, $caution, $prix_emprunt, $empruntable,
				$en_etat, $date_achat, $notes,$cbar );

		$this->id_serie = $id_serie;
		$this->etat = $etat;
		$this->nb_joueurs = $nb_joueurs;
		$this->duree = $duree;
		$this->langue = $langue;
		$this->difficulte = $difficulte;


		$sql = new update ($this->dbrw,
			"inv_jeu",
			array(
				"id_serie" => $this->id_serie,
        'etat_jeu' => $this->etat,
        'nb_joueurs_jeu' => $this->nb_joueurs,
        'duree_jeu' => $this->duree,
        'langue_jeu' => $this->langue,
        'difficulte_jeu' => $this->difficulte

				),
			array("id_objet" => $this->id)
			);

	}



}

?>
