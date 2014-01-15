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

require_once($topdir."include/catalog.inc.php");

/**
 * @defgroup stdentity stdentity : Base des entitées pour l'accès aux données
 */


/**
 * Standart base class for database entities classes
 * @ingroup stdentity
 * @author Julien Etelain
 */
abstract class stdentity
{
  /** Identifiant unique de l'objet */
  var $id;
  /** Lien à la base de données en lecture seule */
  var $db;
  /** Lien à la base de données en lecture et écriture. Peut être null si
   * l'objet est en lecture seule */
  var $dbrw;

  /** Cache des tags de l'objet
   * @private
   */
  var $_tags;

  /**
   * Constructeur par défaut.
   *
   * Il n'est pas nécessaire de redéfinir un construteur pour les classes
   * héritant de stdentity.
   *
   * Id est définit à null par défaut. is_valid() renverra faux.
   *
   * @param $db Lien à la base de données en lecture seule
   * @param $dbrw Lien à la base de données en lecture et écriture
   * @param $id Si fourni, appelle load_by_id avec $id
   */
  function stdentity ( &$db, &$dbrw = null, $id = null )
  {
    $this->db = &$db;
    $this->dbrw = &$dbrw;
    $this->id = null;
    $this->_tags = null;

    if(!is_null($id) && method_exists($this, 'load_by_id'))
      try{
        $this->load_by_id($id);
      }catch(Exception $e){}

  }

  /**
   * Check if entity is valid
   * @return true if valid, false otherwise
   */
  function is_valid()
  {
    return !is_null($this->id) && ($this->id != -1);
  }

  /**
   * Get display name of entity
   * @return entity display name
   */
  function get_display_name()
  {
    if ( !empty($this->nom) )
      return $this->nom;

    if ( !empty($this->titre) )
      return $this->titre;

    if ( !empty($this->num) )
      return "n°".$this->num;

    return "n°".$this->id;
  }

  /**
   * Get an html link to the entity with an icon and entity display name
   * @return an html link to the entity
   */
  function get_html_link()
  {
    global $topdir,$wwwtopdir;

    $class = get_class($this);

    if ( !$this->is_valid() )
      return "(aucun)";


    if ( !isset($GLOBALS["entitiescatalog"][$class][3]) || is_null($GLOBALS["entitiescatalog"][$class][3]) )
    {
    return "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][$class][2]."\" class=\"icon\" alt=\"\" /> ".
      htmlentities($this->get_display_name(),ENT_COMPAT,"UTF-8");
    }

    if ( !isset($GLOBALS["__std_ref"]) )
      $GLOBALS["__std_ref"] = 1;
    else
      $GLOBALS["__std_ref"]++;

    $ref = "std".$GLOBALS["__std_ref"];

    if ( $this->can_preview() )
      return "<a id=\"$ref\" onmouseover=\"show_tooltip('$ref','$wwwtopdir','$class','".$this->id."');\" onmouseout=\"hide_tooltip('$ref');\" href=\"".$wwwtopdir.$GLOBALS["entitiescatalog"][$class][3]."?".$GLOBALS["entitiescatalog"][$class][0]."=".$this->id."\">".
      "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][$class][2]."\" class=\"icon\" alt=\"\" /> ".
      htmlentities($this->get_display_name(),ENT_COMPAT,"UTF-8")."</a>";
    elseif ($this->can_describe() )
      return "<a href=\"".$wwwtopdir.$GLOBALS["entitiescatalog"][$class][3]."?".$GLOBALS["entitiescatalog"][$class][0]."=".$this->id."\" title=\"".$this->get_description()."\">".
      "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][$class][2]."\" class=\"icon\" alt=\"\" /> ".
      htmlentities($this->get_display_name(),ENT_COMPAT,"UTF-8")."</a>";
    else
      return "<a href=\"".$wwwtopdir.$GLOBALS["entitiescatalog"][$class][3]."?".$GLOBALS["entitiescatalog"][$class][0]."=".$this->id."\">".
      "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][$class][2]."\" class=\"icon\" alt=\"\" /> ".
      htmlentities($this->get_display_name(),ENT_COMPAT,"UTF-8")."</a>";
  }

  /**
   * Détermine si l'entité associé à cet objet préfére être sélectionné par
   * le biais d'une liste
   * Par défaut, non.
   *
   * @return true si l'entité associé à cet objet préfére être sélectionné par
   * le biais d'une liste, false sinon
   */
  function prefer_list()
  {
    return false;
  }

  /**
   * Charge l'objet depuis une ligne de la base de données
   * @param $row Ligne issue d'un résultat
   */
  abstract function _load ( $row );

  /** Charge un objet en fonction de son id
   * En cas d'erreur, l'id est défini à null
   * @param $id id de l'objet
   * @return true en cas de succès, false sinon
   */
  abstract function load_by_id ( $id );

  /**
   * Check if class can enumarate its elements using enumerate.
   * @return true if it can enumerate, else return false
   */
  function can_enumerate()
  {
    $class = get_class($this);
    return isset($GLOBALS["entitiescatalog"][$class][4]) && $GLOBALS["entitiescatalog"][$class][4];
  }

  /**
   * Enumerate entities elements in an associative array.
   * This function only required an intialised object (no wonder if it's a valid one).
   * @param $null Adds the null entity in the list (top position)
   * @param $conds Adds conditions to select entities
   * @return an associative array ( id => name )
   */
  function enumerate ( $null=false, $conds = null, $order=false )
  {
    $class = get_class($this);

    if ( !isset($GLOBALS["entitiescatalog"][$class][4]) || !$GLOBALS["entitiescatalog"][$class][4] )
      return null;

    if ( $null )
      $values=array(null=>"(aucun)");
    else
      $values=array();

    $sql =
      "SELECT `".$GLOBALS["entitiescatalog"][$class][0]."`,`".
      $GLOBALS["entitiescatalog"][$class][1]."` ".
      "FROM `".$GLOBALS["entitiescatalog"][$class][4]."`";

    if ( !is_null($conds) && count($conds) > 0 )
    {
      $firststatement=true;
      foreach ($conds as $key => $value)
      {
        if( $firststatement )
        {
          $sql .= " WHERE ";
          $firststatement = false;
        }
        else
          $sql .= " AND ";

        if ( is_null($value) )
          $sql .= "(`" . $key . "` is NULL)";
        else
          $sql .= "(`" . $key . "`='" . mysql_escape_string($value) . "')";
      }
    }

    if($order)
      $sql .= " ORDER BY ".$order;
    else
      $sql .= " ORDER BY 2";

    $req = new requete($this->db,$sql);

    while ( $row = $req->get_row() )
      $values[$row[0]] = $row[1];

    return $values;
  }

  /**
   * Determine si un utilisateur peut consulter cet objet.
   * @param $user Utilisateur qui souhaite consulter cet objet
   *              (instance de utilisateur)
   * @return true si l'utilisateur peut consulter, false sinon
   * @see utilisateur
   */
  function allow_user_consult ( $user )
  {
    return true;
  }

  /**
   * Check if class can make fsearch on its elements.
   * @return true if it can fsearch, else return false
   */
  function can_fsearch ( )
  {
    $class = get_class($this);
    return isset($GLOBALS["entitiescatalog"][$class][4]) && $GLOBALS["entitiescatalog"][$class][4];
  }

  /**
   * Advanced fsearch function. Should be subclass if required.
   * @param $pattern Patern to use to find elements
   * @param $limit Limit the ammount of elements retruned (null if unlimited)
   * @param $count Return count of all matching elements instead of associative array
   * @param $conds Adds conditions to select entities
   * @return an associative array ( id => name ) or the count of elements, or null on error
   */
  function _fsearch ( $sqlpattern, $limit=5, $count=false, $conds = null )
  {
    $class = get_class($this);

    if ( !isset($GLOBALS["entitiescatalog"][$class][4]) || !$GLOBALS["entitiescatalog"][$class][4] )
      //return null;
      return array(0=>"Calsse $class non suportée");
    if ( $count )
    {
      $sql = "SELECT COUNT(*) ";
      $limit=null;
    }
    else
      $sql = "SELECT `".$GLOBALS["entitiescatalog"][$class][0]."`,`".$GLOBALS["entitiescatalog"][$class][1]."` ";

    $sql .= "FROM `".$GLOBALS["entitiescatalog"][$class][4]."` ".
      "WHERE `".$GLOBALS["entitiescatalog"][$class][1]."` REGEXP '^$sqlpattern'";

    if ( !is_null($conds) && count($conds) > 0 )
    {
      foreach ($conds as $key => $value)
      {
        $sql .= " AND ";
        if ( is_null($value) )
          $sql .= "(`" . $key . "` is NULL)";
        else
          $sql .= "(`" . $key . "`='" . mysql_escape_string($value) . "')";
      }
    }

    $sql .= " ORDER BY 1";

    if ( !is_null($limit) && $limit > 0 )
      $sql .= " LIMIT ".$limit;

    $req = new requete($this->db,$sql);

    if ( $count )
    {
      list($nb) = $req->get_row();
      return $nb;
    }

    if ( !$req || $req->errno != 0 )
      //return null;
      return array(0=>$sql);

    $values=array();

    while ( $row = $req->get_row() )
      $values[$row[0]] = $row[1];

    return $values;
  }

  /**
   * Standart function to make regexp patters for insensitive to accent a search
   * @param $pattern User pattern
   * @return transformed pattern
   */
  static function _fsearch_prepare_pattern ( $pattern )
  {
    $pattern = ereg_replace("(e|é|è|ê|ë|É|È|Ê|Ë)","(e|é|è|ê|ë|É|È|Ê|Ë)",$pattern);
    $pattern = ereg_replace("(a|à|â|ä|À|Â|Ä)","(a|à|â|ä|À|Â|Ä)",$pattern);
    $pattern = ereg_replace("(i|ï|î|Ï|Î)","(i|ï|î|Ï|Î)",$pattern);
    $pattern = ereg_replace("(c|ç|Ç)","(c|ç|Ç)",$pattern);
    $pattern = ereg_replace("(u|ù|ü|û|Ü|Û|Ù)","(u|ù|ü|û|Ü|Û|Ù)",$pattern);
    return ereg_replace("(n|ñ|Ñ)","(n|ñ|Ñ)",$pattern);
  }

  /**
   * Standart function to make regexp patterns for insensitive to accent a search and SQL safe
   * @return transformed pattern
   */
  static function _fsearch_prepare_sql_pattern ( $pattern )
  {
    return mysql_real_escape_string(stdentity::_fsearch_prepare_pattern($pattern));
  }

  /**
   * Perform a search in elements from a partern
   * @param $pattern Patern to use to find elements
   * @param $limit Limit the ammount of elements retruned (null if unlimited)
   * @param $conds Adds extra conditions to search entities
   * @return an associative array ( id => name )
   */
  function fsearch ( $pattern, $limit=5, $conds = null )
  {
    return $this->_fsearch(stdentity::_fsearch_prepare_sql_pattern($pattern),$limit,false,$conds);
  }

  /**
   * Count elements matching a partern.
   * @param $pattern Patern to use to find elements
   * @param $conds Adds extra conditions to search entities
   * @return the count of elements matching.
   */
  function fsearch_countall ( $pattern, $conds = null )
  {
    return $this->_fsearch(stdentity::_fsearch_prepare_sql_pattern($pattern),null,true,$conds);
  }

  /**
   * Check if instance can provide a preview image.
   */
  function can_preview()
  {
    return false;
  }

  /**
   * Return instance preview image. Only require 'id' to be loaded.
   */
  function get_preview()
  {
    return null;
  }

  /**
   * Get extended informations on instance (minimalist). Require all instance informations loaded.
   * @return html contents
   */
  function get_html_extended_info()
  {
    return "<b>".htmlentities($this->get_display_name(),ENT_COMPAT,"UTF-8")."</b>";
  }

  /**
   * Check if class universe can be explored using get_root_element(), get_childs(), get_parent()
   */
  function can_explore()
  {
    return false;
  }

  /**
   * Get root element for the class (can be of a different class)
   * @return an instance or null
   */
  function get_root_element()
  {
    return null;
  }

  /**
   * Get parent element of instance (can be of a different class)
   * @return an instance or null
   */
  function get_parent()
  {
    return null;
  }

  /**
   * Get childs of instance (can ba mix of differents class)
   * @return an array of class instances or null
   */
  function get_childs(&$user)
  {
    return null;
  }

  /**
   * Generate the patrh to the element using html link to each parent
   */
  function get_html_path()
  {
    $path = $this->get_html_link();
    $parent = $this->get_parent();
    while ( !is_null($parent) )
    {
      $path = $parent->get_html_link()." / ".$path;
      $parent = $parent->get_parent();
    }
    return $path;
  }

  /**
   * Check if class can describe an instance
   */
  function can_describe()
  {
    return false;
  }

  /**
   * Get description of instance
   * @see can_describe
   */
  function get_description()
  {
    return "";
  }

  /**
   * Return the list of tags associated to the instance if supported
   * @return list of tags (id=>name), or null if unsupported
   */
  function get_tags_list()
  {
    $class = get_class($this);

    if ( !isset($GLOBALS["entitiescatalog"][$class][6]) || !$GLOBALS["entitiescatalog"][$class][6] )
    {
      $class = get_parent_class($class);
      if ( !isset($GLOBALS["entitiescatalog"][$class][6]) || !$GLOBALS["entitiescatalog"][$class][6] )
        return null;
    }

    if ( !is_null($this->_tags) )
      return $this->_tags;

    $req = new requete($this->db,
      "SELECT tag.id_tag, tag.nom_tag ".
      "FROM ".$GLOBALS["entitiescatalog"][$class][6]." ".
      "INNER JOIN tag USING(id_tag) ".
      "WHERE ".$GLOBALS["entitiescatalog"][$class][0]."='".mysql_escape_string($this->id)."' ".
      "ORDER BY nom_tag");

    $this->_tags = array();

    while ( list($id,$tag) = $req->get_row() )
      $this->_tags[$id] = $tag;

    return $this->_tags;
  }

  /**
   * Return the list of tags in ahuman readable format (if supported)
   * @return a string, or null if unsupported
   */
  function get_tags()
  {
    $tags = $this->get_tags_list();

    if ( is_null($tags) )
      return null;

    return implode(", ",$tags);
  }

  /**
   * Set tags of instance using a array of tags name.
   * Create missing tags if any.
   * @param $tags Array of tags name : array("tag1","tag2"...)
   */
  function set_tags_array($tags)
  {
    $class = get_class($this);

    if ( !isset($GLOBALS["entitiescatalog"][$class][6]) || !$GLOBALS["entitiescatalog"][$class][6] )
    {
      $class = get_parent_class($class);
      if ( !isset($GLOBALS["entitiescatalog"][$class][6]) || !$GLOBALS["entitiescatalog"][$class][6] )
        return null;
    }

    $conds = array();

    $tocreate=array();

    if(!empty($tags))
    {
      foreach ( $tags as $tag )
      {
        $conds[] = "nom_tag='".mysql_escape_string($tag)."'";
        $tocreate[$tag]=$tag;
      }
    }

    $tags = array();

    if ( count($conds) > 0 )
    {
      $req = new requete($this->db, "SELECT id_tag, nom_tag FROM tag WHERE ".implode(" OR ",$conds));

      while ( list($id,$tag) = $req->get_row() )
      {
        $tags[$id]=$tag;
        unset($tocreate[$tag]);
      }

      if(!empty($tocreate))
      {
        foreach ( $tocreate as $tag )
        {
          $crt = new insert($this->dbrw, "tag", array("nom_tag"=>$tag));
          $tags[$crt->get_id()]=$tag;
        }
      }
    }

    $actual = $this->get_tags_list();

    foreach ( $tags as $id => $tag )
    {
      if ( !isset($actual[$id]) )
      {
        new insert($this->dbrw, $GLOBALS["entitiescatalog"][$class][6],
          array("id_tag"=>$id,$GLOBALS["entitiescatalog"][$class][0]=>$this->id));
        $id = mysql_escape_string($id);
        new requete($this->dbrw, "UPDATE tag SET nombre_tag=nombre_tag+1 WHERE id_tag='$id'");
      }
      else
        unset($actual[$id]);
    }

    if(!empty($actual))
    {
      foreach ( $actual as $id => $tag )
      {
        new delete($this->dbrw, $GLOBALS["entitiescatalog"][$class][6],
          array("id_tag"=>$id,$GLOBALS["entitiescatalog"][$class][0]=>$this->id));
        $id = mysql_escape_string($id);
        new requete($this->dbrw, "UPDATE tag SET nombre_tag=nombre_tag-1 WHERE id_tag='$id'");
      }
    }

    $this->_tags=$tags;
  }

  /**
   * Set tags using a human readable string
   * Create missing tags if any.
   * @param $tags String of tags : "tag1, tag2, tag3..."
   */
  function set_tags($tags)
  {
    $tags=trim(mb_strtolower($tags,"UTF-8"));

    if ( empty($tags) )
    {
      $this->set_tags_array(array());
      return;
    }

    $l1 = explode(",",$tags);
    $l2 = array();
    foreach ( $l1 as $tag )
    {
      $l2[] = trim($tag);
    }

    $this->set_tags_array($l2);
  }

}


?>
