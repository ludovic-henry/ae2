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

$topdir = '../';
require_once($topdir."include/entities/eticket.inc.php");
require_once($topdir."include/entities/produit.inc.php");
require_once($topdir."include/entities/files.inc.php");
require_once($topdir."include/site.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");

$site = new site ();
if (!$site->user->is_valid())
    $site->error_forbidden("services");
if (!$site->user->is_in_group ('gestion_ae'))
    $site->error_forbidden("services");

$cts = new contents("Gestion des E-Tickets");

if (isset ($_REQUEST['action']) && !empty ($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
    $eticket = new eticket ($site->db, $site->dbrw);
    $prod = new produit ($site->db);
    $banner = new dfile ($site->db);
    if (isset ($_REQUEST['id_ticket'])) {
        $eticket->load_by_id ($_REQUEST['id_ticket']);
        if ($eticket->id_produit > 0)
            $prod->load_by_id ($eticket->id_produit);
        if ($eticket->banner > 0)
            $banner->load_by_id ($eticket->banner);
    }

    if ($eticket->is_valid ()) {
        if ($action == "delete")
            $eticket->delete ();
        else if ($action == "doupdate") {
            $eticket->id_produit = $_REQUEST['id_produit'];
            $eticket->banner = $_REQUEST['id_banner'][0]->id;
            $eticket->update ();
        }
    }

    if ($action == "docreate") {
        $eticket->create ($_REQUEST['id_produit'], $_REQUEST['id_banner'][0]->id);
    }

    if ($action == "create" || $action == "edit") {
        $formaction = $action == "create" ? "docreate" : "doupdate";
        $formurl = "eticket.php" . ($action == "create" ? "" : "?id_ticket=".$eticket->id);
        $frm = new form ($formaction, $formurl, true, "POST", "Créer/modifier un eticket");
        $frm->add_hidden ("action", $formaction);
        $frm->add_entity_smartselect ("id_produit", "Produit associé", $prod);
        $frm->add_attached_files_field ("id_banner", "Bannière", $eticket->banner == null ? array () : array ($banner));
        $frm->add_submit ("valid", "Enregistrer");

        $site->add_contents ($frm);
        $site->end_page ();

        exit ();
    }
}

$sql = 'SELECT cpt_etickets.id_ticket as id_ticket, cpt_etickets.id_produit as id_produit, cpt_produits.nom_prod as nom_prod, cpt_etickets.banner as id_file, d_file.titre_file as titre_file '.
    'FROM cpt_etickets '.
    'INNER JOIN cpt_produits ON cpt_produits.id_produit = cpt_etickets.id_produit '.
    'INNER JOIN d_file ON d_file.id_file = cpt_etickets.banner';
$req = new requete ($site->db, $sql);

$columns = array ("id_ticket" => "N°",
                  "nom_prod" => "Produit",
                  "titre_file" => "Bannière");
$tbl = new sqltable ("listetickets", "Liste des e-tickets existants", $req, "eticket.php", "id_ticket", $columns,
                     array ("delete" => "Supprimer", "edit" => "Modifier"), array ());

$cts->add_paragraph ('<a href="eticket.php?action=create">Créer un nouveau e-ticket</a>');
$cts->add($tbl);

$site->add_contents($cts);
$site->end_page();

?>