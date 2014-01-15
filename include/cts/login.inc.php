<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des Etudiants de
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

/**
 * @file
 */

require_once($topdir."include/cts/board.inc.php");

/**
 * Affiche un formulaire de connexion et des liens pour l'inscription.
 *
 * Vous ne devriez pas avoir besoin de ce contents.
 * Utilisez site::allow_only_logged_users
 *
 * @author Julien Etelain
 * @ingroup display_cts
 * @see site::allow_only_logged_users
 */
class loginerror extends board
{

  function loginerror($section = "none")
  {
    global $wwwtopdir;

    $_SESSION['session_redirect'] = $_SERVER["REQUEST_URI"];

    $this->board("Veuillez vous identifier","loginerror");

    $frm = new form("connect2",$wwwtopdir."connect.php",true,"POST","Vous avez déjà un compte");
    $frm->add_select_field(
        "domain",
        "Connexion",
        array(
          "utbm" => "UTBM / Assidu",
          "carteae" => "Carte AE",
          "id" => "ID",
          "autre" => "E-mail",
          "alias" => "Alias"),
        $section=="jobetu"?"autre":"utbm",
        "",
        false,
        true,
        "javascript:switchSelConnection(this);"
      );
    $frm->add_text_field("username","Utilisateur","prenom.nom","",20,true,true,null,false,35);
    $frm->add_password_field("password","Mot de passe","","",20);
    $frm->add_checkbox ( "personnal_computer", "Me connecter automatiquement la prochaine fois", false );
    $frm->add_submit("connectbtn2","Se connecter");
    $this->add($frm,true);

    $cts = new contents("Créer un compte");
    $cts->add_paragraph("Pour acceder à cette page vous devez posséder un compte.<br/>La création d'un compte nécessite que vous possédiez une addresse e-mail pour pouvoir l'activer.<br/> Le fait que vous soyez membre ou non de l'utbm vous donnera plus ou moins de droits d'accès sur le site. Un compte vous permettra au minimum de pouvoir utiliser job étu, e-boutic, et de poster des messages sur les forums publics.");
    $cts->add_paragraph("<a href=\"/newaccount.php\">Créer un compte</a>");
    $this->add($cts,true);
  }
}


?>
