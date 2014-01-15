<?php
/** @file
 *
 * @brief Page d'inscription au pré-parrainage pour les nouveaux
 *
 */

/* Copyright 2007
 * - Julien Ehrhart <julien POINT ehrhart CHEZ utbm POINT fr>
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";
include($topdir. "include/site.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/pays.inc.php");
require_once($topdir . "include/cts/special.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");

$site = new site();
$site->start_page("services", "Pré-parrainage");


$d = date("d");
$m = date("m");
if ( $m <= 2 )
  $sem = "P".sprintf("%02d",(date("y")));
elseif ( $m > 6 && $m < 9)
  $sem = "A".sprintf("%02d",(date("y")));
else
{
  $cts = new contents("Pré-parrainage",
                      "Pas de campagne de pré-parrainage pour le moment");
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

// seul les gens ayant un compte peuvent venir ici
if ( !$site->user->is_valid() )
{
  $cts = new contents("Pré-parrainage",
                      "Pour accéder à cette page veuillez vous <a href=\"../index.php\">connecter</a> ".
                      "ou <a href=\"../newaccount.php?only_mode=futurutbm\">Creer un compte</a>.");
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

// seul les gens de l'equipe integ peuvent venir ici, membres actifs et superieur
elseif ( $site->user->is_asso_role(14,1) )
{
  $cts = new contents("Pré-parrainage",
                      "");

  $sql = new requete($site->db, "SELECT utilisateurs.nom_utl,
						utilisateurs.prenom_utl,
						utilisateurs.id_utilisateur,
						utilisateurs.email_utl AS email_utilisateur,
						pre_parrainage.tc,
						pre_parrainage.branche,
					CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl) AS `nom_utilisateur`
					FROM pre_parrainage
					LEFT JOIN utilisateurs
					ON pre_parrainage.id_utilisateur = utilisateurs.id_utilisateur
					WHERE `semestre` = '".$sem."'");

  $tbl = new sqltable("bijoux",
				"Liste des bijoux inscrits à la campagne de pré-parrainage",
				$sql,
				"index.php",
				"utilisateurs.id_utilisateur",
				array("=num" => "N°",
					"nom_utilisateur" => "Utilisateur",
					"tc" => "TC",
					"branche" => "Branche",
					"email_utilisateur" => "Mail"
					),
				array(),
				array(),
				array("tc" => array(0 => "Non", 1 => "Oui"))
				);

  $cts->add($tbl,true);
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

// les anciens ne peuvent pas encore accéder à cette partie, il faudra mettre en place
// la page "choisi" ton fillot
// $site->user->etudiant || $site->user->ancien_etudiant ne peuvent pas s'appliquer ici et c'est
// chiant.
elseif ( $site->user->utbm || $site->user->ae )
{
  $cts = new contents("Pré-parrainage",
                      "Le module de pré-parrainage est en cours de dévelopement, merci de votre compréhension");
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$req = new requete($site->db, "SELECT `id_utilisateur` FROM `pre_parrainage` WHERE `id_utilisateur` = '".$site->user->id."' AND `semestre` = '".$sem."'LIMIT 1");
if($req->lines==1)
{
  $cts = new contents("Pré-parrainage",
                      "Vous êtes déjà inscrit à la campagne en cours");
  $site->add_contents($cts);
  $site->end_page();
  exit();
}
// partie pour les bijoux.
else
{
  $cts = new contents("Pré-parrainage",
                      "Sur cette page, vous allez pouvoir vous inscrire pour le pré-parrainage");
  $cts->add_title(2,"Informations");
  $cts->add_paragraph("Le pré-parrainage permet aux nouveaux étudiants d'être accompagnés par un étudiant de ".
                      "de l'UTBM dans ses démarches administratives, la découverte de Belfort, ...");
  $site->add_contents($cts);
  if(isset($_POST["etape"]))
  {
    if($_POST["etape"] == 3)
    {
      $_cts = new contents("Inscription : Etape 3/3");
      $_cts->add_paragraph("Ton inscription au pré-parrainage est effective. Un e-mail te sera envoyé au cours du mois d'août afin de t'indiquer les coordonnées de ton pré-parrain. N'hésite pas à le contacter avant d'arriver avant de venir à Belfort pour que celui-ci puisse être présent à ton arrivé pour t'accueillir et te guider.");
      if($_POST['departement'] == "tc")
        $_req = new insert($site->dbrw,"pre_parrainage", array('semestre'=>$sem,'id_utilisateur' => $site->user->id,'tc'=>1,'branche'=>$_POST["voeux"]));
      else
        $_req = new insert($site->dbrw,"pre_parrainage", array('semestre'=>$sem,'id_utilisateur' => $site->user->id,'tc'=>0,'branche'=>$_POST["branche"]));
      $site->add_contents($_cts);
      $site->end_page();
      exit();
    }
    if($_POST["etape"] == 2)
    {
      $ville = new ville($site->db);
      $pays = new pays($site->db);
      if(empty($_POST['id_ville']) && empty($_POST['id_pays']))
      {
        $cts = new contents("Erreur",
                            "Veuillez entrer une ville ou un pays.");
        $site->add_contents($cts);
      }
      else
      {
        $erreur=false;
        $site->user->addresse = $_POST['addresse'];
        if ( $_POST['id_ville'] )
        {
          $ville->load_by_id($_POST['id_ville']);
          $site->user->id_ville = $ville->id;
          $site->user->id_pays = $ville->id_pays;
        }
        elseif($pays->load_by_id($_POST['id_pays']))
        {
          $site->user->id_ville = null;
          $site->user->id_pays = $pays->id;
        }
        else
        {
          $cts = new contents("Erreur",
            "Une erreur s'est produite, veuillez recommencer.");
          $site->add_contents($cts);
          $erreur=true;
        }
        if(!$erreur)
        {
          $site->user->tel_maison = telephone_userinput($_POST['tel_maison']);
          $site->user->tel_portable = telephone_userinput($_POST['tel_portable']);
          $site->user->date_maj = time();
          if ($site->user->saveinfos())
          {
            $_cts = new contents("Inscription : Etape 2/3");
            $_cts->add_paragraph("Information relative à votre cursus.");
            $frm = new form("infocursus","index.php",false,"POST","Cursus envisagé");
            $frm->add_hidden("etape","3");
            $frm->add_info("À votre arrivée vous serez :");
              $TC = new form("departement",null,null,null,"en tronc commun (TC)");
            $voeux=array();
            foreach($GLOBALS["utbm_departements"] AS $key => $value)
            {
              if($key!="tc" && $key!="na" && $key!="hum")
                $voeux[$key]=$value;
            }
              $TC->add_select_field("voeux","Branche envisagée",$voeux,$site->user->departement);
            $frm->add($TC,false,true,1,"tc",false,true,true);
              $branche = new form("departement",null,null,null,"en branche :");
              $branche->add_select_field("branche","Quelle branche ?",$voeux,$site->user->departement);
            $frm->add($branche,false,true,0,"branche",false,true);
            $frm->add_submit("save","Terminer");
            $_cts->add($frm,true);
            $site->add_contents($_cts);
            $site->end_page();
            exit();
          }
        }
        else
        {
          $cts = new contents("Erreur",
                              "Une erreur s'est produite, veuillez recommencer.");
          $site->add_contents($cts);
        }
      }
    }
  }
}

$ville = new ville($site->db);
$pays = new pays($site->db);
$ville->load_by_id($site->user->id_ville);
$pays->load_by_id($site->user->id_pays);

$cts = new contents("Inscription : Etape 1/3");
$cts->add_paragraph("Vous êtes sur le point de vous inscrire au système de pré-parrainage.");
$frm = new form("verifinfo","index.php",true,"POST","Vérifications des informations personnelles");
$frm->add_hidden("etape","2");
$frm->add_info("Si les informations suivantes ne sont pas correctes veuillez mettre à jour votre ".
               "<a href=\"".$topdir."user.php?page=edit\">compte</a>.");
$frm->add_text_field("nom","Nom",$site->user->nom,true,false,false,false);
$frm->add_text_field("prenom","Prenom",$site->user->prenom,true,false,false,false);
$frm->add_text_field("email","Votre adresse email",$site->user->email,true,false,false,false);
$frm->add_text_field("addresse","Adresse",$site->user->addresse,true);
$frm->add_entity_smartselect ("id_ville","Ville (France)", $ville,true);
$frm->add_entity_smartselect ("id_pays","ou pays", $pays,true);
$frm->add_text_field("tel_maison","Telephone (fixe)",$site->user->tel_maison);
$frm->add_text_field("tel_portable","Telephone (portable)",$site->user->tel_portable);
$frm->add_submit("save","Suivant");
$cts->add($frm,true);

$site->add_contents($cts);
$site->end_page();

?>
