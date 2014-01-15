<?php
/**
 * Copyright 2008
 * - Manuel Vonthron  <manuel DOT vonthron AT acadis DOT org>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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

$VAL_GENERALE = array (
     '-1' => 'Sans avis',
      '0' => 'Nul',
      '1' => 'Pas terrible',
      '2' => 'Neutre',
      '3' => 'Bonne UV',
      '4' => 'Génial'
      );

$VAL_UTILITE = array(
     '-1' => 'Non renseigné',
      '0' => 'Inutile',
      '1' => 'Pas très utile',
      '2' => 'Utile',
      '3' => 'Très utile',
      '4' => 'Indispensable'
      );


$VAL_INTERET = array(
     '-1' => 'Non renseigné',
      '0' => 'Aucun',
      '1' => 'Faible',
      '2' => 'Bof',
      '3' => 'Intéressant',
      '4' => 'Passionnant'
      );

$VAL_ENSEIGNEMENT = array (
     '-1' => 'Sans avis',
      '0' => 'Inexistante',
      '1' => 'Mauvaise',
      '2' => 'Moyenne',
      '3' => 'Bonne',
      '4' => 'Excellente'
      );

$VAL_TRAVAIL = array (
     '-1' => 'Non renseigné',
      '0' => 'Symbolique',
      '1' => 'Faible',
      '2' => 'Moyenne',
      '3' => 'Importante',
      '4' => 'Très importante'
      );


/**
 * Représentation d'un commentaire à une UV
 * @ingroup stdentity
 * @author Manuel Vonthron
 * @author Pierre Mauduit
 */
class uv_comment extends stdentity
{
  var $id;
  var $id_uv; /* en general, uv_comment appele depuis une UV, donc a n'utiliser que dans les autres cas */
  var $id_utilisateur;

  /* notes entre 0 et 5 */
  var $note_generale;
  var $note_utilite;
  var $note_interet;
  var $note_enseignement;
  var $note_travail;

  var $content;

  var $date;
  // 0 : signalé, 1 : normal, 2 : validé par un modérateur
  var $valid;

  /**
   * eval_comment est une evaluation du commentaire
   * il ne s'agit pas de trier par nombres de votes des commentaires
   * mais de mettre en exergue des remarques jugée "au dessus du lot"
   * donc on propose un système de +/- mais sans afficher de note
   * mais si un ou deux commentaires ont des notes particulièrement
   * élevées, on les détache et on les met en avant.
   */
  var $eval_comment;

  public function load_by_id($id){
    $sql = new requete($this->db, "SELECT * FROM `pedag_uv_commentaire`
                                    WHERE `id_commentaire` = ".$id." LIMIT 1");
    if($sql->is_success())
      return $this->_load($sql->get_row());
    else
      return false;
  }

  public function _load($row){
    $this->id = $row['id_commentaire'];
    $this->id_uv = $row['id_uv'];
    $this->id_utilisateur = $row['id_utilisateur'];

    $this->note_generale = $row['note_generale'];
    $this->note_utilite = $row['note_utilite'];
    $this->note_interet = $row['note_interet'];
    $this->note_enseignement = $row['note_enseignement'];
    $this->note_travail = $row['note_travail'];

    $this->content = $row['content'];
    $this->date = $row['date'];
    $this->valid = $row['valid'];
    $this->eval_comment = $row['eval_comment'];

    return $this->id;
  }

  public function add($id_uv, $id_utilisateur,
                      $note_generale, $note_utilite, $note_interet, $note_enseignement, $note_travail,
                      $content){
    if(!uv::exists($this->db, $id_uv))
      throw new Exception("Invalid UV id ".$id_uv);

    if($date == null)
      $date = date("Y-m-d H:i:s");

    $data = array("id_uv" => intval($id_uv),
                  "id_utilisateur" => intval($id_utilisateur),
                  "note_generale" => intval($note_generale),
                  "note_utilite" => intval($note_utilite),
                  "note_interet" => intval($note_interet),
                  "note_enseignement" => intval($note_enseignement),
                  "note_travail" => intval($note_travail),
                  "content" => $content,
                  "date" => $date);

    $sql = new insert($this->dbrw, "pedag_uv_commentaire", $data);

    if($sql->is_success())
      return $this->load_by_id($sql->get_id());
    else
      return false;
  }

  public function update($id_uv=null, $id_utilisateur=null,
                      $note_generale=null, $note_utilite=null, $note_interet=null, $note_enseignement=null, $note_travail=null,
                      $content=null){

    if(func_num_args() < 1) return false;

    $data = array('valid'=>1);
    if($id_uv)          $data["id_uv"] = intval($id_uv);
    if($id_utilisateur) $data["id_utilisateur"] = intval($id_utilisateur);
    if($note_generale)  $data["note_generale"] = intval($note_generale);
    if($note_utilite)   $data["note_utilite"] = intval($note_utilite);
    if($note_interet)   $data["note_interet"] = intval($note_interet);
    if($note_enseignement)  $data["note_enseignement"] = intval($note_enseignement);
    if($note_travail)   $data["note_travail"] = intval($note_travail);
    if($content)        $data["content"] = $content;

    $sql = new update($this->dbrw, "pedag_uv_commentaire", $data, array("id_commentaire" => $this->id));
    return $sql->is_success();
  }

  public function remove(){
    $sql = new delete($this->dbrw, "pedag_uv_commentaire", array("id_commentaire" => $this->id));
    return $sql->is_success();
  }

  public function set_valid($val=1){
    $sql = new update($this->dbrw, "pedag_uv_commentaire",
                      array("valid" => $val),
                      array("id_commentaire" => $this->id));
    return $sql->is_success();
  }

  /**
   * Alias de la fonction update
   * destinee a deplacer un commentaire vers une autre UV notamment
   * si qqun a mis a une UV type XE03 alors qu'on souhaite les regrouper
   * dans LE03
   */
  public function move($id_new_uv){
    return $this->update($id_new_uv);
  }
}

?>
