<?php
/* Copyright 2006
 * - Pierre Mauduit
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
/*
 * @brief gestion du site de l'AE; modification des boites,
 * articles ?, etc ...
 *
 * Accessible par l'administration du site
 */

$topdir = "../";

require_once($topdir. "include/site.inc.php");

$site = new site ();

if (!$site->user->is_in_group("root"))
  $site->error_forbidden();

$site->start_page ("none", "Gestion du site");



if(isset($_REQUEST['action']))
{
  if($_REQUEST['action']=='onstate')
    $site->set_param("warning_enabled",true);
  if($_REQUEST['action']=='offstate')
    $site->set_param("warning_enabled",false);
  if($_REQUEST['action']=='changemessage')
    $site->set_param("warning_message",$_REQUEST['message']);
}

$cts = new contents("<a href=\"./\">Administration</a> / <a href=\"warning.php\">ALARM!</a>","<p>Cette page permet de gérer le message d'alerte.");
if($site->get_param('warning_enabled'))
  $cts->add_paragraph('<a href="warning.php?action=offstate">Désctiver le message d\'alerte</a>');
else
  $cts->add_paragraph('<a href="warning.php?action=onstate">Activer le message d\'alerte</a>');
$site->add_contents ($cts);

$frm = new form ("editwarning",
          "warning.php",
          true,
          "post",
          "Edition du message d'alerte");
$frm->add_hidden("action","changemessage");
$frm->add_text_field('message','Message d\'alerte',$site->get_param('warning_message'));
$frm->add_submit("sub", "Modifier");
$site->add_contents($frm);


$site->end_page ();

exit();
?>
