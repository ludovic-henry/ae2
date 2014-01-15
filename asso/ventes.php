<?php
/* Copyright 2006
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
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

require_once($topdir. "comptoir/include/typeproduit.inc.php");
require_once($topdir. "comptoir/include/produit.inc.php");
require_once($topdir. "comptoir/include/comptoir.inc.php");



$site = new site ();
$asso = new asso($site->db,$site->dbrw);

$site->allow_only_logged_users("presentation");

$asso->load_by_id($_REQUEST["id_asso"]);
if ( $asso->id < 1 )
{
  $site->error_not_found("services");
  exit();
}

if ( !$site->user->is_in_group("gestion_ae") && !$asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU) )
  $site->error_forbidden("presentation");

if (isset($_REQUEST['action']) && $_REQUEST['action']=="pdf")
{
  require_once ($topdir . "include/pdf/facture_pdf.inc.php");
  define('FPDF_FONTPATH', $topdir.'./font/');
  $pdf=new FPDF();
  $pdf->AliasNbPages();
  $pdf->SetAutoPageBreak(false);
  $pdf->AddPage();
  $pdf->SetFont('Arial','B',14);

  $pdf->Image($topdir."./images/Ae-blanc.jpg", 10, 10, 0, 20);
  $pdf->SetFont('Times','BI',22);
  $pdf->Cell(0, 18, date("d/m/Y"),'','','R');
  $pdf->Ln();

  $pdf->SetFont('Arial','B',14);
  $pdf->Cell('', 20, "Liste",'','','C');
  $pdf->Ln();

  $pdf->SetFont('Arial','B',11);

  //Header
  $w=array(35,35,50,10,15,15,30);

  //Data
  $fill=0;
  $skip=57;
  $height=0;
  $page=1;
  $breakpage = false;

  $conds = array();
  foreach($_POST[conds] as $value)
  {
    $conds[] = $value;
  }

  $req_= new requete($site->db, "SELECT " .
                                "`cpt_debitfacture`.`id_facture`, " .
                                "`cpt_debitfacture`.`date_facture`, " .
                                "`asso`.`id_asso`, " .
                                "`asso`.`nom_asso`, " .
                                "`client`.`prenom_utl` as `nom_client`, " .
                                "`client`.`id_utilisateur` AS `id_utilisateur_client`, " .
                                "`client`.`nom_utl` as `prenom_client`, " .
                                "`vendeur`.`id_utilisateur` AS `id_utilisateur_vendeur`, " .
                                "`cpt_vendu`.`quantite`, " .
                                "`cpt_vendu`.`prix_unit`/100 AS `prix_unit`, " .
                                "`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`/100 AS `total`," .
                                "`cpt_produits`.`prix_achat_prod`*`cpt_vendu`.`quantite`/100 AS `total_coutant`," .
                                "`cpt_comptoir`.`id_comptoir`, " .
                                "`cpt_comptoir`.`nom_cpt`," .
                                "`cpt_produits`.`nom_prod`, " .
                                "`cpt_produits`.`id_produit`, " .
                                "`cpt_type_produit`.`id_typeprod`, " .
                                "`cpt_type_produit`.`nom_typeprod`, " .
                                "`cpt_produits`.`nom_prod` AS `produit`, " .
                                "`utl_etu_utbm`.`surnom_utbm` AS `surnom_client` " .
                                "FROM `cpt_vendu` " .
                                "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
                                "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
                                "INNER JOIN `cpt_type_produit` ON `cpt_produits`.`id_typeprod` =`cpt_type_produit`.`id_typeprod` " .
                                "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
                                "INNER JOIN `utilisateurs` AS `vendeur` ON `cpt_debitfacture`.`id_utilisateur` =`vendeur`.`id_utilisateur` " .
                                "INNER JOIN `utilisateurs` AS `client` ON `cpt_debitfacture`.`id_utilisateur_client` =`client`.`id_utilisateur` " .
                                "LEFT JOIN `utl_etu_utbm` ON `cpt_debitfacture`.`id_utilisateur_client` =`utl_etu_utbm`.`id_utilisateur` " .
                                "INNER JOIN `cpt_comptoir` ON `cpt_debitfacture`.`id_comptoir` =`cpt_comptoir`.`id_comptoir` " .
                                "WHERE " .implode(" AND ",$conds).
                                "ORDER BY `client`.`nom_utl`, `client`.`prenom_utl`");

  while ($res = $req_->get_row())
  {
    if(($skip + $height) > 255)
    {
      $breakpage = true;
      $border = 'LRB';
    }
    else if($height == 0)
    {
      //Colors, line width and bold font
      $pdf->SetFillColor(0,0,0);
      $pdf->SetTextColor(255);
      $pdf->SetDrawColor(128,128,128);
      $pdf->SetLineWidth(.3);
      $pdf->SetFont('','B');

      $pdf->Cell($w[0],7,"Nom", 1, 0, 'C', 1);
      $pdf->Cell($w[1],7,"Prenom", 1, 0, 'C', 1);
      $pdf->Cell($w[6],7,"Surnom", 1, 0, 'C', 1);
      $pdf->Cell($w[2],7,"Produit", 1, 0, 'C', 1);
      $pdf->Cell($w[3],7,utf8_decode("Qté"), 1, 0, 'C', 1);
      $pdf->Cell($w[4],7,"Prix", 1, 0, 'C', 1);
      $pdf->Cell($w[5],7,"Total", 1, 0, 'C', 1);

      $pdf->Ln();

      //Color and font restoration
      $pdf->SetFillColor(200,200,200);
      $pdf->SetTextColor(0);
      $pdf->SetFont('');

      $height += 7;

      $border = 'LR';
    }
    else
    {
      $border = 'LR';

      $height += 6;
    }

    $pdf->Cell($w[0],6,utf8_decode($res['nom_client']),$border,0,'L',$fill);
    $pdf->Cell($w[1],6,utf8_decode($res['prenom_client']),$border,0,'L',$fill);
    $pdf->Cell($w[6],6,utf8_decode($res['surnom_client']),$border,0,'L',$fill);
    $pdf->Cell($w[2],6,utf8_decode($res['produit']),$border,0,'L',$fill);
    $pdf->Cell($w[3],6,utf8_decode($res['quantite']),$border,0,'L',$fill);
    $pdf->Cell($w[4],6,number_format(utf8_decode($res['prix_unit']),2),$border,0,'L',$fill);
    $pdf->Cell($w[5],6,number_format(utf8_decode($res['total']),2),$border,0,'L',$fill);

    $pdf->Ln();

    if($breakpage == true)
    {
      $skip = 0;
      $height = 0;
      $pdf->Cell(0, 10, "Page " . $page ,0,0,'C');
      $pdf->AddPage();
      $page++;
      $breakpage = false;
    }

    $fill=!$fill;
  }

  $pdf->Cell(array_sum($w),0,'','T');
  $pdf->Output('liste_compta_'.date("d-m-Y").'.pdf',D);
}

$site->start_page("presentation",$asso->nom);

$cts = new contents($asso->get_html_path());

$cts->add(new tabshead($asso->get_tabs($site->user),"slds"));

$cts->add_title(1,"Ventes cartes AE + e-boutic");

if (! isset($_REQUEST["allprod"]))
{
  $allprod = false;
  $req_produits = new requete($site->db, "SELECT `id_produit`, `nom_prod`
                                          FROM `cpt_produits`
                                          WHERE `cpt_produits`.`id_assocpt` = ".$asso->id."
                                          AND prod_archive =0
                                          ORDER BY `nom_prod`");
}
if (isset($_REQUEST["allprod"]) || ($req_produits->lines <= 0))
{
  $allprod = true;
  $req_produits = new requete($site->db, "SELECT `id_produit` , `nom_prod`
                                          FROM `cpt_produits`
                                          ORDER BY `nom_prod`");
}

$produits = array(0=> "(aucun)");
while($row = $req_produits->get_row())
  $produits[$row['id_produit']] = $row['nom_prod'];

$frm = new form ("cptacpt","ventes.php",true,"POST","Critères de selection");
$frm->add_hidden("action","view");
$frm->add_hidden("id_asso",$asso->id);
$frm->add_datetime_field("debut","Date et heure de début");
$frm->add_datetime_field("fin","Date et heure de fin");
$frm->add_entity_select("id_typeprod", "Type", $site->db, "typeproduit",$_REQUEST["id_typeprod"],true);
$frm->add_entity_select("id_comptoir","Lieu", $site->db, "comptoir",$_REQUEST["id_comptoir"],true);
$frm->add_select_field("id_produit", "Produit", $produits);

if (! $allprod)
  $frm->add_info("<a href=\"?id_asso=".$asso->id."&amp;allprod\">Afficher tous les produits</a>");

$frm->add_select_field("a_retirer_vente", "A retirer", array(null => "", 1 => "Non retiré", 2 => "Retiré"));
$frm->add_submit("valid","Voir");
$cts->add($frm,true);


$conds = array("cpt_vendu.id_assocpt='".$asso->id."'");
$comptoir = false;

if ( $_REQUEST["debut"] )
  $conds[] = "cpt_debitfacture.date_facture >= '".date("Y-m-d H:i:s",$_REQUEST["debut"])."'";

if ( $_REQUEST["fin"] )
  $conds[] = "cpt_debitfacture.date_facture <= '".date("Y-m-d H:i:s",$_REQUEST["fin"])."'";

if ( $_REQUEST["id_comptoir"] )
{
  $conds[] = "cpt_debitfacture.id_comptoir='".intval($_REQUEST["id_comptoir"])."'";
  $comptoir=true;
}

if ( $_REQUEST["id_typeprod"] )
  $conds[] = "cpt_produits.id_typeprod='".intval($_REQUEST["id_typeprod"])."'";

if ( $_REQUEST["id_produit"] )
  $conds[] = "cpt_vendu.id_produit='".intval($_REQUEST["id_produit"])."'";

if ( $_REQUEST["a_retirer_vente"] == '1')
  $conds[] = "cpt_vendu.a_retirer_vente='1'";
if ( $_REQUEST["a_retirer_vente"] == '2')
  $conds[] = "cpt_vendu.a_retirer_vente='0'";

if ( count($conds) >= 1 )
{

  $req = new requete($site->db, "SELECT " .
                                "COUNT(`cpt_vendu`.`id_produit`), " .
                                "SUM(`cpt_vendu`.`quantite`), " .
                                "SUM(`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`) AS `total`," .
                                "SUM(`cpt_produits`.`prix_achat_prod`*`cpt_vendu`.`quantite`) AS `total_coutant`" .
                                "FROM `cpt_vendu` " .
                                "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
                                "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
                                "INNER JOIN `cpt_type_produit` ON `cpt_produits`.`id_typeprod` =`cpt_type_produit`.`id_typeprod` " .
                                "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
                                "INNER JOIN `utilisateurs` AS `vendeur` ON `cpt_debitfacture`.`id_utilisateur` =`vendeur`.`id_utilisateur` " .
                                "INNER JOIN `utilisateurs` AS `client` ON `cpt_debitfacture`.`id_utilisateur_client` =`client`.`id_utilisateur` " .
                                "INNER JOIN `cpt_comptoir` ON `cpt_debitfacture`.`id_comptoir` =`cpt_comptoir`.`id_comptoir` " .
                                "WHERE " .implode(" AND ",$conds).
                                "ORDER BY `cpt_debitfacture`.`date_facture` DESC");

  list($ln,$qte,$sum,$sumcoutant) = $req->get_row();


  $cts->add_title(2,"Sommes");
  $cts->add_paragraph("Quantité : $qte unités<br/>" .
                      "Chiffre d'affaire: ".($sum/100)." Euros<br/>" .
                      "Prix countant total estimé* : ".($sumcoutant/100)." Euros");

  $frm = new form ("cptacptpdf","ventes.php?id_asso=".$asso->id,true,"POST","PDF");
  $frm->add_hidden("action","pdf");
  $i=0;
  foreach($conds as $value)
  {
    $frm->add_hidden("conds[".$i."]",$value);
    $i++;
  }
  $frm->add_submit("valid","Générer le PDF");
  $cts->add($frm,true);


  if ( $ln < 1000 )
  {

    $req = new requete($site->db, "SELECT " .
                                  "`cpt_debitfacture`.`id_facture`, " .
                                  "`cpt_debitfacture`.`date_facture`, " .
                                  "`asso`.`id_asso`, " .
                                  "`asso`.`nom_asso`, " .
                                  "CONCAT(`client`.`prenom_utl`,' ',`client`.`nom_utl`) as `nom_utilisateur_client`, " .
                                  "`client`.`id_utilisateur` AS `id_utilisateur_client`, " .
                                  "CONCAT(`vendeur`.`prenom_utl`,' ',`vendeur`.`nom_utl`) as `nom_utilisateur_vendeur`, " .
                                  "`vendeur`.`id_utilisateur` AS `id_utilisateur_vendeur`, " .
                                  "`cpt_vendu`.`quantite`, " .
                                  "`cpt_vendu`.`prix_unit`/100 AS `prix_unit`, " .
                                  "`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`/100 AS `total`," .
                                  "`cpt_produits`.`prix_achat_prod`*`cpt_vendu`.`quantite`/100 AS `total_coutant`," .
                                  "`cpt_comptoir`.`id_comptoir`, " .
                                  "`cpt_comptoir`.`nom_cpt`," .
                                  "`cpt_produits`.`nom_prod`, " .
                                  "`cpt_produits`.`id_produit`, " .
                                  "`cpt_type_produit`.`id_typeprod`, " .
                                  "`cpt_type_produit`.`nom_typeprod`" .
                                  "FROM `cpt_vendu` " .
                                  "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
                                  "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
                                  "INNER JOIN `cpt_type_produit` ON `cpt_produits`.`id_typeprod` =`cpt_type_produit`.`id_typeprod` " .
                                  "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
                                  "INNER JOIN `utilisateurs` AS `vendeur` ON `cpt_debitfacture`.`id_utilisateur` =`vendeur`.`id_utilisateur` " .
                                  "INNER JOIN `utilisateurs` AS `client` ON `cpt_debitfacture`.`id_utilisateur_client` =`client`.`id_utilisateur` " .
                                  "INNER JOIN `cpt_comptoir` ON `cpt_debitfacture`.`id_comptoir` =`cpt_comptoir`.`id_comptoir` " .
                                  "WHERE " .implode(" AND ",$conds).
                                  "ORDER BY `cpt_debitfacture`.`date_facture` DESC");


    $cts->add(new sqltable( "listresp",
                            "Listing", $req, "ventes.php",
                            "id_facture",
                            array( "id_facture"=>"Facture",
                                   "date_facture"=>"Date",
                                   "nom_typeprod"=>"Type",
                                   "nom_prod"=>"Produit",
                                   "nom_cpt"=>"Lieu",
                                   "nom_utilisateur_vendeur"=>"Vendeur",
                                   "nom_utilisateur_client"=>"Client",
                                   "nom_asso"=>"Asso.",
                                   "quantite"=>"Qte",
                                   "total"=>"Som.",
                                   "total_coutant"=>"Coutant*"),
                            array(),
                            array(),
                            array( )
                          ),true);
  }
  $cts->add_paragraph("* ATTENTION: Prix coutant basé sur le prix actuel.");
}


$site->add_contents($cts);

$site->end_page();
?>
