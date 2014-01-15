<?php
/* Copyright 2006,2007
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
 */

/**
 * @file
 */

require_once($topdir."include/entities/basedb.inc.php");
require_once($topdir."include/entities/wiki.inc.php");

/**
 * Page editable du site.
 *
 * Essentiellement utilisé par articles.php
 *
 * Exploite en réalité une page du wiki, mais sans être encombré par toutes
 * les spécificités du wiki.
 *
 * @see wiki
 * @author Julien Etelain
 */
class page extends wiki
{
  var $nom;
  var $texte;
  var $date;
  var $titre;
  var $section;

  /**
   * Transforme un nom de page en son nom wiki
   * @param $name Nom de page
   * @return le nom de la page dans le wiki
   */
  function translate_pagename ( $name )
  {
    $name = preg_replace("/[^a-z0-9\-_:#]/","_",strtolower(utf8_enleve_accents($name)));
    return "articles:".$name;
  }

  /**
   * Charge une page par son nom
   * @param $name Nom de la page
   * @return true en cas de succès, false sinon
   */
  function load_by_pagename ( $name )
  {
    return $this->load_by_fullpath($this->translate_pagename($name));
  }

  function _load ( $row )
  {
    parent::_load($row);

    $this->nom = substr($this->fullpath,9);
    $this->texte = $this->rev_contents;
    $this->date = $this->rev_date;
    $this->titre = $this->rev_title;
    //$this->section = $this->section;
  }

  /**
   * Récupère un stdcontents pour afficher la page
   * @return une intsance de stdcontents
   */
  function get_contents ( )
  {
    return $this->get_stdcontents();
  }

  /**
   * Enregistre la page
   * @param $user Utilisateur enregistrant la page
   * @param $titre Titre de la page
   * @param $texte Texte de la page
   * @param $section Section où se trouve la page
   */
  function save ( &$user, $titre, $texte, $section )
  {
    if ( $this->is_locked($user) )
      return false;

    $this->section = $section;
    $this->update();

    $this->revision ( $user->id, $titre, $texte, "Edité comme un article" );

    return true;
  }

  /**
   * Crée une page page
   * @param $user Utilisateur enregistrant la page
   * @param $nom Nom de la page
   * @param $titre Titre de la page
   * @param $texte Texte de la page
   * @param $section Section où se trouve la page
   * @return true en cas de succès, false sinon
   */
  function add ( &$user, $nom, $titre, $texte, $section )
  {
    $path = $this->translate_pagename($nom);

    $parent = new wiki($this->db,$this->dbrw);

    $pagename = $parent->load_or_create_parent($path, $user, $this->droits_acces, $this->id_groupe, $this->id_groupe_admin);

    if ( is_null($pagename) || !$parent->is_valid() || $this->load_by_name($parent,$pagename) )
      return false;

    $this->create ( $parent, null, $pagename, 0, $titre, $texte, "Créée comme un article", $section );

    return true;
  }

  function is_admin ( &$user )
  {
    if ( $user->is_in_group("moderateur_site") ) return true;
    return parent::is_admin($user);
  }

}

?>
