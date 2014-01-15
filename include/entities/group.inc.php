<?php

/* Copyright 2006
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
 * 02111-1307, USA.
 */

/**
 * @file Gestion des groupes.
 * Les groupes permettent en général de definir des droits d'accés.
 * De nombreux groupes sont automatiques :
 * - ae : personnes ayant l'attribu ae=true
 * - utbm : personnes ayant l'attribu utbm=true
 * - ancien_etudiant : personnes ayant l'attribu ancien_etudiant=true
 * - etudiant : personnes ayant l'attribu etudiant=true
 * - asso-bureau : personnes membres du bureau de l'association asso
 * - asso-membres : membres de l'association asso (non valable pour les association de 1 niveau (parent=null))
 *
 * Attention: ces groupes ont un id > 10000 et ne peuvent pas êtres édités ni chargés via la classe group.
 * Les ids 10000 à 49999 sont réservés pour ces groupes.
 */

$types_groupes = array(100 => "Membres du bureau AE", 80 => "Membres des clubs", 60 => "Modérateurs", 50 => "Comptoirs", 40 => "Utilisateurs bannis", 20 => "Équipe info", 0 => "Groupes divers");

/**
 * Classe gérant les groupes
 */
class group extends stdentity
{

  /** Nom unix du groupe */
  var $nom;
  /** Description du groupe */
  var $description;

  /** Accés à la base de donnés en lecture seule.*/
  var $db;
  /** Accés à la base de donnés en lecture et ecriture.*/
  var $dbrw;

  /** Charge un groupe par son ID
   * @param $id ID du groupe
   */
  function load_by_id ( $id )
  {
    if ( $id >= 10000 )
    {
      $all = $this->enumerate();

      if ( !isset($all[$id]) )
       return false;

      $this->id = $id;
      $this->nom = $all[$id];
      $this->description = "";
      return true;
    }

    $req = new requete($this->db, "SELECT * FROM `groupe`
        WHERE `id_groupe` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /**
   * Charge un groupe depuis une ligne SQL.
   * @param $row Ligne SQL
   */
  function _load ( $row )
  {
    $this->id = $row['id_groupe'];
    $this->nom = $row['nom_groupe'];
    $this->description = $row['description_groupe'];
    $this->type = $row['type_groupe'];
  }

  /**
   * Crée un groupe
   * @param $nom Nom du groupe (unix)
   * @param $description Description du groupe
   */
  function add_group ( $nom, $description, $type=0 )
  {

    $this->nom = $nom;
    $this->description = $description;
    $this->type = $type;

    $sql = new insert ($this->dbrw,
        "groupe",
        array(
          "nom_groupe" => $this->nom,
          "description_groupe" => $this->description,
          "type_groupe" => $this->type
          )
        );

    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;
  }

  /**
   * Supprime un groupe
   */
  function delete_group ( )
  {
    $req = new delete($this->dbrw,"utl_groupe",
            array(
              "id_groupe"=>$this->id
              ));
    $req = new delete($this->dbrw,"groupe",
            array(
              "id_groupe"=>$this->id
              ));
  }
  /**
   * Ajoute un utilisateur au groupe
   * @param $id_utilisateur Id de l'utilisateur
   */
  function add_user_to_group ( $id_utilisateur )
  {
    $req = new insert($this->dbrw,"utl_groupe",
            array(
              "id_groupe"=>$this->id,
              "id_utilisateur"=>$id_utilisateur
              ));

  }
  /**
   * Enlève un utilisateur
   * @param $id_utilisateur Id de l'utilisateur
   */
  function remove_user_from_group ( $id_utilisateur )
  {
    $req = new delete($this->dbrw,"utl_groupe",
            array(
              "id_groupe"=>$this->id,
              "id_utilisateur"=>$id_utilisateur
              ));

  }

  function can_enumerate()
  {
    return true;
  }

  function enumerate ( $null=false, $conds = null )
  {
    if ( !isset($GLOBALS["groupscache"]) )
    {
      $values = array();
      $req = new requete($this->db, "SELECT `id_groupe`,`nom_groupe` FROM `groupe` ORDER BY `nom_groupe`");

      while ( list($id,$fname) = $req->get_row() )
        $values[$id] = $fname;

      $values[10000] = "ae-membres";
      $values[10001] = "utbm";
      $values[10002] = "etudiants-anciens";
      $values[10003] = "etudiants-actuels";
      $values[10004] = "etudiants-utbm-actuels";
      $values[10005] = "etudiants-utbm-anciens";
      $values[10006] = "etudiants-utbm-tous";
      $values[10007] = "etudiants-tous";
      $values[10008] = "utilisateurs-valides";
      $values[10009] = "responsables-clubs";
      $values[10010] = "assidu-membres";
      $values[10011] = "amicale-membres";
      $values[10013] = "crous-membres";
      $values[10012] = "cotisants-tous";
      $values[10014] = "ca-membres";
      $values[10015] = "cotisants-sympathisants";

      $req = new requete($this->db,
        "SELECT `id_asso`, `nom_unix_asso` " .
        "FROM  `asso`  " .
        "ORDER BY `nom_asso`");

      while ( list($id,$fname) = $req->get_row() )
        $values[$id+20000] = strtolower($fname)."-bureau";

      $req = new requete($this->db,
        "SELECT `id_asso`, `nom_unix_asso` " .
        "FROM `asso` " .
        "WHERE `id_asso_parent` IS NOT NULL " .
        "ORDER BY `nom_asso`");

      while ( list($id,$fname) = $req->get_row() )
        $values[$id+30000] = strtolower($fname)."-membres";

      $promo = 1;
      while ( in_array("promo".sprintf("%02d",$promo)."-bureau", $values) )
      {
        $values[$promo+40000] = "promo".sprintf("%02d",$promo)."-membres";
        $promo++;
      }

      asort($values);

      $GLOBALS["groupscache"] = $values;

    }

    if ( $null )
    {
      $ret = $GLOBALS["groupscache"];
      $ret[null] = "(aucun)";
      asort($ret);
      return $ret;
    }
    return $GLOBALS["groupscache"];
  }

  function can_fsearch ()
  {
    return false;
  }

  function can_describe()
  {
    return true;
  }

  function get_description()
  {
    global $topdir;

    if ( $this->id > 40000 )
      return "membres de la promo ".sprintf("%02d",$this->id-40000)." de l'utbm";

    if ( $this->id > 20000 )
    {
      if ( $this->id > 30000 )
      {
        $id_asso = $this->id-30000;
        $append = "";
      }
      else
      {
        $id_asso = $this->id-20000;
        $append = " qui ont un rôle supérieur ou égal à \"Membre du bureau\"";
      }
      require_once($topdir . "include/entities/asso.inc.php");

      $asso = new asso($this->db);
      $asso->load_by_id($id_asso);
      return "membres de ".$asso->nom.$append;
    }

    if ( $this->id == 10000 )
      return "cotisants à l'AE";

    if ( $this->id == 10001 )
      return "etudiants, ancien étudiants, membres du personnel et enseignants à l'utbm";

    if ( $this->id == 10002 )
      return "anciens étudiants";

    if ( $this->id == 10003 )
      return "étudiants";

    if ( $this->id == 10004 )
      return "étudiants de l'utbm";

    if ( $this->id == 10005 )
      return "anciens étudiants de l'utbm";

    if ( $this->id == 10006 )
      return "étudiants et anciens étudiants de l'utbm";

    if ( $this->id == 10007 )
      return "étudiants et anciens étudiants";

    if ( $this->id == 10008 )
      return "utilisateurs dont le compte a été modéré";

    if ( $this->id == 10009 )
      return "responsables des clubs";

    if ( $this->id == 10010 )
      return "cotisants par ASSIDU";

    if ( $this->id == 10011 )
      return "cotisants par l'amicale de l'UTBM";

    if ( $this->id == 10013 )
      return "cotisants CROUS";

    if ( $this->id == 10012 )
      return "cotisants à l'AE y compris par ASSIDU, l'amicale et le CROUS";

    if ( $this->id == 10014 )
        return "Membres du CA de l'AE";

    if ( $this->id == 10015 )
        return "Cotisants et anciens cotisants à l'AE";

    return trim($this->description);
  }

  function get_type_desc()
  {
    global $types_groupes;

    if (array_key_exists($this->type, $types_groupes))
      return $types_groupes[$this->type];
    else
      return "Groupes non classés";
  }

}

/**
 * Enumère tous les groupes existants.
 * @param $db Lien à la base de donné.
 * @return Renvoie un tableau array(id groupe=>nom groupe)
 */
function enumerates_groups($db)
{
  $grp = new group($db);
  return $grp->enumerate();
}

?>
