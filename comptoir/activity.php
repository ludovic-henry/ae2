<?php
/* Copyright 2007
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
$topdir="../";
require_once("include/comptoirs.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");

$site = new sitecomptoirs();

$comptoir = new comptoir($site->db);
$comptoir->load_by_id($_REQUEST["id_comptoir"]);
if ( $comptoir->id < 1 )
  $site->error_forbidden("services");


$site->start_page("services","Activité sur le comptoir ".$comptoir->nom);

$cts = new contents("Activité sur le comptoir ".$comptoir->nom);

$cts->add_paragraph("Cette page vous permet de savoir s'il y a de l'activité au comptoir ".$comptoir->nom." et ainsi savoir si le comptoir est ouvert.");



$req = new requete ($site->db,
           "SELECT

           `utilisateurs`.`id_utilisateur`,
            IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`,
            cpt_tracking.activity_time as `date_act`

            FROM `cpt_tracking`
            INNER JOIN utilisateurs ON cpt_tracking.id_utilisateur=utilisateurs.id_utilisateur
            LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur`

            WHERE `activity_time` > '".date("Y-m-d H:i:s",time()-intval(ini_get("session.gc_maxlifetime")))."'
            AND `closed_time` IS NULL
            AND id_comptoir='".mysql_real_escape_string($comptoir->id)."'
            GROUP BY `utilisateurs`.`id_utilisateur`");
$led = "red";
$descled = "fermé (ou pas d'activité depuis plus de ".(intval(ini_get("session.gc_maxlifetime"))/60)." minutes)";

if ( $req->lines > 0 )
{
  $row = $req->get_row();

  $last_act = strtotime($row['date_act']);

  $led = "green";
  $descled = "ouvert";

  if ( time()-$last_act > 600 )
  {
    $led = "yellow";
    $descled = "ouvert (mais pas d'activité depuis plus de 10 minutes)";
  }

  $req->go_first();
}

$cts->add_paragraph("Le comptoir ".$comptoir->nom." est actuellement <img src=\"../images/leds/".$led."led.png\" class=\"icon\" /> $descled");

if ( $req->lines > 0 )

$cts->add(new sqltable(
    "lstactcpt",
    "Barmen connectés", $req, "activity.php",
    "id_utilisateur",
    array(
      "date_act"=>"Dernière activité",
      "nom_utilisateur"=>"Barman"),
    array(),
    array(),
    array( )
    ),true);

$site->add_contents($cts);
$site->end_page();

?>
