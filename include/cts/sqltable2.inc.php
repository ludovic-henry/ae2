<?php
/* Copyright 2005-2008
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
require_once($topdir."include/catalog.inc.php");


/**
 * Nouvelle version de sqltable qui vise à proposer de nouvelles fonctionalités
 * trés coin coin.
 *
 * L'API est différent de la v1, sqltable1 vous permet de disposer d'un API
 * compatible avec la v1.
 *
 * Nouveautés : "Filtrer" et "Ordonner par" (c'est le serveur qui fait le travail)
 * et peut être la pagination si je suis en forme.
 *
 * Malgrés ses nouvelles fonctionalités sqltable v2 est en moyenne 65% plus
 * rapide que sqltable v1 grâce à une meilleure implémentation. Même s'il est
 * vrai que quelques pourcents pourraient être gagnés en enlevant les nouvelles
 * fonctionalités.
 *
 * @author Julien Etelain
 */
class sqltable2 extends stdcontents
{

  private $columns;
  private $page;
  private $actions;
  private $batch;
  private $nom;
  private $id_name;

  private $sort;

  private $page_self;

  /**
   * Construit un sqltable2
   * @param $nom Nom du tableau (doit être unique)
   * @param $titre Titre du tableau (peut être null)
   * @param $page Page qui va être la cible des actions par défaut
   * @param $page_self Url vers la page contenant la sqltable2
   */
  public function sqltable2 ( $nom, $titre, $page, $page_self = null )
  {
    $this->actions = array();
    $this->batch = array();
    $this->columns = array();
    $this->nom = $nom;
    $this->page = $page;
    $this->title = $titre;

    if ( is_null($page_self) )
      $this->page_self = $page;
    else
      $this->page_self = $page_self;
  }

  /**
   * Ajoute une action pouvant s'effectuer sur un seul élément à la fois
   * L'id de la ligne concernée sera passé dans une variable du même nom
   * que le champ SQL précisé à set_data.
   * @param $action Action (sera passé dans $_GET/$_REQUEST["action"] à $page)
   * @param $title Titre de l'action
   * @param $icon Iconne à affiché (si null, utilie l'iconne appropriée dans images/actions)
   * @param $page Page cible de l'action (si null utilise celle passée au constructeur )
   */
  public function add_action ( $action, $title, $icon=null, $page=null )
  {
    global $wwwtopdir,$topdir;

    if ( is_null($page) )
      $page = $this->page;

    if ( strstr($page,"?") )
      $page = $page."&action=".$action."&";
    else
      $page = $page."?action=".$action."&";

    if ( is_null($icon) && file_exists($topdir."images/actions/".$action.".png") )
      $icon = $wwwtopdir."images/actions/".$action.".png";

    $this->actions[$action] = array($title,$icon,$page);
  }

  /**
   * Ajoute une action pouvant s'effectuer sur plusieurs éléments à la fois
   * Les ids des lignes selectionnées seront passé dans une variable du même nom
   * que le champ SQL précisé à set_data avec un "s" à la fin.
   * @param $action Action (sera passé dans $_GET/$_REQUEST["action"] à $page précisé au constructeur)
   * @param $title Titre de l'action
   */
  public function add_batch_action ( $action, $title )
  {
    $this->batch[$action] = array($title);
  }

  private static function found_entity ( $field, &$class, &$idfield )
  {
    foreach ( $GLOBALS["entitiescatalog"] as $key => $row )
    {
      $l = strlen($row[1]);
      if ( !strncmp($row[1],$field,$l) )
      {
        $idfield = $row[0].substr($field,$l);
        $class = $key;
        return true;
      }
    }
    return false;
  }

  /**
   * Ajoute une colonne et detecte automatiquement son type
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column ( $column, $title, $fields=null )
  {
    if ( $column == "solde" || $column == "montant" || !strncasecmp("sum",$column,3) )
      $this->add_column_price($column, $title, $fields);

    else if ( !strncasecmp("date",$column,4) )
      $this->add_column_date($column, $title, $fields);

    else if ( !strncasecmp("stock",$column,5) )
      $this->add_column_quantity($column, $title, $fields);

    else if ( !strncasecmp("doku",$column,4) )
      $this->add_column_dokuwiki($column, $title, $fields);

    else
      $this->add_column_entity($column, $title, $fields);

  }

  /**
   * Ajoute une colonne de type texte
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_text ( $column, $title, $fields=null )
  {
    global $timing;
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("text",$title,$fields);
  }

  /**
   * Ajoute une colonne de type date ou date/heure
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_date ( $column, $title, $fields=null )
  {
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("date",$title,$fields);
  }

  /**
   * Ajoute une colonne de type monétaire.
   * Attention: La valeur attendue est en centimes (contrairement à sqltable v1)
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_price ( $column, $title, $fields=null )
  {
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("price",$title,$fields);
  }

  /**
   * Ajoute une colonne de type quantitée.
   * La valeur attentue est un entier : -1 pour "Non limité", positif ou 0
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_quantity ( $column, $title, $fields=null )
  {
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("qty",$title,$fields);
  }

  /**
   * Ajoute une colonne de type quantitée.
   * La valeur attentue est un nombre
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_number ( $column, $title, $fields=null )
  {
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("number",$title,$fields);
  }

  /**
   * Ajoute une colonne de type image.
   * La valeur attendue est l'url de l'image.
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_image ( $column, $title, $fields=null )
  {
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("image",$title,$fields);
  }

  /**
   * Ajoute une colonne de type répétition image.
   * La valeur attendue est le nombre de répétition de l'image
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $src URL de l'image à répéter
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_image_repetition ( $column, $title, $src, $fields=null )
  {
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("imrpt",$title,$fields,null,null,$src);
  }

  /**
   * Ajoute une colonne de type texte formatté.
   * La valeur attentue est un texte au format dokuwiki.
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_dokuwiki ( $column, $title, $fields=null )
  {
    if ( is_null($fields) )
      $fields = array($column);
    $this->columns[$column] = array("doku",$title,$fields);
  }

  /**
   * Ajoute une colonne de type entité : la classe est détectée automatiquement.
   *
   * Précisez comme champ SQL le champ correspondant au titre de l'entité, il
   * doit commencer comme le champ standart de nommage de l'entité.
   * (pour "asso", le champ standart est "nom_asso", on pourra prendre
   * nom_asso_stuff par exemple ou simplement nom_asso).
   *
   * Deux valeurs sont attendues :
   * - le titre de l'entité, dans le champ précisé (ex: nom_asso_stuff)
   * - l'id de l'entité, dans le champ id correspondant (ex: alors id_asso_stuff)
   *
   * @param $column Nom de la colonne, nom du champ SQL si $fields non précisé.
   * @param $title Titre de la colonne
   * @param $fields Champs SQL (voir add_column)
   * @see add_column
   */
  public function add_column_entity ( $column, $title, $fields=null )
  {
    if ( !is_null($fields) )
    {
      $entities = true;
      $classes = array();
      $idfields = array();

      foreach ( $fields as $field )
      {
        if ( $entities = ($entities && $this->found_entity($field,$class,$idfield)) )
        {
          $classes[$field] = $class;
          $idfields[$field] = $idfield;
        }
      }

      if ( $entities )
      {
        $this->add_column_entities($column,$title,$classes,$fields,$idfields);
        return;
      }
    }
    else if ( $this->found_entity($column,$class,$idfield) )
    {
      $this->add_column_entities($column,$title,array($column=>$class),array($column),array($column=>$idfield));
      return;
    }

    $this->add_column_text($column, $title, $fields);

  }

  private function add_column_entities ( $column, $title, $classes, $fields, $idfields)
  {
    $this->columns[$column] = array("entity",$title,$fields,$classes,$idfields);
  }

  /**
   * Définit un lien pour une colonne
   * Pour chaque cellule un lien vers cette page avec l'identifiant de la ligne
   * sera inséré et de la colonne (si entity).
   * @param $column Nom de la colonne
   * @param $link Lien
   */
  public function set_column_link ( $column, $link )
  {
    if ( strstr($link,"?") )
      $this->columns[$column][7] = $link."&";
    else
      $this->columns[$column][7] = $link."?";
  }

  /**
   * Définit une action pour une colonne
   * Pour chaque cellule un lien vers cette action avec l'identifiant de la ligne
   * sera inséré et de la colonne (si entity).
   * @param $column Nom de la colonne
   * @param $action Action (sera passé dans $_GET/$_REQUEST["action"] à $page)
   * @param $page Page cible de l'action (si null utilise celle passée au constructeur )
   */
  public function set_column_action ( $column, $action, $page=null )
  {
    if ( is_null($page) )
      $page = &$this->page;

    if ( strstr($page,"?") )
      $this->columns[$column][7] = $page."&action=".$action."&";
    else
      $this->columns[$column][7] = $page."?action=".$action."&";
  }

  /**
   * Définit une table de correspondance pour les valeurs d'une colonne.
   * Cette table de correspondance sera appliqué pour tous les types de champs.
   * @param $column Nom de la colonne
   * @param $enumeration Table de correspondance
   */
  public function set_column_enumeration ( $column, $enumeration )
  {
    $this->columns[$column][6] = $enumeration;
  }

  /**
   * Définit une colonne comme ayant un nombre de valeurs trés diverses.
   * Ceci permettra de mettre un filtre aproprié.
   * @param $column Nom de la colonne
   * @param $enumeration Table de correspondance
   */
  public function set_column_isdiverse ( $column )
  {
    $this->columns[$column][8] = true;
  }

  /**
   * Définit les données du tableau
   * @param $id_name Champ SQL contenant l'identifiant unique de chaque ligne
   * @param $data Données : soit une objet requete, soit un tableau
   */
  public function set_data ( $id_name, &$data, $rewrited=false )
  {
    global $timing;
    $this->id_name = $id_name;


    if ( is_array($data) )
      $this->data = $data;
    else
    {
      $this->data = array();

      while ( $row = $data->get_row() )
        $this->data[] = $row;
    }

    // Support des fonctionalités asynchrone (tri et filtrage)

    if ( isset($_REQUEST["sqltable2"]) && $_REQUEST["sqltable2"] == $this->nom  )
    {
      if ( !$rewrited )
      {
        if ( isset($_REQUEST["__st2f"]) && is_array($_REQUEST["__st2f"]) )
        // SqlTable2Filter (fonctionne par champ sql!!)
        {
          $newdata = array();

          $filters = array();
          foreach ( $_REQUEST["__st2f"] as $field => $filter )
          {
            if ( $filter{1} == "d" )
              $filters[$field] = array($filter{0},"d",datetime_to_timestamp(substr($filter,2)));
            else if ( $filter{1} == "m" )
              $filters[$field] = array($filter{0},"m",get_prix(substr($filter,2)));
            else
              $filters[$field] = array($filter{0},$filter{1},substr($filter,2));
          }

          foreach ( $this->data as $row )
          {
            $match = true;
            foreach ( $filters as $field => $filter )
            {
              if ( $filter[1] == 'd' )
              {
                switch ( $filter[0] )
                {
                  case "=" : $match = $match && ( $filter[2] == strtotime($row[$field]) ); break;
                  case "!" : $match = $match && ( $filter[2] != strtotime($row[$field]) ); break;
                  case ">" : $match = $match && ( $filter[2] <= strtotime($row[$field]) ); break;
                  case "<" : $match = $match && ( $filter[2] >= strtotime($row[$field]) ); break;
                }
              }
              else
              {
                switch ( $filter[0] )
                {
                  case "=" : $match = $match && ( $filter[2] == $row[$field] ); break;
                  case "l" : $match = $match && ( strpos($row[$field],$filter[2]) !== false ); break;
                  case "!" : $match = $match && ( $filter[2] != $row[$field] ); break;
                  case ">" : $match = $match && ( $filter[2] <= $row[$field] ); break;
                  case "<" : $match = $match && ( $filter[2] >= $row[$field] ); break;
                }
              }
            }
            if ( $match )
              $newdata[] = $row;
          }
          $this->data = $newdata;
        }

        if ( isset($_REQUEST["__st2s"]) && is_array($_REQUEST["__st2s"]) )
         // SqlTable2Sorter (fonctionne par colonne!!)
        {
          $this->sorter = array();
          foreach ( $_REQUEST["__st2s"] as $column => $sort )
          {
            $this->sorter[$column] = array($sort{0}=="d"?-1:1,$sort{1}=="i"||$sort{1}=="m"?true:false);
          }
          usort ($this->data, array("sqltable2","compare_row"));
        }
      }

      header("Content-Type: text/html; charset=utf-8");
      /*$timing["all"] += microtime(true);
      echo "<tr><td colspan=\"".(count($this->columns)+count($this->action))."\">all:".$timing["all"].", mysql:".$timing["mysql"]."</td></tr>";
      if ( $rewrited )
        echo "<tr><td colspan=\"".(count($this->columns)+count($this->action))."\">".$rewrited."</td></tr>";*/
      echo $this->html_render(true);
      exit();
    }

  }

  private function compare_row ( &$a, &$b )
  {
    foreach ( $this->sorter as $column => $sort )
    {
      $this->get_colum_value($this->columns[$column],$a,$va,$fa);
      $this->get_colum_value($this->columns[$column],$b,$vb,$fb);
      if ( $va != $vb )
      {
        if ( $sort[1] )
          return $sort[0]*(intval($va)-intval($vb));
        else
          return $sort[0]*strcasecmp($va,$vb);
      }
    }
    return 0;
  }

  private function get_colum_value ( &$col, &$row, &$value, &$field )
  {
    $field=$col[2][0]; // Par défaut prends le premier champ

    if ( count($col[2]) > 1 ) // Prends la dernière colonne n'ayant pas une valeure non vide/nulle
    {
      for($i=1;$i<count($col[2]);$i++)
        if ( $row[$col[2][$i]] )
          $field = $col[2][$i];
    }

    if ( isset($col[6]) && !is_null($col[6]) ) // Colonne énuméré
      $value = $col[6][$row[$field]];
    else
      $value = $row[$field];
  }

  /**
   * Définit les données du tableau par le biai d'une requête SQL.
   * L'usage de cette fonction permet une meilleure implémentation des
   * fonctionalités avancées de sqltable v2.
   *
   * Cette fonction pourra être amenée à ré-écrire votre requête, elle doit donc
   * respecter les contraintes de sqlrewriter.

   * @param $db Lien à la base de donnée
   * @param $id_name Champ SQL contenant l'identifiant unique de chaque ligne
   * @param $sql Requête SQL
   * @see sqlrewriter
   */
  public function set_sql ( &$db, $id_name, $sql )
  {
    global $topdir;

    if ( isset($_REQUEST["sqltable2"]) && $_REQUEST["sqltable2"] == $this->nom )
    {
      require_once($topdir."include/sqlrewriter.inc.php");
      $rewriter = new sqlrewriter($sql);
      $rewriter->extract_fields();
      if ( isset($_REQUEST["__st2f"]) && is_array($_REQUEST["__st2f"]) )
      // SqlTable2Filter (fonctionne par champ sql!!)
      {
        foreach ( $_REQUEST["__st2f"] as $field => $filter )
        {
          switch ( $filter{1} )
          {
            case "d" : $val = date('Y-m-d H:i:s',datetime_to_timestamp(substr($filter,2))); break;
            case "m" : $val = get_prix(substr($filter,2)); break;
            default : $val = substr($filter,2); break;
          }
          switch ( $filter{0} )
          {
            case "=" : $cond = "= '".mysql_real_escape_string($val)."'"; break;
            case "l" : $cond = "LIKE '%".mysql_real_escape_string($val)."%'"; break;
            case "!" : $cond = "!= '".mysql_real_escape_string($val)."'"; break;
            case ">" : $cond = ">= '".mysql_real_escape_string($val)."'"; break;
            case "<" : $cond = "<= '".mysql_real_escape_string($val)."'"; break;
          }
          $rewriter->add_condition($field,$cond);
        }
      }

      if ( isset($_REQUEST["__st2s"]) && is_array($_REQUEST["__st2s"]) )
      // SqlTable2Sorter (fonctionne par colonne!!)
      {
        //NOTE: les colonnes énumérées ne sont pas correctement supportées
        $rewriter->reset_orderby();
        foreach ( $_REQUEST["__st2s"] as $column => $sort )
        {
          $col = $this->columns[$column];

          if ( count($col[2]) == 1 ) // cas d'une simple colonne
            $rewriter->add_orderby($col[2][0],$sort{0}=="d"?'DESC':'ASC');
          else
          {
            $fields=array();
            foreach ( $col[2] as $nom )
            {
              $fields[] = $rewriter->fields[$nom][1];
            }
            $rewriter->add_orderbyraw('COALESCE('.implode(',',array_reverse($fields)).')',$sort{0}=="d"?'DESC':'ASC');
          }
        }
      }
      $this->set_data($id_name,new requete($db,$rewriter->get_sql()),$rewriter->get_sql());
      return;
    }

    $this->set_data($id_name,new requete($db,$sql));

  }


  public function html_render ($dataonly=false)
  {
    global $wwwtopdir,$timing;

    // ===== Contents =====
    // (Header après)

    $lnum = 0;
    $this->buffer .= "<input type=\"hidden\" id=\"".$this->nom."_count\" value =\"".count($this->data)."\" />\n";

    if ( !$dataonly && $this->page_self )
      $domains=array();

    foreach ( $this->data as $row )
    {
      $t = ($lnum+1)%2;

      $this->buffer .= "<tr id=\"".$this->nom."_l".$lnum."\" class=\"ln".$t."\" ".
        "onmouseout=\"stdc(this,'l',".$t.");\" ".
        "onmouseover=\"stdc(this,'o',".$t.");\" ";

      if ( count($this->batch) > 0 )
      {
        $this->buffer .= "onmousedown=\"stckl(this,'".$this->id_name."',".$lnum.");\">\n";
        $this->buffer .= "<td>".
          "<input type=\"checkbox\" class=\"chkbox\" ".
          "name=\"".$this->id_name."s[".$lnum."]\" value=\"".$row[$this->id_name]."\" ".
          "id=\"".$this->nom."_c".$lnum."\" ".
          "onclick=\"stsck(this,".$lnum.",".$t.");\" /></td>\n";
      }
      else
        $this->buffer .= ">\n";

      foreach ( $this->columns as $key => $col )
      {
        $this->get_colum_value ( $col, $row, $value, $field );

        if ( isset($domains) && $col[0] != "price" && $col[0] != "date" &&
             $col[0] != "qty" && $col[0] != "number" && !isset($col[8]) )
        {
          if ( $col[0] == "entity" )
          {
            $idfield = $col[4][$field];
            if ( !isset($domains[$key][$idfield][$row[$idfield]]) )
              $domains[$key][$idfield][$row[$idfield]] = array($value?$value:"(aucun)",$col[3][$field]);
          }
          else if ( !isset($domains[$key][$field][$row[$field]]) )
            $domains[$key][$field][$row[$field]] = array($value?$value:"(aucun)");
        }

        if ( $col[0] == "price" )
          $this->buffer .= "<td class=\"".$col[0]."\">";
        else
          $this->buffer .= "<td>";

        if ( isset($col[7]) && !is_null($col[7]) && !$col[3] ) // $col[3] non null si entity
          $this->buffer .= "<a href=\"".$col[7].$this->id_name."=".$row[$this->id_name]."\">";

        switch( $col[0] )
        {
          case "text" :
          $this->buffer .= htmlentities($value,ENT_COMPAT,"UTF-8");
          break;

          case "image" :
          $this->buffer .= "<img src=\"".htmlentities($value,ENT_COMPAT,"UTF-8")."\" alt=\"\" class=\"icon\" />";
          break;

          case "imrpt" :
          for($i=0;$i<$value;$i++)
            $this->buffer .= "<img src=\"".htmlentities($col[5],ENT_COMPAT,"UTF-8")."\" alt=\"\" class=\"icon\" />";
          break;

          case "price" :
          $this->buffer .= sprintf("%01.2f",$value/100);
          break;

          case "qty" :
          $this->buffer .= $value == -1 ? "Non limit&eacute;" : $value;
          break;

          case "number" :
          $this->buffer .= $value;
          break;

          case "doku" :
          $this->buffer .= doku2xhtml($value);
          break;

          case "date" :
          if ( preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/",$value,$m) )
            $this->buffer .= $m[3]."/".$m[2]."/".$m[1];
          else if ( preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/",$value,$m) )
            $this->buffer .= $m[3]."/".$m[2]."/".$m[1]." ".$m[4].":".$m[5];
          else
            $this->buffer .= htmlentities($value,ENT_COMPAT,"UTF-8");
          break;

          case "entity" :
          if ( $value )
          {
            $class=$col[3][$field];
            $icon = $GLOBALS["entitiescatalog"][$class][2];
            $link = $GLOBALS["entitiescatalog"][$class][3];
            if ( isset($col[7]) )
            {
               $idvar = $GLOBALS["entitiescatalog"][$class][0];
               $id = $row[$col[4][$field]];
               if ( $idvar != $this->id_name )
                 $this->buffer .= "<a href=\"".$col[7].$this->id_name."=".$row[$this->id_name]."&".$idvar."=".$id."\">";
               else
                 $this->buffer .= "<a href=\"".$col[7].$this->id_name."=".$row[$this->id_name]."\">";
            }
            else if ( $link )
            {
               $idvar = $GLOBALS["entitiescatalog"][$class][0];
               $id = $row[$col[4][$field]];
               $this->buffer .= "<a href=\"".$wwwtopdir.$link."?".$idvar."=$id\">";
               $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/".$icon."\" class=\"icon\" alt=\"\" />";
               $this->buffer .= "</a> ";
            }
            else
              $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/".$icon."\" class=\"icon\" alt=\"\" /> ";

            $this->buffer .= htmlentities($value,ENT_COMPAT,"UTF-8");
          }
          break;

        }

        if ( isset($col[7]) && !is_null($col[7]) )
          $this->buffer .= "</a>\n";

        $this->buffer .= "</td>\n";
      }

      foreach ( $this->actions as $action => $col )
      {
        $this->buffer .= "<td><a href=\"".$col[2].$this->id_name."=".$row[$this->id_name]."\">";

        if ( $col[1] )
          $this->buffer .= "<img src=\"".$col[1]."\" class=\"icon\" ".
            "alt=\"".htmlentities($col[0],ENT_COMPAT,"UTF-8")."\" ".
            "title=\"".htmlentities($col[0],ENT_COMPAT,"UTF-8")."\" />";
        else
          $this->buffer .= htmlentities($col[0],ENT_COMPAT,"UTF-8");

        $this->buffer .= "</a></td>\n";
      }

      $this->buffer .= "</tr>\n";

      $lnum++;
    }

    if ( $dataonly )
      return $this->buffer;

    $contents = $this->buffer;

    // ===== Header =====

    $this->buffer="";

    $this->buffer .= "<div class=\"sqltable2\">\n";

    if ( count($this->batch) > 0 )
    {
      $this->buffer .= "<form name=\"".$this->nom."\" action=\"".$this->page."\" method=\"post\">\n";
      $this->buffer .= "<input type=\"hidden\" name=\"magicform[name]\" value =\"".$this->nom."\" />\n";
    }

    $this->buffer .= "<input type=\"hidden\" id=\"".$this->nom."_self\" value =\"".$this->page_self."\" />\n";

    $this->buffer .= "<table>\n";
    $this->buffer .= "<thead>\n<tr class=\"head\">\n";

    if ( count($this->batch) > 0 )
      $this->buffer .= "<th><input type=\"checkbox\" onclick=\"stcka(this,'".$this->nom."');\" /></th>\n";

    foreach ( $this->columns as $key => $col )
    {
      $this->buffer .= "<th id=\"".$this->nom."_".$key."\">".htmlentities($col[1],ENT_COMPAT,"UTF-8");

      if ( $this->page_self )
      {
        $this->buffer .= " <a href=\"#\" onclick=\"stst('".$this->nom."','".$key."'); return false;\"><img src=\"".$wwwtopdir."images/icons/16/sort_a.png\" id=\"".$this->nom."_s".$key."_i\" class=\"icon\" alt=\"\" /></a>";

        switch ( $col[0] )
        {
          case "price" :
          $this->buffer .= "<input type=\"hidden\" id=\"".$this->nom."_".$key."_t\" value=\"m\">";
          break;
          case "qty" :
          case "number" :
          case "imrpt" :
          $this->buffer .= "<input type=\"hidden\" id=\"".$this->nom."_".$key."_t\" value=\"i\">";
          break;
          case "date" :
          $this->buffer .= "<input type=\"hidden\" id=\"".$this->nom."_".$key."_t\" value=\"d\">";
          break;
          default :
          $this->buffer .= "<input type=\"hidden\" id=\"".$this->nom."_".$key."_t\" value=\"s\">";
          break;
        }

        if ( isset($col[8]) )
        {


        }
        else if ( $col[0] == "price" || $col[0] == "date" || $col[0] == "qty" || $col[0] == "number" )
        {
          if ( count($col[2]) == 1 ) // Ne fonctionne que dans ce cas
          {
            $this->buffer .= " <a href=\"#\" onclick=\"stft('".$this->nom."','".$key."'); return false;\"><img src=\"".$wwwtopdir."images/icons/16/find.png\" class=\"icon\" alt=\"\" /></a>";
            $this->buffer .= "<div id=\"".$this->nom."_f".$key."\" class=\"filter\" style=\"display:none;\">";
            $this->buffer .= "<h4>Filtrer</h4>";
            $this->buffer .= "<div class=\"fcts\">";

            $this->buffer .= "<div><select id=\"".$this->nom."_f".$key."_s\" ";
            $this->buffer .= "onchange=\"stftcf('".$this->nom."','".$key."');\">";
            $this->buffer .= "<option value=\"\">Tout afficher</option>";
            $this->buffer .= "<option value=\"=\">&eacute;gal &agrave;</option>";
            $this->buffer .= "<option value=\"!\">diff&eacute;rent de</option>";
            $this->buffer .= "<option value=\"&gt;\">&gt;=</option>";
            $this->buffer .= "<option value=\"&lt;\">&lt;=</option>";
            $this->buffer .= "</select>\n";
            $this->buffer .= "<input type=\"text\" id=\"".$this->nom."_f".$key."_v\" class=\"val\" /></div>";
            $this->buffer .= "<input type=\"button\" onclick=\"stcft('".$this->nom."','".$key."','".$col[2][0]."');\" value=\"Filtrer\" />";
            $this->buffer .= "</div></div>\n";
          }
        }
        else // Liste les valeurs de chaque colonne
        {
          $this->buffer .= " <a href=\"#\" onclick=\"stft('".$this->nom."','".$key."'); return false;\"><img src=\"".$wwwtopdir."images/icons/16/find.png\" class=\"icon\" alt=\"\" /></a>";
          $this->buffer .= "<div id=\"".$this->nom."_f".$key."\" class=\"filter\" style=\"display:none;\">";
          $this->buffer .= "<h4>Filtrer</h4>";
          $this->buffer .= "<ul>";
          $this->buffer .= "<li class=\"sel\"><a href=\"#\" onclick=\"stuft(this,'".$this->nom."','".$key."'); return false;\">Tout afficher</a></li>";
          if (! empty($this->data))
          {
            foreach ( $domains[$key] as $field => $values )
            {
              asort($values);
              foreach ( $values as $value => $label )
              {
                $value = htmlentities(addslashes($value),ENT_COMPAT,"UTF-8");

                $this->buffer .= "<li><a href=\"#\" onclick=\"stftv(this,'".$this->nom."','".$key."','".$field."','".$value."'); return false;\">";
                switch ( $col[0] )
                {
                  case "image" :
                  $this->buffer .= "<img src=\"".htmlentities($label[1],ENT_COMPAT,"UTF-8")."\" ".
                    "alt=\"\" class=\"icon\" />";
                  break;
                  case "imrpt" :
                  for($i=0;$i<$label[0];$i++)
                    $this->buffer .= "<img src=\"".htmlentities($col[5],ENT_COMPAT,"UTF-8")."\" ".
                      "alt=\"\" class=\"icon\" />";
                  break;
                  case "doku" :
                  $this->buffer .= doku2xhtml($value);
                  break;
                  case "entity" :
                  $icon = $GLOBALS["entitiescatalog"][$label[1]][2];
                  $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/".$icon."\" class=\"icon\" alt=\"\" />";
                  default:
                  $this->buffer .= htmlentities($label[0],ENT_COMPAT,"UTF-8");
                  break;
                }
                $this->buffer .= "</a></li>";
              }
            }
          }
          $this->buffer .= "</ul>";
          $this->buffer .= "</div>\n";
        }
      }
      $this->buffer .= "</th>\n";
    }

    foreach ( $this->actions as $action => $col )
      $this->buffer .= "<th></th>\n";

    $this->buffer .= "</tr>\n</thead>\n";
    $this->buffer .= "<tbody id=\"".$this->nom."_contents\">\n";

    $this->buffer .= $contents;

    // ===== Footer =====

    $this->buffer .= "</tbody>\n";
    $this->buffer .= "</table>\n";
    if ( count($this->batch) > 0 )
    {
      $this->buffer .= "<p class=\"batch\">Pour la s&eacute;lection : <select name=\"action\">\n";

      foreach ( $this->batch as $action => $col )
          $this->buffer .= "<option value=\"".$action."\">".htmlentities($col[0],ENT_COMPAT,"UTF-8")."</option>\n";

      $this->buffer .= "</select>\n<input type=\"submit\" value=\"Valider\" />\n</p>\n";
      $this->buffer .= "</form>\n";
    }
    $this->buffer .= "</div>\n";

    return $this->buffer;
  }

}


/**
 * Permet d'utiliser une sqltable2 avec l'API de sqltable1.
 *
 * Les "hack" ne sont pas disponibles :
 * - Les champs "=num" et "*_folder"
 * - Le paramètre $htmlentitize
 *
 */
class sqltable1 extends sqltable2
{

  /**
   * Génére une table basé sur une requéte SQL avec actions (supprimer, édtier)
   * @param $formname Nom du formulaire
   * @param $title Titre
   * @param $req objet request associé (ou un array(array(field=>value)))
   * @param $page Page qui va être la cible des actions
   * @param $id_field Champ qui contient l'id de l'objet
   * @param $cols colonnes à traiter (id=>Description) (ou id_defaut=>array(Description,id1,id2,id3...))
   * @param $actions actions sur chaque objet (envoyé à %page%?action=%action%&%id_field%=[id])
   * @param $batch_actions actions possibles sur plusieurs objets (envoyé à page, les id sont le tableau %id_field%s)
   * @param $enumerated valeurs des champs énumérés ($enumerated[id] = array(0=>"truc"))
   * @param $htmlentitize indique si les entrées du tableau doivent être passées par la fonction htmlentities()
   */
  function sqltable1 ( $formname, $title, $sql, $page, $id_field, $cols, $actions, $batch_actions, $enumerated=array())
  {
    $this->sqltable2($formname, $title, $page);

    foreach ( $cols as $field => $name )
    {
      if ( is_array($name) )
      {
        $name = $name[0];
        $this->add_column($field,$name,array_slice($name,1));
      }
      else
        $this->add_column($field,$name);
    }

    foreach ( $actions as $action => $title )
      $this->add_action($action,$title);

    foreach ( $batch_actions as $action => $title )
      $this->add_batch_action($action,$title);

    foreach ( $enumerated as $field => $enumeration )
      $this->set_column_enumeration($field,$enumeration);

    $this->set_data($id_field,$sql);

  }
}


?>
