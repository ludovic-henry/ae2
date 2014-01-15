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
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/sondage.inc.php");
$site = new site ();

if (!$site->user->is_in_group ("moderateur_site"))
  $site->error_forbidden("accueil");

$site->start_page ("accueil", "Sondages");

if (isset($_REQUEST["addsdn"]) && $_REQUEST["question"] && $_REQUEST["end_date"])
{
  $cts = new contents("Sondage ajouté avec succès");
  $nb=0;

  foreach ( $_REQUEST["reponses"] as $rep )
    if ( $rep ) $nb++;

  if ( $nb > 1 )
  {
    $sdn = new sondage($site->db,$site->dbrw);
    $sdn->new_sondage($_REQUEST["question"], $_REQUEST["end_date"]);
    foreach ( $_REQUEST["reponses"] as $rep )
    {
      if ( $rep )
        $sdn->add_reponse($rep);
    }
    $cts->add_paragraph("<img src=\"".$topdir."images/actions/done.png\">&nbsp;Le sondage \"".$sdn->question."\" a bien été ajouté.");
    $site->add_contents($cts,true);
    unset($_REQUEST["question"]);
    unset($_REQUEST["reponses"]);
    unset($_REQUEST["end_date"]);
  }

}

if (isset($_REQUEST["editsdn"]) && $_REQUEST["question"] && $_REQUEST["reponses"] && $_REQUEST["end_date"])
{
  $nb=0;

  foreach ( $_REQUEST["reponses"] as $rep )
    if ( $rep ) $nb++;

  if ( $nb > 1 )
  {
    $sdn = new sondage($site->db,$site->dbrw);
    $sdn->load_lastest();
    $sdn->update_sondage($_REQUEST["question"], $_REQUEST["total_reponses"] , $_REQUEST["date_sondage"], $_REQUEST["end_date"]);

    foreach ( $_REQUEST["reponses"] as $num=>$rep)
    {
      if ( $rep )
        $sdn->update_reponse($rep,$num);
      else
        $sdn->remove_reponse($num);

    }
    unset($_REQUEST["total_reponses"]);
    unset($_REQUEST["question"]);
    unset($_REQUEST["reponses"]);
    unset($_REQUEST["end_date"]);
  }

}

if (isset($_REQUEST["action"]) && $_REQUEST["action"]=="edit")
{
  $sdn = new sondage($site->db,$site->dbrw);
  $sdn->load_lastest();

  if (!$sdn->is_lastest($_REQUEST['id_sondage']))
  {
    $cts = new contents("Erreur");
    $cts->add_paragraph("<img src=\"".$topdir."images/actions/info.png\">&nbsp;&nbsp;L' édition de sondage n'est possible que sur le dernier sondage en vigueur.<br/><br/>Cliquez <a href=\"".$topdir."ae/sondage.php?id_sondage=$sdn->id&action=edit\">ici</a> pour éditer le dernier sondage");
    $site->add_contents($cts,true);
  }
  else
  {

  $_REQUEST['question'] = $sdn->question;
  $_REQUEST['end_date'] = $sdn->date_fin;
  $_REQUEST['date_sondage'] = $sdn->date;
  $_REQUEST['total_reponses'] = $sdn->total;

  foreach ( $sdn->get_reponses() as $num=>$rep )
    {
      if ( $rep )
        $_REQUEST['reponses'][$num]= $rep;
    }
  // Variable qui prend la valeur 1 pour une modif et 0 pour un ajout classique
  $text_submit = 1;
  }
}

if ($text_submit || $_REQUEST['text_submit'])
  $frm = new form ("nvsondage","sondage.php",false,"POST","Edition du dernier sondage <a href=\"".$topdir."ae/sondage.php\" style=\"background-image: url(./../images/page.png); background-repeat: no-repeat; background-position: 3px; background-color: white; float: right; font-size: 90%; border: 1px dashed black; padding: 3px; padding-left: 22px; margin-top: -7px;\">Nouveau Sondage</a>");
else
  $frm = new form ("nvsondage","sondage.php",false,"POST","Nouveau sondage");

/* Duree de validite d'un sondage = 15 jours par defaut */

$default_valid = time() + (15 * 24 * 60 * 60);

if ($_REQUEST["end_date"])
  $frm->add_date_field("end_date", "Date de fin de validite : ",$_REQUEST["end_date"],true);
else
  $frm->add_date_field("end_date", "Date de fin de validite : ",$default_valid,true);

$frm->add_text_field("question", "Question",$_REQUEST["question"],true,80);

$frm->add_info("Pour supprimer une réponse, il suffit de la laisser vide !");

if (isset($_REQUEST["reponses"]))
{
  $n = 1;
  foreach ( $_REQUEST["reponses"] as $num=>$rep )
  {
    if ( $rep )
    {
      $frm->add_text_field("reponses[$num]", "Reponse $n",$rep,false,80);
      $n++;
    }
  }
  if (isset($_REQUEST["newrep"]))
    $frm->add_text_field("reponses[]", "Reponse $n","",false,80);
}
else
{
  $frm->add_text_field("reponses[]", "Reponse 1","",true,80);
  $frm->add_text_field("reponses[]", "Reponse 2","",false,80);
  $frm->add_text_field("reponses[]", "Reponse 3","",false,80);
  $frm->add_text_field("reponses[]", "Reponse 4","",false,80);
  $frm->add_text_field("reponses[]", "Reponse 5","",false,80);
  $frm->add_text_field("reponses[]", "Reponse 6","",false,80);
}

$frm->add_hidden("text_submit",$text_submit);
$frm->add_hidden("date_sondage",$_REQUEST['date_sondage']);
$frm->add_hidden("total_reponses",$_REQUEST['total_reponses']);

$frm->add_submit("newrep","Reponse supplémentaire");

if ($text_submit || $_REQUEST['text_submit'])
  $frm->add_submit("editsdn","Valider la modification");
else
  $frm->add_submit("addsdn","Ajouter");

$site->add_contents($frm);

$req = new requete($site->db, "SELECT * FROM `sdn_sondage` ORDER BY date_sondage DESC");
  if ( $req->lines > 0 )
  {
    $cts = new sqltable(
        "listsdn",
        "Derniers Sondages", $req, $topdir."ae/sondage.php",
        "id_sondage",
        array("question"=>"Question du sondage","total_reponses"=>"Nb total de réponses","date_sondage"=>"Depuis le","date_fin"=>"Jusqu'au"),
        array("edit"=>"Editer"), array());
  }
$site->add_contents($cts);
$site->end_page ();

?>
