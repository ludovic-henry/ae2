<?php
/*
 * FORUM2
 *
 * Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
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

/**
 * @file
 */


/**
 * Message dand un sujet d'un forum
 */
class message extends stdentity
{

  var $id_utilisateur;
  var $id_sujet;

  var $titre;
  var $contenu;
  var $date;
  var $syntaxengine;

  var $id_utilisateur_moderateur;

  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `frm_message`
        WHERE `id_message` = '" .
           mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_initial_of_sujet ( $id_sujet )
  {
    $req = new requete($this->db, "SELECT * FROM `frm_message`
        WHERE `id_sujet` = '".mysql_real_escape_string($id_sujet) . "'
        ORDER BY id_message
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function _load ( $row )
  {
    $this->id = $row['id_message'];
    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_sujet = $row['id_sujet'];
    $this->titre = $row['titre_message'];
    $this->contenu = $row['contenu_message'];
    $this->date = strtotime($row['date_message']);
    $this->syntaxengine = $row['syntaxengine_message'];
    $this->id_utilisateur_moderateur = $row['id_utilisateur_moderateur'];
  }

  function create ( &$forum, &$sujet, $id_utilisateur, $titre, $contenu, $syntaxengine )
  {
    if ( $forum->id != $sujet->id_forum )
      return;

    $this->id_utilisateur = $id_utilisateur;
    $this->id_sujet = $sujet->id;
    $this->titre = $titre;
    $this->contenu = $contenu;
    $this->date = time();
    $this->syntaxengine = $syntaxengine;
    $this->id_utilisateur_moderateur = null;


    $req = new insert ($this->dbrw,
            "frm_message", array(
              "id_utilisateur"=>$this->id_utilisateur,
              "id_sujet"=>$this->id_sujet,
              "titre_message"=>$this->titre,
              "contenu_message"=>$this->contenu,
              "date_message"=>date("Y-m-d H:i:s",$this->date),
              "syntaxengine_message"=>$this->syntaxengine,
              "id_utilisateur_moderateur"=>$this->id_utilisateur_moderateur
            ));

    if ( $req )
    {
      $this->id = $req->get_id();

      $sujet->update_last_message($forum);
      $forum->update_last_sujet();

      $sujet->set_user_read ( $id_utilisateur, $this->id );

      return true;
    }

    $this->id = null;
    return false;
  }

  function update ( &$forum, &$sujet, $titre, $contenu, $syntaxengine, $id_utilisateur )
  {
    global $topdir;

    if ( $forum->id != $sujet->id_forum || $sujet->id != $this->id_sujet )
      return;

    // On fait expirer le cache du message : version normale puis la version passée au nosecret
    require_once($topdir."include/cts/cached.inc.php");
    $cache = new cachedcontents("msg".$this->id);
    $cache->expire();
    $cache = new cachedcontents("msg".$this->id."nsc");
    $cache->expire();

    $this->titre = $titre;
    $this->contenu = $contenu;
    $this->syntaxengine = $syntaxengine;
    $this->id_utilisateur_moderateur = null;

    $req = new update ($this->dbrw,
            "frm_message", array(
              "titre_message"=>$this->titre,
              "contenu_message"=>$this->contenu,
              "syntaxengine_message"=>$this->syntaxengine,
              "id_utilisateur_moderateur"=>$this->id_utilisateur_moderateur,
              "msg_modere_info"=>1,
            ),
            array("id_message"=>$this->id) );

    if ($id_utilisateur == $this->id_utilisateur)
      new insert($this->dbrw,"frm_modere_info", array("id_message"=>$this->id, "id_utilisateur"=>$id_utilisateur, "modere_action"=>"AUTOEDIT"));
    else
      new insert($this->dbrw,"frm_modere_info", array("id_message"=>$this->id, "id_utilisateur"=>$id_utilisateur, "modere_action"=>"EDIT"));
  }

  function set_modere ( $id_utilisateur )
  {
    $this->id_utilisateur_moderateur = $id_utilisateur;
    $req = new update ($this->dbrw,"frm_message",
        array("id_utilisateur_moderateur"=>$this->id_utilisateur_moderateur),
        array("id_message"=>$this->id) );
  }

  function delete ( &$forum, &$sujet, $id_utilisateur )
  {
    if ( $forum->id != $sujet->id_forum || $sujet->id != $this->id_sujet )
      return;

    new update($this->dbrw,"frm_message",array("msg_supprime"=>1, "msg_modere_info"=>1), array("id_message"=>$this->id));

    if ($id_utilisateur == $this->id_utilisateur)
      new insert($this->dbrw,"frm_modere_info", array("id_message"=>$this->id, "id_utilisateur"=>$id_utilisateur, "modere_action"=>"AUTODELETE"));
    else
      new insert($this->dbrw,"frm_modere_info", array("id_message"=>$this->id, "id_utilisateur"=>$id_utilisateur, "modere_action"=>"DELETE"));

    $sujet->update_last_message($forum);
    $forum->update_last_sujet();
  }

  function undelete ( &$forum, &$sujet, $id_utilisateur )
  {
    if ( $forum->id != $sujet->id_forum || $sujet->id != $this->id_sujet )
      return;

    new update($this->dbrw,"frm_message",array("msg_supprime"=>0, "msg_modere_info"=>1), array("id_message"=>$this->id));
    new insert($this->dbrw,"frm_modere_info", array("id_message"=>$this->id, "id_utilisateur"=>$id_utilisateur, "modere_action"=>"UNDELETE"));

    $sujet->update_last_message($forum);
    $forum->update_last_sujet();
  }

  /**
  * Permet de faire des remplacements au moment du commit
  */
  function commit_replace($text,$user)
  {
    $text = preg_replace("/(\n|^)\/me\s/","\n* ".$user->get_surnom_or_alias()." ",$text);

    return $text;
  }

}

?>
