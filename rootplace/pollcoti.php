<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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
$topdir = "../";

require_once($topdir. "include/site.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("root") )
  $site->error_forbidden("none","group",7);

$site->start_page("none","Administration");

$cts = new contents("<a href=\"./\">Administration</a> / Maintenance / Auto-Reparation de la base de données");

$req = new requete($site->dbrw,"UPDATE `ae_carte` SET `etat_vie_carte_ae`='".CETAT_EXPIRE."' " .
    "WHERE `date_expiration` <= NOW() AND `etat_vie_carte_ae`<".CETAT_EXPIRE."");

$cts->add_paragraph($req->lines." cartes ont expirées");


$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `ae_utl`='1' " .
    "WHERE `ae_utl`='0' AND EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND type_cotis IN ('0', '1', '2', '3', '4', '7') " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs de plus sont désormais cotisants AE");

$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `assidu_utl`='1' " .
    "WHERE `assidu_utl`='0' AND EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND type_cotis = '5' " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs de plus sont désormais cotisants Assidu");

$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `amicale_utl`='1' " .
    "WHERE `amicale_utl`='0' AND EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND type_cotis = '6' " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs de plus sont désormais cotisants Amicale");

$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `crous_utl`='1' " .
    "WHERE `crous_utl`='0' AND EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND type_cotis = '8' " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs de plus sont désormais cotisants CROUS");


$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `ae_utl`='0' " .
    "WHERE `ae_utl`='1' AND NOT EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND (type_cotis IN ('0', '1', '2', '3', '4', '7') OR type_cotis IS NULL) " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs ne sont plus cotisants AE");

$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `assidu_utl`='0' " .
    "WHERE `assidu_utl`='1' AND NOT EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND type_cotis = '5' " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs ne sont plus cotisants Assidu");

$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `amicale_utl`='0' " .
    "WHERE `amicale_utl`='1' AND NOT EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND type_cotis = '6' " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs ne sont plus cotisants Amicale");

$req = new requete($site->dbrw,"UPDATE `utilisateurs` SET `crous_utl`='0' " .
    "WHERE `crous_utl`='1' AND NOT EXISTS(SELECT * FROM `ae_cotisations` " .
      "WHERE `ae_cotisations`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "AND type_cotis = '8' " .
      "AND `date_fin_cotis` > NOW())");

$cts->add_paragraph($req->lines." utilisateurs ne sont plus cotisants CROUS");


$site->add_contents($cts);
$site->end_page();

?>
