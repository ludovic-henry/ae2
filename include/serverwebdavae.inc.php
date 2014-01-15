<?
/**
 * @file
 */

/* Copyright 2007
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

/*
 * Some parts of this file is subject to version 2.02 of the PHP license
 */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Hartmut Holzgraefe <hholzgra@php.net>                       |
// |          Christian Stocker <chregu@bitflux.ch>                       |
// +----------------------------------------------------------------------+



require_once($topdir."include/mysql.inc.php");
require_once($topdir."include/mysqlae.inc.php");
require_once($topdir . "include/entities/std.inc.php");
require_once($topdir . "include/entities/utilisateur.inc.php");
require_once($topdir."include/lib/webdavserver.inc.php");

/**
 * Serveur WebDAV exploitant l'authentification du site de l'AE.
 * @author Julien Etelain
 */
class webdavserverae extends HTTP_WebDAV_Server
{
  var $db;
  var $dbrw;
  var $user;

  function webdavserverae ()
  {
    $this->HTTP_WebDAV_Server();

    $this->http_auth_realm = "Connexion site AE. Entrez votre adresse e-mail et votre mot de passe. Pour une connexion anonyme, precisez anonymous comme nom d'utilisateur (sans mot de passe).";
    $this->dav_powered_by = "AE-2.1.5";

    $this->db = new mysqlae ();
    $this->dbrw = new mysqlae ("rw");
		$this->user = new utilisateur( $this->db );
  }

  /**
    * check authentication
    *
    * @param string type Authentication type, e.g. "basic" or "digest"
    * @param string username Transmitted username
    * @param string passwort Transmitted password
    * @returns bool Authentication status
    */
  function checkAuth($type, $username, $password)
  {
    if (isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "on") // on n'est pas en HTTPS : Pas d'auth, accès anonyme
      return true;

    if ( $type == "digest" ) // Digest not supported
      return false;

    if ( $username == "anonymous" )
      return true;

    if ( preg_match('/^\/var\/www\/taiste\//', $_SERVER['SCRIPT_FILENAME']) )
    {
      $this->user->load_by_alias($username);
      return true;
    }

    $this->user->load_by_email($username);

    if ( !$this->user->is_valid() || !$this->user->is_password($password) )
      return false;

    return true;
  }

}


?>
