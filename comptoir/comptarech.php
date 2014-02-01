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
$topdir="../";
require_once("include/comptoirs.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
$site = new sitecomptoirs();

if ( !$site->user->is_valid() )
{
        header("Location: /connexion.php?redirect_to=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
}

$site->fetch_proprio_comptoirs();
$comptoirs = array_merge(array(0=>"-"),$site->proprio_comptoirs);
$comptoirs[-42]='Bureau beflort+Machines';

if ( !count($site->proprio_comptoirs) && !$site->user->is_in_group("gestion_ae") )
        $site->error_forbidden("services");

$TypesPaiementsFull[-1]="--";

$site->set_admin_mode();

$site->start_page("services","Comptabilité comptoirs");

$cts = new contents("<a href=\"admin.php\">Administration comptoirs</a> / Rechargement carte AE");

$frm = new form ("cptacpt","comptarech.php",true,"POST","Critères de selection");
$frm->add_hidden("action","view");
//$frm->add_select_field("mode","Mode", array(""=>"Brut","day"=>"Statistiques/Jour","week"=>"Statistiques/Semaines","month"=>"Statistiques/Mois","year"=>"Statistiques/Année"),$_REQUEST["mode"]);
$frm->add_datetime_field("debut","Date et heure de début");
$frm->add_datetime_field("fin","Date et heure de fin");
$frm->add_select_field("id_comptoir","Lieu", $comptoirs,$_REQUEST["id_comptoir"]);

$frm->add_select_field("banque_rech","Banque", $Banques,$_REQUEST["banque_rech"]);
$frm->add_select_field("type_paiement_rech","Type paiement", $TypesPaiementsFull,isset($_REQUEST["type_paiement_rech"])?$_REQUEST["type_paiement_rech"]:-1);


$frm->add_submit("valid","Voir");
$cts->add($frm,true);

if ( $_REQUEST["action"] == "view" )
{
        $conds = array();
        $comptoir = false;

        if ( $_REQUEST["debut"] )
                $conds[] = "cpt_rechargements.date_rech >= '".date("Y-m-d H:i:s",$_REQUEST["debut"])."'";

        if ( $_REQUEST["fin"] )
                $conds[] = "cpt_rechargements.date_rech <= '".date("Y-m-d H:i:s",$_REQUEST["fin"])."'";

        if ( isset($comptoirs[$_REQUEST["id_comptoir"]]) && $_REQUEST["id_comptoir"] )
        {
          //si bureau ae, on compte aussi le comptoire machine
          if($_REQUEST["id_comptoir"]==-42)
            $conds[] = "(cpt_rechargements.id_comptoir='6' OR cpt_rechargements.id_comptoir='8')";
          else
            $conds[] = "cpt_rechargements.id_comptoir='".intval($_REQUEST["id_comptoir"])."'";
        }

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
                        "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_rechargements`.`id_assocpt` " .
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
                        "nom_asso"=>"Asso.",
                        "total"=>"Som."),
                /*$site->user->is_in_group("gestion_ae")?array("delete"=>"Annuler le rechargement"):*/array(),
                array(),
                array( "type_paiement_rech"=>$TypesPaiementsFull,"banque_rech"=> $Banques)
                ),true);
        }
  }
}

$site->add_contents($cts);
$site->end_page();


?>
