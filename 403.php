<?php
/* Copyright 2005,2006
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
/** @file
 *
 * @brief Page d'erreur HTTP 403
 */

include($topdir. "include/site.inc.php");
include($topdir. "include/entities/page.inc.php");

$site = new site ();

$site->start_page("none","Erreur 403");

if ( !$site->user->is_valid() )
{
  $cts = new contents("Veuillez vous connecter pour accéder à  la page demandée");

  if ( $_SESSION['session_redirect'] )
    $cts->add_paragraph("Vous serez automatiquement redirigé vers la page que vous avez demandé.");

  $frm = new form("connect2","connect.php",true,"POST","Connexion");
  $frm->add_select_field("domain","Connexion",array("utbm"=>"UTBM","assidu"=>"Assidu","id"=>"ID","autre"=>"Autre","alias"=>"Alias"));
  $frm->add_text_field("username","Utilisateur","prenom.nom","",27);
  $frm->add_password_field("password","Mot de passe","","",27);
  $frm->add_submit("connectbtn2","Se connecter");

  $cts->add($frm,true);

  $site->add_contents($cts);

}
else
{
  /* TODO à traiter les reasons du 403 */
  if ($_REQUEST['reason'] != "reserved" && $_REQUEST['reason'] != "reservedutbm")
    $site->add_contents(new error("Accés refusé (403)",$_REQUEST['reason']));
  else
    $site->add_contents(new error("Accés refusé (403)","Vous n'avez pas les droits requis pour accéder à  cette page."));
}
$site->end_page();

?>
