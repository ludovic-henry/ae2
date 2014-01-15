<?php

/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
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
require_once($topdir. "include/site.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir . "include/pdf/facture_pdf.inc.php");
require_once($topdir . "comptoir/include/comptoir.inc.php");
require_once($topdir . "comptoir/include/cptasso.inc.php");
require_once($topdir . "comptoir/include/defines.inc.php");
require_once($topdir . "comptoir/include/facture.inc.php");
require_once($topdir . "comptoir/include/produit.inc.php");
require_once($topdir . "comptoir/include/typeproduit.inc.php");
require_once($topdir . "comptoir/include/venteproduit.inc.php");
require_once($topdir."include/entities/books.inc.php");


$site = new site ();

if ( !$site->user->is_in_group("gestion_syscarteae") )
  $site->error_forbidden("accueil");

if ( $_REQUEST["action"] == "genfact" )
{
  $month = substr($_REQUEST["mois"],3,4).substr($_REQUEST["mois"],0,2);

  $sql = new requete($site->db,
    "SELECT ".
    "`asso`.`nom_asso`,".
    "`asso`.`id_asso`,".
    "SUM( `prix_unit` * `quantite` ) /100 as `somme` ".
    "FROM `cpt_vendu` ".
    "INNER JOIN cpt_debitfacture USING ( `id_facture` ) ".
    "LEFT JOIN asso ON asso.id_asso = cpt_vendu.id_assocpt ".
    "WHERE id_produit NOT IN ( 40, 41, 42, 43, 338 ) AND " .
    "EXTRACT( YEAR_MONTH FROM `date_facture` ) ='".mysql_real_escape_string($month)."'  ".
    "GROUP BY `asso`.`id_asso` ".
    "ORDER BY `asso`.`nom_asso`");

  $factured_infos = array ('name' => "AE UTBM - Carte AE",
      'addr' => array("6 Boulevard Anatole France",
      "90000 BELFORT"),
      'logo' => "http://ae.utbm.fr/images/Ae-blanc.jpg");

  $date_facturation = 'Période du '
                      .date("d/m/Y", mktime ( 0, 0, 0, substr($month,4), 1, substr($month,0,4)))
                      .' au '
                      .date("d/m/Y",
                            mktime (0,
                                    0,
                                    0,
                                    substr($month,4),
                                    cal_days_in_month(CAL_GREGORIAN,
                                                      substr($month,4),
                                                      substr($month,0,4)
                                                     )
                                   )
                           )
                      .' - '
                      .'Édité le : '.date("d/m/Y", mktime ( 0, 0, 0, substr($month,4)+1, 1, substr($month,0,4)));
  $ref = $month;

  $asso = new asso($site->db);

  $fact_pdf = new facture_pdf (null, $factured_infos, $date_facturation,null,$ref,null);
  $fact_pdf->AliasNbPages ();

  while ( $row = $sql->get_row() )
  {
    $asso->load_by_id($row['id_asso']);
    if ( !$asso->is_valid() )
      continue;
    if(file_exists("/var/www/ae2/data/img/logos/".$asso->nom_unix.".jpg"))
      $logo="/var/www/ae2/data/img/logos/".$asso->nom_unix.".jpg";
    else
      $logo="";
    if($asso->id==1)//ae
      $_asso="AE - TG";
    else
      $_asso=$asso->nom;
    $facturing_infos = array ('name' => $asso->nom,
       'addr' => explode("\n",utf8_decode($asso->adresse_postale)),
       'logo' => $logo,
       'asso' => $_asso);

    $query = new requete ($site->db, "SELECT " .
        "CONCAT(`cpt_produits`.`id_typeprod`,'-',`cpt_vendu`.`id_produit`,'-',`cpt_vendu`.`prix_unit`) AS `groupby`, " .
        "SUM(`cpt_vendu`.`quantite`) AS `quantite`, " .
        "`cpt_vendu`.`prix_unit` AS `prix_unit`, " .
        "SUM(`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`) AS `total`," .
        "`cpt_produits`.`nom_prod`," .
        "`cpt_type_produit`.`nom_typeprod`"  .
        "FROM `cpt_vendu` " .
        "LEFT JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
        "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
        "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
        "INNER JOIN `utilisateurs` ON `cpt_debitfacture`.`id_utilisateur` =`utilisateurs`.`id_utilisateur` " .
        "INNER JOIN `cpt_type_produit` ON `cpt_type_produit`.`id_typeprod`=`cpt_produits`.`id_typeprod` " .
        "WHERE `cpt_vendu`.`id_assocpt`='".mysql_real_escape_string($asso->id)."' AND `cpt_produits`.`id_typeprod`!='11' " .
        "AND EXTRACT(YEAR_MONTH FROM `date_facture`)='".mysql_real_escape_string($month)."' " .
        "GROUP BY `groupby` " .
        "ORDER BY `cpt_type_produit`.`nom_typeprod`, `cpt_produits`.`nom_prod`, `cpt_vendu`.`prix_unit`");

    $lines = array();
    while ($line = $query->get_row ())
    {
      $lines[] = array('nom' => utf8_decode($line['nom_prod']),
           'quantite' => intval($line['quantite']),
           'prix' => $line['prix_unit'],
           'sous_total' => intval($line['quantite']) * $line['prix_unit']);
    }

    $fact_pdf->set_infos($facturing_infos,
             $factured_infos,
             utf8_decode($date_facturation),
             $titre,
             $ref.'-'.$asso->id,
             $lines);
    $fact_pdf->AddPage ();
    $fact_pdf->print_items ();

  }
  $fact_pdf->Output ();
  exit();
}
elseif ( $_REQUEST["action"] == "genonefact" )
{
  $asso = new asso($site->db);
  $asso->load_by_id($_REQUEST["id_asso"]);

  $month = $_REQUEST["month"];

  $factured_infos = array ('name' => "AE - UTBM",
       'addr' => array("6 Boulevard Anatole France",
           "90000 BELFORT"),
       'logo' => "http://ae.utbm.fr/images/Ae-blanc.jpg");

  $facturing_infos = array ('name' => $asso->nom,
       'addr' => explode("\n",utf8_decode($asso->adresse_postale)));
  if(file_exists("/var/www/ae2/data/img/logos/".$asso->nom_unix.".jpg"))
    $facturing_infos['logo'] = "/var/www/ae2/data/img/logos/".$asso->nom_unix.".jpg";

  $date_facturation = date("d/m/Y", mktime ( 0, 0, 0, substr($month,4)+1, 1, substr($month,0,4)));

  $titre = "Facture système carte AE";

  $ref = $month;

  $query = new requete ($site->db, "SELECT " .
      "CONCAT(`cpt_produits`.`id_typeprod`,'-',`cpt_vendu`.`id_produit`,'-',`cpt_vendu`.`prix_unit`) AS `groupby`, " .
      "SUM(`cpt_vendu`.`quantite`) AS `quantite`, " .
      "`cpt_vendu`.`prix_unit` AS `prix_unit`, " .
      "SUM(`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`) AS `total`," .
      "`cpt_produits`.`nom_prod`," .
      "`cpt_type_produit`.`nom_typeprod`"  .
      "FROM `cpt_vendu` " .
      "LEFT JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
      "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
      "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
      "INNER JOIN `utilisateurs` ON `cpt_debitfacture`.`id_utilisateur` =`utilisateurs`.`id_utilisateur` " .
      "INNER JOIN `cpt_type_produit` ON `cpt_type_produit`.`id_typeprod`=`cpt_produits`.`id_typeprod` " .
      "WHERE `cpt_vendu`.`id_assocpt`='".mysql_real_escape_string($asso->id)."' AND `cpt_produits`.`id_typeprod`!='11' " .
      "AND EXTRACT(YEAR_MONTH FROM `date_facture`)='".mysql_real_escape_string($month)."' " .
      "GROUP BY `groupby` " .
      "ORDER BY `cpt_type_produit`.`nom_typeprod`, `cpt_produits`.`nom_prod`, `cpt_vendu`.`prix_unit`");


  while ($line = $query->get_row ())
  {
    $lines[] = array('nom' => utf8_decode($line['nom_prod']),
         'quantite' => intval($line['quantite']),
         'prix' => $line['prix_unit'],
         'sous_total' => intval($line['quantite']) * $line['prix_unit']);
  }


  $fact_pdf = new facture_pdf ($facturing_infos,
             $factured_infos,
             $date_facturation,
             $titre,
             $ref,
             $lines);

  $fact_pdf->renderize ();


  exit();
}

$site->start_page("services","Système carte AE");

$cts = new contents("Système carte AE");

$tabs = array(array("","ae/syscarteae.php", "Résumé"),
      array("factures","ae/syscarteae.php?view=factures", "Appels à facture"),
      array("comptes","ae/syscarteae.php?view=comptes", "Comptes"),
      array("retrait","ae/syscarteae.php?view=retrait", "Produits non retirés"),
      array("remb","ae/syscarteae.php?view=remb", "Remboursement")
      );
$cts->add(new tabshead($tabs,$_REQUEST["view"]));

if (   $_REQUEST["view"] == "remb" )
{
  $user = new utilisateur($site->db,$site->dbrw);
  if ( $_REQUEST["action"] == "doremb" )
  {
    $user->load_by_id($_REQUEST["id_utilisateur"]);

    if ( $site->is_sure ( "","Solder le compte de ".$user->get_html_link()." : "
          .number_format($user->montant_compte/100, 2)." €.",
          "doremb".$user->id, 1 ) )
    {
      $cts->add_title(2,"Remboursement de ".$user->get_html_link());

      require_once ($topdir . "comptoir/include/comptoir.inc.php");
      require_once ($topdir . "comptoir/include/comptoirs.inc.php");
      require_once ($topdir . "comptoir/include/cptasso.inc.php");
      require_once ($topdir . "comptoir/include/defines.inc.php");
      require_once ($topdir . "comptoir/include/facture.inc.php");
      require_once ($topdir . "comptoir/include/produit.inc.php");
      require_once ($topdir . "comptoir/include/typeproduit.inc.php");
      require_once ($topdir . "comptoir/include/venteproduit.inc.php");

      $debfact = new debitfacture ($site->db, $site->dbrw);

      $cpt = new comptoir ($site->db, $site->dbrw);
      $cpt->load_by_id (6);

      $cart = array();

      $vp = new venteproduit ($site->db, $site->dbrw);
      $vp->load_by_id (338, 6);
      $vp->produit->prix_vente = $user->montant_compte;
      $vp->produit->prix_vente_barman = $user->montant_compte;
      $vp->produit->id_assocpt = 0;
      $cart[0][0] = 1;
      $cart[0][1] = $vp;

      $debfact->debitAE ($user, $site->user, $cpt, $cart, false);

      $cts->add_paragraph("Compte soldé. <b>Etablir un chèque du compte carte AE de ".($user->montant_compte/100)." &euro; à l'ordre de ".$user->get_html_link()."</b>");
    }
  }

  $user->id = null;


  $cts->add_title(2,"Solder un compte");
  $frm = new form ("genfact","syscarteae.php?view=remb",false);
  $frm->add_hidden("action","doremb");
  $frm->add_entity_smartselect ( "id_utilisateur", "Utilisateur", $user );
  $frm->add_submit("generate","Remboursement");
  $cts->add($frm);

}
else if (   $_REQUEST["view"] == "retrait" )
{

  if ( $_REQUEST["action"] == "retires")
  {
    $fact = new debitfacture($site->db,$site->dbrw);
    foreach( $_REQUEST["id_factprods"] as $id_factprod )
    {
      list($id_facture,$id_produit) = explode(",",$id_factprod);
      $fact->load_by_id($id_facture);
      if ( $fact->is_valid() )
        $fact->set_retire($id_produit);
    }
  }

  $req = new requete($site->db, "SELECT " .
    "CONCAT(`cpt_debitfacture`.`id_facture`,',',`cpt_produits`.`id_produit`) AS `id_factprod`, " .
    "`cpt_debitfacture`.`id_facture`, " .
    "`cpt_debitfacture`.`date_facture`, " .
    "`asso`.`id_asso`, " .
    "`asso`.`nom_asso`, " .
    "`cpt_vendu`.`a_retirer_vente`, " .
    "`cpt_vendu`.`a_expedier_vente`, " .
    "`cpt_vendu`.`quantite`, " .
    "`cpt_vendu`.`prix_unit`/100 AS `prix_unit`, " .
    "`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`/100 AS `total`," .
    "`cpt_produits`.`nom_prod`, " .
    "`cpt_produits`.`id_produit`, " .
    "`utilisateurs`.`id_utilisateur` AS `id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) AS `nom_utilisateur`, " .
    "IF(`cpt_vendu`.`a_retirer_vente`='1','a retirer','a expedier') AS `info` " .
    "FROM `cpt_vendu` " .
    "LEFT JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
    "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
    "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`cpt_debitfacture`.`id_utilisateur_client` " .
    "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
    "WHERE `cpt_vendu`.`a_retirer_vente`='1' OR `cpt_vendu`.`a_expedier_vente`='1' " .
    "ORDER BY `cpt_debitfacture`.`date_facture` DESC");


  $cts->add(new sqltable(
    "listresp",
    "Produits à retirer/à expédier", $req,
    "syscarteae.php?view=retrait",
    "id_factprod",
    array(
    "nom_utilisateur"=>"Client",
    "id_facture"=>"Facture",
    "nom_prod"=>"Produit",
    "quantite"=>"Quantité",
    "date_facture"=>"Depuis le",
    "info"=>""
    ),
    array(),
    array("retires"=>"Marquer comme retiré"),
    array()), true);

}
elseif (   $_REQUEST["view"] == "factures" )
{
  $cts->add_title(2,"Tous les appels à facture");

  $sql = new requete($site->db,
  "SELECT ".
  "`asso`.`nom_asso`,".
  "`asso`.`id_asso`,".
  "EXTRACT( YEAR_MONTH FROM `date_facture` ) AS `month`, ".
  "CONCAT( `id_assocpt` , '-', EXTRACT( YEAR_MONTH FROM `date_facture` ) ) AS `C` , ".
  "TRUNCATE(SUM( `prix_unit` * `quantite` ) /100,2) as `somme` ".
  "FROM `cpt_vendu` ".
  "INNER JOIN cpt_debitfacture ".
  "USING ( `id_facture` ) ".
  "INNER JOIN asso ON asso.id_asso = cpt_vendu.id_assocpt ".
  "WHERE id_produit NOT ".
  "IN ( 40, 41, 42, 43 ) ".
  "GROUP BY `C` ".
  "ORDER BY `month`");


  $headers = array(); // Nom des colonnes
  $table = array(); // Contenu du tableau

  while ( $row = $sql->get_row() )
  {

    $asso = $row["id_asso"];
    $month = $row["month"];

    if ( !isset($table[$month]) )
      $table[$month] = array("mois"=>substr($month,4,2)."/".substr($month,0,4),
                             "mois2"=>substr($month,4,2)."/".substr($month,0,4));

    if ( !isset($headers["a".$asso]) )
      $headers["a".$asso] = $row["nom_asso"];

    $table[$month]["a".$asso] = $row["somme"];

  }

  asort($headers);

  $headers = array_merge(array("mois"=>"Mois"),$headers,array("mois2"=>"Mois"));


  $cts->add(new sqltable(
    "compta",
    "Comptabilité comptoirs", $table, "syscarteae.php?view=factures",
    "mois",
    $headers,
    array("genfact"=>"Factures"),
    array(),
    array( )
    ));

  $cts->add_title(2,"Re-Générer un appel à facture");

  $months = array();

  $req = new requete($site->db, "SELECT " .
      "EXTRACT(YEAR_MONTH FROM `date_facture`) as `month` " .
      "FROM `cpt_debitfacture` " .
      "GROUP BY `month` " .
      "ORDER BY `month` DESC");

  while ( list($month) = $req->get_row() )
    $months[$month] = substr($month,4)."/".substr($month,0,4);


  $frm = new form ("genfact","syscarteae.php?view=factures",false);
  $frm->add_hidden("action","genonefact");
  $frm->add_select_field("month","Mois",$months);
  $frm->add_entity_select("id_asso", "Association émétrice", $site->db, "assocpt");
  $frm->add_submit("generate","Générer");
  $cts->add($frm);

}
elseif (   $_REQUEST["view"] == "comptes" )
{
  $cts->add_title(2,"Solde théorique du compte");
  $cts->add_paragraph("Ceci correspond au solde dès lors que tous les encaissements sont effectués, que les commissions carte bleue sont compensés et que l'ensemble des factures ont été payées.");

  $when = time();

  if ( $_REQUEST["action"] == "sumsoldes" )
    $when = $_REQUEST["when"];

  $req = new requete($site->db, "SELECT " .
    "SUM(`montant_rech`) " .
    "FROM `cpt_rechargements` " .
    "WHERE date_rech <= '".date("Y-m-d H:i:s",$when)."'");

  list($rech) = $req->get_row();

  $req = new requete($site->db, "SELECT " .
    "SUM(`montant_facture`) " .
    "FROM `cpt_debitfacture` " .
    "WHERE mode_paiement='AE' AND date_facture <= '".date("Y-m-d H:i:s",$when)."'");

  list($dep) = $req->get_row();

  $sum = $rech-$dep;

  $cts->add_paragraph("Solde théorique le ".date("d/m/Y H:i:s",$when)." : <b>".($sum/100)." &euro;</b>");

  $frm = new form ("cptsoldes","syscarteae.php?view=comptes");
  $frm->add_hidden("action","sumsoldes");
  $frm->add_datetime_field("when","Date et heure",$when);
  $frm->add_submit("valid","Re-calculer");
  $cts->add($frm);

  $cts->add_title(2,"Solde rechargements");

  $TypesPaiementsFull[-1] = "-";

  $frm = new form ("cptrech","syscarteae.php?view=comptes",true);
  $frm->add_hidden("action","sumrech");
  $frm->add_datetime_field("debut","Date et heure de début");
  $frm->add_datetime_field("fin","Date et heure de fin");
  $frm->add_entity_select("id_comptoir", "Lieu", $site->db, "comptoir",$_REQUEST["id_comptoir"],true);
  $frm->add_select_field("banque_rech","Banque", $Banques,$_REQUEST["banque_rech"]);
  $frm->add_select_field("type_paiement_rech","Type paiement",$TypesPaiementsFull,
    isset($_REQUEST["type_paiement_rech"])?$_REQUEST["type_paiement_rech"]:-1);

  $frm->add_submit("valid","Calculer");
  $cts->add($frm);

  if ( $_REQUEST["action"] == "sumrech" )
  {
    $conds = array();
    $comptoir = false;

    if ( $_REQUEST["debut"] )
      $conds[] = "cpt_rechargements.date_rech >= '".date("Y-m-d H:i:s",$_REQUEST["debut"])."'";

    if ( $_REQUEST["fin"] )
      $conds[] = "cpt_rechargements.date_rech <= '".date("Y-m-d H:i:s",$_REQUEST["fin"])."'";

    if ( $_REQUEST["id_comptoir"] )
      $conds[] = "cpt_rechargements.id_comptoir='".intval($_REQUEST["id_comptoir"])."'";

    if ( $_REQUEST["banque_rech"] )
      $conds[] = "cpt_rechargements.banque_rech = '".intval($_REQUEST["banque_rech"])."'";

    if ( $_REQUEST["type_paiement_rech"] != -1 )
      $conds[] = "cpt_rechargements.type_paiement_rech = '".intval($_REQUEST["type_paiement_rech"])."'";


    if ( count($conds) )
    {
      $req = new requete($site->db, "SELECT " .
        "COUNT(`cpt_rechargements`.`id_rechargement`), " .
        "SUM(`cpt_rechargements`.`montant_rech`) " .
        "FROM `cpt_rechargements` " .
        "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_rechargements`.`id_assocpt` " .
        "INNER JOIN `utilisateurs` AS `vendeur` ON `cpt_rechargements`.`id_utilisateur_operateur` =`vendeur`.`id_utilisateur` " .
        "INNER JOIN `utilisateurs` AS `client` ON `cpt_rechargements`.`id_utilisateur` =`client`.`id_utilisateur` " .
        "INNER JOIN `cpt_comptoir` ON `cpt_rechargements`.`id_comptoir` =`cpt_comptoir`.`id_comptoir` " .
        "WHERE " .implode(" AND ",$conds));

      list($ln,$sum) = $req->get_row();

      $cts->add_title(2,"Somme");
      $cts->add_paragraph("$ln rechargements pour ".($sum/100)." Euros");

      if ( $ln < 4000 )
      {
        $req = new requete($site->db, "SELECT " .
          "`cpt_rechargements`.`id_rechargement`, " .
          "`cpt_rechargements`.`date_rech`, " .
          "`cpt_rechargements`.`banque_rech`, " .
          "`cpt_rechargements`.`type_paiement_rech`, " .
          "`asso`.`id_asso`, " .
          "`asso`.`nom_asso`, " .
          "CONCAT(`client`.`prenom_utl`,' ',`client`.`nom_utl`) as `nom_utilisateur_client`, " .
          "`client`.`id_utilisateur` AS `id_utilisateur_client`, " .
          "CONCAT(`vendeur`.`prenom_utl`,' ',`vendeur`.`nom_utl`) as `nom_utilisateur_vendeur`, " .
          "`vendeur`.`id_utilisateur` AS `id_utilisateur_vendeur`, " .
          "`cpt_rechargements`.`montant_rech`/100 AS `total`," .
          "`cpt_comptoir`.`id_comptoir`, " .
          "`cpt_comptoir`.`nom_cpt`" .
          "FROM `cpt_rechargements` " .
          "LEFT JOIN `asso` ON `asso`.`id_asso` =`cpt_rechargements`.`id_assocpt` " .
          "INNER JOIN `utilisateurs` AS `vendeur` ON `cpt_rechargements`.`id_utilisateur_operateur` =`vendeur`.`id_utilisateur` " .
          "INNER JOIN `utilisateurs` AS `client` ON `cpt_rechargements`.`id_utilisateur` =`client`.`id_utilisateur` " .
          "INNER JOIN `cpt_comptoir` ON `cpt_rechargements`.`id_comptoir` =`cpt_comptoir`.`id_comptoir` " .
          "WHERE " .implode(" AND ",$conds).
          "ORDER BY `cpt_rechargements`.`date_rech` DESC");

        $cts->add(new sqltable(
          "listresp",
          "Listing", $req, "comptarech.php",
          "id_facture",
          array(
            "id_rechargement"=>"Rech.",
            "date_rech"=>"Date",
            "nom_cpt"=>"Lieu",
            "type_paiement_rech"=>"Type",
            "banque_rech"=>"Banque",
            "nom_utilisateur_vendeur"=>"Operateur",
            "nom_utilisateur_client"=>"Client",
            "total"=>"Som."),
          array(),
          array(),
          array( "type_paiement_rech"=>$TypesPaiementsFull,"banque_rech"=> $Banques)
          ),true);
      }
    }
  }
}
else
{
  $sublist = new itemlist("Autre outils");
  $sublist->add("<a href=\"".$topdir."comptoir/admin.php\">Administration des produits</a>");
  $sublist->add("<a href=\"".$topdir."comptoir/caisse.php\">Relevés de caisse</a>");
  $cts->add($sublist,true);
}

$site->add_contents($cts);

$site->end_page();

?>
