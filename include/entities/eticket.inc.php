<?php
/* Copyright 2011
 * - Jérémie Laval < jeremie dot laval at gmail dot com >
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

require_once($topdir."include/mysql.inc.php");
require_once($topdir."include/entities/std.inc.php");

define('ETICKET_TABLE', 'cpt_etickets');

function hmac ($algo, $data, $key, $raw_output = false)
{
    $algo = strtolower($algo);
    $pack = 'H'.strlen($algo('test'));
    $size = 64;
    $opad = str_repeat(chr(0x5C), $size);
    $ipad = str_repeat(chr(0x36), $size);

    if (strlen($key) > $size) {
        $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
    } else {
        $key = str_pad($key, $size, chr(0x00));
    }

    for ($i = 0; $i < strlen($key) - 1; $i++) {
        $opad[$i] = $opad[$i] ^ $key[$i];
        $ipad[$i] = $ipad[$i] ^ $key[$i];
    }

    $output = $algo($opad . pack($pack, $algo($ipad . $data)));

    return ($raw_output) ? pack($pack, $output) : $output;
}

class eticket extends stdentity
{
    var $id_produit;
    // Secret utilisé pour générer le hash de chaque ticket
    var $secret;
    // id du dfile représentant la banière
    var $banner;

    function eticket ( &$db, &$dbrw = null )
    {
        $this->stdentity ($db,$dbrw);
        $this->id = -1;
        $this->id_produit = -1;
        $this->secret = '';
        $this->banner = null;
    }

    function load_by_id ($id)
    {
        $req = new requete($this->db, 'SELECT * FROM `'.ETICKET_TABLE.'` WHERE `id_ticket` = '.intval($id).' LIMIT 1');

        if ($req->lines == 1) {
            $this->_load ($req->get_row ());
            return true;
        }

        $this->id = -1;
        return false;
    }

    function _load ($row)
    {
        $this->id = $row['id_ticket'];
        $this->id_produit = $row['id_produit'];
        $this->secret = $row['secret'];
        $this->banner = $row['banner'];
    }

    function create ($id_produit, $banner)
    {
        $this->secret = base64_encode(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
        $this->id_produit = intval ($id_produit);
        $this->banner = intval ($banner);
        $values = array('id_produit' => $this->id_produit,
                        'secret' => $this->secret,
                        'banner' => $this->banner);

        $req = new insert($this->dbrw, ETICKET_TABLE, $values);
        $this->id = $req->get_id ();
    }

    function update ()
    {
        $values = array('id_produit' => $this->id_produit,
                        'secret' => $this->secret,
                        'banner' => intval ($this->banner));
        $req = new update ($this->dbrw, ETICKET_TABLE, $values, array('id_ticket' => $this->id));
    }

    function delete ()
    {
        if (!$this->is_valid ())
            return;

        $req = new delete ($this->dbrw, ETICKET_TABLE, array ('id_ticket' => $this->id));
    }

    function compute_hash_for ($value)
    {
        return substr(hmac('sha1', $value, $this->secret), 0, 8);
    }
}

?>