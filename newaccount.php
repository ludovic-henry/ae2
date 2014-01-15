<?php

/* Copyright 2007
 * - Julien Etelain <julien POINT etelain CHEZ gmail POINT com>
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "./";
require_once($topdir. "include/site.inc.php");

$site = new site ();

$user = new utilisateur($site->db,$site->dbrw);


if ( isset($_REQUEST["mode"]) )
{
  $mode = $_REQUEST["mode"];

  if ( !$_REQUEST["agree"] )
    $Erreur = "Veuillez accepter le réglement informatique.";
  elseif ( !$_REQUEST["nom"] )
    $Erreur = "Veuillez préciser votre nom";
  elseif ( !$_REQUEST["prenom"] )
    $Erreur = "Veuillez préciser votre prenom";
  elseif ( !$_REQUEST["email"] )
    $Erreur = "Veuillez ppréciser votre adresse e-mail";
  elseif ( !ereg("^([A-Za-z0-9\._-]+)@([A-Za-z0-9_-]+)\.([A-Za-z0-9\._-]*)$", $_REQUEST["email"] ) )
    $Erreur="Adresse e-mail non valide";
  elseif ( $user->load_by_email($_REQUEST["email"]) )
    $Erreur = "Votre adresse e-mail est déjà utilisée pour un autre compte";
  elseif ( $mode == "utbm" && !ereg("^([a-zA-Z0-9\.\-]+)@(utbm\.fr|assidu-utbm\.fr)$",$_REQUEST["email"]) )
    $Erreur = "Adresse e-mail non utbm";
  else
  {
    if ( $_REQUEST["action"] == "create" )
    {
      $user->id=null;

      if ( !$GLOBALS["svalid_call"] )
        $Erreur = "Votre demande à déjà été traitée. Si le prolème persiste contactez nous.";
      elseif ( !$_REQUEST["password"] )
        $Erreur = "Veuillez préciser un mot de passe";
      elseif ( $_REQUEST["password"] != $_REQUEST["password_repeat"] )
        $Erreur = "Les deux saisies du mot de passe ne dont pas identiques";
      else
      {
        /** @todo créer le compte avec les informations élémentaires */
        if ( $mode == "utbm" )
          $ret = $user->create_utbm_user ( $_REQUEST["nom"], $_REQUEST["prenom"], $_REQUEST["email"], $_REQUEST["password"], $_REQUEST["droitimage"], $_REQUEST["naissance"], $_REQUEST["sexe"], $_REQUEST["role"], $_REQUEST["dep"] );
        elseif ( $mode == "etu" )
          $ret = $user->create_etudiant_user ( $_REQUEST["nom"], $_REQUEST["prenom"], $_REQUEST["email"], $_REQUEST["password"], $_REQUEST["droitimage"], $_REQUEST["naissance"], $_REQUEST["sexe"], $_REQUEST["ecole"] );
        else
          $ret = $user->create_user ( $_REQUEST["nom"], $_REQUEST["prenom"], $_REQUEST["email"], $_REQUEST["password"], $_REQUEST["droitimage"], $_REQUEST["naissance"], $_REQUEST["sexe"] );

        $site->start_page("services","Inscription");
        $cts = new contents("Inscription : Etape 3/3");
        if($ret) {
          $cts->add_paragraph("Votre compte vient d'être crée, il faut maintenant l'activer. Pour cela vous devez cliquer le lien qui vous a été envoyé par email à l'adresse ".htmlentities($_REQUEST["email"]).". Votre compte ne sera utilisable que dès lors que cette opération sera terminée.");
          $cts->add_paragraph("Votre compte sera soumis à vérification (modération), vous ne pourrez accéder à toutes les fonctions dès que votre compte sera vérifié.");
        } else {
          $cts->add_paragraph("Une erreur s'est produite, veuillez contacter l'équipe informatique de l'association des étudiants à l'adresse suivante : ae.info @ utbm.fr (sans les espaces).");
        }
        $site->add_contents($cts);
        $site->end_page();
        exit();
      }
    }


    $site->start_page("services","Inscription");
    $cts = new contents("Inscription : Etape 2/3");

    $frm = new form("createaccount","newaccount.php?mode=$mode",true);
    $frm->allow_only_one_usage();
    $frm->add_hidden("action","create");

    if ( isset($Erreur) )
      $frm->error($Erreur);

    $frm->add_select_field("sexe","Je suis",array(1=>"un homme",2=>"une femme"));
    $frm->add_text_field("nom","Votre nom",$_REQUEST["nom"],true);
    $frm->add_text_field("prenom","Votre prenom",$_REQUEST["prenom"],true);

    if ( $mode == "utbm" )
    {
      $frm->add_text_field("email","Votre adresse email utbm",$_REQUEST["email"],true);
      $frm->add_select_field("role","Votre fonction",$GLOBALS["utbm_roles"],$_REQUEST["role"]);
      $frm->add_select_field("dep","Votre departement",$GLOBALS["utbm_departements"]);
    }
    elseif ( $mode == "etu" )
    {
      $frm->add_text_field("email","Votre adresse email (pas utbm.fr)",$_REQUEST["email"],true);
      $frm->add_select_field("ecole","Votre ecole",array("utt","utc","iut"),$_REQUEST["role"]);
    }
    else
      $frm->add_text_field("email","Votre adresse email (pas utbm.fr)",$_REQUEST["email"],true);

    $frm->add_password_field("password","Mot de passe","",true);
    $frm->add_password_field("password_repeat","Mot de passe (pour vérification)","",true);

    $frm->add_date_field("naissance","Date de naissance");

    $frm->add_checkbox("droitimage","J'accorde mon droit à l'image");


    $frm->add_checkbox("agree","J'ai lu et j'accepte le <a href=\"article.php?name=legals:rinfo\">réglement informatique</a>",true);
    $frm->add_submit("next","Etape suivante");

    $cts->add($frm);

    $cts->add_paragraph("Les informations recueillies font l'objet d'un traitement informatique. Conformément à la loi « informatique et libertés » du 6 janvier 1978, vous bénéficiez d'un droit d'accès et de rectification aux informations qui vous concernent. Si vous souhaitez exercer ce droit et obtenir communication des informations vous concernant, veuillez vous adresser par courrier éléctronique à ae arroba utbm point fr ou par courrier postal à ae utbm, 6 Boulevard Anatole France, 90000 Belfort.");

    $list = new itemlist("Voir aussi");
    $list->add("<a href=\"article.php?name=docs:inscription\">Documentation : Inscription</a>");
    $list->add("<a href=\"article.php?name=docs:inscription\">Documentation : Inscription : Questions et problèmes fréquents</a>");
    $list->add("<a href=\"article.php?name=docs:index\">Documentation</a>");
    $cts->add($list,true);


    $site->add_contents($cts);
    $site->end_page();
    exit();
  }
}

$only_mode=null;
if ( isset($_REQUEST["only_mode"]) )
{
  $only_mode = $_REQUEST["only_mode"];
  if ( $only_mode == "futurutbm" )
    $only_mode = "nonutbm";
  $mode = $only_mode;
}

$site->start_page("services","Inscription");

$cts = new contents("Inscription : Etape 1/3");

$cts->add_paragraph("Vous êtes sur le point d'ouvrir un compte sur le site de l'association des etudiants de l'utbm.");

if ( is_null($only_mode) || $only_mode == "utbm" )
{
  $ctsutbm = new contents("Etudiant à l'utbm ou membre du personnel de l'utbm");
  $ctsutbm->add_paragraph("Pour pouvoir procéder à votre inscription vous devez posséder une adresse e-mail personnelle utbm.fr et y avoir accès. Votre inscription sera soumise à modération.");
  $frm = new form("utbm","newaccount.php?mode=utbm",true);
  if ( isset($Erreur) && $mode == "utbm" )
    $frm->error($Erreur);
  $frm->add_text_field("nom","Votre nom","",true);
  $frm->add_text_field("prenom","Votre prenom","",true);
  $frm->add_text_field("email","Votre adresse email utbm","@utbm.fr",true);
  $frm->add_select_field("role","Votre fonction",$GLOBALS["utbm_roles"]);
  $frm->add_checkbox("agree","J'ai lu et j'accepte le <a href=\"article.php?name=legals:rinfo\">réglement informatique</a>",false);
  $frm->add_submit("next","Etape suivante");
  $ctsutbm->add($frm);
  $cts->add($ctsutbm,true,true, "secutbm", false, true, $mode == "utbm", false);
}

if ( is_null($only_mode) || $only_mode == "etu" )
{
  $ctsetu = new contents("Etudiant dans l'aire urbaine, ou dans une université de technologie");
  $ctsetu->add_paragraph("Pour pouvoir procéder à votre inscription vous devez posséder une adresse e-mail personnelle valide, votre inscription sera soumise à modération, vous pourrez cependant accèder à quelques services en attendant.");
  $frm = new form("etu","newaccount.php?mode=etu",true);
  if ( isset($Erreur) && $mode == "etu" )
    $frm->error($Erreur);
  $frm->add_text_field("nom","Votre nom","",true);
  $frm->add_text_field("prenom","Votre prenom","",true);
  $frm->add_text_field("email","Votre adresse email (pas utbm.fr)","",true);
  $frm->add_select_field("ecole","Votre ecole",array("utt","utc","iut"));
  $frm->add_checkbox("agree","J'ai lu et j'accepte le <a href=\"article.php?name=legals:rinfo\">réglement informatique</a>",false);
  $frm->add_submit("next","Etape suivante");
  $ctsetu->add($frm);
  $cts->add($ctsetu,true,true, "secetu", false, true, $mode == "etu", false);
}

if ( is_null($only_mode) || $only_mode == "nonutbm" )
{
  $ctsnonutbm = new contents("Personnes tierces ou futur étudiant");
  $ctsnonutbm->add_paragraph("Pour pouvoir procéder à votre inscription vous devez posséder une adresse e-mail personnelle valide, votre inscription sera soumise à modération. Vous pourrez accéder au forum, à l'e-boutic et à jobétu.");
  $frm = new form("nonutbm","newaccount.php?mode=nonutbm",true);
  if ( isset($Erreur) && $mode == "nonutbm" )
    $frm->error($Erreur);
  $frm->add_text_field("nom","Votre nom","",true);
  $frm->add_text_field("prenom","Votre prenom","",true);
  $frm->add_text_field("email","Votre adresse email (pas utbm.fr)","",true);
  $frm->add_checkbox("agree","J'ai lu et j'accepte le <a href=\"article.php?name=legals:rinfo\">réglement informatique</a>",false);
  $frm->add_submit("next","Etape suivante");
  $ctsnonutbm->add($frm);
  $cts->add($ctsnonutbm,true,true, "secnonutbm", false, true, $mode == "nonutbm", false);
}

$list = new itemlist("Voir aussi");
$list->add("<a href=\"article.php?name=docs:inscription\">Documentation : Inscription</a>");
$list->add("<a href=\"article.php?name=docs:inscription\">Documentation : Inscription : Questions et problèmes fréquents</a>");
$list->add("<a href=\"article.php?name=docs:index\">Documentation</a>");
$cts->add($list,true);


$site->add_contents($cts);

$site->end_page();

?>
