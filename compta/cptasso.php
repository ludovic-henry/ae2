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
$topdir="../";
require_once("include/compta.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();
$cptasso = new compte_asso($site->db);
$cpbc  = new compte_bancaire($site->db);
$asso  = new asso($site->db);

if ( !$site->user->is_valid() )
  $site->error_forbidden("services");

$cptasso->load_by_id($_REQUEST["id_cptasso"]);
if( $cptasso->id < 1 )
{
  $site->error_not_found("services");
  exit();
}
$cpbc->load_by_id($cptasso->id_cptbc);
$asso->load_by_id($cptasso->id_asso);

$site->set_current($asso->id,$asso->nom,null,null,$cpbc->nom);

if ( !$site->user->is_in_group("compta_admin") && !$asso->is_member_role($site->user->id,ROLEASSO_TRESORIER) )
  $site->error_forbidden("services");

if ( $_REQUEST["action"] == "newclasseur" && $GLOBALS["svalid_call"] )
{
  $cla   = new classeur_compta($site->db,$site->dbrw);
  if ( ($_REQUEST["debut"] > 0) && ($_REQUEST["fin"] > 0) && $_REQUEST["nom"] )
    $cla->ajouter($cptasso->id,$_REQUEST["debut"],$_REQUEST["fin"],$_REQUEST["nom"]);
}
elseif ( $_REQUEST["action"] == "save" )
{
  $cla   = new classeur_compta($site->db,$site->dbrw);
  $cla->load_by_id($_REQUEST['id_classeur']);
  if ( ($_REQUEST["debut"] > 0) && ($_REQUEST["fin"] > 0) && $_REQUEST["nom"] )
    $cla->update($_REQUEST["debut"],$_REQUEST["fin"],$_REQUEST["nom"]);
}
elseif ( $_REQUEST["action"] == "fermer" )
{
  $cla   = new classeur_compta($site->db,$site->dbrw);
  $cla->load_by_id($_REQUEST['id_classeur']);
  if ( $cla->id > 0 )
    $cla->fermer();
}
elseif ( $_REQUEST["action"] == "fermerdans" )
{
  $cla   = new classeur_compta($site->db,$site->dbrw);
  $cla->load_by_id($_REQUEST['id_classeur']);


  $site->start_page ("services", "Compte asso" );
  $cts = new contents ("<a href=\"./\">Compta</a> / ".$cpbc->get_html_link()." / ".$cptasso->get_html_link());
  $frm = new form("newclasseur","cptasso.php?id_cptasso=".$cptasso->id,true,"POST","Fermer le classeur et le transferer dans un nouveau classeur");
  $frm->add_hidden("action","transfert");
  $frm->allow_only_one_usage();
  $frm->add_hidden("id_classeur",$cla->id);
  $frm->set_help_page("compta-classeurs");
  $frm->add_text_field("nom","Nom","",true);
  $frm->add_date_field("debut","Date de debut",$cla->date_fin_classeur+(60*60*24),true);
  $frm->add_date_field("fin","Date de fin",$cla->date_fin_classeur+(60*60*24)+($cla->date_fin_classeur-$cla->date_debut_classeur),true);
  $frm->add_submit("newclasseur","Transferer");
  $cts->add($frm,true);

  $site->add_contents ($cts);
  $site->end_page ();
  exit();
}
elseif ( $_REQUEST["action"] == "edit" )
{
  $cla   = new classeur_compta($site->db,$site->dbrw);
  $cla->load_by_id($_REQUEST['id_classeur']);

  $site->start_page ("services", "Compte asso" );
  $cts = new contents ("<a href=\"./\">Compta</a> / ".$cpbc->get_html_link()." / ".$cptasso->get_html_link()." / ".$cla->get_html_link());
  $frm = new form("newclasseur","cptasso.php?id_cptasso=".$cptasso->id,true,"POST","Editer");
  $frm->add_hidden("action","save");
  $frm->add_hidden("id_classeur",$cla->id);
  $frm->add_text_field("nom","Nom",$cla->nom,true);
  $frm->add_date_field("debut","Date de debut",$cla->date_debut_classeur,true);
  $frm->add_date_field("fin","Date de fin",$cla->date_fin_classeur,true);
  $frm->add_submit("newclasseur","Enregistrer");
  $cts->add($frm,true);

  $site->add_contents ($cts);
  $site->end_page ();
  exit();
}
elseif ( $_REQUEST["action"] == "transfert" && $GLOBALS["svalid_call"] )
{

/* A faire vérifier par un expert comptable
 *
 ** Opération de cloture (si solde<0)
 * 791   Transferts de charges d'exploitation
 ** Opération d'ouverture (si solde<0)
 * 678   Autres charges exceptionnelles
 *
 ** Opération de cloture (si solde > 0)
 * 689  Engagements à réaliser
 ** Opération d'ouverture (si solde > 0)
 * 789    Report des ressources non utilisées des exercices antérieurs
 *
 **/
  $cla1   = new classeur_compta($site->db,$site->dbrw);
  $cla1->load_by_id($_REQUEST['id_classeur']);

  $cla2   = new classeur_compta($site->db,$site->dbrw);
  if ( ($_REQUEST["debut"] > 0) && ($_REQUEST["fin"] > 0) && $_REQUEST["nom"] )
    $cla2->ajouter($cptasso->id,$_REQUEST["debut"],$_REQUEST["fin"],$_REQUEST["nom"]);

  if ( $cla1->id > 0 && $cla2->id > 0 )
  {
    $req = new requete ($site->db,
      "SELECT " .
      "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
      "FROM `cpta_operation` " .
      "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
      "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
      "WHERE `cpta_operation`.id_classeur='".$cla1->id."'");

    list($solde)=$req->get_row();

    if ( $solde != 0)
    {
      $tpop1 = new operation_club($site->db,$site->dbrw);
      $tpop2 = new operation_club($site->db,$site->dbrw);
      if ( $solde > 0 )
      {
        $tpop1->load_or_create ( $cptasso->id_asso, 689, "Report vers exercice suivant" );
        $tpop2->load_or_create ( $cptasso->id_asso, 789, "Report depuis exercice antérieur" );
      }
      else
      {
        $tpop1->load_or_create ( $cptasso->id_asso, 791, "Report vers exercice suivant (transfert de charge)" );
        $tpop2->load_or_create ( $cptasso->id_asso, 678, "Report depuis exercice antérieur" );
      }
      $solde=abs($solde);

      $op1    = new operation($site->db,$site->dbrw);
      $op2    = new operation($site->db,$site->dbrw);

      $op1->add_op ( $cla1->id,
            $tpop1->id, $tpop1->id_opstd,
            null,
            null, null, $cptasso->id,
            $solde, $cla2->date_debut_classeur, "Report", true,
            3, ""
            );
      $op2->add_op ( $cla2->id,
            $tpop2->id, $tpop2->id_opstd,
            null,
            null, null, $cptasso->id,
            $solde, $cla2->date_debut_classeur, "Report", true,
            3, ""
            );
      $op1->link_op($op2);
    }
  }

}


$site->start_page ("services", "Compte asso" );

$cts = new contents ("<a href=\"./\">Compta</a> / ".$cpbc->get_html_link()." / ".$cptasso->get_html_link());

$lst = new itemlist("Outils");
$lst->add("<a href=\"typeop.php?id_asso=".$asso->id."\">Types d'opérations</a>");
$lst->add("<a href=\"../entreprise.php\">Entreprises</a> (commun à tous)");

$cts->add($lst,true);

$req_sql = new requete ($site->db,
      "SELECT `id_classeur`,`nom_classeur`," .
      "`date_debut_classeur`,`date_fin_classeur`,`ferme`," .
      "(SELECT " .
      "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
      "FROM `cpta_operation` " .
      "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
      "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
      "WHERE `cpta_operation`.id_classeur=`cpta_classeur`.id_classeur)/100 AS `solde` " .
      "FROM `cpta_classeur` " .
      "WHERE `id_cptasso`='".$cptasso->id."' " .
      "ORDER BY `date_debut_classeur`");

$tbl = new sqltable ("lstclasseur",
               "Classeurs",
               $req_sql,
               "cptasso.php?id_cptasso=".$cptasso->id,
               "id_classeur",
               array("nom_classeur" => "Classeur",
               "solde"=>"Solde",
               "date_debut_classeur"=>"De",
               "date_fin_classeur"=>"Au",
               "ferme"=>"Etat"),
               array("fermer"=>"Fermer","fermerdans"=>"Fermer et transferer","edit"=>"Editer"),
               array(),
               array("ferme"=>array("0"=>"Ouvert","1"=>"Fermé")));

$cts->add($tbl,true);


$frm = new form("newclasseur","cptasso.php?id_cptasso=".$cptasso->id,true,"POST","Ouvrir un classeur");
$frm->add_hidden("action","newclasseur");
$frm->allow_only_one_usage();
$frm->set_help_page("compta-classeurs");
$frm->add_text_field("nom","Nom","",true);
$frm->add_date_field("debut","Date de debut",time(),true);
$frm->add_date_field("fin","Date de fin",time()+(6*30*24*60*60),true);
$frm->add_submit("newclasseur","Ajouter");
$cts->add($frm,true);

$site->add_contents ($cts);
$site->end_page ();
?>
