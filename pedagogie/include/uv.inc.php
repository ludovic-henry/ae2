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


/**
 * Représentation atomique d'une UV à l'UTBM
 * @ingroup stdentity
 * @author Manuel Vonthron
 * @author Pierre Mauduit
 */

class uv extends stdentity
{
  /* basic infos */
  var $id;
  var $code;
  var $intitule;
  var $type;
  var $dept = array();
  var $state;
  var $tc_available;
  var $semestre;
  var $credits;
  /* extra infos */
  var $extra_loaded = false;
  var $guide = array("objectifs" => null,
                     "programme" => null,
                     "c" => null,
                     "td" => null,
                     "tp" => null,
                     "the" => null);
  var $responsable = null;
  var $antecedent = array();
  var $nb_comments = null;
  var $alias_of = null;
  var $aliases = array();
  var $cursus = array();

  var $comments = null;

  /**
   * chargement d'une UV par son id dans la BDD
   * n'initialise que les principaux attributs (code, intitulé, ...)
   * @param $id Id de l'UV
   * @return id/false selon le résultat
   * @see load_extra()
   */
  public function load_by_id($id){
    $sql = new requete($this->db, "SELECT `id_uv` as `id`, `code`, `intitule`,
                                    `semestre`+0 as `semestre`, `state`, `tc_available`,
                                    `type`+0 as `type`, `guide_credits`
                                    FROM `pedag_uv`
                                    WHERE `id_uv` = ".$id." LIMIT 1");
    if($sql->is_success()){
      $this->_load($sql->get_row());
      return $this->id;
    }else{
      $this->id = -1;
      return false;
    }
  }

  /**
   * fonction batarde qui sert a charger une UV depuis l'id
   * d'un de ses groupes de C/TD/TP
   * @param $id identifiant d'un groupe (table pedag_groupe)
   * @see pedagogie/groupe.php
   */
  public function load_by_group_id($id){
    $sql = new requete($this->db, "SELECT `id_uv` FROM `pedag_groupe` WHERE `id_groupe` = ".intval($id));
    if($sql->is_success() && $sql->lines == 1){
      $row = $sql->get_row();
      return $this->load_by_id($row['id_uv']);
    }else
      return false;
  }

  /**
   * chargement d'une UV a partir de son code UTBM (ex RE41)
   * @param $code code de l'UV
   * @return id/false selon le résultat
   * @see load_extra()
   */
  public function load_by_code($code){
    if(!check_uv_format($code))
      return false;

    $sql = new requete($this->db, "SELECT `id_uv` as `id`, `code`, `intitule`,
                                    `semestre`, `state`, `tc_available`,
                                    `guide_credits`
                                    FROM `pedag_uv`
                                    WHERE `code` = '".$code."' LIMIT 1");
    if($sql->is_success()){
      $this->_load($sql->get_row());
      return $this->id;
    }else{
      $this->id = -1;
      return false;
    }
  }

  /* chargement effectif infos basiques */
  public function _load($row){
    $this->id = $row['id'];
    $this->code = $row['code'];
    $this->nom = $this->code; /* compatibilite stdentity */
    $this->intitule = $row['intitule'];
    $this->type = $row['type'];
    $this->semestre = $row['semestre'];
    $this->state = $row['state'];
    $this->tc_available = $row['tc_available'];
    $this->credits = $row['guide_credits'];

    return $this->id;
  }

  /**
   * charge les informations complementaires, susceptibles d'etre utiles
   * dans une presentation "guide" plutot que liste succinte
   * ex: departements, credits, tags, ...
   */
  public function load_extra(){
    $sql = new requete($this->db, "SELECT `responsable`,
                                    `guide_objectifs`, `guide_programme`,
                                    `guide_c`, `guide_td`, `guide_tp`, `guide_the`
                                    FROM `pedag_uv`
                                    WHERE `id_uv` = ".$this->id." LIMIT 1");
    if($sql->is_success()){
      $row = $sql->get_row();

      $this->responsable = $row['responsable'];
      $this->guide['objectifs'] = $row['guide_objectifs'];
      $this->guide['programme'] = $row['guide_programme'];
      $this->guide['c'] = $row['guide_c'];
      $this->guide['td'] = $row['guide_td'];
      $this->guide['tp'] = $row['guide_tp'];
      $this->guide['the'] = $row['guide_the'];
    }
    /* chargement des antecedents */
    $sql = new requete($this->db, "SELECT * FROM `pedag_uv_antecedent`
                                    WHERE `id_uv_source` = ".$this->id);
    if($sql->is_success())
      while($row = $sql->get_row())
        $this->antecedent[] = array("id_cible" => $row['id_uv_cible'],
                                    "obligatoire" => $row['obligatoire'],
                                    "commentaire" => $row['commentaire']);

    /* chargement alias */
    $sql = new requete($this->db, "SELECT `pedag_uv_alias`.*, `cible`.`code` as `code_cible`, `source`.`code` as `code_source`
                                    FROM `pedag_uv_alias`
                                    LEFT JOIN `pedag_uv` as `cible`
                                      ON `pedag_uv_alias`.`id_uv_cible` = `cible`.`id_uv`
                                    LEFT JOIN `pedag_uv` as `source`
                                      ON `pedag_uv_alias`.`id_uv_source` = `source`.`id_uv`
                                    WHERE `id_uv_source` = ".$this->id."
                                      OR `id_uv_cible` = ".$this->id);
    if($sql->is_success()){
      while ($row = $sql->get_row()){
        if($row['id_uv_cible'] == $this->id)
          $this->aliases[] = array("id" => $row['id_uv_source'],
                                   "code" => $row['code_source'],
                                   "commentaire" => $row['commentaire']);
        else
          $this->alias_of = array("id" => $row['id_uv_cible'],
                                   "code" => $row['code_cible'],
                                   "commentaire" => $row['commentaire']);

      }
    }

    /* chargement nb commentaires */
    $sql = new requete($this->db, "SELECT COUNT(*) as `nb_comments`
                                    FROM `pedag_uv_commentaire`
                                    WHERE `id_uv` = ".$this->id);
    if($sql->is_success()){
      $row = $sql->get_row();
      $this->nb_comments = $row['nb_comments'];
    }

    /* chargement cursus */
    $sql = new requete($this->db, "SELECT `id_cursus`
                                    FROM `pedag_uv_cursus`
                                    WHERE `id_uv` = ".$this->id);
    if($sql->is_success())
      while($row = $sql->get_row());
        $this->cursus[] = $row['id_cursus'];

    $this->load_dept();

    $this->extra_loaded = true;
  }

  /**
   * Ajout d'une UV
   */
  public function add($code, $intitule, $type, $responsable=null, $semestre, $tc_available=true){
    if(!check_uv_format($code))
      throw new Exception("Wrong format \$code ".$code);
    /* verification qu elle n existe pas deja, avec le code */
    if(uv::exists($this->db, $code))
      throw new Exception("UV code already used in database");

    $sql = new insert($this->dbrw, "pedag_uv",
                      array("code" => $code,
                            "intitule" => mysql_real_escape_string($intitule),
                            "type" => $type,
                            "semestre" => $semestre,
                            "responsable" => mysql_real_escape_string($responsable),
                            "state" => STATE_PENDING,
                            "tc_available" => (bool) $tc_available));
    if($sql->is_success())
      return $this->load_by_id($sql->get_id());
    else
      return false;
  }

  /* mise a jour des infos */
  public function update($code=null, $intitule=null, $type=null, $responsable=null, $semestre=null, $tc_available=null){
    $data = array();
    if($code)     $data['code'] = $code;
    if($intitule) $data['intitule'] = $intitule;
    if(isset($type))  $data['type'] = $type;
    if($responsable)  $data['responsable'] =  $responsable;
    if($semestre)     $data['semestre'] = $semestre;
    if(isset($tc_available)) $data['tc_available'] = $tc_available;
    $data['state'] = STATE_MODIFIED;

    $sql = new update($this->dbrw, "pedag_uv", $data, array("id_uv"=>$this->id));
    return $sql->is_success();
  }

  /* separation des infos du guide pour ne pas alourdir la fonction de creation */
  public function update_guide_infos($objectifs=null, $programme=null, $c=null, $td=null, $tp=null, $the=null, $credits=null){
    $data = array();
    if(isset($objectifs)) $data['guide_objectifs'] = $objectifs;
    if(isset($programme)) $data['guide_programme'] = $programme;
    if(isset($credits))   $data['guide_credits'] = $credits;
    if(isset($c))  $data['guide_c'] =  $c;
    if(isset($td)) $data['guide_td'] = $td;
    if(isset($tp)) $data['guide_tp'] = $tp;
    if(isset($the))$data['guide_the'] = $the;
    $data['state'] = STATE_MODIFIED;

    $sql = new update($this->dbrw, "pedag_uv", $data, array("id_uv"=>$this->id));
    return $sql->is_success();
  }

  public function add_or_update($code=null, $intitule=null, $type=null, $responsable=null, $semestre=null, $tc_available=null,
                                $objectifs=null, $programme=null, $c=null, $td=null, $tp=null, $the=null, $credits=null){
    $code = strtoupper($code);
    if(!check_uv_format($code))
      throw new Exception("Wrong format \$code ".$code);

    /* vérification si l UV existe déjà dans la base */
    $sql = new requete($this->db, "SELECT `id` FROM `pedag_uv` WHERE `code` = '".$code."'");
    if(!$sql->is_success())
      return false;

    $row = $sql->get_row();
    if($row == null){
      add($code, $intitule, $type, $responsable, $semestre, $tc_available);
      update_guide_infos($objectifs, $programme, $c, $td, $tp, $the, $credits);
    }else{
      update($code, $intitule, $type, $responsable, $semestre, $tc_available);
      update_guide_infos($objectifs, $programme, $c, $td, $tp, $the, $credits);
    }
  }

  public function set_open($value){
    $sql = new update($this->dbrw, "pedag_uv", array("semestre"=>$value), array("id_uv"=>$this->id));
    return $sql->is_success();
  }

  public function set_valid($value=STATE_VALID){
    $sql = new update($this->dbrw, "pedag_uv", array("state"=>$value), array("id_uv"=>$this->id));
    return $sql->is_success();
  }

  /**
   * L'UV est-elle un alias d'une autre UV ? ex XE03 => LE03
   * @todo voir en fonction des besoins d utilisation si prop a detacher de extra
   * @return id de l'UV cible si c'est un alias, false sinon
   */
  public function is_alias(){
    if(!$this->extra_loaded)
      $this->load_extra();

    if(empty($this->alias_of))
      return false;
    else
      return $this->alias_of["id"];
  }

  public function unset_alias_of(){
    $sql = new delete($this->dbrw, 'pedag_uv_alias', array('id_uv_source' => $this->id));
    $this->load_extra();
    return $sql->is_success();
  }

  public function set_alias_of($id_uv, $comment=null){
    $sql = new insert($this->dbrw, 'pedag_uv_alias',
                      array('id_uv_source' => $this->id,
                            'id_uv_cible' => $id_uv,
                            'commentaire' => $comment));
    $this->load_extra();
    return $sql->is_success();
  }

  public function has_alias(){
    if(!$this->extra_loaded)
      $this->load_extra();

    if(empty($this->aliases))
      return false;
    else
      return true;
  }

  /**
   * N'oublions pas les methodes d'acces aux tags heritees de stdentity
   * @see stdentity::set_tags_array
   * @see stdentity::set_tags
   * @see stdentity::get_tags_list
   * @see stdentity::get_tags
   */



  /**
   * Antecedents
   */
  public function has_antecedent(){
    if(!$this->extra_loaded)
      $this->load_extra();

    return !empty($this->antecedent);
  }

  public function add_antecedent($id_uv, $comment=null, $obligatoire=true){
    $sql = new insert($this->dbrw, 'pedag_uv_antecedent',
                      array('id_uv_source' => $this->id,
                            'id_uv_cible' => $id_uv,
                            'commentaire' => $comment,
                            'obligatoire' => $obligatoire),
                      false);
    return $sql->is_success();
  }

  /* nombre d'eleves inscrits a l'UV pour un semestre donne
   * @param $semestre semestre visé, courant par défaut
   * @return nombre d'eleves, false si echec
   */
  public function get_nb_students($semestre=SEMESTER_NOW){
    $sql = new requete($this->db, "SELECT COUNT( DISTINCT `id_utilisateur` ) as `nb`
                                    FROM `pedag_groupe_utl`
                                    NATURAL JOIN `pedag_groupe`
                                    WHERE `id_uv` = ".$this->id."
                                    AND `semestre` = '".$semestre."'");
    if($sql->is_success()){
      $row = $sql->get_row();
      return $row['nb'];
    }else
      return false;
  }

  /**
   * Chargement des commentaires associés à cette uv
   */
  public function load_comments(){
    $sql = new requete($this->db, "SELECT `id_commentaire` as `id` FROM `pedag_uv_commentaire` WHERE `id_uv` = ".$this->id);
    $this->comments = array();

    if($sql->is_success()){
      while($row = $sql->get_row())
        $this->comments[] = $row['id'];

      return count($this->comments);
    }else
      return false;

  }


  /**
   * gestion des groupes
   */

  /**
   * Ajout d'un groupe/seance d'une UV
   * @param $type type d'UV @see TYPE_
   * @param $num numero du groupe tel qu'indiqué sur les EDT de l'UTBM
   * @param $freq fréquence (1|2)
   * @param $semestre semestre d'ouverture @see SEMESTER_
   * @param $jour jour où a lieu la séance (lundi=1, etc)
   * @param $debut heure de debut de la seance (format time)
   * @param $fin heure de fin de la seance (format time)
   * @param $salle salle ou a lieu la seance si connue (varchar 5)
   */
  public function add_group($type, $num, $freq, $semestre, $jour, $debut, $fin, $salle=null){
    if(!check_semester_format($semestre))
      throw new Exception("Wrong format \$semestre ".$semestre);

    $data = array("id_uv" => $this->id,
                  "type" => $type,
                  "num_groupe" => $num,
                  "freq" => $freq,
                  "semestre" => $semestre,
                  "debut" => $debut,
                  "fin" => $fin,
                  "jour" => $jour,
                  "salle" => $salle);

    $sql = new insert($this->dbrw, "pedag_groupe", $data);

    if($sql->is_success())
      return $sql->get_id();
    else
      return false;
  }

  /* suppression de groupe
   * realisee uniquement si personne n'y est inscrit */
  public function remove_group($id_group){
  }

  public function update_group($id_groupe, $type, $num, $freq, $semestre, $jour, $debut, $fin, $salle=null){
    $data = array("id_uv" => $this->id,
                  "type" => $type,
                  "num_groupe" => $num,
                  "freq" => $freq,
                  "semestre" => $semestre,
                  "debut" => $debut,
                  "fin" => $fin,
                  "jour" => $jour,
                  "salle" => $salle);
    $sql = new update($this->dbrw, "pedag_groupe", $data, array("id_groupe"=>$id_groupe));
    return $sql->is_success();
  }

  /**
   * Recuperation des infos de groupes
   * @param $type type des groupes recherches du style GROUP_TD ou null si tout
   * @param $semestre semestre visé
   * @param $id_utilisateur utilisateur concerné
   * @return tableau des informations
   */
  public function get_groups($type=null, $semestre=null, $idgroup=null, $id_utilisateur=null){
    $sql = "SELECT *,
              `type`+0 as `type_num`
            FROM `pedag_groupe` ";
    if ($id_utilisateur != null)
      $sql .= "LEFT JOIN `pedag_groupe_utl` USING (id_groupe)
            WHERE `id_uv` = ".$this->id."
            AND `id_utilisateur` = ".$id_utilisateur;
    else
      $sql .= "WHERE `id_uv` = ".$this->id;

    if($semestre)
      $sql .= "  AND `semestre` = '".$semestre."'";
    if($type)
      $sql .= "  AND `type` = ".$type;
    if($idgroup)
      $sql .= "  AND `id_groupe` = ".$idgroup;

    $sql .= "  ORDER BY `semestre`, `type`";

    $req = new requete($this->db, $sql);
    if(!$req->is_success())
      return false;
    else
      $t = array();

    while($row = $req->get_row())
      $t[] = $row;

    return $t;
  }

  public function has_group($id_group, $type, $semestre = SEMESTER_NOW){
    if(!isset($this->groups)
        || !isset($this->groups[$semestre])
        || empty($this->groups[$semestre])){
      $tab = $this->get_groups(null, $semestre);
      foreach($tab as $grp)
        $this->groups[$semestre][strtolower($grp['type'])][] = $grp['id_groupe'];
    }
    if(isset($this->groups[$semestre][$type]))
      return in_array($id_group, $this->groups[$semestre][$type]);
    else
      return false;
  }

  /**
   * recherche avec un numéro de groupe et non un id
   */
  public function search_group($numgroup, $type, $semestre=SEMESTER_NOW){
    $sql = new requete($this->db, "SELECT `id_groupe` FROM `pedag_groupe`
                                    WHERE `id_uv` = '".$this->id."'
                                    AND `num_groupe` = '".$numgroup."'
                                    AND `type` = ".$type."
                                    AND `semestre` = '".$semestre."'");
    if($sql->is_success() && $sql->lines > 0){
      $row = $sql->get_row();
      return $row['id_groupe'];
    }else
      return false;
  }

  public function get_nb_students_group($id_group){
    $sql = new requete($this->db, "SELECT COUNT(*) as `nb`
                                    FROM `pedag_groupe_utl`
                                    WHERE `id_groupe` = ".$id_group);
    if($sql->is_success()){
      $row = $sql->get_row;
      return $row['nb'];
    }else
      return false;
  }

  /**
   * Departements
   */
  private function load_dept(){
    $sql = new requete($this->db, "SELECT `departement`+0 FROM `pedag_uv_dept` WHERE `id_uv`= ".$this->id);
    if($sql->is_success())
      while($row = $sql->get_row())
        $this->dept[] = $row[0];
  }

  public function get_dept_list(){
    if(empty($this->dept))
      $this->load_dept();

    return $this->dept;
  }

  public function add_to_dept($dept){
    if(empty($this->dept))
      $this->load_dept();
    if(in_array($dept, $this->dept))
      throw new Exception($uv->code." déjà présente dans ".$dept);

    $sql = new insert($this->dbrw, "pedag_uv_dept", array("id_uv" => $this->id, "departement" => $dept));
    return $sql->is_success();
  }

  public function remove_from_dept($dept){
    if(empty($this->dept))
      $this->load_dept();
    if(!in_array(intval($dept), $this->dept))
      throw new Exception($uv->code." non présente dans ".$dept);

    /* pas d'utilisation de 'delete' parce que l'enum est pas compatible avec les guillemets mis autour d'une valeur num */
    $sql = new requete($this->dbrw, "DELETE FROM `pedag_uv_dept` WHERE `id_uv`=".$this->id." AND `departement`=".intval($dept));
    return $sql->is_success();
  }

  /**
   * Admin des commentaires
   * dans leur globalité
   */
  public function reset_eval_comments(){
    $sql = new update($this->dbrw, "pedag_uv_commentaire", array("eval_comment" => 0), array("id_uv" => $this->id));
    return $sql->is_success();
  }

  /**
   * recupere le nombre de commentaires enregistres pour une UV quoi
   * @return le nombre, ou false si echec
   */
  public function get_nb_comments(){
    $sql = new requete($this->db, "SELECT COUNT(*) as `nb`
                                    FROM `pedag_uv_commentaire`
                                    WHERE `id_uv` = ".$this->id);
    if($sql->is_success()){
      $row = $sql->get_row;
      return $row['nb'];
    }else
      return false;
  }

  /**
   * Fonctions static
   * plutot destinees a etre appelees rapidement par un appel AJAX
   */

  /**
   * @brief Tente de détecter une erreur de saisie des UV
   *
   * Si un etudiant noté GI, donc sur Belfort, s'inscrit a une UV sur
   * Sévenans comme LE03 et qu'il existe un alias sur Belfort (XE03)
   * alors on lui propose.
   * Fonction destinee a un controle pendant la creation d'un EDT
   * (voire ajout de resultat), donc en static pour l'instant
   *
   * @param $db base de donnee en lecture seule
   * @param $id_utl utilisateur concerné par le contrôle
   * @param $id_uv UV entrée par l'utilisateur et que l'on controle
   * @return l'id de l'UV conseillée s'il y en a une, false sinon
   */
  public static function find_proper_uv(&$db, $id_utl, $id_uv){
  }

  /**
   * Recuperation en static d'un code d UV a partir d un id
   */
  public static function get_code(&$db, $id_uv){
    $sql = new requete($db, "SELECT `code` FROM `pedag_uv` WHERE `id_uv` = ".$id_uv);
    if($sql->is_success()){
      $row = $sql->get_row;
      return $row['code'];
    }else
      return false;
  }

  /**
   * teste l'existence d'une UV dans la base d apres son id ou son code
   */
  public static function exists(&$db, $uv){
    if(check_uv_format($uv))
      $sql = new requete($db, "SELECT 1 FROM `pedag_uv` WHERE `code` = '".$uv."'");
    else
      $sql = new requete($db, "SELECT 1 FROM `pedag_uv` WHERE `id_uv` = ".$uv);
    return $sql->lines;
  }

  /**
   * retourne la liste des uvs et leurs infos
   */
  public static function get_list(&$db, $type=null, $dept=null){
    $req = "SELECT `pedag_uv`.`id_uv` as id_uv, `pedag_uv`.`code` as code,
		`pedag_uv`.`intitule` as intitule, `pedag_uv`.`type` as type, 
		`pedag_uv`.`responsable` as responsable, 
		`pedag_uv`.`semestre` as semestre FROM `pedag_uv`";
    $where=false;
    global $_DPT;
    if(!is_null($dept) && array_key_exists($dept, $_DPT)){
      $req .= " NATURAL JOIN `pedag_uv_dept`
                WHERE `pedag_uv_dept`.`departement` = ".$dept;
      $where = true;
    }
    if(is_null($dept))
    {
      $req .= " LEFT OUTER JOIN `pedag_uv_dept` 
		ON `pedag_uv`.`id_uv` = `pedag_uv_dept`.`id_uv`
		WHERE `pedag_uv_dept`.`departement` IS NULL";
      $where = true;
    }
    if(!is_null($type) && array_key_exists($type, $_TYPE)){
      if($where)
        $req .= " AND";
      else
        $req .= " WHERE";
      $req .= " `pedag_uv`.`type` = ".$type;
    }
    $req .= " ORDER BY `code` ASC";

    $sql = new requete($db, $req);

    if(!$sql->is_success())
      return false;
    else{
      $t=null;
      while($row = $sql->get_row())
        $t[] = $row;

      return $t;
    }
  }
}
?>
