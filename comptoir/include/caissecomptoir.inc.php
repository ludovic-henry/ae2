<?php

/* Copyright 2006,2008
 * - Mathieu Briand <briandmathieu CHEZ hyprua POINT org>
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


class CaisseComptoir extends stdentity
{
  /* Id du releve */
  var $id;
  /* Id du vendeur */
  var $id_utilisateur;
  /* Id du comptoir */
  var $id_comptoir;
  /* date du relevé */
  var $date_releve;
  /* valeurs en espèce */
  var $especes = array();
  /* valeurs en chèques */
  var $cheques = array();
  /* caisse vidée après le compte */
  var $caisse_videe;
  /* commentaire */
  var $commentaire;


  /**
   * Charge le relevé en fonction de son ID
   * @param $id Id du relevé
   */
  function load_by_id ( $id )
  {

    $req = new requete($this->db,
      "SELECT * FROM `cpt_caisse` WHERE `id_cpt_caisse` = '".intval($id)."'"
      );

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
    $this->id = $row['id_cpt_caisse'];
    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_comptoir = $row['id_comptoir'];
    $this->date_releve = strtotime($row['date_releve']);
    $this->caisse_videe = $row['caisse_videe'];
    $this->commentaire = $row['commentaire'];

    $req = new requete($this->db,
      "SELECT * FROM cpt_caisse_sommes WHERE `id_cpt_caisse` = '".intval($this->id)."'"
      );

    while ($row = $req->get_row())
    {
      if ($row['cheque_caisse'] == 1)
        $this->cheques[$row['valeur_caisse']] = $row['nombre_caisse'];
      else
        $this->especes[$row['valeur_caisse']] = $row['nombre_caisse'];
    }
  }

  function ajout($id_utilisateur,
      $id_comptoir,
      $especes,
      $cheques,
      $caisse_videe,
      $commentaire)
  {
    $this->id_utilisateur = $id_utilisateur;
    $this->id_comptoir = $id_comptoir;
    $this->date_releve = time();
    $this->especes = $especes;
    $this->cheques = $cheques;
    $this->caisse_videe = $caisse_videe;
    $this->commentaire = $commentaire;

    $req = new insert ($this->dbrw,
          "cpt_caisse",
          array("id_utilisateur" => $this->id_utilisateur,
            "id_comptoir" => $this->id_comptoir,
            "caisse_videe" => ($this->caisse_videe) ? '1' : '0',
            "date_releve" => date("Y-m-d H:i:s",$this->date_releve),
            "commentaire" => $this->commentaire,
            ));

    if ( !$req )
      return false;

    $this->id = $req->get_id();

    foreach ($this->especes as $valeur => $nombre)
    {
      $req = new insert ($this->dbrw,
            "cpt_caisse_sommes",
            array("id_cpt_caisse" => $this->id,
              "valeur_caisse" => $valeur,
              "nombre_caisse" => $nombre,
              "cheque_caisse" => 0,
              ));

      if ( !$req )
        $err = true;
    }
    foreach ($this->cheques as $valeur => $nombre)
    {
      $req = new insert ($this->dbrw,
            "cpt_caisse_sommes",
            array("id_cpt_caisse" => $this->id,
              "valeur_caisse" => $valeur,
              "nombre_caisse" => $nombre,
              "cheque_caisse" => 1,
              ));

      if ( !$req )
        $err = true;
    }

    if (isset($err) && $err)
    {
      new delete($this->dbrw,"cpt_caisse_sommes",array("id_cpt_caisse" => $this->id));
      new delete($this->dbrw,"cpt_caisse",array("id_cpt_caisse" => $this->id));
      $this->id = null;
      return false;
    }

    return true;
  }

  /**
   * Marque qu'un passage à la banque à été effectué
   * @param $date Date du passage
   */
  function passage_banque($date)
  {
    if (is_null($date))
      $date = date("Y-m-d H:i:s");
    else
      $date = date("Y-m-d H:i:s",$date);

    $req = new insert ($this->dbrw,
          "cpt_caisse_banque",
          array("date_passage" => $date,)
          );
  }

  function update_comment($commentaire)
  {
    $this->commentaire = $commentaire;
    $req = new update ($this->dbrw,
          "cpt_caisse",
          array("commentaire" => $this->commentaire,),
          array("id_cpt_caisse" => $this->id)
        );
  }
}

/**@}*/
?>
