<?
/* Copyright 2005,2006
 * - Jérémie Laval < jeremie dot laval at gmail dot com>
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

class trombino extends basedb
{
  var $id_utilisateur;
  var $autorisation;
  var $photo;
  var $famille;
  var $infos_personnelles;
  var $associatif;
  var $commentaires;

  public function trombino($db, $dbrw)
  {
    parent::basedb($db, $dbrw);
    $this->autorisation = $this->photo
      = $this->infos_personnelles = $this->famille = $this->associatif
      = $this->commentaires = false;
  }

  public function load_by_id ($id)
  {
    $req = new requete ($this->db,
                        "SELECT * FROM `utl_trombi` WHERE `id_utilisateur`='".intval($id)."'");

    if ($req->lines != 1)
      return false;

    $this->_load($req->get_row());

    return true;
  }

  public function _load($row)
  {
    $this->id_utilisateur = $row['id_utilisateur'];
    $this->autorisation = $row['autorisation'];
    $this->photo = $row['photo'];
    $this->infos_personnelles = $row['infos_personnelles'];
    $this->famille = $row['famille'];
    $this->associatif = $row['associatif'];
    $this->commentaires = $row['commentaires'];
  }

  public function create ($id)
  {
    $this->id_utilisateur = $id;
    $this->autorisation = $this->photo
      = $this->infos_personnelles = $this->famille = $this->associatif
      = $this->commentaires = true;

    $requete = new insert($this->dbrw, 'utl_trombi', array('id_utilisateur' => $this->id_utilisateur,
                                                           'autorisation' => $this->autorisation,
                                                           'photo' => $this->photo,
                                                           'infos_personnelles' => $this->infos_personnelles,
                                                           'famille' => $this->famille,
                                                           'associatif' => $this->associatif,
                                                           'commentaires' => $this->commentaires));
  }

  public function update ()
  {
    $requete = new update($this->dbrw, 'utl_trombi', array('autorisation' => $this->autorisation,
                                                           'photo' => $this->photo,
                                                           'infos_personnelles' => $this->infos_personnelles,
                                                           'famille' => $this->famille,
                                                           'associatif' => $this->associatif,
                                                           'commentaires' => $this->commentaires),
                          array('id_utilisateur' => $this->id_utilisateur));
  }
}

?>
