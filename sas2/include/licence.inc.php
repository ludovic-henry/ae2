<?php
/* Copyright 2009
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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
require_once($topdir."include/entities/std.inc.php");


/**
 * Licences pour les photos
 * @ingroup sas
 * @author Simon Lopez
 */
class licence extends stdentity
{

  var $id;
  var $title;
  var $desc;
  var $url=null;
  var $icone=null;

  /** Charge une licence par son ID
   * @param $id ID de le licence
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `licences`
        WHERE `id_licence` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }
    $this->id = null;
    return false;
  }

  function _load($row)
  {
    $this->id    = $row['id_licence'];
    $this->titre = $row['titre'];
    $this->desc  = $row['description'];
    $this->url   = $row['url'];
    $this->icone = $row['icone'];
  }

  function update($titre,$desc,$url,$icone)
  {
    $this->title = $titre;
    $this->desc  = $desc;
    $this->url   = $url;
    if(empty($icone))
      $this->icone = null;
    else
      $this->icone = $icone;
    new update ( $this->dbrw,
                 'licences',
                 array('titre'       => $this->title,
                       'description' => $this->desc,
                       'url'         => $this->url,
                       'icone'       => $this->icone),
                 array("id_licence"=>$this->id));
  }
 
  function add($titre,$desc,$url,$icone)
  {
    $this->title = $titre;
    $this->desc  = $desc;
    $this->url   = $url;
    $this->icone = $icone;
    $req = new insert($this->dbrw,
                      'licences',
                      array('titre'       => $this->title,
                            'description' => $this->desc,
                            'url'         => $this->url,
                            'icone'       => $this->icone));
    if ($req)
      $this->id = $req->get_id();
    else
    {
      $this->id = -1;
      return false;
    }
    return true;
  }

}

?>
