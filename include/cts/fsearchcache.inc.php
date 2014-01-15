<?php
/* Copyright 2011
 * - Jeremie Laval <jeremie dot laval at gmail dot com>
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

require_once ($topdir.'include/redis.inc.php');

class fsearchcache
{
    var $redis;

    public function fsearchcache ()
    {
        $this->redis = redis_open_connection ();
    }

    function __destruct ()
    {
        $this->redis->close ();
    }

    public function can_get_cached_contents ()
    {
        return !$this->redis->exists('_disable_cache');
    }

    public function disable_cache_temporarily ($minutes = 5)
    {
        $this->redis->setex('_disable_cache', $minutes * 60, true);
    }

    public function enable_cache ()
    {
        $this->redis->del('_disable_cache');
    }

    public function get_cached_contents ($user, $request)
    {
        $content = null;
        if ($user->is_valid() && $user->cotisant)
            $content = $this->redis->get ($this->format_request ($request));
        return $content;
    }

    public function set_cached_contents ($request, $result)
    {
        $this->redis->set ($this->format_request ($request), $result);
    }

    public function set_temporarily_cached_contents ($request, $result, $seconds = 604800) // default is one week
    {
        $this->redis->setex ($this->format_request ($request), $seconds, $result);
    }

    public function must_revalidate_for ($input)
    {
        $input = $this->format_request ($input);
        // add each leading substring of input to be reprocessed
        for ($i = 1; $i < strlen($input); $i++)
            $this->redis->sAdd ('_cache_to_process', substr($input, 0, $i));
    }

    private function format_request ($request)
    {
        if ($request[0] == '_')
            $request = substr($request, 1);
        return strtolower ($request);
    }
}

function fsearch_revalidate_cache_for ($input)
{
    $cache = new fsearchcache ();
    $cache->must_revalidate_for ($input);
}

?>