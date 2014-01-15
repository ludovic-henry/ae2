<?php
/**
 * @brief L'accueil du magasin en ligne de l'AE (e-boutic).
 *
 */

/* Copyright 2006
 * Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

$topdir="../";

require_once($topdir . "include/site.inc.php");
require_once($topdir . "comptoir/include/produit.inc.php");
require_once($topdir . "comptoir/include/venteproduit.inc.php");
require_once("include/e-boutic.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/cts/gallery.inc.php");
require_once($topdir . "include/cts/vignette.inc.php");

$site = new site ();

$site->start_page ("e-boutic", "E-boutic : Annulation");

$site->add_contents (new error("Annulation", "<p>Suite a un incident, ".
                               "la vente a ete annulee.".
                               "<br/><a href=\"./\">Retour accueil</a></p>"));

/* fin page */
$site->end_page ();
?>
