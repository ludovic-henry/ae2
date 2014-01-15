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

$topdir="../";

require_once($topdir."include/site.inc.php");
require_once($topdir."include/entities/utilisateur.inc.php");
require_once($topdir."include/entities/asso.inc.php");
require_once($topdir."include/cts/user.inc.php");
require_once($topdir."include/entities/todoitem.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("none","group",7);

$site->start_page("none","TODO list");

if (isset ($_REQUEST['action']) && $_REQUEST['action'] == 'commit') {
    $todo = new todoitem ($site->db, $site->dbrw);
    $todo->id_task = intval($_REQUEST['id_task']);
    $todo->id_user_reporter = $_REQUEST['utilisateur_reporter'];
    $todo->id_user_assignee = $_REQUEST['utilisateur_assignee'];
    $todo->id_asso_concerned = $_REQUEST['asso_concerned'];

    $todo_date =  new todoitem ($site->db);
    $todo_date->load_by_id ($todo->id_task);
    $todo->date_submitted = isset($todo_date->date_submitted) ? $todo_date->date_submitted : time();

    $todo->priority = intval ($_REQUEST['priority']);
    $todo->status = intval ($_REQUEST['status']);
    $todo->enh_or_bug = $_REQUEST['type'];
    $todo->desc = $_REQUEST['desc'];
    $todo->todo = $_REQUEST['todo'];

    $todo->update ();
}

if (isset($_REQUEST['action']) ) {
  $idtask = isset ($_GET['id_task']) ? intval($_GET['id_task']) : -1;

  if ($_REQUEST['action'] == 'stop') {
    $todo = new todoitem ($site->db, $site->dbrw);
    if ($idtask != -1)
      $todo->update_some('status','1',$idtask);
  }

  if ($_REQUEST['action'] == 'done') {
    $todo = new todoitem ($site->db, $site->dbrw);
    if ($idtask != -1)
      $todo->update_some('status','4',$idtask);
  }

  if ($_REQUEST['action'] == 'accord') {
    $todo = new todoitem ($site->db, $site->dbrw);
    if ($idtask != -1)
      $todo->update_some('status','3',$idtask);
  }

}


if (isset ($_REQUEST['action']) && $_REQUEST['action'] != 'commit' && $_REQUEST['action'] != 'stop'
  && $_REQUEST['action'] != 'done' && $_REQUEST['action'] != 'accord') {
    $idtask = isset ($_GET['id_task']) ? intval($_GET['id_task']) : -1;

    $todo = new todoitem ($site->db);
    $util_reporter = new utilisateur ($site->db);
    $util_assignee = new utilisateur ($site->db);
    $asso_concerne = new asso ($site->db);
    if ($idtask != -1) {
        $todo->load_by_id ($idtask);
        $util_reporter->load_by_id ($todo->id_user_reporter);
        $util_assignee->load_by_id ($todo->id_user_assignee);
        $asso_concerne->load_by_id ($todo->id_asso_concerned);
    } else {
        $util_reporter = $site->user;
        $asso_concerne->load_by_id (1);
        $util_assignee->load_by_id (0);
    }

    $enable = $util_reporter->id == $site->user->id || $site->user->is_in_group("root")? true : false;

    $frm = new form ('details', 'infotodo.php', false, 'POST', 'TODO');
    $frm->add_hidden ('id_task', $idtask);
    $frm->add_hidden ('action', 'commit');
    $frm->add_entity_smartselect ('utilisateur_reporter', 'Rapporteur', $util_reporter);
    if ( $site->user->is_in_group("root") )
      $frm->add_entity_smartselect ('utilisateur_assignee', 'Assigné à', $util_assignee);
    else
      $frm->add_hidden('utilisateur_assignee',0);
    $frm->add_entity_smartselect ('asso_concerned', 'Asso lié', $asso_concerne);
    $frm->add_date_field ('date_submitted', 'Soumis le', $idtask == -1 ? time () : $todo->date_submitted, false, false);
    $_REQUEST['date_submitted'] = $todo->date_submitted;
    if ( $site->user->is_in_group("root") )
      $frm->add_select_field ('priority', 'Priorité', $todo_priorities, $todo->priority);
    else
      $frm->add_hidden('priority','0');
    if ( $site->user->is_in_group("root") )
      $frm->add_select_field ('status', 'Statut', $todo_status, $todo->status);
    else
      $frm->add_hidden('status','0');
    $frm->add_select_field ('type', 'Type', $todo_types, $todo->enh_or_bug, "", false, $enable);
    $frm->add_text_field ('desc', 'Description', $todo->desc,false,false,false, $enable);
    $frm->add_text_area ('todo', 'Todo', $todo->todo, 80, 10, false, false, $enable);
    $frm->add_submit ('submit', 'Valider');

    $cts = new contents ('Détail');
    $cts->add_paragraph ('<a href="infotodo.php">Retour à la liste</a>');
    $cts->add ($frm);
    $site->add_contents ($cts);
} else {
    $cts = new contents ('Filtrage');
    $frmfilter = new form('filter', '?', false, 'POST', 'Filter');
    $frmfilter->add_select_field('etat', 'Etat', array('' => 'Tout', 'new' => 'Nouveau', 'resolu' => 'Résolu', 'encours' => 'En cours'), isset ($_REQUEST['etat']) ? $_REQUEST['etat'] : '');
    $frmfilter->add_checkbox ('onlyme', 'Uniquement ceux assignés à moi', isset ($_REQUEST['onlyme']) ? $_REQUEST['onlyme'] : false);
    $frmfilter->add_submit ('submit', 'Filtrer');
    $cts->add ($frmfilter, false);

    $where = array();
    if (isset ($_REQUEST['onlyme']) && $_REQUEST['onlyme'])
        $where[] = 'id_utilisateur_assignee='.$site->user->id;
    if (isset ($_REQUEST['etat'])) {
        $etats = array('new' => 1, 'resolu' => 5, 'encours' => 4);
        if (array_key_exists ($_REQUEST['etat'], $etats))
            $where[] = 'ae_info_todo.status='.$etats[$_REQUEST['etat']];
    } else {
        // Don't show fixed stuff by default
        $where[] = 'ae_info_todo.status != 5 AND ae_info_todo.status != 2';
    }

    $sql = 'SELECT ae_info_todo.*, asso.nom_asso as nom_asso_concerned,
      (SELECT status_name FROM ae_info_todo_codetxt WHERE id_code=ae_info_todo.status-1) as status_name,
      (SELECT priority_name FROM ae_info_todo_codetxt WHERE id_code=ae_info_todo.priority-1) as priority_name,
      (SELECT be_name FROM ae_info_todo_codetxt WHERE ae_info_todo_codetxt.id_code=ae_info_todo.enh_or_bug) as be_name,
      (SELECT CONCAT(utilisateurs.prenom_utl,\' \',utilisateurs.nom_utl) FROM utilisateurs WHERE
        utilisateurs.id_utilisateur=ae_info_todo.id_utilisateur_reporter) as nom_utilisateur_reporter
    FROM ae_info_todo
    LEFT JOIN asso ON asso.id_asso=ae_info_todo.id_asso_concerned
    WHERE `id_utilisateur_assignee` = \'0\' AND `status` = \'0\'';

   $req = new requete($site->db,$sql);

   $show_tblcts = $req->lines > 0;

    $tblcts = new contents('Nouvelle(s) tâche(s)');

    if ( $site->user->is_in_group("root") )
       $col = array('edit' => 'Détails',
         'stop' => 'WontFix');
    else
      $col = array('view' => 'Détails');

        $tbl = new sqltable ('infotodo2', 'Liste des nouvelles tâches', $req, 'infotodo.php', 'id_task',
                         array('nom_utilisateur_reporter' => 'Demandeur',
                               'nom_asso_concerned' => array('Club associé', 'nom_asso'),
                               'date_submitted' => 'Date soumission',
                               'priority_name' => 'Priorité',
                               'be_name' => 'Type',
                               'status_name' => 'Statut',
                               'description' => 'Description'),
                         $col,
                         array(),
                         array());

    $tblcts->add($tbl);

    $sql = 'SELECT ae_info_todo.*, asso.nom_asso as nom_asso_concerned, CONCAT(utilisateurs.prenom_utl,\' \',utilisateurs.nom_utl) as nom_utilisateur_assignee, (SELECT status_name FROM ae_info_todo_codetxt WHERE id_code=ae_info_todo.status-1) as status_name, (SELECT priority_name FROM ae_info_todo_codetxt WHERE id_code=ae_info_todo.priority-1) as priority_name, (SELECT be_name FROM ae_info_todo_codetxt WHERE ae_info_todo_codetxt.id_code=ae_info_todo.enh_or_bug) as be_name, (SELECT CONCAT(utilisateurs.prenom_utl,\' \',utilisateurs.nom_utl) FROM utilisateurs WHERE utilisateurs.id_utilisateur=ae_info_todo.id_utilisateur_reporter) as nom_utilisateur_reporter FROM ae_info_todo INNER JOIN utilisateurs ON utilisateurs.id_utilisateur=ae_info_todo.id_utilisateur_assignee LEFT JOIN asso ON asso.id_asso=ae_info_todo.id_asso_concerned';
    if (!empty ($where)) {
        if (count ($where) == 1)
            $sql .= ' WHERE '.$where[0];
        else
            $sql .= ' WHERE '.implode(' AND ', $where);
    }
    $sql .=  ' ORDER BY priority DESC, date_submitted';

    $req = new requete($site->db, $sql);

   $show_tblcts2 = $req->lines > 0;

    if ( $site->user->is_in_group("root") )
       $col = array('edit' => 'Détails',
         'accord' => 'InProgress',
         'done' => 'Done');
    else
      $col = array('view' => 'Détails');

    $tblcts2 = new contents('TODO list');
    $tbl = new sqltable ('infotodo', 'Liste des tâches', $req, 'infotodo.php', 'id_task',
                         array('nom_utilisateur_reporter' => 'Demandeur',
                               'nom_utilisateur_assignee' => 'Assigné à',
                               'nom_asso_concerned' => array('Club associé', 'nom_asso'),
                               'date_submitted' => 'Date soumission',
                               'priority_name' => 'Priorité',
                               'be_name' => 'Type',
                               'status_name' => 'Statut',
                               'description' => 'Description'),
                         $col,
                         array(),
                         array(),
                         true,
                         true,
                         array(),
                         "",
                         array('priority_name' => array('css' => 'prio',
                                                        'values' => array('Critical',
                                                                          'High',
                                                                          'Med',
                                                                          'Low',
                                                                          'Invalid'))),
                         array('InProgress' => 'inprog'));
    $tblcts2->add ($tbl);

    $intro = new contents();
    $intro->add_paragraph ('<a href="?action=nouveau">Reporter un bug / demander une fonctionnalité</a>');
    $intro->add_paragraph ('<a href="'.$topdir.'wiki2/?name=ae:info:done">Effectué depuis le dernier passage en production</a>');

    $site->add_contents ($intro);
    $site->add_contents ($cts);
    if ($show_tblcts)
      $site->add_contents ($tblcts);
    if ($show_tblcts2)
      $site->add_contents ($tblcts2);
}

$site->end_page();

?>
