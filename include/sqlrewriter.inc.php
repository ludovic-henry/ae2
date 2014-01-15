<?php
/* Copyright 2008
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
 * Classe utilitaire pour la ré-écriture de requêtes SQL
 *
 * @author Julien Etelain
 */
class sqlrewriter
{
  var $select='';
  var $from='';
  var $where='';
  var $wherewrap=false;
  var $groupby='';
  var $having='';
  var $havingwrap=false;
  var $orderby='';
  var $limit='';
  var $fields=array();

  /**
   * Consrtuit un sqlrewrite en partant sur une requête SQL.
   * Attention/ Ne fonctionne *que* pour les requêtes de selection !
   *
   * Decompose la requête de selection en ses principales composantes.
   *
   * Quelques limitations et contraintes :
   * - Les mots clefs SQL DOIVENT être en majuscules et être entourés d'espaces :
   * ' FROM ', ' WHERE ', ' GROUP BY ', ' HAVING ', ' LIMIT ', ' AS ' ... ;
   * - Les champs calculé DOIVENT avoir des alias ;
   * - Les sous-requêtes NE SONT PAS supportées.
   *
   * @param $sql requête SQL
   */
  function sqlrewriter ( $sql )
  {
    $sql = str_replace('\n',' ',$sql); // Protection des requêtes insérées avec le coding style de pedrov

    $ppos = $pos = stripos($sql, 'FROM ');
    $this->select = substr($sql,0,$pos);

    $pos = strpos($sql, 'WHERE ', $ppos);
    if ( $pos !== false )
    {
      $this->from = substr($sql,$ppos,$pos-$ppos);
      $ppos=$pos;
      $p='where';
    }
    else
      $p='from';

    $pos = strpos($sql, 'GROUP BY ', $ppos);
    if ( $pos !== false )
    {
      $this->$p = substr($sql,$ppos,$pos-$ppos);
      $ppos=$pos;
      $p='groupby';
    }

    $pos = strpos($sql, 'HAVING ', $ppos);
    if ( $pos !== false )
    {
      $this->$p = substr($sql,$ppos,$pos-$ppos);
      $ppos=$pos;
      $p='having';
    }

    $pos = strpos($sql, 'ORDER BY ', $ppos);
    if ( $pos !== false )
    {
      $this->$p = substr($sql,$ppos,$pos-$ppos);
      $ppos=$pos;
      $p='orderby';
    }

    $pos = strpos($sql, 'LIMIT ', $ppos);
    if ( $pos !== false )
    {
      $this->$p = substr($sql,$ppos,$pos-$ppos);
      $this->limit = substr($sql,$pos);
      $ppos=$pos;
    }
    else
      $this->$p = substr($sql,$ppos);
  }

  private function register_field($type,$nom)
  {
    $nom = trim(str_replace('`','',$nom));

    $p = strpos($nom,'.');
    if ( $p !== false )
    {
      $this->fields[substr($nom,$p+1)] = array($type,$nom);
      return;
    }
    $this->fields[$nom] = array($type,$nom);
  }

  /**
   * Extraits les champs de la requête SQL et determine leur type (alias ou champ réel).
   *
   * L'ensemble des données sont positionnées dans $this->fields :
   * nom du champ renvoyé par get_row() => array ( type, nom complet dans la requête ).
   *
   * Avec type =1 pour les alias, et type=0 pour les champs réels.
   */
  function extract_fields()
  {
    $ppos=6;
    $pos0=0;
    $pos1=0;
    do
    {
      $type=0;
      $pos = strpos($this->select, ',', $ppos);
      if ( $pos0 !== false && $pos0 < $ppos )
      $pos0 = stripos($this->select, ' AS ', $ppos);
      if ( $pos1 !== false && $pos1 < $ppos )
      $pos1 = strpos($this->select, '(', $ppos);

      if ( $pos !== false && (( $pos1 !== false && $pos1 < $pos) || ($pos0 !== false && $pos0 < $pos) ) )
      {
        $type=1;

        $ppos = $pos0+4;
        $pos = strpos($this->select, ',', $ppos);
      }

      if ( $pos !== false )
      {
        $this->register_field($type, substr($this->select,$ppos,$pos-$ppos));
        $ppos = $pos+1;
      }
      else
      {
        $this->register_field($type, substr($this->select,$ppos));
        $ppos = false;
      }

    }
    while ( $ppos !== false );

  }

  /**
   * Renvoie la requête SQL reconstruite
   * @return la requête SQL
   */
  function get_sql()
  {
    return $this->select.$this->from.$this->where.$this->groupby.
      $this->having.$this->orderby.$this->limit;
  }

  function reset_orderby()
  {
    $this->orderby = '';
  }

  function add_orderbyraw ( $raw, $o = 'ASC' )
  {
    if ( empty($this->orderby) )
      $this->orderby = 'ORDER BY '.$raw.' '.$o.' ';
    else
      $this->orderby .= ', '.$raw.' '.$o.' ';
    return true;
  }

  function add_orderby ( $nom, $o = 'ASC' )
  {
    if ( empty($this->orderby) )
      $this->orderby = 'ORDER BY '.$this->fields[$nom][1].' '.$o.' ';
    else
      $this->orderby .= ', '.$this->fields[$nom][1].' '.$o.' ';
    return true;
  }

  function add_condition ( $nom, $condition )
  {
    if ( count($this->fields) == 0 )
      $this->extract_fields();

    if ( !isset($this->fields[$nom]) )
      return false;

    $field = $this->fields[$nom];

    if ( $field[0] == 1 ) // Alias => HAVING
    {
      if ( !$this->havingwrap )
      {
        if ( !empty($this->having ) )
          $this->having = 'HAVING ('.substr($this->having,7).') AND ';
        else
          $this->having = 'HAVING ';
        $this->havingwrap = true;
      }
      else
          $this->having .= 'AND ';

      $this->having .= $field[1].' '.$condition.' ';

      return true;
    }

    if ( !$this->wherewrap )
    {
      if ( !empty($this->where ) )
        $this->where = 'WHERE ('.substr($this->where,6).') AND ';
      else
        $this->where = 'WHERE ';
      $this->wherewrap = true;
    }
    else
        $this->where .= 'AND ';

    $this->where .= $field[1].' '.$condition.' ';

    return true;
  }

  function set_limit ( $start, $length )
  {
    $this->limit='LIMIT '.$start.','.$length;
  }

}

?>
