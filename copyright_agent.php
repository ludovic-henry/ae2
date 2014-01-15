<?php
/* Copyright 2009
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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

require_once($topdir. "include/site.inc.php");

$site = new site ();

if(   isset($_POST['sendcopyrightreporteuh'])
   && isset($_POST['di'])
   && isset($_POST['na'])
   && isset($_POST['ie'])
   && CheckEmail($_POST['email'],3))
{
  $body = "Bonjour,
Une violation de propriété intellectuelle a été signalée :

Nom         : ".$_POST['nom']."
Prenom      : ".$_POST['prenom']."
Email       : ".$_POST['email']."
Tel         : ".$_POST['tel']."
Adresse     :
".$_POST['adresse']."

Description :
".$_POST['desc']."

Url : ".$_POST['url']."

Il a déclaré :
  - disposer des droits de propriété intellectuelle
    ou être dûment autorisé à agir au nom et pour le
    compte du titulaire des droits.
  - ne pas avoir autorisé l’utilisation contestée et/ou
    que cette utilisation n'a pas été autorisée par le
    titulaire des droits, son agent, et/ou plus
    généralement les lois et règlements en vigueur.
  - que les informations communiquées dans le cadre de
    cette déclaration sont exactes.

Veuillez prendre les mesures nécessaire afin de corriger ceci :
  - pour le forum : suppression du message incriminé
  - pour le sas   : suppression du média concerné

Le site AE
";
  $ret = mail("ae.com@utbm.fr",
              utf8_decode("Violation de propriété intellectuelle"),
              utf8_decode($body),
              "From: \"AE UTBM\" <ae@utbm.fr>\nReply-To: ".$_POST['email']."\nCC: ae.info@utbm.fr");
  $cts = new contents("Confirmation");
  $cts->add_paragraph("Le rapport a été envoyé.");
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("accueil","Propriété intellectuelle");
$cts = new contents("Propriété intellectuelle");
$cts->add_paragraph("<b>Formulaire de déclaration de violation des droits de propriété intellectuelle</b>");
$cts->add_paragraph("Merci de bien vouloir remplir ce formulaire, tous les champs sont obligatoires !");
$frm = new form("sendcopyrightreport","copyright_agent.php",true,"POST");
$frm->add_info("<h3>1 - Information de contact</h3>");
$frm->add_text_field("nom","Votre nom",'',true);
$frm->add_text_field("prenom","Votre prénom",'',true);
$frm->add_text_field("email","Votre adresse e-mail",'',true);
$frm->add_text_field("tel","Votre numéro de téléphone",'',true);
$frm->add_text_area("adresse","Votre adresse postale","",40,3,true);
$frm->add_info("<h3>2 - Décrivez l'atteinte à la propriété intellectuelle.</h3>","",40,3,true);
$frm->add_text_area("desc","Description","",40,3,true);
$frm->add_info("<h3>3 - URL exacte de la violation des droits de propriété intellectuelle alléguée</h3>");
$frm->add_text_area("url","URL","",40,3,true);
$frm->add_info("<h3>4. Déclaration de bonne foi</h3>");
$frm->add_info("Je certifie sur l’honneur et en toute bonne foi :");
$frm->add_checkbox("di","Disposer des droits de propriété intellectuelle ou être dûment autorisé à agir au nom et pour le compte du titulaire des droits.");
$frm->add_checkbox("na","Ne pas avoir autorisé l’utilisation contestée et/ou que cette utilisation n'a pas été autorisée par le titulaire des droits, son agent, et/ou plus généralement les lois et règlements en vigueur.");
$frm->add_checkbox("ie","Que les informations communiquées dans le cadre de cette déclaration sont exactes.");
$frm->add_submit("sendcopyrightreporteuh","Envoyer");
$cts->add($frm);
$site->add_contents($cts);
$site->end_page();

?>
