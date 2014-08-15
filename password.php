<?php
/* Copyright 2005
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
require_once($topdir. "include/site.inc.php");

$site = new site ();

$site->start_page("accueil","Réinitialisation de votre mot de passe");
$cts = new contents("Réinitialisation de votre mot de passe");

if ( isset($_REQUEST["email"]) )
{
  $user = new utilisateur($site->db,$site->dbrw);

  $user->load_by_email($_REQUEST["email"]);

  if ( $user->is_valid() )
  {
    $pass = genere_pass(12);

    $user->invalidate();
    $user->change_password($pass);

    $user->send_autopassword_email($_REQUEST["email"], $pass);
/*
  $body = "Bonjour,
Votre mot de passe sur le site de l'Association des Étudiants de
l'UTBM a été réinitialisé.

C'est maintenant : " . $pass . "

Pour valider ce nouveau mot de passe, veuillez vous rendre à l'adresse
http://ae.utbm.fr/ae2/confirm.php?id=" . $user->id . "&hash=" . $user->hash . "

Vous pouvez changer votre mot de passe en vous connectant sur le site
(http://ae.utbm.fr) puis en sélectionnant \"Information
personnelles\".

L'équipe info AE";

    $ret = mail($_REQUEST["email"], "[Site AE] Réinitialisation du mot de passe", $body);
    */

    $cts->add_paragraph("Un nouveau mot de passe vous a été envoyé par " .
        "courrier électronique. Dans ce courrier électronique, vous " .
        "trouverez une adresse Web permettant de valider ce nouveau " .
        "mot de passe.");
    $form = 0;
  }
  else
  {
    $cts->add_paragraph("<b>Adresse e-mail inconnue</b>, veuillez la corriger.");
    $form = 1;
  }
}
else
{
  $cts->add_paragraph("Ce formulaire vous permet de réinitialiser votre mot de " .
      "passe en vous en renvoyant un nouveau par e-mail.");
  $form = 1;
}

if ( $form )
{
  $frm = new form("lostpassword","password.php",true);
  $frm->add_text_field("email","Adresse e-mail","",true);
  $frm->add_submit("submit","Envoyer");
  $cts->add($frm);
}

$list = new itemlist("Voir aussi");
$list->add("<a href=\"article.php?name=docs:inscription\">Documentation : Inscription : Questions et problèmes fréquents</a>");
$list->add("<a href=\"article.php?name=docs:profil\">Documentation : Profil personnel : Questions et problèmes fréquents</a>");
$list->add("<a href=\"article.php?name=docs:index\">Documentation</a>");
$cts->add($list,true);

$site->add_contents($cts);
$site->end_page();
exit();

?>
