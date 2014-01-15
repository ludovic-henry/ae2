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
require_once($topdir . "include/entities/entreprise.inc.php");
require_once($topdir . "include/entities/efact.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();

$site->allow_only_logged_users("services");

$cla   = new classeur_compta($site->db);
$cptasso = new compte_asso($site->db);
$cpbc  = new compte_bancaire($site->db);
$asso  = new asso($site->db);
$efact = new efact($site->db,$site->dbrw);

if ( isset($_REQUEST["id_efact"]) )
{
  $efact->load_by_id($_REQUEST["id_efact"]);
  if ( !$efact->is_valid() )
    $site->error_forbidden("services");
  $cla->load_by_id($efact->id_classeur);
}
else
{
  $cla->load_by_id($_REQUEST["id_classeur"]);
  if ( !$cla->is_valid() )
    $site->error_forbidden("services");
}

$cptasso->load_by_id($cla->id_cptasso);
$cpbc->load_by_id($cptasso->id_cptbc);
$asso->load_by_id($cptasso->id_asso);

if ( !$site->user->is_in_group("compta_admin") && !$asso->is_member_role($site->user->id,ROLEASSO_TRESORIER) )
  $site->error_forbidden("services");

if ( $_REQUEST["action"] == "create" && $GLOBALS["svalid_call"] )
{
  $efact->create ( $cla->id, $_REQUEST["nom_facture"], $_REQUEST["adresse_facture"], time(), $_REQUEST["titre"]);
}

if ( !$efact->is_valid() )
{
  header("Location: classeur.php?id_classeur=".$cla->id."&view=factures");
  exit();
}

if ( $_REQUEST["action"] == "addline" )
{
  $efact->create_line ( $_REQUEST["prix_unit"], $_REQUEST["quantite"], $_REQUEST["designation"] );
}
elseif ( $_REQUEST["action"] == "delete" )
{
  $efact->delete_line($_REQUEST["num_ligne_efact"]);
}
elseif ( $_REQUEST["action"] == "deletes" )
{
  foreach($_REQUEST["num_ligne_efacts"] as $num)
    $efact->delete_line($num);
}
elseif ( $_REQUEST["action"] == "update" )
{
  $efact->update ( $cla->id, $_REQUEST["nom_facture"], $_REQUEST["adresse_facture"], time(), $_REQUEST["titre"]);
}
elseif ( $_REQUEST["action"] == "saveline" )
{
  $efact->update_line ( $_REQUEST["num_ligne_efact"], $_REQUEST["prix_unit"], $_REQUEST["quantite"], $_REQUEST["designation"] );
}
elseif ( $_REQUEST["action"] == "pdf" )
{
  require_once ($topdir . "include/pdf/facture_pdf.inc.php");

  $facturing_infos = array ('name' => "Association des Etudiants de l'UTBM",
         'addr' => array("6 Boulevard Anatole France",
          "90000 BELFORT"),
         'logo' => "http://ae.utbm.fr/images/Ae-blanc.jpg");

  $factured_infos = array ('name' => utf8_decode($efact->nom_facture),
         'addr' => explode("\n",utf8_decode($efact->adresse_facture)),
         false);

  $date_facturation = date("d/m/Y",$efact->date);

  $titre = $efact->titre;

  $ref = "e-".$efact->id;

  $lines=array();

  $req = new requete($site->db,"SELECT * ".
        "FROM cpta_facture_ligne ".
        "WHERE id_efact='".mysql_real_escape_string($efact->id)."'");

  while ($row = $req->get_row ())
  {
    $lines[] = array('nom' => utf8_decode($row['designation_ligne_efact']),
         'quantite' => intval($row['quantite_ligne_efact']),
         'prix' => $row['prix_unit_ligne_efact'],
         'sous_total' => intval($row['quantite_ligne_efact']) * $row['prix_unit_ligne_efact']);
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

$site->start_page ("services", "Classeur ".$cla->nom." ( ".$asso->nom ." - ". $cpbc->nom.")" );

$cts = new contents("<a href=\"./\">Compta</a> / ".$cpbc->get_html_link()." / ".$cptasso->get_html_link()." / ".$cla->get_html_link());

$cts->add(new tabshead(array(
  array("typ","compta/typeop.php?id_asso=".$asso->id,"Natures(types) d'opérations"),
  array("lbl","compta/libelle.php?id_asso=".$asso->id,"Etiquettes"),
  array("ent","entreprise.php","Entreprises (commun à tous)")),
  "","","subtab"));

$tabsentries = array (
    array( false, "compta/classeur.php?id_classeur=".$cla->id, "Opérations" ),
    array( false, "compta/classeur.php?id_classeur=".$cla->id."&page=new", "Ajouter" ),
    array( true, "compta/classeur.php?id_classeur=".$cla->id."&view=factures", "Factures" ),
    array( false, "compta/classeur.php?id_classeur=".$cla->id."&view=budget", "Budget" ),
    array( false, "compta/classeur.php?id_classeur=".$cla->id."&view=types", "Bilan/nature" ),
    array( false, "compta/classeur.php?id_classeur=".$cla->id."&view=actors", "Bilan/personne" ),
    array( false, "compta/classeur.php?id_classeur=".$cla->id."&view=blcpt", "Bilan comptable" )
    );

$cts->add(new tabshead($tabsentries,true));

$cts->add_title(2,"<a href=\"classeur.php?id_classeur=".$cla->id."&view=factures\">Factures</a> / ".$efact->get_html_link());

if ( $_REQUEST["action"] == "edit" )
{
  $row = $efact->get_line($_REQUEST["num_ligne_efact"]);
  $frm = new form("saveline","efact.php?id_efact=".$efact->id,false,"POST","Editer une ligne");
  $frm->add_hidden("action","saveline");
  $frm->add_hidden("num_ligne_efact",$row["num_ligne_efact"]);
  $frm->add_text_field("designation","Designation",$row["designation_ligne_efact"]);
  $frm->add_price_field("prix_unit","Prix unitaire",$row["prix_unit_ligne_efact"]);
  $frm->add_text_field("quantite","Quantité",$row["quantite_ligne_efact"]);
  $frm->add_submit("saveline","Enregistrer");
  $cts->add($frm,true);
}
else
{

  $cts->add_paragraph("<a href=\"efact.php?action=pdf&amp;id_efact=".$efact->id."\">Fichier PDF de la facture</a>");

  if ( !is_null($efact->id_op) )
  {
    $op = new operation($site->db);
    $op->load_by_id($efact->id_op);
    $cts->add_paragraph("Opération liée: ".$op->get_html_link());
  }
  else
  {
    $cts->add_paragraph("<a href=\"classeur.php?id_classeur=".$cla->id."&amp;page=new&amp;id_efact=".$efact->id."\">Creer l'operation liée</a>");
  }

  $frm = new form("upfact","efact.php?id_efact=".$efact->id,false,"POST","Informations");
  $frm->add_hidden("action","update");
  $frm->add_text_field("titre","Titre de la facture",$efact->titre);
  $frm->add_text_field("nom_facture","Nom de la personne facturée",$efact->nom_facture);
  $frm->add_text_area("adresse_facture","Adresse de la personne facturée",$efact->adresse_facture);
  $frm->add_submit("update","Enregistrer");
  $cts->add($frm,true);

  $req = new requete($site->db,"SELECT num_ligne_efact, designation_ligne_efact, prix_unit_ligne_efact/100 as `sum_unit`, quantite_ligne_efact, prix_unit_ligne_efact*quantite_ligne_efact/100 as `montant` ".
        "FROM cpta_facture_ligne ".
        "WHERE id_efact='".mysql_real_escape_string($efact->id)."'");

  $cts->add(new sqltable(
      "lstlignes",
      "Lignes", $req, "efact.php?id_efact=".$efact->id,
      "num_ligne_efact",
      array(
        "designation_ligne_efact"=>"Designation",
        "sum_unit"=>"Prix unitaire",
        "quantite_ligne_efact"=>"Quantité",
        "montant"=>""
        ),
      array("delete"=>"Supprimer","edit"=>"Editer"),
      array("deletes"=>"Supprimer"),
      array()
      ),true);

  $frm = new form("addline","efact.php?id_efact=".$efact->id,false,"POST","Ajouter une ligne");
  $frm->add_hidden("action","addline");
  $frm->add_text_field("designation","Designation");
  $frm->add_price_field("prix_unit","Prix unitaire");
  $frm->add_text_field("quantite","Quantité");
  $frm->add_submit("addline","Ajouter");
  $cts->add($frm,true);
}

$site->add_contents($cts);

$site->end_page ();

?>
