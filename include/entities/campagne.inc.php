<?php

/**
 * @file
 */

/* Copyright 2007
 * - Simon Lopez <simon POINT lopez CHEZ ayolo POINT org>
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

/**
 * Classe de gestion des campagnes de recrutement
 */
class campagne extends stdentity
{
  /** Date d'ajout de la campagne */
  var $date;

  /** Date de fin de la campagne */
  var $end_date;

  /** id de la campagne */
  var $id=null;

  /** nom de la campagne */
  var $nom;

  /** description de la campagne */
  var $descrition;

  /** groupe concerné */
  var $group;

  /** asso recrutante */
  var $asso;

  /** Charge une campagne en fonction de son id
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id de la campagne
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `cpg_campagne`
        WHERE `id_campagne` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_lastest ( )
  {
    $req = new requete($this->db, "SELECT * FROM `cpg_campagne` WHERE `date_fin_campagne`>=NOW() ORDER BY date_debut_campagne DESC LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function is_lastest( $id )
  {
    if ($this->id != $id)
      return 0;
    else
      return 1;
  }

  function _load ( $row )
  {
    $this->id  = $row['id_campagne'];
    $this->nom = $row['nom_campagne'];
    $this->description = $row['description_campagne'];
    $this->group = $row["id_groupe"];
    $this->date  = $row['date_debut_campagne'];
    $this->end_date  = $row['date_fin_campagne'];
    $this->asso = $row['id_asso'];
  }

  function new_campagne ( $nom, $description, $end_date, $group , $asso=1)
  {
    $this->nom = $nom;
    $this->end_date = $end_date;
    $this->description = $description;
    $this->date = time();
    $this->group = $group;
    $this->asso= $asso;

    $sql = new insert ($this->dbrw,
      "cpg_campagne",
      array(
        "nom_campagne" => $this->nom,
        "description_campagne" => $this->description,
        "id_groupe" => $this->group,
        "date_debut_campagne" => date("Y-m-d H:i:s"),
        "date_fin_campagne" => date("Y-m-d",$this->end_date),
        "id_asso"=>$this->asso
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;
  }

  /** Met à jour une campagne avec les données en paramètre
   * @param $nom intitulé de la campagne
   * @param $description la description de la campagne
   * @param $begin_date date de début
   * @param $end_date date de fin
   */
  function update_campagne ($nom, $description, $begin_date, $end_date, $id_groupe)
  {
    $this->nom = $nom;
    $this->description=$descritpion;
    $this->end_date = $end_date;

    $sql = new update($this->dbrw,
      "cpg_campagne",
      array(
        "nom_campagne" => $this->nom,
        "description_campagne" => $description,
        "date_debut_campagne" => $begin_date,
        "date_fin_campagne" => date("Y-m-d",$this->end_date),
        "id_groupe" => $id_groupe
        ),array("id_campagne"=>$this->id)
      );
  }

  function update_question ($id, $question, $desc, $type, $resp, $limit=0)
  {
    $sql = new requete($this->db,"SELECT `nom_question` FROM `cpg_question` WHERE `id_campagne`='".mysql_real_escape_string($this->id)."' AND `id_question`='".mysql_real_escape_string($id)."'");

    if ( $sql->lines == 0 )
      $this->add_question($question, $desc, $type, $resp, $limit);
    else
      $sql = new update($this->dbrw,
      "cpg_question",
      array("nom_question" => $question,
            "description_question" => $desc,
            "type_question" => $type,
            "reponses_question" => $resp,
            "limites_reponses_question" => $limit
           ),
      array("id_campagne"=>$this->id,"id_question"=>$id)
      );
  }

  function add_question ( $nom, $desc, $type, $resp="", $limit=0 )
  {
    $sql = new insert ($this->dbrw,
      "cpg_question",
      array(
        "id_campagne" => $this->id,
        "nom_question" => $nom,
        "description_question" => $desc,
        "type_question" => $type,
        "reponses_question" => $resp,
        "limites_reponses_question" => $limit
        )
      );
  }

  function remove_question ( $id )
  {
    $sql = new delete($this->dbrw,
      "cpg_question",
      array(
        "id_campagne" => $this->id,
        "id_question" => $id
        )
      );
  }

  function get_questions()
  {
    $sql = new requete($this->db, "SELECT * " .
            "FROM `cpg_question` " .
            "WHERE id_campagne='".mysql_escape_string($this->id)."' " .
            "ORDER BY `id_question`");

    $questions = array();

    while ( $row = $sql->get_row() )
    {
      $id=$row['id_question'];
      $questions[$id] = array("nom"=>$row["nom_question"],
                                             "description"=>$row["description_question"],
                                             "type"=>$row["type_question"],
                                             "reponses"=>$row["reponses_question"],
                                             "limit"=>$row["limites_reponses_question"],
                                             "id"=>$row["id_question"]);
    }
    return $questions;
  }

  function get_specified_answer($id_question){

    $sql = new requete($this->db,"SELECT COUNT(`valeur_reponse`) AS `nombre_reponse`,
      `id_question`,`nom_question`, `valeur_reponse`
      FROM `cpg_reponse`
      INNER JOIN `cpg_question` USING(`id_question`)
      WHERE `id_question`='".$id_question."'
      AND `type_question`!=\"text\"
      GROUP BY `valeur_reponse`
      ORDER BY `id_question`");

    return $sql;
  }

  function get_user_results($id_utilisateur)
  {
    $sql = new requete($this->db, "SELECT `id_question`, `valeur_reponse` " .
            "FROM `cpg_reponse` " .
            "WHERE `id_campagne`='".mysql_escape_string($this->id)."' && `id_utilisateur`='".$id_utilisateur."'" .
            "ORDER BY `id_question`");

    $resultats = array();

    while ( list($id,$rep) = $sql->get_row() )
      $resultats[$id] = $rep;

    return $resultats;
  }

  function a_repondu ( $id_utilisateur )
  {

    $sql = new requete($this->db, "SELECT * " .
            "FROM `cpg_participe` " .
            "WHERE `id_campagne`='".mysql_escape_string($this->id)."' " .
            "AND `id_utilisateur`='".mysql_escape_string($id_utilisateur)."'");

    if($sql->lines == 1)
      return true;
    else
      return false;

  }

  /**
   * Definit les réponses à la campagne pour un utilisateur
   */
  function repondre ( $id_utilisateur, $answers )
  {
    if ( $this->a_repondu($id_utilisateur) ) return;

    $sql = new insert ($this->dbrw,
      "cpg_participe",
      array(
        "id_campagne" => $this->id,
        "id_utilisateur" => $id_utilisateur,
        "date_participation" => date("Y-m-d H:i:s")
        )
      );

    if ( !empty($answers) )
    {
      foreach($answers as $id => $value)
      {
	if(is_string($value))
		$value = trim($value);
	if(!empty($value))
	{
        	$sql = new insert($this->dbrw,
	          "cpg_reponse",
        	  array("id_campagne"=>$this->id,
	                "id_question"=>$id,
        	        "id_utilisateur"=>$id_utilisateur,
                	"valeur_reponse"=>$value));
	}
      }
    }
  }
}



?>
