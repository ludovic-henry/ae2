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

$site->allow_only_logged_users("services");

$budget = new budget($site->db,$site->dbrw);
$cla   = new classeur_compta($site->db);
$cptasso = new compte_asso($site->db);
$cpbc  = new compte_bancaire($site->db);
$asso  = new asso($site->db);

$budget->load_by_id($_REQUEST["id_budget"]);
if ( $budget->id < 1 )
{
  $site->error_not_found("services");
  exit();
}
$cla->load_by_id($budget->id_classeur);
$cptasso->load_by_id($cla->id_cptasso);
$cpbc->load_by_id($cptasso->id_cptbc);
$asso->load_by_id($cptasso->id_asso);

if ( !$site->user->is_in_group("compta_admin") && !$asso->is_member_role($site->user->id,ROLEASSO_TRESORIER) )
  $site->error_forbidden("services");

$site->set_current($asso->id,$asso->nom,$cla->id,$cla->nom,$cpbc->nom);

if ( $_REQUEST["action"] == "newligne" && !$budget->valide )
{
  $opclb = new operation_club($site->db);
  $opclb->load_by_id($_REQUEST["id_opclb"]);

  if ( $opclb->is_valid() )
  {
    $budget->add_line($opclb->id,get_prix($_REQUEST["montant"]),$_REQUEST["description"]);
  }
}
elseif ( $_REQUEST["action"] == "delete" && !$budget->valide )
{
  $budget->remove_line($_REQUEST["num_lignebudget"]);

}
elseif ( $_REQUEST["action"] == "updatebudget" && !$budget->valide )
{
  if ( $_REQUEST["nom"] )
    $budget->update($_REQUEST["nom"],$_REQUEST["projets"]);
}
elseif ( $_REQUEST["action"] == "termine" && !$budget->valide )
{
  $budget->set_termine();
}
elseif ( $_REQUEST["action"] == "editline" && !$budget->valide )
{
  $num = intval($_REQUEST["num_lignebudget"]);

  $opclb = new operation_club($site->db);
  $opclb->load_by_id($_REQUEST["id_opclb"]);

  if ( $opclb->is_valid() )
    $budget->update_line($num,$opclb->id,get_prix($_REQUEST["montant"]),$_REQUEST["description"]);

}
elseif ( $_REQUEST["action"] == "edit" && !$budget->valide  )
{
  $num = intval($_REQUEST["num_lignebudget"]);

  $datas = $budget->get_line($num);

  if ( is_null($datas) )
  {
    header("Location: budget.php?id_budget=".$budget->id);
    exit();
  }

  $site->start_page ("services", "Budget ".$budget->nom." dans classeur ".$cla->nom." ( ".$asso->nom ." - ". $cpbc->nom.")" );

  //$cts = new contents("Budget ".$budget->nom." dans classeur ".$cla->nom." ( ".$asso->nom ." - ". $cpbc->nom.")");
  $cts = new contents("<a href=\"./\">Compta</a> / ".$cpbc->get_html_link()." / ".$cptasso->get_html_link()." / ".$cla->get_html_link()." / ".$budget->get_html_link());
  $cts->set_help_page("compta-budget");

  $frm = new form("editline","budget.php?id_budget=".$budget->id,true,"POST","Editer une ligne budgetaire");
  $frm->add_hidden("action","editline");
  $frm->add_hidden("num_lignebudget",$num);
  $frm->add_select_field("id_opclb","Type",$site->get_typeop_clb($cptasso->id_asso,false),$datas["id_opclb"]);
  $frm->add_text_field("description","Description",$datas["description_ligne"],false);
  $frm->add_text_field("montant","Montant",$datas["montant_ligne"]/100,false);
  $frm->add_submit("editline","Enregistrer");
  $cts->add($frm,true);

  $site->add_contents($cts);

  $site->end_page ();
  exit();
}

$site->start_page ("services", "Budget ".$budget->nom." dans classeur ".$cla->nom." ( ".$asso->nom ." - ". $cpbc->nom.")" );

$cts = new contents("<a href=\"./\">Compta</a> / ".$cpbc->get_html_link()." / ".$cptasso->get_html_link()." / ".$cla->get_html_link()." / ".$budget->get_html_link());
$cts->set_help_page("compta-budget");

$frm = new form("updatebudget","budget.php?id_budget=".$budget->id,false,"POST","Informations");
$frm->add_hidden("action","updatebudget");
$frm->add_text_field("nom","Nom",$budget->nom);
$frm->add_text_area("projets","Description des projets",$budget->projets,80,10);
if ( !$budget->valide )
$frm->add_submit("updatebudget","Enregistrer");
$cts->add($frm,true);

$req = new requete ( $site->db, "SELECT  " .
    "SUM(`cpta_op_clb`.`type_mouvement`*cpta_ligne_budget.montant_ligne), type_mouvement " .
    "FROM cpta_ligne_budget " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_ligne_budget`.`id_opclb`=`cpta_op_clb`.`id_opclb` " .
    "WHERE cpta_ligne_budget.id_budget='".$budget->id."' ".
    "GROUP BY type_mouvement");

$sums[-1]=0;
$sums[1]=0;

while ( list($sum,$mvt) = $req->get_row() )
  $sums[$mvt]=$sum;

if ( $sums[1]+$sums[-1] != 0 )
  $cts->add_paragraph("Attention, budget non équilibré : Différence de <b>".(($sums[1]+$sums[-1])/100)."</b> Euros","error");

$req = new requete ( $site->db, "SELECT cpta_ligne_budget.num_lignebudget, " .
    "cpta_ligne_budget.description_ligne," .
    "(cpta_ligne_budget.montant_ligne/100) AS montant, " .
    "cpta_op_clb.libelle_opclb " .
    "FROM cpta_ligne_budget " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_ligne_budget`.`id_opclb`=`cpta_op_clb`.`id_opclb` " .
    "WHERE cpta_ligne_budget.id_budget='".$budget->id."' AND `cpta_op_clb`.`type_mouvement`=1 " .
    "ORDER BY cpta_op_clb.libelle_opclb");

$cts->add(new sqltable(
  "lstcredit",
  "Recettes : ".($sums[1]/100)." Euros", $req, "budget.php?id_budget=".$budget->id,
  "num_lignebudget",
  array(
    "libelle_opclb"=>"Libéllé",
    "description_ligne"=>"Description",
    "montant"=>"Montant"
    ),
  $budget->valide?array():array("delete"=>"Supprimer","edit"=>"Editer"),
  array(),
  array()
  ),true);


$req = new requete ( $site->db, "SELECT cpta_ligne_budget.num_lignebudget, " .
    "cpta_ligne_budget.description_ligne," .
    "(cpta_ligne_budget.montant_ligne/100) AS montant, " .
    "cpta_op_clb.libelle_opclb " .
    "FROM cpta_ligne_budget " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_ligne_budget`.`id_opclb`=`cpta_op_clb`.`id_opclb` " .
    "WHERE cpta_ligne_budget.id_budget='".$budget->id."' AND `cpta_op_clb`.`type_mouvement`=-1 " .
    "ORDER BY cpta_op_clb.libelle_opclb");

$cts->add(new sqltable(
  "lstdepenses",
  "Depenses : ".($sums[-1]/100)." Euros", $req, "budget.php?id_budget=".$budget->id,
  "num_lignebudget",
  array(
    "libelle_opclb"=>"Libéllé",
    "description_ligne"=>"Description",
    "montant"=>"Montant"
    ),
  $budget->valide?array():array("delete"=>"Supprimer","edit"=>"Editer"),
  array(),
  array()
  ),true);


if ( $sums[1]+$sums[-1] != 0 )
  $cts->add_paragraph("Attention, budget non équilibré : Différence de <b>".(($sums[1]+$sums[-1])/100)."</b> Euros","error");

if ( !$budget->valide )
{
  $frm = new form("newligne","budget.php?id_budget=".$budget->id,true,"POST","Ajouter ligne budgetaire");
  $frm->add_hidden("action","newligne");
  $frm->add_select_field("id_opclb","Type",$site->get_typeop_clb($cptasso->id_asso,false));
  $frm->add_text_field("description","Description","",false);
  $frm->add_text_field("montant","Montant","0.00",false);
  $frm->add_submit("newligne","Ajouter");
  $cts->add($frm,true);
}

$site->add_contents($cts);

$site->end_page ();

?>
