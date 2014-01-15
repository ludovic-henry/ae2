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

require_once($topdir."include/catalog.inc.php");

/**
 * Affiche un arbre d'éléments depuis une requête SQL
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class treects extends itemlist
{

  var $data;
  var $entity;
  var $ent_id;
  var $ent_name;

  function treects ( $title=null, $req, $start=0, $id_field, $id_parent_field, $name_field )
  {
    global $topdir;

    while ( $row = $req->get_row() )
    {
      if ( !$row[$id_parent_field] ) $row[$id_parent_field] = 0;
      $this->data[$row[$id_field]]["row"] = $row;
      $this->data[$row[$id_parent_field]]["childs"][] = $row[$id_field];
    }

    $reg=null;
    $this->ent_name = $name_field;

    foreach ( $GLOBALS["entitiescatalog"] as $ent )
    {
      if ( ereg("^".$ent[1]."(.*)$",$this->ent_name,$reg))
      {
        $this->ent_id = $ent[0].$reg[1];
        $this->entity = $ent;
        break;
      }
    }

    if ( $start !=0 )
    {
      if ($this->entity && $this->data[$start]["row"][$this->ent_id])
      {
        $this->title = "<a href=\"".$topdir.$this->entity[3]."?".$ent[0]."=".$this->data[$start]["row"][$this->ent_id]."\">";
        $this->title .= "<img src=\"".$topdir."images/icons/16/".$this->entity[2]."\" class=\"icon\" alt=\"\" />";
        $this->title .= " ".htmlentities($this->data[$start]["row"][$this->ent_name],ENT_NOQUOTES,"UTF-8");
        $this->title .= "</a> ";
      }
      else
        $this->title = htmlentities($this->data[$start]["row"][$this->ent_name],ENT_NOQUOTES,"UTF-8");
    }
    else
      $this->title = $title;

    $this->tree_itere(&$this,$start);

  }

  function tree_itere ( $itm, $id )
  {
    global $topdir;
    if(!empty($this->data[$id]["childs"]))
    {
      foreach ( $this->data[$id]["childs"] as $sid )
      {



        if ($this->entity && $this->data[$sid]["row"][$this->ent_id])
        {
          $title = "<a href=\"".$topdir.$this->entity[3]."?".$this->entity[0]."=".$this->data[$sid]["row"][$this->ent_id]."\">";
          $title .= "<img src=\"".$topdir."images/icons/16/".$this->entity[2]."\" class=\"icon\" alt=\"\" />";
          $title .= " ".htmlentities($this->data[$sid]["row"][$this->ent_name],ENT_NOQUOTES,"UTF-8");
          $title .= "</a> ";
        }
        else
          $title = htmlentities($this->data[$sid]["row"][$this->ent_name],ENT_NOQUOTES,"UTF-8");

        if ( !$this->data[$sid]["childs"] )
          $itm->add($title);
        elseif ( $this->data[$sid]["done"]==1 ) // Boulet Proof !
          $itm->add($title." (déjà vu, voir plus haut)");
        else
        {
          $this->data[$sid]["done"]=1;
          $nitm = new itemlist($title);
          $this->tree_itere(&$nitm,$sid);
          $itm->add($nitm);
        }
      }
    }
  }

}

?>
