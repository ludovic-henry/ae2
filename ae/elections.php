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
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/elections.inc.php");

$site = new site();
$elec = new election($site->db,$site->dbrw);

if ( !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("accueil");

if ( isset($_REQUEST["id_election"]))
{
  $elec->load_by_id($_REQUEST["id_election"]);
  if ( $elec->id < 1 )
  {
    $site->error_not_found("accueil");
    exit();
  }
}

if ( $_REQUEST["action"] == "newelec" )
{
  if ( !$_REQUEST["name"] )
    $ErrorElec="Pécisez un nom";
  elseif ( $_REQUEST["fin"] <= $_REQUEST["debut"] )
    $ErrorElec="Dates et heures invalides";
  else
    $elec->new_election($_REQUEST["groupid"],$_REQUEST["debut"],$_REQUEST["fin"],$_REQUEST["name"]);
}

if ( $elec->id > 0 )
{

  if ( $_REQUEST["action"] == "addposte" )
  {
    if ( !$_REQUEST["name"] )
      $ErrorPoste = "Nom manquant";
    else
      $elec->add_poste( $_REQUEST["name"], $_REQUEST["description"]);
  }
  elseif ( $_REQUEST["action"] == "addliste" )
  {
    $user = new utilisateur($site->db);

    $user->load_by_id($_REQUEST["id_utilisateur_head"]);

    if ( !$user->is_valid() && !$user->ae)
      $ErrorListe = "Tête de liste erronée ou non cotisante";
    elseif ( !$_REQUEST["nom"])
      $ErrorListe = "Précisez un nom";
    else
    {

      $rq = $elec->add_liste($user->id,$_REQUEST["nom"]);

      $id_liste = $rq->get_id();

      $sql = new requete($site->db,"SELECT id_poste FROM vt_postes WHERE id_election='".$elec->id."'");
      while ( list($id_poste) = $sql->get_row() )
      {
        if ( $_REQUEST["id_utilisateur_poste".$id_poste] )
        {
          $user->load_by_id($_REQUEST["id_utilisateur_poste".$id_poste]);
          if ( $user->is_valid() & $user->ae)
            $elec->add_candidat($user->id, $id_poste, $id_liste);
        }
      }

    }
  }
  elseif ( $_REQUEST["action"] == "addcandidat" )
  {
    $user = new utilisateur($site->db);

    $user->load_by_id($_REQUEST["id_utilisateur"]);

    if ( $user->is_valid() && $user->ae)
    {
      $id_poste=$_REQUEST["id_poste"];
      $id_liste=null;
      if ( $_REQUEST["id_liste"] > 0 )
        $id_liste=$_REQUEST["id_liste"];
      $elec->add_candidat($user->id, $id_poste, $id_liste);
    }
    else
      $ErrorCandidat = "Utilisateur iconnu ou non cotisant";
  }
  elseif ( $_REQUEST["action"] == "delete" )
  {

    if ( isset($_REQUEST["id_liste"]) )
      $elec->remove_liste($_REQUEST["id_liste"]);
    elseif ( isset($_REQUEST["id_poste"]))
      $elec->remove_poste($_REQUEST["id_poste"]);
    else
    {
      list($id_poste,$id_utilisateur) = explode(",",$_REQUEST["id_candidature"]);

      $elec->remove_candidat($id_utilisateur,$id_poste);
    }
  }

  $site->start_page("accueil","Election");

  $cts = new contents($elec->nom);

  $cts->add_paragraph("Election du ".date("d/m/Y H:s",$elec->debut)." au ".date("d/m/Y H:s",$elec->fin));
  $cts->add_paragraph("<a href=\"../elections.php?id_election=".$elec->id."&page=results\">Resultats</a>");

  $sql = new requete($site->db,"SELECT * FROM vt_postes WHERE id_election='".$elec->id."'");
  while ( $row = $sql->get_row() )
    $postes[$row["id_poste"]] = $row["nom_poste"];

  $listes[0] = "Independant";
  $sql = new requete($site->db,"SELECT * FROM vt_liste_candidat WHERE id_election='".$elec->id."'");
  while ( $row = $sql->get_row() )
    $listes[$row["id_liste"]] = $row["nom_liste"];


  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`," .
    "CONCAT(`vt_candidat`.`id_poste`,',',`utilisateurs`.`id_utilisateur`) as `id_candidature`, " .
    "`vt_postes`.`nom_poste`, " .
    "`vt_liste_candidat`.`nom_liste` " .
    "FROM `vt_candidat` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`vt_candidat`.`id_utilisateur` " .
    "INNER JOIN `vt_postes` ON `vt_postes`.`id_poste`=`vt_candidat`.`id_poste` " .
    "LEFT JOIN `vt_liste_candidat` ON `vt_liste_candidat`.`id_liste`=`vt_candidat`.`id_liste` " .
    "WHERE `vt_postes`.`id_election`='".$elec->id."' " .
    "ORDER BY `vt_candidat`.`id_poste`,`vt_liste_candidat`.`nom_liste`");

  $tbl = new sqltable("lstcand","Candidats",$req,"elections.php?id_election=".$elec->id,"id_candidature",
      array("nom_utilisateur"=>"Candidat","nom_poste"=>"Poste","nom_liste"=>"Liste"),
      array("delete"=>"Supprimer"),array(),
      array());


  $cts->add($tbl,true);

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`," .
    "`vt_liste_candidat`.`id_liste`, " .
    "`vt_liste_candidat`.`nom_liste` " .
    "FROM `vt_liste_candidat` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`vt_liste_candidat`.`id_utilisateur` " .
    "WHERE `vt_liste_candidat`.`id_election`='".$elec->id."' " .
    "ORDER BY `vt_liste_candidat`.`nom_liste`");

  $tbl = new sqltable("lstlst","Listes",$req,"elections.php?id_election=".$elec->id,"id_liste",
      array("nom_liste"=>"Nom","nom_utilisateur"=>"Tête de liste"),
      array("delete"=>"Supprimer"),array(),
      array());
  $cts->add($tbl,true);


  $req = new requete($site->db,"SELECT * FROM vt_postes WHERE id_election='".$elec->id."'");
  $tbl = new sqltable("lstpst","Postes",$req,"elections.php?id_election=".$elec->id,"id_poste",
      array("nom_poste"=>"Nom du poste","description_poste"=>"Description"),
      array("delete"=>"Supprimer"),array(),
      array());

  $cts->add($tbl,true);


  $frm = new form("addposte","elections.php?id_election=".$elec->id,$ErrorPoste!="","POST","Ajouter un poste");
  $frm->add_hidden("action","addposte");
  if ( $ErrorPoste )
    $frm->error($ErrorPoste);
  $frm->add_text_field("name","Nom","",true);
  $frm->add_text_area("description","Description");
  $frm->add_submit("save","Ajouter");
  $cts->add($frm,true);

  $frm = new form("addliste","elections.php?id_election=".$elec->id,$ErrorListe!="","POST","Ajouter une liste");
  $frm->add_hidden("action","addliste");
  if ( $ErrorListe )
    $frm->error($ErrorListe);
  $frm->add_text_field("nom","Nom de la liste");
  $frm->add_entity_smartselect("id_utilisateur_head","Tête de liste",new utilisateur($site->db));
  if( count($postes) )
  {
    foreach ($postes as $id => $nom )
      $frm->add_entity_smartselect("id_utilisateur_poste".$id,"Candidat $nom",new utilisateur($site->db));
  }
  $frm->add_submit("save","Ajouter");
  $cts->add($frm,true);

  if ( count($postes) )
  {
    $frm = new form("addcandidat","elections.php?id_election=".$elec->id,$ErrorCandidat!="","POST","Ajouter un candidat");
    $frm->add_hidden("action","addcandidat");
    if ( $ErrorCandidat )
      $frm->error($ErrorCandidat);
    $frm->add_entity_smartselect("id_utilisateur",
               "Candidat",
               new utilisateur($site->db));
    $frm->add_select_field("id_poste","Poste",$postes);
    $frm->add_select_field("id_liste","Liste",$listes);
    $frm->add_submit("save","Ajouter");
    $cts->add($frm,true);
  }



  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("accueil","Gestion des elections");

$frm = new form("newelec","elections.php",true,"POST","Organiser une élection");
$frm->add_hidden("action","newelec");
if ( $ErrorElec )
  $frm->error($ErrorElec);
$frm->add_text_field("name","Nom","",true);
$frm->add_datetime_field("debut","Date et heure de début",-1,true);
$frm->add_datetime_field("fin","Date et heure de fin",-1,true);
$frm->add_entity_select("groupid","Groupe electeur",$site->db,"group" );
$frm->add_submit("save","Ajouter");
$site->add_contents($frm);


$sql = new requete($site->db,"SELECT * FROM vt_election");
$tbl = new sqltable("lstelecs","Elections",$sql,"elections.php","id_election",
      array("nom_elec"=>"Nom","date_debut"=>"De","date_fin"=>"A","id_groupe"=>"Groupe electeurs"),
      array("admin"=>"Administrer"),array(),
      array("id_groupe"=>$GLOBALS["groupscache"]));

$site->add_contents($tbl);

$site->end_page();

?>
