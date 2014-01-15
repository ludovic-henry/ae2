<?php
/* Copyright 2004-2007
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

/** @defgroup bdbrights Droits d'accés
 * @ingroup stdentity
 * @{
 */
/** lecture */
define("DROIT_LECTURE",0x1);
/** ecriture */
define("DROIT_ECRITURE",0x2);
/** ajout sous catégorie */
define("DROIT_AJOUTCAT",0x4);
/** ajout élément */
define("DROIT_AJOUTITEM",0x8);
/**
 * @}
 */

define("DROIT_MASKCAT",0xFFD);
define("DROIT_MASKITEM",0x331);

/**
 * Gère un objet à droits d'accés et modéré
 * @ingroup stdentity
 * @author Julien Etelain
 */
abstract class basedb extends stdentity
{
  /** Id du propriétaire */
  var $id_utilisateur;

  /** Id du groupe propriétaire */
  var $id_groupe;

  /** Id du groupe d'administration */
  var $id_groupe_admin;

  /** Droits d'accés
   * Ces droits se combinent, et se repértissent sur 3x4 bits
   * 0-4  : Tout le monde (0x0F)
   * 5-8  : Groupe (0x0F0)
   * 7-12 : Propriètaire (0xF00)
   * @see bdbrights
   */
  var $droits_acces;

  /** Element modéré ou non */
  var $modere;



  /**
   * Instancie un objet à droits d'accés.
   * @param $db Accés à la base de donnés en lecture seule.
   * @param $dbrw Accés à la base de donnés en lecture et ecriture.
   */
  function basedb  ( &$db, &$dbrw = null )
  {
    $this->stdentity($db,$dbrw);
  }

  /**
   * Détermine si l'utilisateur est administrateur de l'élément
   * @param $user Instance de utilisateur
   */
  function is_admin ( &$user )
  {
    /*if ( $user->is_in_group("gestion_ae")) return true;  Les droits d'admin devrons être mieux découpés */

    if ( $user->is_in_group_id($this->id_groupe_admin) ) return true;

    return false;
  }

  /**
   * Détermine si l'utilisateur a le droit spécifié sur l'élément
   * @param $user Instance de utilisateur
   * @param $required Droit à tester
   */
  function is_right ( &$user, $required )
  {
    if ( $this->is_admin($user)) return true;

    if ( !is_null($this->id_utilisateur) &&
      ($user->id ==  $this->id_utilisateur) &&
      ($required & ($this->droits_acces >> 8)) == $required ) return true;

    if ( $this->modere == 0 ) return false;

    if ( ($user->is_in_group_id($this->id_groupe)) &&
      ($required & ($this->droits_acces >> 4)) == $required ) return true;
//ce teste merde quand on est pas connecté !!!
    if ( ($required & ($this->droits_acces)) == $required ) return true;

    return false;
  }

  /**
   * Fait hériter les droits depuis un autre élément.
   * Le propriètaire n'est pas héréditaire !
   * @param $basedb Element servant de base
   * @param $category Déprécié, detection via la fonction $this->is_category()
   */
  function herit ( $basedb, $category=true )
  {
    $this->id_utilisateur = null;
    $this->id_groupe = $basedb->id_groupe;
    $this->id_groupe_admin = $basedb->id_groupe_admin;
    $this->modere=false;
    if ( $this->is_category() )
      $this->droits_acces = $basedb->droits_acces & DROIT_MASKCAT;
    else
      $this->droits_acces = $basedb->droits_acces & DROIT_MASKITEM;
  }

  /**
   * Définit les droits d'accés de l'élément (pensez ensuite à enregistrer l'objet)
   * @param $user Instance de l'utilisateur qui fait la modification (sui deviendra propiètaire si aucun n'est défini)
   * @param $rigths Droits
   * @param $id_group Id du groupe propriétaire
   * @param $id_group_admin Id du groupe administrateur (non pris en compte si l'utilisateur n'est pas administrateur)
   * @param $category Déprécié, detection via la fonction $this->is_category()
   */
  function set_rights ( $user,  $rights, $id_group, $id_group_admin, $category=true )
  {

    if ( $this->is_admin($user) && $id_group_admin )
      $this->id_groupe_admin = $id_group_admin;

    if ( !$this->id_utilisateur )
      $this->id_utilisateur = $user->id;

    $this->id_groupe = $id_group;

    if ( $this->is_category() )
      $this->droits_acces = $rights & DROIT_MASKCAT;
    else
      $this->droits_acces = $rights & DROIT_MASKITEM;
  }

  /** Determine si la classe décrit une catégorie
   * Valeur static
   * @return true is de type catégorie, sinon false
   */
  function is_category()
  {
    return true;
  }

}


?>
