<?php
/* Copyright 2012
 * - Antoine Ténart < antoine dot tenart at utbm dot fr >
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
$topdir = "../";

require_once ($topdir. "comptoir/include/comptoirs.inc.php");
require_once ($topdir. "include/cts/user.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("root") && !$site->user->is_in_group ("compta_admin"))
  $site->error_forbidden ("none", "group", 7);

$site->start_page ("none", "Administration");

$cts = new contents ("<a href=\"./\">Administration</a> / Maintenance / Fermeture des comptes des non cotisants de plus de 2 ans");

if (date ("m-d") < "08-15" && date ("m-d") >= "02-15") {
  // semestre de primptemps
  $date = date ("Y") - 2 . "-02-15";
} else {
  if (date ("m-d") > "02-15")
    $date = date ("Y") - 2 . "-08-15";
  else
    $date = date ("Y") - 3 . "-08-15";
}

$req = new requete ($site->db, "SELECT ae_cotisations.id_utilisateur AS id_utl ".
    "FROM `ae_cotisations` ".
    "LEFT JOIN utilisateurs ON utilisateurs.id_utilisateur=ae_cotisations.id_utilisateur ".
    "WHERE utilisateurs.montant_compte > 0 AND `date_fin_cotis` <= \"".$date." 00:00:00\" ".
    "AND id_cotisation IN (SELECT MAX(inner_cotis.id_cotisation) ".
    "FROM ae_cotisations AS inner_cotis WHERE inner_cotis.id_utilisateur=id_utl ".
    "GROUP BY inner_cotis.id_utilisateur)");

if ($req->lines < 1) {
  $cts->add_paragraph ("Aucun compte concerné par l'opération.");
} else {
  if (isset ($_REQUEST['action']) && $_REQUEST['action'] == 'close_accounts') {
    $tot_cpt = $req->lines;
    $tot = 0;

    while ($row = $req->get_row ()) {
      $debfact = new debitfacture ($site->db, $site->dbrw);
      $vprod = new venteproduit ($site->db, $site->dbrw);
      $cpt = new comptoir ($site->db, $site->dbrw);
      $usr = new utilisateur ($site->db, $site->dbrw);
      $cart = array();

      $cpt->load_by_id (6); // Bureau AE Belfort

      $usr->load_by_id ($row['id_utl']);

      $vprod->load_by_id (338, 6); // Produit "Clôture compte"
      $vprod->produit->prix_vente_barman =
          $vprod->produit->prix_vente = $usr->montant_compte;
      $vprod->produit->id_assocpt = 0;
      $tot += $usr->montant_compte;

      $cart[0][0] = 1;
      $cart[0][1] = $vprod;

      $debfact->debitAE ($usr, $site->user, $cpt, $cart, false);
    }

    $cts->add_paragraph ($tot_cpt ." comptes clôturés pour un total de ". $tot/100 ." euros.");
  } else {
    $req2 = new requete ($site->db, "SELECT SUM(utilisateurs.montant_compte) AS montant, COUNT(*) as tot FROM `ae_cotisations` ".
        "LEFT JOIN utilisateurs ON utilisateurs.id_utilisateur=ae_cotisations.id_utilisateur ".
        "WHERE utilisateurs.montant_compte > 0 AND `date_fin_cotis` <= \"".$date." 00:00:00\" ".
        "AND id_cotisation IN (SELECT MAX(inner_cotis.id_cotisation) ".
        "FROM ae_cotisations AS inner_cotis WHERE inner_cotis.id_utilisateur=ae_cotisations.id_utilisateur ".
        "GROUP BY inner_cotis.id_utilisateur)");

    if ($req2->lines > 0) {
      $row = $req2->get_row ();

      if (is_null ($row['montant'])) $row['montant'] = 0;

      if ($row['tot'] > 1)
        $cts->add_paragraph ($row['tot']." comptes peuvent être clôturés pour un total de ". $row['montant']." euro(s).");
      else
        $cts->add_paragraph ($row['tot']." compte peut être clôturé pour un total de ". $row['montant']." euro(s).");
    }

    $frm = new form ("askclose", "./fermeture_comptes.php", false, "POST", "Clôturer les comptes des non cotisants de plus de 2 ans");
    $frm->add_hidden ("action", "close_accounts");
    $frm->add_submit ("valid", "Procéder à l'opération");
  }

}


$site->add_contents ($cts);
if (isset ($frm)) $site->add_contents ($frm);
$site->end_page ();

?>
