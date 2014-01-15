<?php
/**
 * Copyright 2008
 * - Manuel Vonthron  <manuel DOT vonthron AT acadis DOT org>
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

$UV_RELATION = array(
  CURSUS_FILIERE => array("simple étoilée(s)", "double étoilée(s)"),
  CURSUS_MINEUR => array("à choisir parmi...", "à obtenir"),
  CURSUS_AUTRE => array("secondaires", "principales")
);

/**
 * Représentation d'un cursus
 * @ingroup stdentity
 * @author Manuel Vonthron
 */
class cursus extends stdentity
{
  var $id;
  var $intitule;
  var $name;
  var $type;
  var $description;
  var $responsable;
  var $departement;
  var $closed;

  var $nb_all_of;
  var $nb_some_of;
  var $uv_all_of=array();
  var $uv_some_of=array();

  public function load_by_id($id){
    $sql = new requete($this->db, "SELECT *, `departement`+0 as `departement`, `type`+0 as `type` FROM `pedag_cursus` WHERE `id_cursus` = ".$id." LIMIT 1");
    if(!$sql->is_success())
      return false;

    $this->_load($sql->get_row());

    $sql = new requete($this->db, "SELECT * FROM `pedag_uv_cursus` WHERE `id_cursus` = ".$this->id);
    if(!$sql->is_success())
      return false;

    while($row = $sql->get_row())
      if($row['relation'] == 'ALL_OF')
        $this->uv_all_of[] = $row['id_uv'];
      else
        $this->uv_some_of[] = $row['id_uv'];

    return $this->id;
  }

  public function _load($row){
    $this->id = $row['id_cursus'];
    $this->intitule = $row['intitule'];
    $this->name = $row['name'];
    $this->type = $row['type'];
    $this->departement = $row['departement'];
    $this->responsable = $row['responsable'];
    $this->description = $row['description'];
    $this->closed = $row['closed'];

    $this->nb_some_of = $row['nb_some_of'];
    $this->nb_all_of = $row['nb_all_of'];

    return $this->id;
  }

  public function add($intitule, $type, $name=null, $description=null, $responsable=null, $nb_some_of=null, $nb_all_of=null, $departement=null){
    if($nb_some_of)
      $nb_some_of = null;
    if($nb_all_of)
      $nb_all_of = null;

    $data = array("type" => $type,
                  "intitule" => ($intitule));
    if(!is_null($responsable)) $data["responsable"] = $responsable;
    if(!is_null($name))        $data["name"] = $name;
    if(!is_null($nb_some_of))  $data["nb_some_of"] = $nb_some_of;
    if(!is_null($nb_all_of))   $data["nb_all_of"] = $nb_all_of;
    if(!is_null($departement)) $data["departement"] = $departement;

    $sql = new insert($this->dbrw, "pedag_cursus", $data);
    if($sql->is_success())
      return $this->load_by_id($sql->get_id());
    else
      return false;
  }

  /**
   * finalement on authorise pas la suppression
   * mais on passe un flag 'closed' a true
   */
  public function set_closed($var=true){
    $sql = new update($this->dbrw, "pedag_cursus",
                      array("id_cursus" => $this->id),
                      array("closed" => $val));
    return $sql->is_success();
  }

  public function update($intitule=null, $name=null, $type=null, $departement=null, $description=null, $responsable=null, $nb_some_of=null, $nb_all_of=null){
    $data = array();
    if(!is_null($type)) $data["type"] = $type;
    if(!is_null($departement)) $data["departement"] = $departement;
    if($intitule) $data["intitule"] = ($intitule);
    if($name) $data["name"] = ($name);
    if($description) $data["description"] = ($description);
    if($responsable) $data["responsable"] = ($responsable);
    if(!is_null($nb_some_of)) $data["nb_some_of"] = intval($nb_some_of);
    if(!is_null($nb_all_of)) $data["nb_all_of"] = intval($nb_all_of);

    $sql = new update($this->dbrw, "pedag_cursus", $data, array("id_cursus" => $this->id));
    return $sql->is_success();
  }

  /**
   * Ajout d'une UV au cursus
   * @param $id_uv UV a ajouter
   * @param $relation (SOME_OF|ALL_OF)
   */
  public function add_uv($id_uv, $relation){
    if(uv::exists($this->db, $id_uv))
      $sql = new insert($this->dbrw, "pedag_uv_cursus", array("id_uv" => $id_uv, "id_cursus" => $this->id, "relation" => $relation));
    return $sql->is_success();
  }

  public function remove_uv($id_uv){
    $sql = new delete($this->dbrw, "pedag_uv_cursus", array("id_uv" => $id_uv, "id_cursus" => $this->id));
    return $sql->is_success();
  }

  public function get_uv_list($relation=null){
    $req = "SELECT `pedag_uv_cursus`.* , `pedag_uv`.`code`  , `pedag_uv`.`intitule`, `pedag_uv`.`guide_credits`
            FROM `pedag_uv_cursus`
            LEFT JOIN `pedag_uv`
              ON `pedag_uv`.`id_uv` = `pedag_uv_cursus`.`id_uv`
            WHERE `id_cursus` = ".$this->id;

    if(!is_null($relation)){
      $relation = strtoupper($relation);
      if($relation != 'SOME_OF' && $relation != 'ALL_OF')
        return;
      else
        $req .= " AND `relation` = '".$relation."'";
    }

    $sql = new requete($this->db, $req);
    if(!$sql->is_success())
      return false;
    else{
      $t=array();
      while($row = $sql->get_row())
        $t[] = $row;

      return $t;
    }
  }

  public function get_nb_students($ignore_graduated=false){
  }

  /**
   * recuperation de la liste des cursus enregistres
   * @param &$db lien vers la BDD ro
   * @param $dept filtre sur le departement @see $_DPT
   * @param $type filtre sur le type de cursus @see $_CURSUS
   * @param $ignore_closed filtre sur les cursus fermes ou non
   */
  public static function get_list(&$db, $dept=null, $type=null, $ignore_closed=false){
    $req = "SELECT *, `departement`+0 as `departement`, `type`+0 as `type` FROM `pedag_cursus`";
    $where = false;

    if(!is_null($dept)){
      $req .= ($where?" AND":" WHERE")." `departement` = ".$dept;
      $where = true;
    }

    if(!is_null($type)){
      $req .= ($where?" AND":" WHERE")." `type` = ".$type;
      $where = true;
    }

    if($ignore_closed === false){
      $req .= ($where?" AND":" WHERE")." `closed` = 0";
      $where = true;
    }

    $req .= " ORDER BY `departement`, `type`, `intitule`";

    $sql = new requete($db, $req);

    if(!$sql->is_success())
      return false;
    else{
      $t=array();
      while($row = $sql->get_row())
        $t[] = $row;

      return $t;
    }
  }
}
?>
