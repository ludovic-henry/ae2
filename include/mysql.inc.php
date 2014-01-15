<?php

/** @file
 *
 * @brief Connexion à la base MySQL et classes facilitant la
 * réalisation de requêtes.
 *
 */

/* Copyright 2004
 * - Alexandre Belloni <alexandre POINT belloni CHEZ utbm POINT fr>
 * - Thomas Petazzoni <thomas POINT petazzoni CHEZ enix POINT org>
 *
 * Copyright 2006 (messages + mysql_escape_joker_string)
 * - Julien Etelain
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
 * @defgroup mysql Accès à la base de données
 */

/**
 * Prépare une chaine de caractère pour injection dans une requête SQL pour être
 * utilisée avec l'opérateur 'LIKE'
 * @param $string Chaine de caractère à traiter
 * @return la chaine de caractère apte à être insérée dans une requête SQL
 * @ingroup mysql
 */
function mysql_escape_joker_string ( $string ) {
  return str_replace("_","\\_",str_replace("%","\\%",mysql_real_escape_string($string)));
}

/** Classe permettant de se connecter à la base
 * @ingroup mysql
 */
class mysql {
  var $base;
  var $user;
  var $pass;
  var $serveur;

  var $errmsg;

  var $dbh;

  function mysql ($my_user, $my_pass, $my_serveur, $my_base) {
    $this->user = $my_user;
    $this->serveur = $my_serveur;
    $this->base = $my_base;

    $my_dbh = @mysql_connect("$my_serveur", "$my_user", "$my_pass");
    if (!$my_dbh) {
      $this->errmsg = "Connexion impossible.";
      return FALSE;
    }
    /* une fois la connexion etablie, on peut oublier les mots de passe */

    if (!@mysql_select_db($my_base, $my_dbh)) {
      $this->errmsg = "S&eacute;lection de la base de donn&eacute;es impossible.";
      return FALSE;
    }

    $this->errmsg = "";
    $this->dbh = $my_dbh;
  }

  /**
   * ferme la connexion à la base de données
   */
  function close()
  {
    @mysql_close($this->dbh);
  }

}

/**
 * Permet de réaliser une requête sur une connexion MySQL
 * @ingroup mysql
 */
class requete {

  var $result=null;
  var $errno=0;
  var $errmsg=null;
  var $lines=-1;
  var $base=null;

  /**
   * Execute une requête sur la base de données
   * @param $base Connexion à la base de données
   * @param $req_sql Requête SQL à executer
   * @param $debug Mode debuggage (si 1) (non utilisé)
   */
  function requete (&$base, $req_sql, $debug = 0) {
    global $timing;

    $timing["mysql"] -= $st = microtime(true);

    if($debug == 1)
      echo "Votre requete SQL est <b> " . $req_sql . "</b><br/>";

    if(!$base->dbh)
    {
      $this->errmsg = "Non connecté";
      if( $GLOBALS["taiste"] )
        echo "<p>NON MAIS CA VA PAS ! c'est un \$site->db et pas un \$this->db (ou inversement)</p>\n";
      return;
    }
    $this->base = $base;

    $this->result = mysql_query($req_sql, $base->dbh);

    $timing["mysql"] += $fn = microtime(true);
    $timing["mysql.counter"]++;
    if ( $fn-$st > 0.001 )
    $timing["req"][] = array($fn-$st,$req_sql);

    if ( ($this->errno = mysql_errno($base->dbh)) != 0)
    {
      $this->errmsg = mysql_error($base->dbh);
      if( $GLOBALS["taiste"] )
        echo "<p>Erreur lors du traitement de votre demande : ".$this->errmsg."</p>\n";
      $this->lines = -1;
      return;
    }

    if ($GLOBALS["taiste"] && (strncasecmp(trim($req_sql), $req_sql, 1) != 0))
      echo "<p>Pas de cochonneries en début de requête s'il vous plait !</p>\n";

    if(strncasecmp($req_sql, "SELECT",6) == 0)
      $this->lines =  mysql_num_rows ($this->result);
    else
      $this->lines =  mysql_affected_rows ();
  }

  /**
   * Recupère ligne par ligne le résultat de la requête
   * @return un tableau associatif, ou null s'il n'ya plus aucune ligne.
   */
  function get_row () {
    if(!empty($this->result))
      return mysql_fetch_array($this->result);
    else
      return null;
  }

  /**
   * Retroune à la première ligne du résultat de la requête (si applicable)
   */
  function go_first ()
  {
    if ($this->lines > 0 )
    mysql_data_seek($this->result, 0);
  }

  /**
   * Détermine si la requête s'est déroulée avec succès
   * @return true si la requête a été éxécuté avec succès, false sinon
   */
  function is_success()
  {
    return $this->errno == 0;
  }
}

/** Classe d'insertion dans une base de données.
 *  Cette classe facilite l'insertion dans une base de données, en
 *  construisant la requête d'insertion à partir d'un tableau donné en
 *  paramètre.
 * @ingroup mysql
 */
class insert extends requete {

  /** Constructeur de la classe insert.
   *
   *  @param base La base de données dans laquelle l'insertion doit
   *  avoir lieu.
   *  @param table La table dans laquelle l'insertion doit avoir lieu
   *  @param insert_array Une liste de couples (nom du champ, valeur)
   *  pour l'insertion dans la table.
   *
   *  @return false si échec. Le code d'erreur est dans errno, le
   *  message d'erreur dans errmsg.
   */
  function insert($base, $table, $insert_array, $debug = 0)
    {
      if(!$base || !$table)
  {
    return false;
  }

      $insert_array_count = count($insert_array);

      if($insert_array_count <= 0)
  {
    return false;
  }

      $sql = "insert into `" . $table . "` (";
      $sql2 = "";
      $i = 0;

      foreach ($insert_array as $key => $value)
  {
    $sql .= "`" . $key . "`";
    if ( $value === false )
    $sql2 .= "'0'";
    elseif ( $value === true )
      $sql2 .= "'1'";
    elseif ($value === "0" )
      $sql2 .= "''";
    elseif ( is_null($value) )
    $sql2 .= "NULL";
    else
    $sql2 .= "'" . mysql_escape_string($value) . "'";

    if($i != ($insert_array_count-1))
      {
        $sql .= ",";
        $sql2 .= ",";
      }

    $i++;
  }

      $sql .= ") values (" . $sql2 . ")";

      if($debug == 1)
  {
    echo "Votre requete SQL est <b> " . $sql . "</b><br/>";
  }

      if(! $this->requete($base, $sql))
  {
    return false;
  }
    }

  /** Récupération de l'ID de l'élément inséré
   */
  function get_id()
    {
      return mysql_insert_id($this->base->dbh);
    }
}

/** Classe de mise à jour dans une base de données.
 *  Cette classe facilite la mise à jour d'une base de données, en
 *  construisant la requête d'update à partir de deux tableaux donnés
 *  en paramètre.
 * @ingroup mysql
 */

class update extends requete {

  /** Constructeur de la classe update.
   *
   *  @param base La base de données dans laquelle l'insertion doit
   *  avoir lieu.
   *  @param table La table dans laquelle la modification doit avoir lieu
   *  @param update_array Une liste de couples (nom du champ, valeur)
   *  donnant les champs qui doivent être mis à jour
   *  @param update_conds Une liste de couples (nom du champ, valeur)
   *  donnant la liste des conditions pour les champs qui doivent être
   *  mis à jour.
   *
   *  @return false si échec. Le code d'erreur est dans errno, le
   *  message d'erreur dans errmsg.
   */
  function update($base, $table, $update_array, $update_conds, $debug = 0)
    {
      if(!$base || !$table || ! $update_array)
  {
    return false;
  }

      $update_array_count = count($update_array);

      if($update_array_count <= 0)
  {
    return false;
  }

      $sql = "update `" . $table . "` set ";

      $i = 0;

      foreach ($update_array as $key => $value)
  {
    if ( $value === false )
      $sql .= "`" . $key . "`='0'";
    elseif ( $value === true )
      $sql .= "`" . $key . "`='1'";
    elseif ( is_null($value) )
      $sql .= "`" . $key . "`= NULL";
    elseif ( $value === '')
      $sql .= "`" . $key . "` = ''";
    else
       $sql .= "`" . $key . "`= '" . mysql_escape_string($value) . "'";

    if($i != ($update_array_count-1))
      {
        $sql .= ",";
      }

    $i++;
  }

      /* Gestion du tableau update_conds qui contient les conditions
         permettant de délimiter les entrées de la table qu'il faut
         modifier.
      */
      if($update_conds)
  {
    $sql .= " WHERE (";

    $update_conds_count = count($update_conds);
    $i = 0;

    foreach ($update_conds as $key => $value)
      {
        if ( is_null($value) )
          $sql .= "(`" . $key . "` is NULL)";
        else
          $sql .= "(`" . $key . "`='" . mysql_escape_string($value) . "')";

        if($i != ($update_conds_count-1))
    {
      $sql .= " AND ";
    }

        $i++;
      }

    $sql .= ")";
  }

      if($debug == 1)
  {
    echo "Votre requete SQL est <b> " . $sql . "</b><br/>";
  }

      if(! $this->requete($base, $sql))
  {
    return false;
  }
    }
}

/** Classe de suppression dans une base de données.
 *  Cette classe facilite la suppression d'entrées d'une base de
 *  données, en construisant la requête de suppression à partir d'un
 *  tableau donné en paramètre.
 * @ingroup mysql
 */

class delete extends requete {

  /** Constructeur de la classe delete.
   *
   *  @param base La base de données dans laquelle la suppression doit
   *  avoir lieu.
   *
   *  @param table La table dans laquelle la suppression doit avoir
   *  lieu
   *
   *  @param delete_conds Une liste de couples (nom du champ, valeur)
   *  donnant la liste des conditions permettant de délimiter les
   *  entrées qui doivent être supprimées
   *
   *  @return false si échec. Le code d'erreur est dans errno, le
   *  message d'erreur dans errmsg.
   */
  function delete($base, $table, $delete_conds, $debug = 0)
  {
    if(!$base || !$table || ! $delete_conds)
    {
      return false;
    }

    $delete_conds_count = count($delete_conds);

    if($delete_conds_count <= 0)
    {
      return false;
    }

    $sql = "delete from `" . $table . "` where (";

    $i = 0;

    foreach ($delete_conds as $key => $value)
    {
      if ( is_null($value) )
        $sql .= "(`" . $key . "` is NULL)";
      else
        $sql .= "(`" . $key . "`='" . mysql_escape_string($value) . "')";


      if($i != ($delete_conds_count-1))
      {
        $sql .= " AND ";
      }

      $i++;
    }

    $sql .= ")";

    if($debug == 1)
    {
      echo "Votre requete SQL est <b> " . $sql . "</b><br/>";
    }

    $this->requete($base, $sql);
  }
}

?>
