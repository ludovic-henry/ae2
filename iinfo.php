<?php


/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
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
include($topdir. "include/entities/page.inc.php");

$site = new site ();
$page = new page ($site->db);
$page->load_by_pagename("info:imode");
$site->start_page("services","Informations sur la version mobile du site");

$cts = new contents();

$site->add_contents($page->get_contents());

if ( $site->user->id )
{
  $cts = new contents("Recevoir un lien d'accés direct par e-mail");

  $cts->add_paragraph("Nous pouvons vous envoyer un lien personnel, qui vous permettra d'acceder directement, connecté avec votre compte, au site de l'AE pour mobile. Envoyez ce lien sur l'adresse email de votre portable, ensuite ajoutez le à vos favoris pour accéder trés rapidement aux services.");

  if ( isset($_POST["emailadress"]) && CheckEmail($_POST["emailadress"],3) )
  {
    $cts->add_paragraph("Email envoyé à ".htmlentities($_POST["emailadress"]));

    $sid = $site->create_session(true);

    $url = "http://ae.utbm.fr/i/?sid=$sid";

  $body = "Bonjour,
Pour accéder au site mobile de l'AE, veuillez vous rendre à l'adresse :
$url

L'équipe info AE";

		mail($_POST["emailadress"], "[Site AE] AE pour mobile", utf8_decode($body),
                            "From: \"AE UTBM\" <ae@utbm.fr>\nReply-To: ae@utbm.fr");

  }
  else
  {
    $frm = new form("sendmail","iinfo.php",false);
    $frm->add_text_field("emailadress","Adresse email","numtel@imode.fr");
    $frm->add_submit("sendmail","Envoyer e-mail");
    $cts->add($frm);
  }
  $site->add_contents($cts);
}

$site->end_page();
