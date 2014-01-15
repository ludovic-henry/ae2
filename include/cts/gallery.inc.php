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
 * @file
 */

/**
 * Permet d'afficher une "galerie" de contents ou d'éléments.
 *
 * Nottament utilisé dans le SAS, le matmatronch...
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class gallery extends stdcontents
{

  var $page;
  var $id_name;
  var $actions;
  var $batchactions;
  var $get_page;


  /**
   * Construit une galerie.
   * API similaire à sqltable.
   * @param $title Tire du contents
   * @param $class Classe css complémentaire
   * @param $name Nom du formulaire
   * @param $id_name Nom du cham identifiant
   * @param $actions Actions individuelles sur les éléments (array(action=>titre))
   * @param $batchactions Actions collectives (array(action=>titre))
   * @see sqltable
   */
  function gallery($title=false,$class=false,$name=false,$page=false,$id_name=false,$actions=array(),$batchactions=array())
  {
    $this->title = $title;


    $this->page = $page;
    $this->id_name = $id_name;
    $this->actions = $actions;
    $this->batchactions = $batchactions;

    if ( strstr($page,"?"))
      $this->get_page = $page."&";
    else
      $this->get_page = $page."?";

    if ( count($batchactions) )
      $this->buffer .= "<form name=\"$name\" action=\"$page\" method=\"POST\">\n";

    $this->buffer .= "<div class=\"gallery".($class?" ".$class:"")."\">\n";
  }
  /**
   * Ajoute un élèment
   * @param $data Contenu (html brut ou stdcontents)
   * @param $lable Légende de l'élèment (affiché en bas de la fiche)
   * @param $id Id de l'élèment (si actions ou batchactions)
   * @param $actallowed Actions authorisés (false:aucune, true:toutes, array(action1,action2...)
   * @param $class Classe css complémentaire
   */
  function add_item ( $data, $label=false, $id=false, $actallowed=false, $class=false )
  {

    $this->buffer .= "<div class=\"galitem".($class?" ".$class:"")."\">\n";

    $this->buffer .= "<div class=\"galitemdata\">";

    if ( is_object($data) )
      $this->buffer .= $data->html_render()."\n";
    else
      $this->buffer .= $data."\n";

    $this->buffer .= "</div>";

    $this->buffer .= "<div class=\"galiteminfo\">";

    if ( count($this->batchactions) )
      $this->buffer .= "<input type=\"checkbox\" class=\"chkbox\" name=\"".$this->id_name."s[]\" value=\"".$id."\" />\n";

    if ( $label )
      $this->buffer .= $label."\n";

    if ( $actallowed && $label )
      $this->buffer .= "</div><div class=\"tooldep\">\n";

    if ( is_array($actallowed) )
    {
      foreach ( $actallowed as $action  )
        $this->_render_action($id, $action, $this->actions[$action] );
    }
    elseif ( $actallowed==true )
    {
      foreach ( $this->actions as $action => $name )
        $this->_render_action($id, $action, $name);
    }

    $this->buffer .= "</div>\n";

    $this->buffer .= "</div>\n";
  }

  function _render_action(  $id, $action, $name)
  {
    global $wwwtopdir,$topdir;
    $this->buffer .= "<a href=\"".$this->generate_hlink($action,$id)."\">";
    if ( file_exists( $topdir . "images/actions/" . $action.".png")   )
      $this->buffer .= "<img src=\"".$wwwtopdir . "images/actions/" . $action.".png\" alt=\"".htmlentities($name,ENT_NOQUOTES,"UTF-8")."\" title=\"".htmlentities($name,ENT_NOQUOTES,"UTF-8")."\" class=\"icon\" />";
    else
      $this->buffer .= htmlentities($name,ENT_NOQUOTES,"UTF-8");
    $this->buffer .= "</a>\n";
  }

  function generate_hlink ( $action, $id )
  {
    if ( strpos($id,"=") )
    {
      if ( !$action )
        return htmlentities($this->get_page.$id);
      else
        return htmlentities($this->get_page.$id."&action=".$action);
    }

    if ( !$action )
      return htmlentities($this->get_page.$this->id_name."=".$id);
    else
      return htmlentities($this->get_page.$this->id_name."=".$id."&action=".$action);
  }

  function html_render ()
  {
    $this->buffer .= "<div class=\"clearboth\"></div>\n</div>";

    if ( count($this->batchactions) )
    {
      $this->buffer .= "<p>\n<select name=\"action\">\n";
      foreach ($this->batchactions as $action => $name )
        $this->buffer .= "<option value=\"$action\">".htmlentities($name,ENT_NOQUOTES,"UTF-8")."</option>\n";
      $this->buffer .= "</select>\n<input type=\"submit\" value=\"ok\" />\n</p>\n";
      $this->buffer .= "</form>\n";
    }

    return $this->buffer;
  }

}

?>
