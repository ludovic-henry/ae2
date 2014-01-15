<?php
/* Copyright 2012
 * - Antoine Ténart
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
 * forum operation for root group
 */

$topdir = "../";

require_once ($topdir. "include/site.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("root"))
  $site->error_forbidden ();

$site->start_page ("none", "Gestion du forum");



if (isset ($_REQUEST['action'])) {
  if ($_REQUEST['action'] == 'closeforum')
    $site->set_param ('forum_open', false);
  else if ($_REQUEST['action'] == 'openforum')
    $site->set_param ('forum_open', true);
 
  if ($_REQUEST['action']=='changemessage')
    $site->set_param ('forum_message', htmlentities ($_REQUEST['message']));
}

$cts = new contents ("<a href=\"./\">Administration</a> / <a href=\"forum.php\">forum</a>");

if ($site->get_param ('forum_open', true))
  $cts->add_paragraph ('<a href="?action=closeforum">Fermer le forum</a>');
else
  $cts->add_paragraph ('<a href="?action=openforum">Ouvrir le forum</a>');

$site->add_contents ($cts);

$frm = new form ('editmessage', 'forum.php', true, 'post');
$frm->add_hidden ('action','changemessage');
$frm->add_text_field ('message', 'Message d\'alerte',
    $site->get_param ('forum_message'));
$frm->add_submit ('sub', 'Modifier');
$site->add_contents($frm);


$site->end_page ();

exit();
?>
