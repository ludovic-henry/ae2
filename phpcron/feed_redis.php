<?php
/* Copyright 2006-2007
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

$_SERVER['SCRIPT_FILENAME']="/var/www/ae2/phpcron";
$topdir="../";

require_once ($topdir. "include/site.inc.php");
require_once ($topdir. "include/cts/fsearch.inc.php");
require_once ($topdir. "include/redis.inc.php");

$site = new site ();
$redis = redis_open_connection ();

function get_char_off ($offset)
{
    return chr (ord ('a') + $offset);
}

function compute_pattern_with_size ($size)
{
    global $site, $redis;

    // we do all combination of 4 character
    $upper = pow (25, $size);

    for ($i = 0; $i < $upper; ++$i) {
        $str = '';
        for ($j = $size - 1; $j >= 0; $j--)
            $str .= get_char_off (($i / pow (25, $j)) % 25);

        $_REQUEST['pattern'] = $str;

        $fsearch = new fsearch ($site, false, true);
        if (!empty ($fsearch->buffer))
            $redis->set($str, $fsearch->buffer);
    }
}

compute_pattern_with_size (1);
compute_pattern_with_size (2);
compute_pattern_with_size (3);
compute_pattern_with_size (4);

$redis->close ();

?>
