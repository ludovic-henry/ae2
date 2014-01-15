<?php
/* Copyright 2011
 * - Jérémie Laval < jeremie dot laval at gmail dot com >
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

$topdir = '../';
require_once($topdir."include/mysql.inc.php");

define('TODO_TABLE', 'ae_info_todo');

$todo_status = array ('New', 'WontFix', 'Invalid', 'InProgress', 'Fixed');
$todo_priorities = array ('Invalid', 'Low', 'Med', 'High', 'Critical', 'OMG');
$todo_types = array ('Fonc', 'Bug');

class todoitem extends stdentity
{
    var $id_task;
    var $id_user_reporter;
    var $id_user_assignee;
    var $id_asso_concerned;
    var $date_submitted;
    var $priority;
    var $enh_or_bug;
    var $status;

    var $desc;
    var $todo;

    function todoitem ( &$db, &$dbrw = null )
    {
        $this->stdentity ($db,$dbrw);
        $this->id_task = -1;
        $this->priority = 0;
        $this->status = 0;
        $this->enh_or_bug = 0;
        $this->desc = '';
        $this->todo = '';
        $this->date_submitted = time();
    }

    function load_by_id ($id)
    {
        $req = new requete($this->db, 'SELECT * FROM `'.TODO_TABLE.'` WHERE `id_task` = '.$id.' LIMIT 1');

        if ($req->lines == 1) {
            $this->_load ($req->get_row ());
            return true;
        }

        $this->id_task = -1;
        return false;
    }

    function _load ($row)
    {
        $this->id_task = $row['id_task'];
        $this->id_user_reporter = $row['id_utilisateur_reporter'];
        $this->id_user_assignee = $row['id_utilisateur_assignee'];
        $this->id_asso_concerned = $row['id_asso_concerned'];
        $this->date_submitted = strtotime($row['date_submitted']);
        $this->priority = $row['priority'];
        $this->enh_or_bug = $row['enh_or_bug'];
        $this->status = $row['status'];
        $this->desc = $row['description'];
        $this->todo = $row['todo'];
    }

    function update_some ($what,$value, $id)
    {
      $update = new update($this->dbrw, TODO_TABLE,
        array($what => $value),
        array('id_task' => $id));
    }

    function update ()
    {
        if ($this->id_task == -1) {
            $insert = new insert ($this->dbrw, TODO_TABLE,
                                     array ('id_utilisateur_reporter' => $this->id_user_reporter,
                                            'id_utilisateur_assignee' => $this->id_user_assignee,
                                            'id_asso_concerned' => $this->id_asso_concerned,
                                            'date_submitted' => date("Y-m-d", $this->date_submitted),
                                            'priority' => $this->priority,
                                            'enh_or_bug' => $this->enh_or_bug,
                                            'status' => $this->status,
                                            'description' => $this->desc,
                                            'todo' => html_entity_decode($this->todo, ENT_NOQUOTES, 'UTF-8')
                                            ));
            $this->id_task = $insert->get_id ();
        } else {
            $update = new update ($this->dbrw, TODO_TABLE,
                                  array ('id_utilisateur_reporter' => $this->id_user_reporter,
                                         'id_utilisateur_assignee' => $this->id_user_assignee,
                                         'id_asso_concerned' => $this->id_asso_concerned,
                                         'date_submitted' => date("Y-m-d", $this->date_submitted),
                                         'priority' => $this->priority,
                                         'enh_or_bug' => $this->enh_or_bug,
                                         'status' => $this->status,
                                         'description' => $this->desc,
                                         'todo' => html_entity_decode($this->todo, ENT_NOQUOTES, 'UTF-8')
                                         ),
                                  array ('id_task' => $this->id_task));
        }
    }
}

?>
