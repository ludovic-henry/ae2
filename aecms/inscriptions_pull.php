<?php
/* Copyright 2009
 * - Jérémie Laval < jeremie dot laval at gmail dot com >
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
 */

require_once('include/site.inc.php');
require_once('include/participation_pull.php');

// Réservé au site du pull
if ($site->asso->id != 110) {
  header ('Location: '.$site->pubUrl);
  exit (0);
}

$Erreur = null;

$site->start_page (CMS_PREFIX."inscriptions_pull", "Inscriptions au Prix Universitaire du Logiciel Libre");
$cts = new contents();

if ( $_REQUEST["action"] == 'addparticipation' ) {
  $part = new participation ();

  if (CheckEmail($_REQUEST['email'], 3) == -1)
    $Erreur = 'L\'email fourni est invalide';

  if (!$Erreur) {
    $req = new $requete($part->db,
                        "SELECT * WHERE `email`='".mysql_real_escape_string($_REQUEST['email'])."'");

    if ($req->lines > 0)
      $Erreur = "Une participation est déjà enregistré avec votre email";
  }

  if (!$Erreur) {
    $part->nom= $_REQUEST['nom'];
    $part->prenom= $_REQUEST['prenom'];
    $part->date_de_naissance= $_REQUEST['date_de_naissance'];
    $part->email= $_REQUEST['email'];
    $part->telephone= $_REQUEST['telephone'];
    $part->adresse_rue= $_REQUEST['adresse_rue'];
    $part->adresse_additional= $_REQUEST['adresse_additional'];
    $part->adresse_ville= $_REQUEST['adresse_ville'];
    $part->adresse_codepostal= $_REQUEST['adresse_codepostal'];
    $part->contribution_nom= $_REQUEST['contribution_nom'];
    $part->contribution_parent= $_REQUEST['contribution_parent'];
    $part->contribution_siteweb= $_REQUEST['contribution_siteweb'];
    $part->contribution_depot= $_REQUEST['contribution_depot'];
    $part->contribution_description= $_REQUEST['contribution_description'];

    $Erreur = $part->add_participation ();
  }

  if (!$Erreur) {
    $cts->add_paragraph ('Votre participation a bien été enregistré, merci');
    $cts->end_page();
    exit(0);
  }
}

$frm = new form('addparticipation', 'inscriptions_pull.php', false, 'POST', 'Formulaire d\'inscription');
$frm->allow_only_one_usage ();
if ($Erreur)
  $frm->error($Erreur);

$frm->add_info ('Informations personelles');
$frm->add_text_field('prenom', 'Prénom', '', true, 50);
$frm->add_text_field('nom', 'Nom', '', true, 50);
$frm->add_date_field('date_de_naissance', 'Date de naissance', -1, true);
$frm->add_text_field('email', 'Email', '', true, 50);
$frm->add_text_field('telephone', 'Téléphone', '', true, 50);

$frm->add_text_field('adresse_rue', 'Adresse', '', true, 50);
$frm->add_text_field('adresse_additional', 'Adresse(bis)', '', false, 50);
$frm->add_text_field('adresse_ville', 'Ville', '', true, 50);
$frm->add_text_field('adresse_codepostal', 'Code postal', '', true, 10);

$frm->add_info ('Informations sur la contribution');
$frm->add_text_field('contribution_nom', 'Titre de la contribution', '', true, 50);
$frm->add_text_field('contribution_parent', 'Projet pour qui a été fait la contribution', '', false, 50);
$frm->add_text_field('contribution_siteweb', 'Site web du projet', '', true, 50);
$frm->add_text_field('contribution_depot', 'Adresse du dépot (SVN, Git, ...) contenant la contribution', '', true, 50);
$frm->add_text_area('contribution_description', 'Description de la contribution (but, fonctionalitées, ...)', '', 40, 10, true);

$cts->add ($frm);

$cts->add_paragraph('Si vous avez des questions sur ce formulaire ou une si vous voulez déposer une candidature particulière qui ne rentrerait pas dans le cadre du formulaire précédent, contactez-nous sur contact <at> etoiles-du-libre <dot> org');

$cts->add_title(2,"Mentions légales");
$cts->add_paragraph ("Les informations recueillies sont nécessaires pour valider votre participation.

Elles font l’objet d’un traitement informatique et sont destinées au secrétariat de l’association uniquement. En application des articles 39 et suivants de la loi du 6 janvier 1978 modifiée, vous bénéficiez d’un droit d’accès et de rectification aux informations qui vous concernent.

Si vous souhaitez exercer ce droit et obtenir communication des informations vous concernant, veuillez vous adresser à : <a href=\"mailto:pull@utbm.fr\">pull@utbm.fr</a>.");

$site->add_contents($cts);
$site->end_page ();
?>
