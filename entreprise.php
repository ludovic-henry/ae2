<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

$topdir = "./";
include($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/entreprise.inc.php");
require_once($topdir. "include/entities/ville.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$site = new site ();

$site->allow_only_logged_users("services");


$assos=$site->user->get_assos(ROLEASSO_MEMBREBUREAU);
if ( !count($assos) && !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("services");

$entreprise = new entreprise($site->db,$site->dbrw);
$ville = new ville($site->db);

if (isset($_REQUEST["id_ent"]))
  $entreprise->load_by_id($_REQUEST["id_ent"]);

if ( $entreprise->is_valid() )
{

  $ville->load_by_id($entreprise->id_ville);

  if ( $_REQUEST["action"] == "addcontact" )
  {
    $contact = new contact_entreprise($site->db,$site->dbrw);

    if ( $_REQUEST["nom"] )
      $contact->add ( $entreprise->id, $_REQUEST["nom"], $_REQUEST["telephone"], $_REQUEST["service"], $_REQUEST["email"], $_REQUEST["fax"] );
  }
  elseif ( $_REQUEST["action"] == "addcomment" )
  {
    if ( $_REQUEST["commentaire"] )
    {
      $contact = new contact_entreprise($site->db);
      $commentaire = new commentaire_entreprise($site->db,$site->dbrw);

      if ( $_REQUEST["id_contact"] )
        $contact->load_by_id($_REQUEST["id_contact"]);

      if ( ($contact->id < 1) || ($entreprise->id != $contact->id_ent) )
        $contact->id = NULL;

      $commentaire->add ( $site->user->id, $entreprise->id, $contact->id, $_REQUEST["commentaire"] );
    }
  }
  elseif ( $_REQUEST["action"] == "joinent" )
  {
    if ($_REQUEST["id_ent_doublon"])
      $entreprise->join($_REQUEST["id_ent_doublon"]);

  }
  elseif ( $_REQUEST["action"] == "addtosecteur" )
  {
    if ($_REQUEST["id_secteur"])
      $entreprise->add_secteur($_REQUEST["id_secteur"]);

  }
  elseif ( $_REQUEST["page"] == "edit" )
  {
    $site->start_page("services",$entreprise->nom);


    $frm = new form("addent","entreprise.php?id_ent=".$entreprise->id,false,"POST","Editer");
    $frm->add_hidden("action","save");
    $frm->add_text_field("nom","Nom",$entreprise->nom,true);
    $frm->add_text_field("rue","Rue",$entreprise->rue);
    $frm->add_entity_smartselect ("id_ville","Ville", $ville);
    $frm->add_text_field("email","Adresse email",$entreprise->email);
    $frm->add_text_field("telephone","Téléphone",$entreprise->telephone);
    $frm->add_text_field("fax","Numéro de fax",$entreprise->fax);
    $frm->add_text_field("siteweb","Site web",$entreprise->siteweb);
    $frm->add_submit("valid","Enregistrer");

    $site->add_contents($frm);
    $site->end_page();
    exit();
  }
  elseif( $_REQUEST["action"] == "save" )
  {
    if ( $_REQUEST["nom"] != "" )
    {
      $entreprise->save($_REQUEST["nom"],$_REQUEST["rue"],$_REQUEST["id_ville"],
        $_REQUEST["telephone"],$_REQUEST["email"],$_REQUEST["fax"], $_REQUEST["siteweb"]);
      $ville->load_by_id($entreprise->id_ville);
    }
  }

  if ( $_REQUEST["action"] == "viewcomment" )
  {
    $commentaire = new commentaire_entreprise($site->db);
    $utl = new utilisateur($site->db);
    $commentaire->load_by_id($_REQUEST["id_com_ent"]);
    $utl->load_by_id($commentaire->id_utilisateur);

    $title = "Commentaire de ".$utl->prenom." ".$utl->nom;
    if ( $commentaire->id_contact )
    {
      $contact = new contact_entreprise($site->db);
      $contact->load_by_id($commentaire->id_contact);
      $title .= " (interlocuteur: ".$contact->nom.")";
    }
    $cmt = new wikicontents($title,$commentaire->commentaire);

  }

  $site->start_page("services",$entreprise->nom);

  if ( $cmt )
    $site->add_contents($cmt);
  $l = strtolower(substr($entreprise->nom,0,1));

  $cts = new contents("<a href=\"entreprise.php\">Entreprises</a> / <a href=\"entreprise.php?letter=$l\">$l</a> / ".$entreprise->get_html_link());

  $tbl = new table("Informations");
  if ( $site->user->is_in_group("gestion_ae") )
    $tbl->set_toolbox(new toolbox(array("entreprise.php?page=edit&id_ent=".$entreprise->id=>"Editer")));
  $tbl->add_row(array("Rue",$entreprise->rue));
  $tbl->add_row(array("Ville",$ville->get_html_link()));
  $tbl->add_row(array("Telephone",$entreprise->telephone));
  $tbl->add_row(array("Fax",$entreprise->fax));
  $tbl->add_row(array("Adresse email",$entreprise->email));
  $tbl->add_row(array("Site web","<a href=\"".$entreprise->siteweb."\">".$entreprise->siteweb."</a>"));
  $cts->add($tbl,true);

  $cts->add_paragraph("Voir aussi : <a href=\"entreprise.php\">Autres entreprises</a>");

  $frm = new form("addtosecteur","entreprise.php?id_ent=".$entreprise->id,false,"POST","Ajouter à un secteur d'activité");
  $frm->add_hidden("action","addtosecteur");
  $frm->add_entity_smartselect ("id_secteur","Secteur", new secteur($site->db) );
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);

  $frm = new form("joinent","entreprise.php?id_ent=".$entreprise->id,false,"POST","Fusionner avec un doublon");
  $frm->add_hidden("action","joinent");
  $frm->add_entity_smartselect ("id_ent_doublon","Entreprise", new entreprise($site->db) );
  $frm->add_submit("valid","Fusionner");
  $cts->add($frm,true);

  $req = new requete($site->db,
    "SELECT `id_contact`, `nom_contact`, `service_contact`, `telephone_contact`, `email_contact`, `fax_contact` " .
    "FROM `contact_entreprise` WHERE `id_ent`='".$entreprise->id."' ORDER BY `nom_contact`");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
      "listcontacts",
      "Contacts au sein de l'entreprise", $req, "entreprise.php?id_ent=".$entreprise->id,
      "id_contact",
      array("nom_contact"=>"Contact","service_contact"=>"Service","service_contact"=>"Telephone","email_contact"=>"Adresse email","fax_contact"=>"Fax"),
      array(), array(), array()
      );
    $cts->add($tbl,true);
  }

  $req = new requete($site->db,
    "SELECT `commentaire_entreprise`.`id_com_ent`, `commentaire_entreprise`.`date_com_ent`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "`contact_entreprise`.`nom_contact` " .
    "FROM `commentaire_entreprise` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`commentaire_entreprise`.`id_utilisateur` " .
    "LEFT JOIN `contact_entreprise` ON `contact_entreprise`.`id_contact`=`commentaire_entreprise`.`id_contact` " .
    "WHERE `commentaire_entreprise`.`id_ent`='".$entreprise->id."' ORDER BY `nom_contact`");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
      "listcomment",
      "Commentaires", $req, "entreprise.php?id_ent=".$entreprise->id,
      "id_com_ent",
      array("nom_utilisateur"=>"Déposé par","date_com_ent"=>"Date","nom_contact"=>"Interlocuteur"),
      array("viewcomment"=>"Voir commentaire"), array(), array()
      );
    $cts->add($tbl,true);
  }






  $req->go_first();
  $contacts=array(0=>"(Aucun)");
  while ( $row = $req->get_row() )
    $contacts[$row['id_contact']] = $row['nom_contact'];


  $frm = new form("addcomment","entreprise.php?id_ent=".$entreprise->id,false,"POST","Ajouter un commentaire");
  $frm->add_hidden("action","addcomment");
  $frm->add_select_field("id_contact","Interlocuteur",$contacts);
  $frm->add_text_area("commentaire","Commentaire","",40,6,true);
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);

  $frm = new form("addcontact","entreprise.php?id_ent=".$entreprise->id,false,"POST","Ajouter contact");
  $frm->add_hidden("action","addcontact");
  $frm->add_text_field("nom","Nom","",true);
  $frm->add_text_field("service","Service","");
  $frm->add_text_field("email","Adresse email","");
  $frm->add_text_field("telephone","Téléphone","");
  $frm->add_text_field("fax","Numéro de fax","");
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);



  $site->add_contents($cts);
  $site->end_page();
  exit();
}

if( $_REQUEST["action"] == "addent"/* && $site->user->is_in_group("gestion_ae")*/)
{
  if ( $_REQUEST["nom"] != "" )
  {
    $entreprise->add($_REQUEST["nom"],$_REQUEST["rue"],$_REQUEST["id_ville"],$_REQUEST["telephone"],
      $_REQUEST["email"],$_REQUEST["fax"], $_REQUEST["siteweb"]);
  }
}


$site->start_page("services","Entreprises");

$cts = new contents("Entreprises");

if ( isset($_REQUEST["id_secteur"]) )
{
  $id_secteur = intval($_REQUEST["id_secteur"]);
  $letter = null;
}
else
{
  $id_secteur = null;
  $letter = strtolower($_REQUEST["letter"]);
  if ( !$letter ) $letter = "a";
}


$req = new requete($site->db,
  "SELECT * " .
  "FROM `secteur` ORDER BY `nom_secteur`");

$tabsentries = array();

while ( $row = $req->get_row() )
  $tabsentries[] = array($row["id_secteur"],"entreprise.php?id_secteur=".$row["id_secteur"], $row["nom_secteur"] );

$cts->add(new tabshead($tabsentries,$id_secteur,"_lg"));

if ( !is_null($id_secteur) )
{
  $req = new requete($site->db,
    "SELECT `id_ent`, `nom_entreprise`, `telephone_entreprise`, `email_entreprise`, `siteweb_entreprise` " .
    "FROM `entreprise_secteur` INNER JOIN `entreprise` USING(`id_ent`) WHERE `id_secteur`='".mysql_real_escape_string($id_secteur)."' ORDER BY `nom_entreprise`");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
      "listent",
      "Liste", $req, "entreprise.php",
      "id_ent",
      array("nom_entreprise"=>"Nom","telephone_entreprise"=>"Telephone","email_entreprise"=>"Adresse email","siteweb_entreprise"=>"Site web"),
      array("view"=>"Voir fiche, commentaires..."), array(), array()
      );
    $cts->add($tbl);
  }
}

$cts->add_paragraph("&nbsp;");

$req = new requete($site->db,
  "SELECT DISTINCT(SUBSTRING(`nom_entreprise`,1,1)) AS `letter` " .
  "FROM `entreprise` ORDER BY `letter`");

$tabsentries = array();

while ( $row = $req->get_row() )
{
  $tabsentries[] = array(strtolower($row[0]),"entreprise.php?letter=".$row[0], strtoupper($row[0]));
}

$tabsentries[] = array("%","entreprise.php?letter=%", "Tout");

$cts->add(new tabshead($tabsentries,$letter,"_lg"));

if ( !is_null($letter) )
{
  $req = new requete($site->db,
    "SELECT `id_ent`, `nom_entreprise`, `telephone_entreprise`, `email_entreprise`, `siteweb_entreprise` " .
    "FROM `entreprise` WHERE `nom_entreprise` LIKE '$letter%' ORDER BY `nom_entreprise`");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
      "listent",
      "Liste", $req, "entreprise.php",
      "id_ent",
      array("nom_entreprise"=>"Nom","telephone_entreprise"=>"Telephone","email_entreprise"=>"Adresse email","siteweb_entreprise"=>"Site web"),
      array("view"=>"Voir fiche, commentaires..."), array(), array()
      );
    $cts->add($tbl);
  }
}


$frm = new form("addent","entreprise.php",false,"POST","Ajouter entreprise");
$frm->add_hidden("action","addent");
$frm->add_text_field("nom","Nom","",true);
$frm->add_text_field("rue","Rue","");
$frm->add_entity_smartselect ("id_ville","Ville", $ville);
$frm->add_text_field("email","Adresse email","");
$frm->add_text_field("telephone","Téléphone","");
$frm->add_text_field("fax","Numéro de fax","");
$frm->add_text_field("siteweb","Site web","");
$frm->add_submit("valid","Ajouter");
$cts->add($frm,true);


$site->add_contents($cts);
$site->end_page();
?>
