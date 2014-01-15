<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
 * Copyright 2010
 * - Jérémie Laval < jeremie dot laval at gmail dot com >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once ('include/site.inc.php');
require_once ('include/form.inc.php');
require_once ($topdir.'include/entities/news.inc.php');

$form = new formulaire ($site->db, $site->dbrw);

if (isset($_REQUEST['id_form']))
  $form->load_by_id ($_REQUEST['id_form']);
else
  $form->load_by_asso ($site->asso->id);

$Erreur = false;

$site->start_page (CMS_PREFIX.'form', 'Formulaire');
$cts = new contents();

if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action'] == 'addentry') {
    $Erreur = $form->validate_and_post ();

    if ($Erreur == false) {
      $cts->add_title (2, 'Merci de votre participation à : '.$form->name);
      $cts->add_paragraph ($form->success_text);

      $site->add_contents ($cts);
      $site->end_page ();

      exit(0);
    }
  }

  if ($_REQUEST['action'] == 'admin' && $form->is_admin ($site->user)) {
    if (!isset($_REQUEST['view']))
      $_REQUEST['view'] = 'panel';

    if (isset($_REQUEST['op'])) {
      $form->id_asso = $site->asso->id;
      $form->name = $_REQUEST['name'];
      $form->prev_text = $_REQUEST['prev_text'];
      $form->next_text = $_REQUEST['next_text'];
      $form->success_text = $_REQUEST['success_text'];
      $form->json = $_REQUEST['json'];

      if ($_REQUEST['op'] == 'createform') {
        $Erreur = $form->create ();
      } else if ($_REQUEST['op'] == 'updateform') {
        $form->id = $_REQUEST['id_form'];
        $Erreur = $form->update ();
      }
    }

    if (isset($_REQUEST['select']) && $_REQUEST['select'] == 'true') {
      $req = new requete ($site->db, 'SELECT * FROM `aecms_forms` WHERE id_asso = '.$site->asso->id);
      $names = array();

      while ($row = $req->get_row ()) {
        $names[$row['id_form']] = $row['name'];
      }

      $sfrm = new form ('admin', 'forms.php?action=admin&view='.$_REQUEST['view'], false, 'POST', 'Sélection du formulaire');
      $sfrm->add_select_field ('id_form', 'Nom du formulaire : ', $names);
      $sfrm->add_submit ('submit', 'Valider');

      $cts->add ($sfrm);
    }

    if ($_REQUEST['view'] == 'panel') {
      $cts->add_title(2, 'Admin des formulaires');

      $cts->add_paragraph ('<a href="forms.php?action=admin&view=addform">Ajouter un formulaire</a> / <a href="forms.php?action=admin&view=modform&select=true">Modifier un formulaire</a>');
      $cts->add_paragraph ('<a href="forms.php?action=admin&view=answers&select=true">Visualiser les résultats</a>');
    } else if ($_REQUEST['view'] == 'answers' && isset($_REQUEST['id_form'])) {
      $cts->add_paragraph ('<a href="forms.php?action=admin">Retour à l\'interface d\'admin</a>');

      $req = new requete ($site->db, 'SELECT * FROM `aecms_forms_results` WHERE id_form = '.$_REQUEST['id_form']);

      $cts->add_title(2, 'Résultats');

      $csv = '';
      $tbl = new table ('Résultats', 'inline doku');
      while ($row = $req->get_row ()) {
        $obj = json_decode ($row['json_answer'], TRUE);
        if ($obj == NULL)
          continue;

        $values = array_values($obj);
        $tbl->add_row ($values);

        foreach ($values as $value) {
          $csv .= $value;
          $csv .= ',';
        }
        $csv .= "\n";
      }

      $cts->add ($tbl);

      $cts->add_title (3, 'Format CSV');
      $cts->add_paragraph('<pre>'.$csv.'</pre>');

    } else if ($_REQUEST['view'] == 'addform') {
      $cts->add_paragraph ('<a href="forms.php?action=admin">Retour à l\'interface d\'admin</a>');

      $cfrm = new form ('admin', 'forms.php?action=admin&view=addform', false, 'POST', 'Ajout d\'un formulaire');
      if ($Erreur != false)
        $cfrm->error ($Erreur);
      $cfrm->add_hidden ('op', 'createform');
      $cfrm->add_text_field ('name', 'Nom du formulaire', $_REQUEST['name'], true);
      $cfrm->add_text_area('prev_text', 'Texte avant le formulaire', $_REQUEST['prev_text']);
      $cfrm->add_text_area('next_text', 'Texte après le formulaire', $_REQUEST['next_text']);
      $cfrm->add_text_area('success_text', 'Texte si succès de l\'opération', $_REQUEST['success_text']);
      $cfrm->add_text_area('json', 'Description JSON', $_REQUEST['json'], 60, 20, true);
      $cfrm->add_submit('submit', 'Valider');

      $cts->add ($cfrm);
    } else if ($_REQUEST['view'] == 'modform' && isset($_REQUEST['id_form'])) {
      $cts->add_paragraph ('<a href="forms.php?action=admin">Retour à l\'interface d\'admin</a>');

      $form->load_by_id ($_REQUEST['id_form']);

      $cfrm = new form ('admin', 'forms.php?action=admin&view=modform', false, 'POST', 'Modification d\'un formulaire');
      if ($Erreur != false)
        $cfrm->error ($Erreur);
      $cfrm->add_hidden ('op', 'updateform');
      $cfrm->add_hidden ('id_form', $_REQUEST['id_form']);
      $cfrm->add_text_field ('name', 'Nom du formulaire', $form->name, true);
      $cfrm->add_text_area('prev_text', 'Texte avant le formulaire', $form->prev_text);
      $cfrm->add_text_area('next_text', 'Texte après le formulaire', $form->next_text);
      $cfrm->add_text_area('success_text', 'Texte si succès de l\'opération', $form->success_text);
      $cfrm->add_text_area('json', 'Description JSON', $form->json, 60, 20, true);
      $cfrm->add_submit('submit', 'Valider');

      $cts->add ($cfrm);
    }

    $site->add_contents ($cts);
    $site->end_page ();

    exit(0);
  }

}

if (!$form->is_valid($site->asso->id)) {
  $cts->add_title(2, 'Erreur');
  $cts->add_paragraph ('Formulaire non disponible');
} else {
  $frm = $form->get_form ('addentry', 'forms.php', $Erreur);

  if ($frm == false) {
    $cts->add_title(2, 'Erreur');
    $cts->add_paragraph ('Il y\'a eu une erreur lors de la génération du formulaire, merci de réessayer plus tard');
  } else {
    $cts->add_title(2, $form->name);

    $cts->add_paragraph ($form->prev_text);
    $cts->add ($frm);
    $cts->add_paragraph ($form->next_text);
  }
}

$site->add_contents ($cts);
$site->end_page ();

?>