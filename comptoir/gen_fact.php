<?php
/* Copyright 2006
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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

$topdir="../";
require_once ("include/comptoirs.inc.php");

require_once ($topdir . "include/pdf/facture_pdf.inc.php");

require_once ($topdir . "include/entities/ville.inc.php");

/* on n'a pas l'intention de sortir du (x)html en sortie
 * mais les objets membres de site vont etre utiles      */
$site = new site ();

/* on a besoin d'un objet facture
 * utilisation uniquement de la lecture seule ! */

$fact = new debitfacture ($site->db, $site->db);
$fact->load_by_id ($_REQUEST['id_facture']);

/* ACLs */
if (($site->user->id != $fact->id_utilisateur_client)
    && (!$site->user->is_in_group ("gestion_ae")))
  error_403 ();

/* Si droits d'acces suffisants on genere une facture */

/* infos du facturant
 */

if ($site->user->id == $fact->id_utilisateur_client)
  $user = $site->user;
else
{
  $user = new utilisateur ($site->db);
  $user->load_by_id ($fact->id_utilisateur_client);
}

$ville = new ville($site->db);
$ville->load_by_id($user->id_ville);

$facturing_infos = array ('name' => "AE - UTBM",
       'addr' => array("6 Boulevard Anatole France",
           "90000 BELFORT"),
       'logo' => "http://ae.utbm.fr/images/Ae-blanc.jpg");

$factured_infos = array ('name' => utf8_decode($user->nom)
       . " " .
       utf8_decode($user->prenom),
       'addr' => array(
           utf8_decode($user->addresse),
           utf8_decode($ville->cpostal)
           . " " .
           utf8_decode($ville->nom)),
       false);

$date_facturation = date("d/m/Y H:i", $fact->date);

$titre = "Facture Comptoirs AE";

$ref = $fact->transacid > 0 ? $fact->id . "-" .$fact->transacid : $fact->id;

$req = "SELECT * FROM `cpt_vendu`
          INNER JOIN `cpt_produits` USING (`id_produit`)
        WHERE `id_facture` = $fact->id";

$query = new requete ($site->db, $req);

//print_r($query);

$total = 0;

while ($line = $query->get_row ())
{
  $lines[] = array('nom' => utf8_decode($line['nom_prod']),
       'quantite' => intval($line['quantite']),
       'prix' => $line['prix_unit'],
       'sous_total' => intval($line['quantite']) * $line['prix_unit']);

  $total += intval($line['quantite']) * $line['prix_unit'];
}

if ( $fact->mode == "AE" && $user->type != "srv" )
{
  $lines[] = array('nom' => utf8_decode("Reprise sur accompte"),
       'quantite' => 1,
       'prix' => -$total,
       'sous_total' => -$total);
}

$fact_pdf = new facture_pdf ($facturing_infos,
           $factured_infos,
           $date_facturation,
           $titre,
           $ref,
           $lines);

/* on sort la facture */
$fact_pdf->renderize ();

?>
