<?php
/** @file
 *
 * @brief Class représentant un produit devant être revalidé automatiquement de manière hebdomadaire.
 */

/* Copyright 2011
 * - Jérémie Laval <jeremie dot laval at gmail dot com>
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

require_once('produit.inc.php');

class produitrecurrent extends stdentity
{
    /* ID du produit associé à cette demande */
    var $id_produit;
    /* numéro du jour de la semaine quand le produit est remis en vente (0 = dimanche, 1 = lundi, ...)
     * (voir fonction idate et son paramètre w */
    var $jour_remise_en_vente;
    /* Valeur numérique définissant le nombre de seconde durant laquelle le produit doit rester en vente */
    var $ttl;

    function produitrecurrent ( &$db, &$dbrw = null )
    {
        $this->stdentity ($db,$dbrw);
        $this->id = -1;
    }

    function load_by_id ($id)
    {
        $req = new requete ($this->db, 'SELECT * FROM cpt_produit_recurrent WHERE id_recurrence='.$id.' LIMIT 1');

        if ($req->lines == 1) {
            $this->_load ($req->get_row ());
            return true;
        }

        $this->id = -1;
        return false;
    }

    function load_by_produit ($prodid)
    {
        $req = new requete ($this->db, 'SELECT * FROM cpt_produit_recurrent WHERE id_produit='.$prodid.' LIMIT 1');

        if ($req->lines == 1) {
            $this->_load ($req->get_row ());
            return true;
        }

        $this->id = -1;
        return false;
    }

    function _load ($row)
    {
        $this->id = $row['id_recurrence'];
        $this->id_produit = $row['id_produit'];
        $this->jour_remise_en_vente = $row['jour_remise_en_vente'];
        $this->ttl = $row['ttl'];
    }

    function ajout ()
    {
        $req = new insert ($this->dbrw, 'cpt_produit_recurrent',
                           array ('id_produit' => $this->id_produit,
                                  'jour_remise_en_vente' => $this->jour_remise_en_vente,
                                  'ttl' => $this->ttl));
        $this->id = $req->get_id ();
    }

    function modifie ()
    {
        $req = new update ($this->dbrw, 'cpt_produit_recurrent',
                           array ('id_produit' => $this->id_produit,
                                  'jour_remise_en_vente' => $this->jour_remise_en_vente,
                                  'ttl' => $this->ttl),
                           array ('id_recurrence' => $this->id));
    }

    function supprime ()
    {
        $req = new delete ($this->dbrw, 'cpt_produit_recurrent',
                           array ('id_recurrence' => $this->id));
        $this->id = -1;
    }

    /* Si le produit qu'on cherche à mettre en vente a été archivé celà veut dire qu'il n'est
     * pas à remettre en vente, return false dans ce cas
     */
    function remettre_en_vente ()
    {
        if (!$this->is_valid ())
            return false;

        $produit = new produit ($this->db, $this->dbrw);
        if (!$produit->load_by_id ($this->id_produit))
            return false;
        if ($produit->archive)
            return false;

        $produit->modifier_date_expiration (time () + $this->count_remis);
        return true;
    }
}

?>