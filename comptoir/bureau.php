<?php

/* Copyright 2005,2006,2008
 * - Julien Etelain <julien CHEZ pmad POINT net>
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
/**

 * @file
 * Interface de vente par carte AE sur des comptoirs de type "bueau" .
 * Remarque: Ce fichier ne contient que les spécificités de ce type de comptoir.
 *
 * L'id du comptoir doit être définit en GET ou en POST : id_comptoir
 *
 * L'opérateur est définit à l'utilisateur connecté, si ce dernier n'est pas
 * membre du groupe vendeurs du comptoir, une erreur 403 sera renvoyée.
 *
 * @see comptoir/frontend.inc.php
 * @see comptoir
 * @see sitecomptoirs
 * @see get_localisation
 */

$topdir="../";
require_once("include/comptoirs.inc.php");
require_once($topdir. "include/cts/user.inc.php");

$site = new sitecomptoirs(true);

if (! $site->comptoir->ouvrir($_REQUEST["id_comptoir"]))
  $site->error_not_found("services");

if ( !$site->comptoir->is_valid() )
  $site->error_not_found("services");

if ( $site->comptoir->type != 2 )
  $site->error_forbidden("services","invalid");

if ( !$site->comptoir->set_operateur($site->user) )
  $site->error_forbidden("services","group",$site->comptoir->groupe_vendeurs);

include("frontend.inc.php");

?>
