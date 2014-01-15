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
 */

/**
 * @file
 */

/**
 * Classe de gestion d'un tag
 * @see stdentity::set_tags_array
 * @see stdentity::set_tags
 * @see stdentity::get_tags_list
 * @see stdentity::get_tags
 */
class tag extends stdentity
{
  var $nom;
  var $modere;
  var $nombre;

  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `tag`
                                   WHERE `id_tag` = '" . mysql_real_escape_string($id) . "'
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
    $this->id = $row["id_tag"];
    $this->nom = $row["nom_tag"];
    $this->modere = $row["modere_tag"];
    $this->nombre = $row["nombre_tag"];
  }

  /**
   * Definit l'état de modération du tag
   * @param $modere Etat de moderation (true: modéré, false: non modéré)
   */
  function set_modere ( $modere=true )
  {
    $this->modere = $modere;
    new update($this->dbrw, "tag", array("modere_tag"=>$this->modere), array("id_tag"=>$this->id));
  }

  /**
   * Supprime le tag
   */
  function delete()
  {
    foreach ( $GLOBALS["entitiescatalog"] as $row )
    {
      if ( isset($row[6]) && !empty($row[6]) )
        new delete($this->dbrw, $row[6], array("id_tag"=>$this->id));
    }

    new delete($this->dbrw, "tag", array("id_tag"=>$this->id));
  }





}



?>
