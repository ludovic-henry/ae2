<?php
/* Copyright 2011
 * - Antoine Ténart < antoine dot tenart at gmail dot com >
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

/**
 *  mobile version for forum
 */

$topdir = "../";

require_once ($topdir . "include/site.inc.php");
require_once ($topdir . "include/entities/forum.inc.php");
require_once ($topdir . "include/entities/sujet.inc.php");
require_once ($topdir . "include/entities/message.inc.php");
require_once ($topdir . "include/cts/forum.inc.php");


$npp = 20;

$site = new site ();
$site->set_mobile (true);
$site->add_css("themes/mobile/css/forum.css");

/* Pas de forum mobile pour l'instant */
if(!$GLOBALS["taiste"]) header("HTTP/1.0 404 Not Found");

$cts = new contents ();
$cts->add_title (1, "Forum", "mob_title");

if ($site->user->is_in_group ("ban_forum")) {
  $cts->add_title (1, "Accès interdit");
  $cts->add_paragraph ("Vous n'avez pas respecté la charte de publication, votre
        présence n'est désormais plus souhaitée.");
  $site->end_page ();
  exit ();
}

$forum = new forum ($site->db, $site->dbrw);
$sujet = new sujet ($site->db, $site->dbrw);
$message = new message ($site->db, $site->dbrw);

if (isset ($_REQUEST["id_message"])) {
  $message->load_by_id ($_REQUEST["id_message"]);
  if ($message->is_valid ()) {
    $sujet->load_by_id ($message->id_sujet);
    $forum->load_by_id ($sujet->id_forum);
  }
} elseif (isset ($_REQUEST["id_sujet"])) {
  $sujet->load_by_id ($_REQUEST["id_sujet"]);
  if ($sujet->is_valid ())
    $forum->load_by_id ($sujet->id_forum);
} elseif (isset ($_REQUEST["id_forum"])) {
  $forum->load_by_id ($_REQUEST["id_forum"]);
}

/* The next 3 lines aren't here, I promise */
if (isset($_REQUEST["setnosecret"]))
  setcookie ("nosecret", $_REQUEST["setnosecret"], time() + 31536000, "/",
        $domain, 0);

if (!$forum->is_valid ())
  $forum->load_by_id (1); // forum's root

if (!$forum->is_right ($site->user, DROIT_LECTURE)) {
  $cts->add_title (1, "Erreur");
  $cts->add_paragraph ("Vous n'avez pas les droits requis pour visionner cette
        page.");
  exit ();
}

if ($_REQUEST["action"] == "setallread") {
  if ($site->user->is_valid ()) {
    $site->user->set_all_read ();
    header ("Location: ".$wwwtopdir."forum2/index.php");
    exit ();
  }
}

if ($sujet->is_valid ()) {
  $cts->add (new sujetforum ($forum, $sujet, $site->user, "forum2.php", 0,
          $npp));
} elseif ($forum->categorie) {
  $cts->add (new forumslist ($forum, $site->user, "./forum2.php"));
} else {
  $start = 0;
  $nb_pages = ceil ($forum->nb_sujets / $npp);

  if (isset ($_REQUEST["fpage"])) {
    $start = intval ($_REQUEST["fpage"]) * $npp;
    if ($start > $forum->nb_sujets) {
      $start = $forum->nb_sujets;
      $start -= $start%$npp;
    }
  }

  $cts->add (new sujetslist ($forum, $site->user, "./forum2.php", $start, $npp));

  $entries = array();
  for( $n=0;$n<$nbpages;$n++)
    $entries[] = array ($n, "forum2.php/?id_forum=" . $forum->id . "&fpage=" . $n,
        $n+1);

  $cts->add (new tabshead ($entries, floor($start/$npp), "_bottom"));
}

$site->add_contents ($cts);

/* Do not cross. */
$site->end_page ();

?>
