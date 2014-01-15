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
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/notefrais.inc.php");


$site = new site();

$site->allow_only_logged_users("matmatronch");

if ( isset($_REQUEST['id_utilisateur']) )
{
	$user = new utilisateur($site->db,$site->dbrw);
	$user->load_by_id($_REQUEST["id_utilisateur"]);

	if ( !$user->is_valid() )
		$site->error_not_found("matmatronch");

	if ( !($user->id==$site->user->id || $site->user->is_in_group("gestion_ae")) )
		$site->error_forbidden("matmatronch","private");
}
else
	$user = &$site->user;

$notefrais = new notefrais($site->db,$site->dbrw);

if ( isset($_REQUEST["id_notefrais"]) )
{
  $notefrais->load_by_id($_REQUEST["id_notefrais"]);
  if ( $notefrais->id_utilisateur != $user->id )
    $notefrais->id = null;
}

if ( $_REQUEST["action"] == "addnote" && $GLOBALS["svalid_call"] )
{
  $lignes=array();
  for($i=0;$i<5;$i++)
  {
    if ( $_REQUEST["montant"][$i] && $_REQUEST["depense"][$i] )
      $lignes[]=array($_REQUEST["depense"][$i],$_REQUEST["montant"][$i]);

  }

  if ( count($lignes) == 0 )
    $Erreur="Veuillez spécifier des dépenses";
  else if ( !$_REQUEST["commentaire"] )
    $Erreur="Veuillez spécifier le motif des dépenses";
  else
  {
    $notefrais->create ( null, $_REQUEST["id_asso"], $user->id, $_REQUEST["commentaire"], $_REQUEST["avance"]  );
    foreach ( $lignes as $l)
      $notefrais->create_line($l[0],$l[1]);

  }

}
elseif ( $_REQUEST["action"] == "delete" && isset($_REQUEST["num_notefrais_ligne"]) )
{
  if ( $notefrais->is_valid() && !$notefrais->valide )
    $notefrais->delete_line($_REQUEST["num_notefrais_ligne"]);
}
elseif ( $_REQUEST["action"] == "delete" && isset($_REQUEST["id_notefrais"]) )
{
  if ( $notefrais->is_valid() && !$notefrais->valide )
    $notefrais->delete();
}
elseif ( $_REQUEST["action"] == "addline" && $GLOBALS["svalid_call"] )
{
  if ( $notefrais->is_valid() && !$notefrais->valide && $_REQUEST["montant"] && $_REQUEST["depense"] )
      $notefrais->create_line($_REQUEST["depense"],$_REQUEST["montant"]);
}


$site->start_page("matmatronch", $user->prenom . " " . $user->nom );
$cts = new contents( $user->prenom . " " . $user->nom );
$cts->add(new tabshead($user->get_tabs($site->user),"notefrais"));


if ( $notefrais->is_valid() )
{
  $can_edit = !$notefrais->valide;

  $asso = new asso($site->db);
  $asso->load_by_id($notefrais->id_asso);

  if ( !$notefrais->valide )
  {
    $cts->add_paragraph("Cette note de frais n'a pas encore été validée par le trésorier.");
    $cts->add_paragraph("Si vous ne l'avez pas encore fait, vous devez <a href=\"notefrais.php?id_utilisateur=".$user->id."&amp;id_notefrais=".$notefrais->id."&amp;action=print\">l'imprimer</a>, la signer et la faire parvenir au trésorier avec tous les justificatifs.");
    $cts->add_paragraph("<a href=\"notefrais.php?id_utilisateur=".$user->id."&amp;id_notefrais=".$notefrais->id."&amp;action=print\">Imprimer</a>");
  }
  else
  {
    $cts->add_paragraph("Cette note de frais a été validée par le trésorier. Si vous n'avez pas encore été remboursé, cela ne devrai pas tarder.");
    $cts->add_paragraph("<a href=\"notefrais.php?id_utilisateur=".$user->id."&amp;id_notefrais=".$notefrais->id."&amp;action=print\">Re-Imprimer</a>");
  }

  $cts->add_paragraph("<a href=\"notefrais.php?id_utilisateur=".$user->id."\">Autres notes de frais</a>");

  $cts->add_title(2,"Note de frais n°".$notefrais->id);

  if ( $asso->id_parent )
    $cts->add_paragraph("Activité : ".$asso->nom);
  else
    $cts->add_paragraph("Association : ".$asso->nom);

  if ( $user->sexe == 1 )
    $cts->add_paragraph("M ".$user->prenom . " " . $user->nom);
  else
    $cts->add_paragraph("Mme ".$user->prenom . " " . $user->nom);

  $cts->add_paragraph("Date : ".date("d/m/Y",$notefrais->date));

  $cts->add_paragraph("Commentaire : ".$notefrais->commentaire);

  $req = new requete($site->db,"SELECT designation_ligne_notefrais, prix_ligne_notefrais/100 AS montant,num_notefrais_ligne FROM cpta_notefrais_ligne WHERE id_notefrais='".$notefrais->id."'");

  $tbl = new sqltable(
    "listdep",
    "", $req, "notefrais.php?id_utilisateur=".$user->id."&id_notefrais=".$notefrais->id,
    "num_notefrais_ligne",
    array("designation_ligne_notefrais"=>"Description","montant"=>"Montant"),
    $can_edit?array("delete"=>"Supprimer"):array(), array(), array()
    );
  $cts->add($tbl);

  $cts->add_paragraph("Total : ".($notefrais->total/100)." &euro;");

  $cts->add_paragraph("Avance : ".($notefrais->avance/100)." &euro;");

  $cts->add_paragraph("Solde dû : ".($notefrais->total_payer/100)." &euro;");

  if ( $can_edit )
  {
    $frm = new form("addnote","notefrais.php?id_utilisateur=".$user->id."&id_notefrais=".$notefrais->id,false,"POST","Ajouter une dépense");
    $frm->allow_only_one_usage();
    $frm->add_hidden("action","addline");
    $frm->add_text_field("depense","Libéllé");
    $frm->add_price_field("montant","Montant");
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);

  $cts->add_paragraph("N'oubliez pas de faire parvenir la note imprimée et signée avec les justificatifs au trésorier pour être remboursé.");
  }


}
else
{
  $req = new requete($site->db,"SELECT id_notefrais,date_notefrais,commentaire_notefrais,valide_notefrais FROM cpta_notefrais WHERE id_utilisateur='".$user->id."' ORDER BY id_notefrais");

  $tbl = new sqltable(
    "listnf",
    "Notes de frais", $req, "notefrais.php?id_utilisateur=".$user->id,
    "id_notefrais",
    array(
      "id_notefrais"=>"N°",
      "date_notefrais"=>"Date",
      "commentaire_notefrais"=>"Description",
      "valide_notefrais"=>"Validée"),
    array("delete"=>"Supprimer","info"=>"Voir","print"=>"Imprimer"), array(), array("valide_notefrais"=>array("Non","Oui"))
    );
  $cts->add($tbl,true);

  $frm = new form("addnote","notefrais.php?id_utilisateur=".$user->id,false,"POST","Saisir une nouvelle note de frais");
  $frm->allow_only_one_usage();
  $frm->add_hidden("action","addnote");
  $frm->add_entity_select ( "id_asso", "Association/Activité", $site->db, "asso");
  $frm->add_text_field("commentaire","Motif des dépenses","",true);
  $frm->add_price_field ( "avance", "Avance qui vous a déjà été versée");
  for($i=0;$i<5;$i++)
  {
    $sfrm = new form(null,null,null,null,"Dépense $i");
    $sfrm->add_text_field("depense[$i]","Libéllé");
    $sfrm->add_price_field("montant[$i]","Montant");
    $frm->add($sfrm, false, false, false, false, true);
  }
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);
  $cts->add_paragraph("N'oubliez pas de faire parvenir la note imprimée et signée avec les justificatifs au trésorier pour être remboursé.");

}

$site->add_contents($cts);

$site->end_page();


?>
