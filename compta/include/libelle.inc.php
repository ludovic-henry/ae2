<?php
/* Copyright 2006
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
 * Etiquette à associer à une opération pour classer ses dernières.
 * @ingroup compta
 */
class compta_libelle extends stdentity
{
  /** Id de l'activité/association associé */
  var $id_asso;
  /** Nom de l'etiquette */
  var $nom;

  /** Charge une etiquette en fonction de son id
   * En cas d'erreur, l'id est défini à null
   * @param $id id de l'etiquette
   * @return true en cas de succès, false sinon
   */
	function load_by_id ( $id )
	{
		$req = new requete ($this->db, "SELECT * FROM `cpta_libelle`
							WHERE id_libelle='".intval($id)."'");

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
			$this->id = $row['id_libelle'];
			$this->id_asso = $row['id_asso'];
			$this->nom = $row['nom_libelle'];
  }


  function add_libelle ( $id_asso, $nom )
  {
    $this->nom = $nom;
    $this->id_asso = $id_asso;

    $req = new insert ($this->dbrw,
		       "cpta_libelle",
		       array(
		        "id_asso" => $this->id_asso,
		        "nom_libelle" => $this->nom
		        ));

    if ( $sql )
      $this->id = $sql->get_id();
    else
    {
      $this->id = null;
      return;
    }

  }

  function update_libelle ( $nom )
  {
    $this->nom = $nom;

    $req = new update ($this->dbrw,
		       "cpta_libelle",
		       array("nom_libelle" => $nom),
		       array("id_libelle" => $this->id));

    if ( !$req )
      return false;

    return true;
  }

  function remove_libelle ()
  {
    new delete ($this->dbrw,"cpta_libelle",array("id_libelle" => $this->id));
  }


}

?>
