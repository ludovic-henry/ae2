<?php
/* Copyright 2012
 * - Ludovic Henry <ludovichenry dot utbm at gmail dot com>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr
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

require_once $topdir . 'include/cache/apc.inc.php';

abstract class cache {

  protected $ttl = 3600;

  private static $instance;

  static function getInstance($id = null) {
    if (self::$instance === null) {
      self::$instance = new apccache();
    }

    return self::$instance;
  }

  abstract function get($key);
  abstract function set($key, $value);
  abstract function del($key);
  abstract function exists($key);
  abstract function expireAt($key, $timestamp);
  abstract function expire($key, $ttl);
  abstract function setex($key, $ttl, $value);
  abstract function flush();
  abstract function close();
}

?>
