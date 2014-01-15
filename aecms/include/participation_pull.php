<?php
/* Copyright 2009
 * - Jérémie Laval < jeremie dot laval at gmail dot com >
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

class participation extends basedb
{
  public $id;

  // Infos personelles
  var $nom;
  var $prenom;
  var $date_de_naissance;
  var $email;
  var $telephone;
  var $universite;
  var $position;

  var $adresse_rue;
  var $adresse_additional;
  var $adresse_ville;
  var $adresse_codepostal;

  var $contribution_nom;
  var $contribution_parent;
  var $contribution_siteweb;
  var $contribution_depot;
  var $contribution_description;

  public function add_participation ()
  {
    $req = new insert($this->dbrw, 'pull_participations',
                      array('nom' => $this->nom,
                            'prenom' => $this->prenom,
                            'date_de_naissance' => date("Y-m-d", $this->date_de_naissance),
                            'email' => $this->email,
                            'telephone' => $this->telephone,
                            'adresse_rue' => $this->adresse_rue,
                            'adresse_additional' => $this->adresse_additional,
                            'adresse_ville' => $this->adresse_ville,
                            'adresse_codepostal' => $this->adresse_codepostal,
                            'contribution_nom' => $this->contribution_nom,
                            'contribution_parent' => $this->contribution_parent,
                            'contribution_siteweb' => $this->contribution_siteweb,
                            'contribution_depot' => $this->contribution_depot,
                            'contribution_description' => $this->contribution_description,
                            'univ' => $this->universite,
                            'role_univ' => $this->position));

    if (!$req->is_success())
      return false;

    $id = $req->get_id ();

    return true;
  }

  public function load_by_id ($id)
  {
    $req = new requete ($this->db,
                        "SELECT * FROM `pull_participations` WHERE `id_participation`='".intval($id)."'");

    if ($req->lines != 1)
      return false;

    $this->_load($req->get_row());

    return true;
  }

  public function _load ($row)
  {
    $this->id = $row['id_participation'];
    $this->nom = $row['nom'];
    $this->prenom = $row['prenom'];
    $this->date_de_naissance = $row['date_de_naissance'];
    $this->email = $row['email'];
    $this->telephone = $row['telephone'];
    $this->adresse_rue = $row['adresse_rue'];
    $this->adresse_additional = $row['adresse_additional'];
    $this->adresse_ville = $row['adresse_ville'];
    $this->adresse_codepostal = $row['adresse_codepostal'];
    $this->contribution_nom = $row['contribution_nom'];
    $this->contribution_parent = $row['contribution_parent'];
    $this->contribution_siteweb = $row['contribution_siteweb'];
    $this->contribution_depot = $row['contribution_depot'];
    $this->contribution_description = $row['contribution_description'];
    $this->universite = $row['univ'];
    $this->position = $row['role_univ'];
  }

}

?>