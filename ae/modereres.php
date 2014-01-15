<?php

/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 * - Sarah Amsellem < sarah dot amsellem at gmail dot com >
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

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/sitebat.inc.php");
require_once($topdir. "include/entities/batiment.inc.php");
require_once($topdir. "include/entities/salle.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/cts/planning.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("gestion_ae") && !$site->user->is_in_group("bdf-bureau") )
  $site->error_forbidden("services");

$sfilter='';
$filter='';

$resa = new reservation($site->db, $site->dbrw);

if(in_array($_REQUEST['site'],array("belfort","sevenans","montbeliard")))
{
  $sfilter=' INNER JOIN sl_batiment ON sl_salle.id_batiment=sl_batiment.id_batiment '
          .'INNER JOIN sl_site ON sl_batiment.id_site=sl_site.id_site ';
  if($_REQUEST['site']=="belfort")
    $filter.=' AND `sl_site`.`id_ville`=34582 ';
  elseif($_REQUEST['site']=="sevenans")
    $filter.=' AND `sl_site`.`id_ville`=34655 ';
  elseif($_REQUEST['site']=="montbeliard")
    $filter.=' AND `sl_site`.`id_ville`=9137 ';
}
if( $_REQUEST["debut"] && $_REQUEST["fin"] && ( $_REQUEST["debut"]  < $_REQUEST["fin"] ))
{
  $filter.= " AND sl_reservation.date_debut_salres >= '".date("Y-m-d H:i:s",$_REQUEST["debut"])."' ";
  $filter.= " AND sl_reservation.date_fin_salres <= '".date("Y-m-d H:i:s",$_REQUEST["fin"])."' ";
}
elseif($_REQUEST['debut'])
{
  $filter.= " AND sl_reservation.date_debut_salres >= '".date("Y-m-d H:i:s",$_REQUEST["debut"])."' ";
}
elseif($_REQUEST['fin'])
{
  $filter.= " AND sl_reservation.date_fin_salres <= '".date("Y-m-d H:i:s",$_REQUEST["fin"])."' ";
}


if ( isset($_REQUEST['id_salres']))
  $resa->load_by_id($_REQUEST['id_salres']);

if ( $_REQUEST["action"] == "accord" && $resa->id > 0 )
  $resa->accord($site->user->id);
elseif ( $_REQUEST["action"] == "delete" && $resa->id > 0 )
  $resa->delete();
elseif ( $_REQUEST["action"] == "convention" && $resa->id > 0 )
  $resa->convention_done();
elseif ( $_REQUEST["action"] == "accords" )
{
  foreach ( $_REQUEST["id_salress"] as $id_salres )
  {
    $resa->load_by_id($id_salres);
    if( $resa->id > 0 )
      $resa->accord($site->user->id);
  }
}
elseif ( $_REQUEST["action"] == "conventions" )
{
  foreach ( $_REQUEST["id_salress"] as $id_salres )
  {
    $resa->load_by_id($id_salres);
    if( $resa->id > 0 )
      $resa->convention_done();
  }
}
elseif ( $_REQUEST["action"] == "deletes" )
{
  foreach ( $_REQUEST["id_salress"] as $id_salres )
  {
    $resa->load_by_id($id_salres);
    if( $resa->id > 0 )
      $resa->delete();
  }
}
elseif ( $_REQUEST["action"] == "info")
{
  $user = new utilisateur($site->db);
  $userop = new utilisateur($site->db);
  $sitebat = new sitebat($site->db);
  $bat = new batiment($site->db);
  $salle = new salle($site->db);
  $asso = new asso($site->db);

  $salle->load_by_id($resa->id_salle);
  $bat->load_by_id($salle->id_batiment);
  $sitebat->load_by_id($bat->id_site);
  $user->load_by_id($resa->id_utilisateur);
  $userop->load_by_id($resa->id_utilisateur_op);
  $asso->load_by_id($resa->id_asso);

  if (isset($_REQUEST["notes"]))
    $resa->set_notes($_REQUEST["notes"]);

  $site->start_page("services","Moderation des reservations de salle");

  $cts = new contents("Reservation n°".$resa->id);

  $tbl = new table("Informations");
  $tbl->add_row(array("Demande faite le ",date("d/m/Y H:i",$resa->date_demande)));
  $tbl->add_row(array("Période",date("d/m/Y H:i",$resa->date_debut)." au ".date("d/m/Y H:i",$resa->date_fin)));
  $tbl->add_row(array("Demandeur",$user->get_html_link()));
  if ( $asso->id > 0 )
    $tbl->add_row(array("Association",$asso->get_html_link()));
  $tbl->add_row(array("Convention de locaux requise",$salle->convention?"Oui":"Non"));
  $tbl->add_row(array("Convention de locaux faite",$resa->convention?"Oui":"Non"));
  $util_bar_txt = array(1=>"Non", 2=>"Oui", 3=>"BDF");
  if ($resa->util_bar)
    $tbl->add_row(array("Utilisation du bar",$util_bar_txt[$resa->util_bar]));
  if( $resa->date_accord )
    $tbl->add_row(array("Accord","le ".date("d/m/Y H:i",$resa->date_accord)." par ".$userop->get_html_link()));
  $tbl->add_row(array("Salle",$salle->get_html_link()));
  $tbl->add_row(array("Batiment",$bat->get_html_link()));
  $tbl->add_row(array("Site",$sitebat->get_html_link()));
  $tbl->add_row(array("Motif",htmlentities($resa->description,ENT_NOQUOTES,"UTF-8")));
  $cts->add($tbl,true);

  $frm = new form("notes","modereres.php?id_salres=".$resa->id."&action=info", false,"POST","Notes");
  $frm->add_text_area("notes","Notes",$resa->notes,40,4);
  $frm->add_submit("valid","Enregistrer");
  $cts->add($frm,true);


  if($site->user->is_in_group("gestion_ae"))
  {
    $lst = new itemlist("Opérations");

    if( !$resa->date_accord )
      $lst->add("<a href=\"modereres.php?id_salres=".$resa->id."&action=accord\">Accord</a>");

    if ( !$resa->convention && $salle->convention )
      $lst->add("<a href=\"modereres.php?id_salres=".$resa->id."&action=convention\">Convention faite</a>");

    $lst->add("<a href=\"modereres.php?id_salres=".$resa->id."&action=delete\">Refuser/Supprimer</a>");
    $lst->add("<a href=\"modereres.php\">Retour modération</a>");

    $cts->add($lst,true);
  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("services","Moderation des reservations de salle");


if($site->user->is_in_group("gestion_ae"))
{
  $frm = new form ("filter","?",false,"POST","Filtrer");
  $frm->add_select_field("site","Site",array(""=>"--","belfort"=>"Belfort","sevenans"=>"Sévenans","montbeliard"=>"Montbéliard"),$_REQUEST['site']);
  $frm->add_datetime_field("debut","Date et heure de d&eacute;but",$_REQUEST['debut']);
  $frm->add_datetime_field("fin","Date et heure de fin",$_REQUEST['fin']);
  $frm->add_submit("valid","Filtrer");
  $site->add_contents ($frm);

  $req = new requete($site->db,"SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "sl_salle.id_salle, sl_salle.nom_salle," .
    "asso.id_asso, asso.nom_asso," .
    "sl_reservation.id_salres,  sl_reservation.date_debut_salres," .
    "sl_reservation.date_fin_salres, sl_reservation.description_salres, " .
    "sl_reservation.date_accord_res," .
    "(sl_reservation.convention_salres*10+sl_salle.convention_salle) as `convention` " .
    "FROM sl_reservation " .
    "INNER JOIN utilisateurs ON `utilisateurs`.`id_utilisateur`=sl_reservation.id_utilisateur " .
    "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
    "LEFT JOIN asso ON asso.id_asso=sl_reservation.id_asso " .
    $sfilter.
    "WHERE ((sl_reservation.date_accord_res IS NULL) OR " .
    "(sl_salle.convention_salle=1 AND sl_reservation.convention_salres=0)) " .
    "AND sl_reservation.date_fin_salres > NOW() ".
    $filter.
    "ORDER BY date_debut_salres");

  $site->add_contents(new sqltable(
      "modereres",
      "Demandes de reservation", $req, "modereres.php",
      "id_salres",
      array("nom_utilisateur"=>array("Demandeur","nom_utilisateur","nom_asso"),
        "nom_salle"=>"Salle",
        "date_debut_salres"=>"De",
        "date_fin_salres"=>"A",
        "description_salres" => "Motif",
        "convention"=>"Conv.",
        "date_accord_res"=>"Accord le"
        ),
      array("accord"=>"Donner accord", "convention"=>"Convention faite", "delete"=>"Refuser","info"=>"Details"),
      array("accords"=>"Donner accord", "conventions"=>"Convention faite", "deletes"=>"Refuser"),
      array("convention"=>array(0=>"Non requise",1=>"A faire",11=>"Faite") )
      ));

  $req = new requete($site->db,"SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "sl_salle.id_salle, sl_salle.nom_salle," .
    "asso.id_asso, asso.nom_asso," .
    "sl_reservation.id_salres,  MIN(sl_reservation.date_debut_salres) date_debut_salres, " .
    "sl_reservation.date_fin_salres, sl_reservation.description_salres, " .
    "sl_reservation.date_accord_res, IF(COUNT(*) > 1, MAX(sl_reservation.date_debut_salres), '') repet," .
    "(sl_reservation.convention_salres*10+sl_salle.convention_salle) as `convention` " .
    "FROM sl_reservation " .
    "INNER JOIN utilisateurs ON `utilisateurs`.`id_utilisateur`=sl_reservation.id_utilisateur " .
    "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
    "LEFT JOIN asso ON asso.id_asso=sl_reservation.id_asso " .
    $sfilter.
    "WHERE sl_reservation.date_accord_res IS NOT NULL " .
    "AND sl_reservation.date_debut_salres > NOW() ".
    $filter.
    "GROUP BY date_demande_res ".
    "ORDER BY date_debut_salres");

  /* On groupe les réservations en fonction de la date de demande...
   * en espérant qu'il y ai pas deux utilisateurs qui fassent une demande en
   * même temps... faudrait limite revoir le schéma des tables à l'occas...
   */

  $site->add_contents(new sqltable(
      "modereres",
      "Réservations validées", $req, "modereres.php",
      "id_salres",
      array("nom_utilisateur"=>array("Demandeur","nom_utilisateur","nom_asso"),
        "nom_salle"=>"Salle",
        "date_debut_salres"=>"De",
        "date_fin_salres"=>"A",
        "repet"=>"Répété jusqu'au",
        "description_salres" => "Motif",
        "date_accord_res"=>"Accord le"
        ),
      array("info"=>"Details"),
      array(),
      array()
      ));

}
elseif($site->user->is_in_group("bdf-bureau"))
{
  $req = new requete($site->db,"SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "sl_salle.id_salle, sl_salle.nom_salle," .
    "asso.id_asso, asso.nom_asso," .
    "sl_reservation.id_salres,  MIN(sl_reservation.date_debut_salres) date_debut_salres, " .
    "sl_reservation.date_fin_salres, sl_reservation.description_salres, " .
    "sl_reservation.date_accord_res, sl_reservation.util_bar_salres," .
    "IF(COUNT(*) > 1, MAX(sl_reservation.date_debut_salres), '') repet," .
    "(sl_reservation.convention_salres*10+sl_salle.convention_salle) as `convention` " .
    "FROM sl_reservation " .
    "INNER JOIN utilisateurs ON `utilisateurs`.`id_utilisateur`=sl_reservation.id_utilisateur " .
    "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
    "LEFT JOIN asso ON asso.id_asso=sl_reservation.id_asso " .
    "WHERE sl_reservation.date_debut_salres > NOW()" .
    "AND (sl_salle.id_salle='5' OR sl_salle.id_salle='28') ".
    "GROUP BY date_demande_res ".
    "ORDER BY date_debut_salres");

  $site->add_contents(new sqltable(
      "modereres",
      "Demandes de reservation", $req, "modereres.php",
      "id_salres",
      array("nom_utilisateur"=>array("Demandeur","nom_utilisateur","nom_asso"),
        "nom_salle"=>"Salle",
        "date_debut_salres"=>"De",
        "date_fin_salres"=>"A",
        "repet"=>"Répété jusqu'au",
        "description_salres" => "Motif",
        "convention"=>"Conv.",
        "util_bar_salres" => "Bar",
        "date_accord_res"=>"Accord le"
        ),
      array("info"=>"Details"),
      array(),
      array("convention"=>array(0=>"Non requise",1=>"A faire",11=>"Faite"), "util_bar_salres"=>array(1=>"Non", 2=>"Oui", 3=>"BDF"))
      ));
}

$site->end_page();

?>
