<?php

/* Copyright 2008
 * - Benjamin Collet < bcollet at oxynux dot org >
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

define("TRACKTYPE_BUG",1);
define("TRACKTYPE_IPT",2);
define("TRACKTYPE_TASK",3);

define("TRACKPRIORITY_CRITIAL",1);
define("TRACKPRIORITY_HIGH",2);
define("TRACKPRIORITY_AVG",3);
define("TRACKPRIORITY_LOW",4);

define("TRACKSTATUS_FREE",0);
define("TRACKSTATUS_DONE",1);
define("TRACKSTATUS_INVALID",2);
define("TRACKSTATUS_WONTFIX",3);
define("TRACKSTATUS_DUPLICATE",4);

define("TRACKCOMPONENT_SITE",1);
define("TRACKCOMPONENT_SAS",2);
define("TRACKCOMPONENT_NEWS",3);
define("TRACKCOMPONENT_MMT",4);
define("TRACKCOMPONENT_FORUM",5);
define("TRACKCOMPONENT_WIKI",6);
define("TRACKCOMPONENT_COMPTOIRS",7);
define("TRACKCOMPONENT_RESA",8);
define("TRACKCOMPONENT_COMPTA",9);
define("TRACKCOMPONENT_AECMS",10);
define("TRACKCOMPONENT_JOBETU",11);
define("TRACKCOMPONENT_UVS",12);
define("TRACKCOMPONENT_LAVERIE",13);
define("TRACKCOMPONENT_COVOIT",14);
define("TRACKCOMPONENT_PG",15);
define("TRACKCOMPONENT_DISPLAY",16);
define("TRACKCOMPONENT_PLANET",17);
define("TRACKCOMPONENT_TRACKING",18);

$GLOBALS['TRACKTYPE'] = array
(
  TRACKTYPE_BUG => "Bug",
  TRACKTYPE_IPT => "Amélioration",
  TRACKTYPE_TASK => "Tâche"
);

$GLOBALS['TRACKPRIORITY'] = array
(
  TRACKPRIORITY_CRITICAL => "Critique",
  TRACKPRIORITY_HIGH => "Urgent",
  TRACKPRIORITY_AVG => "Moyen",
  TRACKPRIORITY_LOW => "Faible"
);

$GLOBALS['TRACKSTATUS'] = array
{
  TRACKSTATUS_FREE => "Non attribué",
  TRACKSTATUS_DONE => "Résolu",
  TRACKSTATUS_INVALID => "Non valide",
  TRACKSTATUS_WONTFIX => "Ne sera pas corrigé",
  TRACKSTATUS_DUPLICATE => "Doublon"
};

$GLOBALS['TRACKCOMPONENT'] = array
{
  TRACKCOMPONENT_SITE => "Site",
  TRACKCOMPONENT_SAS => "SAS",
  TRACKCOMPONENT_NEWS => "Nouvelles",
  TRACKCOMPONENT_MMT => "Matmatronch",
  TRACKCOMPONENT_FORUM => "Forum",
  TRACKCOMPONENT_WIKI => "Wiki",
  TRACKCOMPONENT_COMPTOIRS => "Comptoirs/eboutic",
  TRACKCOMPONENT_RESA => "Réservations (salles, matériel, inventaire)",
  TRACKCOMPONENT_COMPTA => "Comptabilité",
  TRACKCOMPONENT_AECMS => "aecms",
  TRACKCOMPONENT_JOBETU => "Jobetu",
  TRACKCOMPONENT_UVS => "Pédagogie",
  TRACKCOMPONENT_LAVERIE => "Laverie",
  TRACKCOMPONENT_COVOIT => "Covoiturage",
  TRACKCOMPONENT_PG => "Petit géni",
  TRACKCOMPONENT_PLANET => "Planet",
  TRACKCOMPONENT_DISPLAY => "Affichage",
  TRACKCOMPONENT_TRACKING => "Tracking"
};

class tracking extends stdentity
{
  var $id;
  var $title;
  var $type;
  var $content;
  var $component;
  var $private;
  var $owner;
  var $priority;
  var $status;

  function load_by_id($id)
  {
    $req = new requete($this->db, "SELECT * FROM `tracking`
                                   INNER JOIN `tracking_history` ON `tracking`.`last_ticket_history` = `tracking_history`.`id_ticket_history`
                                   WHERE `id_ticket` = '".mysql_real_escape_string($id)."'");

    if($req->lines == 1)
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = -1;
    return false;
  }

  function _load($row)
  {
    $this->id = $row['id_ticket'];
    $this->title = $row['title_ticket'];
    $this->type = $row['type_ticket'];
    $this->content = $row['content_ticket'];
    $this->component = $row['component_ticket'];
    $this->private = $row['private_ticket'];
    $this->owner = $row['id_utilisateur_owner'];
    $this->priority = $row['priority_ticket'];
    $this->status = $row['status_ticket'];
  }

  function add($title, $type, $content, $description, $content, $component, $private, $priority)
  {
    $this->title = $title;
    $this->type = $type;
    $this->content = $content;
    $this->component = $component;
    $this->private = $private;
    $this->owner = NULL;
    $this->priority = $priority;
    $this->status = 0;

    $req = new insert($this->dbrw,"tracking_tickets",
                      array("title_ticket" => $this->title,
                            "type_ticket" => $this->type,
                            "content_ticket" => $this->content,
                            "component_ticket" => $this->component,
                            "private_ticket" => $this->private));

    if($req)
      $this->id = $req->get_id();

    $this->add_history($this->content, $site->user->id);
  }

  function update()
  {
    if($this->id > 0)
    {
      $req = new update($this->dbrw,"tracking_tickets",
                        array("title_ticket" => $this->title,
                              "type_ticket" => $this->type,
                              "content_ticket" => $this->content,
                              "component_ticket" => $this->component,
                              "private_ticket" => $this->private),
                        array("id_ticket" => $this->id));
    }
  }

  function add_history($comment, $id_utilisateur)
  {
    if($this->id > 0)
    {
      $req = new insert($this->dbrw,"tracking_history",
                        array("priority_ticket" => $this->priority,
                              "status_ticket" => $this->status,
                              "comment_ticket" => $this->

    }
  }

  function delete_history($id_history)
  {
    new delete($this->dbrw,"tracking_history",array("id_ticket_history" => $id_history));
  }

  function update_history($id_history)
  {

  }

  function get_history($id_history)
  {
    $req = new requete($this->db,"SELECT * FROM `tracking_history`
                                  WHERE `id_ticket_history`='".$id_history."'
                                  AND `id_ticket`='".$this->id."'");
    if($req->lines == 1)
      return $req->get_row();
    else
      return false;
  }

  function get_all_history()
  {
    $req = new requete($this->db,"SELECT * FROM `tracking_history`
                                  WHERE `id_ticket`='".$this->id."'");
    return $req->result;
  }
}

?>
