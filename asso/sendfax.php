<?php
/** @file
 *
 * @brief Page réservée aux responsables de clubs pour l'envoi de fax.
 *
 */
/* Copyright 2007
 *
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/fax.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$site = new site();

$asso = new asso($site->db,$site->dbrw);
$asso->load_by_id($_REQUEST["id_asso"]);
if ( $asso->id < 1 )
{
  $site->error_not_found("services");
  exit();
}

if ((!$site->user->is_in_group("gestion_ae"))
    && (!$asso->is_member_role($site->user->id,ROLEASSO_PRESIDENT)))
{
  $site->error_forbidden("presentation");
}

$site->start_page("presentation", $asso->nom);

if (isset($_POST['sendfaxsbmt']))
{
  $fax = new fax ($site->db, $site->dbrw);
  $fax->load_by_id($_POST['faxinstanceid']);
  $fax->set_captcha($_POST['captcha']);
  $ret = $fax->send_fax(false);
  if ($ret)
    $cts = new contents("Etat d'envoi du fax",
      "Fax envoyé, sous réserve d'acceptation du fichier ".
      "PDF par les serveurs de notre fournisseur.");
  else
    $cts = new contents("Etat d'envoi du fax",
      "<b>Echec de l'envoi du fax</b>");

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

if (isset($_POST['preparefaxsbmt']))
{

  $fax = new fax($site->db, $site->dbrw);

  $fax->create_instance($site->user->id,
      $_POST['numdest'],
      $_FILES['mypdf'],
      $asso->id);

  $cts = new contents("Vérification",
          "Veuillez entrer la série de caractères contenue ".
          "dans l'image ci-dessous :");

  $cts->puts("<br/><img src=\"".$fax->imgcaptcha."\" alt=\"captchos\" /><br /><a href=\"".$fax->captchaaudio."\"><br />Version Audio</a>");

  $frm = new form("sendfax",
      "sendfax.php",
      true,
      "POST");

  $frm->add_hidden("faxinstanceid", $fax->id);
  $frm->add_hidden("id_asso", $asso->id);

  $frm->add_text_field("captcha",
           "Captcha : ",
           "");

  $frm->add_submit("sendfaxsbmt", "Envoyer");

  $site->add_contents($cts);
  $site->add_contents($frm);
  $site->end_page();

  exit();

}

$cts = new contents("Envoi de fax",
        "Par cette page, vous pouvez addresser des fax ".
        "via notre fournisseur d'accès.<br/>".
        "Veuillez entrer un fichier PDF, ainsi que le numéro".
        " du destinataire. Une vérification anti-robot vous ".
        "sera demandée sur la page suivante.");


$frm = new form("preparefax",
    "sendfax.php",
    true,
    "POST");

$frm->add_text_field("numdest","Numéro du destinataire : ");
$frm->add_file_field("mypdf", "Fichier PDF : ", true);
$frm->add_submit("preparefaxsbmt","Valider");
$frm->add_hidden("id_asso", $asso->id);

$cts->add($frm);

$site->add_contents($cts);

$site->end_page();


?>
