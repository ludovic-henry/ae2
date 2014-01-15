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

require_once($topdir."include/entities/basedb.inc.php");
require_once($topdir."include/entities/asso.inc.php");
require_once($topdir."include/lib/mailer.inc.php");

// Type de champ possible, chacun a ses particularités
define(TYPE_TEXT, 'text');
define(TYPE_DATE, 'date');
define(TYPE_EMAIL, 'email');
define(TYPE_SELECT, 'select');
define(TYPE_TEXT_AREA, 'text_area');
define(TYPE_SUBMIT, 'submit');
define(TYPE_INFO, 'info');
define(TYPE_RADIO, 'radio');
define(TYPE_CHECK, 'check');

/*

  Type de JSON à parser

  Structure globale :

  { nom_du_champ : [type, estNecessaire, args], ... }

  nom_du_champ : un string qui définit la propriété name du champ
  type : une valeur numérique correspondant aux defines ci-dessus
  estNecessaire : un boolean, définit si le champ est facultatif ou pas
  args : un dico avec les clés et les valeurs correspondant aux paramètres de nos fonctions

 */

function array_get ($array, $key, $default)
{
  return array_key_exists ($key, $array) ? $array[$key] : $default;
}

class formulaire extends basedb
{
  var $id = 0;
  var $id_asso = 0;
  var $name = '';
  var $prev_text = '';
  var $next_text = '';
  var $success_text = '';
  var $mail_text = '';
  var $json = '';

  function load_by_id ($id)
  {
    $req = new requete ($this->db,
                        'SELECT * FROM `aecms_forms` WHERE id_form = '.intval($id).' LIMIT 1');

    if ($req->lines != 1) {
      $this->id = -1;
      return false;
    }

    $row = $req->get_row ();

    return $this->_load ($row);
  }

  function load_by_asso ($id)
  {
    $req = new requete ($this->db,
                        'SELECT * FROM `aecms_forms` WHERE id_asso = '.intval($id).' LIMIT 1');

    if ($req->lines != 1) {
      $this->id = -1;
      return false;
    }

    $row = $req->get_row ();

    return $this->_load ($row);
  }

  function _load ($row)
  {
    $this->id = $row['id_form'];
    $this->id_asso = $row['id_asso'];
    $this->name = $row['name'];
    $this->prev_text = $row['prev_text'];
    $this->next_text = $row['next_text'];
    $this->success_text = $row['success_text'];
    $this->mail_text = $row['mail_text'];
    $this->json = $row['json'];

    return true;
  }

  function create ()
  {
    $rep = $this->check ($this->name, $this->json);
    if ($rep != false)
      return $rep;

    $req = new insert ($this->dbrw, 'aecms_forms', array( 'id_asso' => $this->id_asso,
                                                          'name' => $this->name,
                                                          'prev_text' => $this->prev_text,
                                                          'next_text' => $this->next_text,
                                                          'success_text' => $this->success_text,
                                                          'mail_text' => $this->mail_text,
                                                          'json' => $this->json));

    $this->id = $req->get_id ();

    return false;
  }

  function update ()
  {
    $rep = $this->check ($this->name, $this->json);
    if ($rep != false)
      return $rep;

    $req = new update ($this->dbrw, 'aecms_forms',
                       array( 'id_asso' => $this->id_asso,
                              'name' => $this->name,
                              'prev_text' => $this->prev_text,
                              'next_text' => $this->next_text,
                              'success_text' => $this->success_text,
                              'mail_text' => $this->mail_text,
                              'json' => $this->json),
                       array ('id_form' => $this->id));

    return $req == false ? 'Erreur lors de la mise à jour' : false;
  }

  function check($name, $json)
  {
    $obj = json_decode ($json, TRUE);
    if ($obj == NULL) {
      return "Erreur lors de la vérification du code JSON, il y'a surement une faute de syntaxe";
      /*switch(json_last_error()) {
      case JSON_ERROR_DEPTH:
        return ' - Maximum stack depth exceeded';
        break;
      case JSON_ERROR_CTRL_CHAR:
        return ' - Unexpected control character found';
        break;
      case JSON_ERROR_SYNTAX:
        return ' - Syntax error, malformed JSON';
        break;
      case JSON_ERROR_NONE:
        return ' - No errors';
        break;
        }*/
    }

    if (empty($name))
      return "Le nom du formulaire est vide";

    return false;
  }

  function is_valid ($id_asso)
  {
    return $this->id > 0 && $this->id_asso == $id_asso;
  }

  function is_admin (&$user)
  {
    if (!$user->is_valid ())
      return false;

    global $site;
    if(!$site->asso->is_member_role ($user->id,ROLEASSO_MEMBREBUREAU)
       && !$user->is_in_group ('root') )
      return false;

    return true;
  }

  function validate_and_post ()
  {
    $obj = json_decode ($this->json, TRUE);
    if ($obj == NULL)
      return 'JSON decode error';

    $result_array = array();

    foreach ($obj as $name=>$args) {
      if (count($args) != 3 || empty($name))
        return 'JSON malformed';

      if ($args[0] == "info")
        continue;

      if (!isset($_REQUEST[$name]))
        return 'Erreur du champ '.$name;

      if ($args[1] == TRUE) {
        if (empty($_REQUEST[$name]))
          return 'Le champ '.$name.' n\'est pas renseigné';

        if ($args[0] == TYPE_EMAIL && !CheckEmail($_REQUEST[$name], 3))
          return 'L\'email donné dans le champ '.$name.' n\'est pas valide';
      }

      if ($args[0] != TYPE_SUBMIT && $args[0] != TYPE_DATE)
        $result_array[$name] = $_REQUEST[$name];
      if ($args[0] == TYPE_DATE)
        $result_array[$name] = date('Y-m-d', $_REQUEST[$name]);
    }

    $req = new insert ($this->dbrw, 'aecms_forms_results', array('id_form' => $this->id,
                                                                 'json_answer' => json_encode($result_array)));

    $this->send_validation_email ($result_array);

    return false;
  }

  function get_form ($action, $page, $erreur)
  {
    $frm = new form ($action, $page, false, 'POST', $this->name);
    $frm->allow_only_one_usage ();
    if ($erreur != false)
      $frm->error ($erreur);

    $frm->add_hidden('action', $action);

    return $this->build ($frm);
  }

  function build ($frm)
  {
    $obj = json_decode ($this->json, TRUE);
    if ($obj == NULL)
      return false;

    foreach ($obj as $name=>$args) {
      if (count($args) != 3 || empty($name))
        return false;

      // Get the type of field
      switch ($args[0]) {
      case TYPE_TEXT:
        $this->_build_text ($frm, $name, $args[1], $args[2]);
        break;
      case TYPE_DATE:
        $this->_build_date ($frm, $name, $args[1], $args[2]);
        break;
      case TYPE_EMAIL:
        $this->_build_email ($frm, $name, $args[1], $args[2]);
        break;
      case TYPE_SELECT:
        $this->_build_select($frm, $name, $args[1], $args[2]);
        break;
      case TYPE_TEXT_AREA:
        $this->_build_text_area ($frm, $name, $args[1], $args[2]);
        break;
      case TYPE_SUBMIT:
        $this->_build_submit ($frm, $name, $args[2]);
        break;
      case TYPE_INFO:
        $this->_build_info ($frm, $args[2]);
        break;
      case TYPE_RADIO:
        $this->_build_radio ($frm, $name, $args[1], $args[2]);
        break;
      case TYPE_CHECK:
        $this->_build_check ($frm, $name, $args[2]);
        break;
      }

    }

    return $frm;
  }

  function _build_text ($frm, $name, $necessaire, $args)
  {
    $frm->add_text_field ($name, array_get($args, 'title', $name),
                          array_get($args, 'value', ''),
                          $necessaire,
                          array_get($args, 'size', false));
  }

  function _build_date ($frm, $name, $necessaire, $args)
  {
    $frm->add_date_field ($name, array_get($args, 'title', $name),
                          array_get($args, 'value', -1),
                          $necessaire);
  }

  function _build_email ($frm, $name, $necessaire, $args)
  {
    // Same as text, it's only different upon verification
    $this->_build_text ($frm, $name, $necessaire, $args);
  }

  function _build_select ($frm, $name, $necessaire, $args)
  {
    $frm->add_select_field ($name, array_get($args, 'title', $name),
                            array_get($args, 'values', array()),
                            array_get($args, 'value', false), '', $necessaire);
  }

  function _build_text_area ($frm, $name, $necessaire, $args)
  {
    $frm->add_text_area ($name, array_get($args, 'title', $name),
                         array_get($args, 'value', ''),
                         array_get($args, 'width', 40),
                         array_get($args, 'height', 3),
                         $necessaire);
  }

  function _build_submit ($frm, $name, $args)
  {
    $frm->add_submit($name, array_get($args, 'title', $name));
  }

  function _build_info ($frm, $args)
  {
    $frm->add_info (array_get($args, 'infos', ''));
  }

  function _build_radio ($frm, $name, $necessaire, $args)
  {
    $frm->add_radiobox_field ($name, array_get($args, 'title', $name),
                              array_get($args, 'values', array()),
                              array_get($args, 'value', false),
                              false,
                              $necessaire);
  }

  function _build_check ($frm, $name, $args)
  {
    $frm->add_checkbox ($name, array_get($args, 'title', $name),
                        array_get($args, 'checked', false));
  }

  function _build_html ($valeurs)
  {
    $result .= '<p>';
    $result .= $this->mail_text;
    $result .= '</p>';

    $result = '<br /><p><table>';

    foreach ($valeurs as $key=>$value) {
      $result .= '<tr><th>'.$key.'</th><th>'.$value.'</th></tr>';
    }

    $result .= '</table></p>';

    return $result;
  }

  function _build_plain ($valeurs)
  {
    $result .= '\n';
    $result .= $this->mail_text;

    $result = '\n\n--------------------------';

    foreach ($valeurs as $key=>$value) {
      $result .= '# '.$key.' # '.$value.' #';
    }

    $result .= '--------------------------';

    return $result;
  }

  function send_validation_email ($valeurs)
  {
    $obj = json_decode ($this->json, TRUE);
    if ($obj == NULL)
      return;

    foreach ($obj as $name=>$args) {
      if ($args[0] != TYPE_EMAIL)
        continue;

      if (array_get($args[2], 'validation_dest', false)) {
        $asso = new asso ($this->db, $this->dbrw);
        $asso->load_by_id ($this->id_asso);

        $from = $asso->is_valid () ? $asso->email : 'ae@utbm.fr';

        $mail = $_REQUEST[$name];
        $mailer = new mailer($from, 'Confirmation de '.$this->name);
        $mailer->add_dest ($mail);

        $infos_plain = $this->_build_plain ($valeurs);
        $infos_html = $this->_build_html ($valeurs);

        $mailer->set_plain ('Confirmation de votre participation à '.$this->name.$infos_plain);
        $mailer->set_html ('<p>Confirmation de votre participation à '.$this->name.'</p>'
                           .$infos_html);

        $mailer->send ();

        if (!$asso->is_valid ())
          return;

        $mailer = new mailer ($from, 'Nouveau participant à '.$this->name);
        $mailer->add_dest ($asso->email);
        $mailer->set_plain ('Nouveau participant avec l\'adresse email : '.$mail);
        $mailer->set_html ('<p>Nouveau participant avec l\'adresse email : '.$mail.'</p>');

        return;
      }
    }
  }
}

?>
