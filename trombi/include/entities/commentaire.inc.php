<?php
/*
 * FORUM2
 *
 * Copyright 2007
 * - Nicolas Demengel < maitre dot poireau at gmail dot com >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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
 * Gestion des commentaires de trombi
 */


/**
 * Commentaire dans le trombi d'une promo
 */
class commentaire extends stdentity
{

  var $id_commente;
  var $id_commentateur;
  var $commentaire;
  var $date;
  var $modere;
  var $id_utilisateur_moderateur;

  function load_by_couple ( $id_commente, $id_commentateur )
  {
    $req = new requete($this->db, "SELECT * FROM `trombi_commentaire`
              WHERE `id_commente` = '" .
            mysql_real_escape_string($id_commente) . "'
              AND `id_commentateur` = '" .
            mysql_real_escape_string($id_commentateur) . "'
              LIMIT 1"
           );

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `trombi_commentaire`
				WHERE `id_commentaire` = '" .
		       mysql_real_escape_string($id) . "'
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
    $this->id = $row['id_commentaire'];
    $this->id_commente = $row['id_commente'];
    $this->id_commentateur = $row['id_commentateur'];
    $this->commentaire = $row['commentaire'];
    $this->date = strtotime($row['date_commentaire']);
    $this->modere = $row['modere_commentaire'];
    $this->id_utilisateur_moderateur = $row['id_utilisateur_moderateur'];
  }

  function get_contents ()
  {
    $cts = new contents();

    $cts->add(new wikicontents(false,$this->commentaire));

    return $cts;
  }

  function comment_exists ($id_commente, $id_commentateur)
  {
    $req = new requete($this->db, "SELECT id_commentaire FROM `trombi_commentaire`
				WHERE `id_commente` = '" .
		       mysql_real_escape_string($id_commente) . "'
                AND `id_commentateur` = '" .
               mysql_real_escape_string($id_commentateur) . "'
				LIMIT 1");

    return ( $req->lines == 1 );
  }

  function create ( $id_commente, $id_commentateur, $commentaire )
  {
    if ( !$this->dbrw )
      return false;

    if ( $this->comment_exists($id_commente, $id_commentateur) )
      return false;

    $this->id_commente = $id_commente;
    $this->id_commentateur = $id_commentateur;
    $this->commentaire = $commentaire;
    $this->date = time();
    $this->modere = false;
    $this->id_utilisateur_moderateur = null;

    $req = new insert ($this->dbrw,
             "trombi_commentaire", array(
               "id_commente"=>$this->id_commente,
               "id_commentateur"=>$this->id_commentateur,
               "commentaire"=>$this->commentaire,
               "date_commentaire"=>date("Y-m-d H:i:s",$this->date),
               "modere_commentaire"=>$this->modere,
               "id_utilisateur_moderateur"=>$this->id_utilisateur_moderateur
             )
           );

    if ( $req )
    {
      $this->id = $req->get_id();
      return true;
     }

    $this->id = null;
    return false;
  }

  function update ( $commentaire )
  {
    if ( !$this->dbrw )
      return;

    $this->commentaire = $commentaire;
    $this->id_utilisateur_moderateur = null;
    $req = new update ($this->dbrw,
             "trombi_commentaire", array(
               "commentaire"=>$this->commentaire,
               "id_utilisateur_moderateur"=>$this->id_utilisateur_moderateur
             ),
             array("id_commentaire"=>$this->id)
           );
  }

  function set_modere ( $id_moderateur )
  {
    if ( !$this->dbrw )
      return;

    $this->modere = !$this->modere;
    if ( $this->modere )
      $this->id_utilisateur_moderateur = $id_moderateur;
    else
      $this->id_utilisateur_moderateur = null;

    $req = new update ($this->dbrw,
             "trombi_commentaire",
             array(
               "modere_commentaire"=>$this->modere,
               "id_utilisateur_moderateur"=>$this->id_utilisateur_moderateur),
             array("id_commentaire"=>$this->id)
           );
  }

  function delete ()
  {
    if ( !$this->dbrw )
      return;

    new delete($this->dbrw,
      "trombi_commentaire",
      array("id_commentaire"=>$this->id)
    );

    $this->id = null;
  }

}

?>
