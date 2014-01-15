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
require_once("include/compta.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/entities/notefrais.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();

$site->allow_only_logged_users("services");
$notefrais = new notefrais($site->db,$site->dbrw);
$asso = new asso($site->db);

if ( isset($_REQUEST["id_notefrais"]) )
{
  $notefrais->load_by_id($_REQUEST["id_notefrais"]);
  if ( $notefrais->is_valid() )
    $asso->load_by_id($notefrais->id_asso);
}
elseif ( $_REQUEST["action"] == "create" )
{
  if ( !$_REQUEST["commentaire"] )
    $Erreur = "Commentaire requis";
  elseif ( !$_REQUEST["designation"][0] || ! $_REQUEST["prix"][0] )
    $Erreur = "Veuillez saisir au moins un frais";
  else
  {
    $asso->load_by_id($_REQUEST["id_asso"]);
    $notefrais->create ( null, $asso->id, $site->user->id, $_REQUEST["commentaire"], $_REQUEST["avance"] );
    foreach( $_REQUEST["designation"] as $i => $designation )
    {
      if ( $designation && $_REQUEST["prix"][$i] )
        $notefrais->create_line( $designation, $_REQUEST["prix"][$i] );
    }
  }
}

if ( $notefrais->is_valid() )
{
  if ( !$site->user->is_in_group("compta_admin")
       && !$asso->is_member_role($site->user->id,ROLEASSO_TRESORIER)
       && $site->user->id != $notefrais->id_utilisateur )
    $site->error_forbidden("services","group");

  $user = new utilisateur($site->db);
  $user->load_by_id($notefrais->id_utilisateur);

  if ( $_REQUEST["action"] == "delete" && !$notefrais->valide )
  {
    $notefrais->delete();
    header("Location: notefrais.php");
    exit();
  }
  elseif ( $_REQUEST["action"] == "save" && !$notefrais->valide )
  {
    $asso->load_by_id($_REQUEST["id_asso"]);
    $notefrais->update ( $asso->id, $_REQUEST["commentaire"], $_REQUEST["avance"] );

    $notefrais->delete_all_lines();

    foreach( $_REQUEST["designation"] as $i => $designation )
    {
      if ( $designation && $_REQUEST["prix"][$i] )
        $notefrais->create_line( $designation, $_REQUEST["prix"][$i] );
    }

  }
  elseif ( $_REQUEST["action"] == "edit" && !$notefrais->valide )
  {
    $site->start_page ("services", "Note de frais" );

    $cts = new contents("<a href=\"notefrais.php\">Note de frais</a> / N°".$notefrais->id);

    $frm = new form ("editnotefrais","notefrais.php?id_notefrais=".$notefrais->id,true,"POST","Modification");
    $frm->add_hidden("action","save");
    $frm->add_entity_smartselect ("id_asso","Activité concernée", $asso, false, true);
    $frm->add_text_area("commentaire","Commentaire",$notefrais->commentaire,40,3,true);
    $frm->add_price_field("avance","Avance (qui vous a déjà été versée)",$notefrais->avance);

    $lines = $notefrais->get_lines();

    for($i=0;$i<5;$i++)
    {
      $sfrm = new form(null,null,null,null);
      $sfrm->add_text_field("designation[$i]","Designation",$lines[$i]["designation_ligne_notefrais"]);
      $sfrm->add_price_field("prix[$i]","Prix",$lines[$i]["prix_ligne_notefrais"]);
      $frm->add($sfrm, false, false, false, false, true);
    }

    $frm->add_submit("record","Enregistrer");

    $cts->add($frm,true);

    $site->add_contents($cts);
    $site->end_page ();
    exit();
  }

  $site->start_page ("services", "Note de frais" );

  $cts = new contents("<a href=\"notefrais.php\">Note de frais</a> / N°".$notefrais->id);

  $cts->add_paragraph("Benevole : ".$user->get_html_link());
  $cts->add_paragraph("Activité : ".$asso->get_html_link());
  $cts->add_paragraph("Date : ".date("d/m/Y",$notefrais->date));
  $cts->add_paragraph("Commentaire : ".$notefrais->commentaire);

  $lines = $notefrais->get_lines();

  foreach($lines as $k => $d )
    $lines[$k]["prix_ligne_notefrais"] = $d["prix_ligne_notefrais"]/100;


  $tbl = new sqltable("frais",
    "",
    $lines,
    "notefrais.php?id_notefrais=".$notefrais->id,
    "num_notefrais_ligne",
    array(
      "designation_ligne_notefrais" => "Designation",
      "prix_ligne_notefrais" => "Prix"),
    array(),
    array(),
    array() );

  $cts->add($tbl);

  $cts->add_paragraph("Total : ".($notefrais->total/100));

  $cts->add_paragraph("Avance : ".($notefrais->avance/100));

  $cts->add_paragraph("A payer : ".($notefrais->total_payer/100));

  if ( $notefrais->valide )
  {
    if ( $notefrais->id_classeur )
      $cts->add_paragraph("Validé et classé");
    else
      $cts->add_paragraph("Validé");
  }
  else
  {
    $cts->add_paragraph("En attente de validation");
    $cts->add_paragraph("<a href=\"?id_notefrais=".$notefrais->id."&amp;action=edit\">Editer</a>");
    $cts->add_paragraph("<a href=\"?id_notefrais=".$notefrais->id."&amp;action=delete\">Supprimer</a>");

    if ( $asso->is_member_role($site->user->id,ROLEASSO_TRESORIER)
         || $site->user->is_in_group("compta_admin") )
    {
      // TODO: Valider, classer, et decomposer en operations
    }
  }
  $site->add_contents($cts);
  $site->end_page ();
  exit();
}

if ( isset($_REQUEST["id_asso"]) )
  $asso->load_by_id($_REQUEST["id_asso"]);

$site->start_page ("services", "Note de frais" );

$cts = new contents("<a href=\"notefrais.php\">Note de frais</a>");

$cts->add_title(2,"Informations sur les notes de frais");

$cts->add_paragraph("Les notes de frais vous permettent d'obtenir le remboursement des frais que vous avez engagés pour l'AE. Il peut s'agir de matériel que vous avez acheté pour une activité de l'AE.");

$cts->add_paragraph("La note de frais devra être accompagné de tous les justificatifs nécessaires (factures), qui devrons être remis au trésorier de l'activité. Les factures devront être à votre nom, justifiant ainsi que vous avez bien engagé les frais.");

$req = new requete($site->db,"SELECT id_notefrais,date_notefrais,asso.id_asso,nom_asso
FROM cpta_notefrais
INNER JOIN asso USING(id_asso)
WHERE id_utilisateur='".$site->user->id."'
ORDER BY id_notefrais");

$tbl = new sqltable("mines",
  "Mes notes de frais",
  $req,
  "notefrais.php",
  "id_notefrais",
  array(
    "id_notefrais" => "Numéro",
    "date_notefrais" => "Date",
    "nom_asso" => "Activité"),
  array(),
  array(),
  array() );

$cts->add($tbl,true);

$frm = new form ("saisienotefrais","notefrais.php",true,"POST","Saisie d'une nouvelle note de frais");
$frm->add_hidden("action","create");
if ( isset($Erreur) )
  $frm->error($Erreur);
$frm->add_entity_smartselect ("id_asso","Activité concernée", $asso, false, true);
$frm->add_text_area("commentaire","Commentaire","",40,3,true);
$frm->add_price_field("avance","Avance (qui vous a déjà été versée)");

for($i=0;$i<5;$i++)
{
  $sfrm = new form(null,null,null,null);
  $sfrm->add_text_field("designation[$i]","Designation");
  $sfrm->add_price_field("prix[$i]","Prix");
  $frm->add($sfrm, false, false, false, false, true);
}

$frm->add_submit("record","Enregistrer");

$cts->add($frm,true);

$site->add_contents($cts);

$site->end_page ();

?>
