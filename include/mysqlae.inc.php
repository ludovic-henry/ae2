<?php

/** @file
 *
 * @brief Connexion aux bases MySQL de l'AE.
 *
 * Cette version du fichier est celle distribuée avec le code source :
 * elle ne contient pas les mots de passe de connexion aux bases de
 * données.
 *
 */

/* Copyright 2004
 * - Alexandre Belloni <alexandre POINT belloni CHEZ utbm POINT fr>
 * - Thomas Petazzoni <thomas POINT petazzoni CHEZ enix POINT org>
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

/* On interdit le chargement de ce script si il ne vient pas du site
   officiel et qu'il n'y a pas de conf personnelle */

if( !preg_match('/^\/var\/www\/(taiste|ae2)\//', $_SERVER['SCRIPT_FILENAME'])
    && !ereg("^/var/www/accounts/([a-z0-9][a-z0-9_-]*)/aecms",$_SERVER['SCRIPT_FILENAME']) )
{
  /* On est peut_etre dans le cas d'une utilisation "home" */
  if (file_exists($topdir . "include/mysqlae_home.inc.php"))
    require_once($topdir . "include/mysqlae_home.inc.php");
  else
    die("denied");

}
else
{
  /** Classe permettant de se connecter à la base de l'ae. Permet de
    créer une base qui se connecte sur la base de l'ae. En passant en
    paramètre "rw", on obtient une base en lecture écriture pour tout
    autre paramètre, la base est en lecture seule. */
  class mysqlae extends mysql {

    public static $host = 'host';
    public static $database = 'database';

    public static $login_read_write = 'login_read_write';
    public static $mdp_read_write = 'mdp_read_write';

    public static $login_read_only = 'login_read_only';
    public static $mdp_read_only = 'mdp_read_only';

    function mysqlae ($type = "ro") {
      if ($type == "rw") {
        if ( ! $this->mysql(self::$login_read_write, self::$mdp_read_write, self::$host, self::$database)) {
          return FALSE;
        }
      } else {
        if ( ! $this->mysql(self::$login_read_only, self::$mdp_read_only, self::$host, self::$database)) {
          return FALSE;
        }
      }
    }
  }
  class mysqlforum extends mysql
  {

    function mysqlforum ()
    {
      // Tschuut on a rien vu ...
      if ( ! self::$mysql('importforum', 'importequoi', 'host', 'UTBM'))
        return FALSE;
    }
  }
}

?>
