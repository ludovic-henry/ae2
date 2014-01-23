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

require_once $topdir . 'include/cache.inc.php';

class apccache extends cache
{

  function get($key) {
    $value = apc_fetch($key);

    if ($value === false) {
      return null;
    }

    return $value;
  }

  function set($key, $value) {
    apc_store($key, $value, $this->ttl);
  }
  
  function setex($key, $ttl, $value) {
    apc_store($key, $value, $ttl);
  }
  
  function del($key) {
    apc_delete($key);
  }
  
  function exists($key) {
    return (bool)apc_fetch($key);
  }
  
  function expireAt($key, $timestamp) {
    $value = $this->get($key);

    if ($value !== null) {
      $this->setex($key, $value, $timestamp);
    }
   }
  
  function expire($key, $ttl) {
    $this->expireAt($key, time() + $ttl);
  }
  
  function flush() {
    apc_clear_cache("user");
  }
  
  function close() {
  }
}