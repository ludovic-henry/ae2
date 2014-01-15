<?php
/**
 * @brief Covoiturage : proposition d'un trajet.
 *
 */

/* Copyright 2007
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
require_once($topdir . "include/pgsqlae.inc.php");

require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/trajet.inc.php");


$site = new site();

$pgsql = new pgsqlae();

$trajet = new trajet($site->db, $site->dbrw);

$site->start_page("services",
          "Covoiturage - proposition d'un trajet");


if ($site->user->id <= 0)
{
  $site->error_forbidden("services");
  exit();
}

if (isset($_REQUEST['finalizetrip']))
{
  $cts = new contents("Proposition de trajet",
              "Merci d'avoir utilisé le système de covoiturage ! votre trajet a bien été ".
              "enregistré. Il est dorénavant proposé aux autres utilisateurs du site. ".
              "Vous aurez évidemment la possibilité de rajouter des dates si l'occation ".
              "se présentait.<br/><a href=\"./\">Retour à la page d'accueil du covoiturage</a>");

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

if (isset($_REQUEST['step2']))
{
  $trajet->load_by_id($_REQUEST['id_trajet']);
  $trajet->load_dates();

  /* trajet retour ? */
  if (isset($_REQUEST['id_trajet_ret']))
    {
      $trajet_ret = new trajet($site->db, $site->dbrw);
      $trajet_ret->load_by_id($_REQUEST['id_trajet_ret']);
    }

  $ret = false;

  /* il est évident que seul le responsable d'un trajet
   * peut ajouter des dates ...
   */
  if ($site->user->id ==  $trajet->id_utilisateur)
    {
      $ret = $trajet->add_date($_REQUEST['date']);
    }

  if (! $ret)
    {
      $cts = new contents("Ajout de dates", "<b>Echec lors de l'ajout de date.</b>");
    }
  else
    {
      $cts = new contents("Ajout de dates", "Date ajoutée avec succès !");
      $trajet->load_dates();
    }

  $site->add_contents($cts);

  /* affichage des dates du trajet */
  if (count($trajet->dates))
    {
      $itmlst = new itemlist("Dates proposées :",false, $trajet->dates);
      $cts->add($itmlst);
    }

  if (isset($trajet_ret))
    {
      /* tentative d'ajout de dates pour le trajet de retour */
      $ret = false;
      if ($site->user->id == $trajet_ret->id_utilisateur)
    {
      $ret = $trajet_ret->add_date($_REQUEST['date_ret']);
    }

      if (!$ret)
    {
      $cts = new contents("Ajout de dates sur trajet retour",
                  "<b>Echec lors de l'ajout de date sur le trajet de retour.</b>");
    }

      else
    {
      $cts = new contents("Ajout de dates sur trajet retour",
                  "Date sur trajet de retour ajoutée avec succès !");
      $trajet_ret->load_dates();
    }

      if (count($trajet_ret->dates))
    {
      $itmlst = new itemlist("Dates proposées pour le trajet de retour :",false, $trajet_ret->dates);
      $cts->add($itmlst);
    }
      $site->add_contents($cts);
    } // fin trajet retour


  $cts = new contents('Ajout de dates');
  $frm = new form('trip_step2', "propose.php", true);
  $frm->add_hidden('id_trajet', $trajet->id);
  $frm->add_date_field('date', 'Date de voyage proposée');

  /* trajet retour */
  if (isset($trajet_ret))
    {
      $frm->add_hidden('id_trajet_ret', $trajet_ret->id);
      $frm->add_date_field('date_ret', 'Date de voyage retour proposée');
    }
  $frm->add_submit('step2', 'Ajouter des dates de trajet');
  $frm->add_submit('finalizetrip', 'Finaliser la proposition');


  $cts->add($frm);

  $site->add_contents($cts);
  $site->end_page();
  exit();

}

if (isset($_REQUEST['step1']))
{
  $type = (isset($_REQUEST['type']) ? $_REQUEST['type'] : TRJ_PCT);
  $ident = (isset($_REQUEST['id_ent'])) ? intval($_REQUEST['id_ent']) : NULL;


  $dep = intval($_REQUEST['start']);
  $arr = intval($_REQUEST['stop']);

  $vdep = new ville($site->db);
  $varr = new ville ($site->db);

  $vdep->load_by_id($dep);
  $varr->load_by_id($arr);


  $cts = new contents("Proposition de trajet",
              "Vous proposez un trajet ".
              $vdep->nom ." / " . $varr->nom.".");

  $comments = $_REQUEST['comments'];



  if (strlen($_REQUEST['comments']))
    {
      $cts->add_paragraph("Vous avez laissé les observations suivantes sur ce trajet : <div class=\"comment\">".
              doku2xhtml($comments)."</div>");
    }
  else
    {
      $cts->add_paragraph("Vous n'avez pas laissé d'observations sur le trajet. ".
              "Pensez à préciser des informations sur l'effectif, ".
              "les bagages, le coût estimé par personnes, etc ... ".
              "lors de la prise de contacts avec vos voyageurs !");
    }

  $ret = $trajet->create($site->user->id, $vdep->id, $varr->id, $comments, $type, $ident);

  if ($ret)
    {
      if ($type == TRJ_PCT)
    {
      $frm = new form('trip_step2', "propose.php", true);
      $frm->add_hidden('id_trajet', $trajet->id);
      $frm->add_date_field('date', 'Date de voyage proposée');

      if (!isset($_REQUEST['retour']))
        {
          $frm->add_submit('step2', 'Ajouter des dates de trajet');
          $cts->add($frm);
        }
    }

      else
    {
      $site->add(new contents("Ajout d'un trajet", "Votre trajet a été ajouté avec succès."));
    }

    }
  else
    {
      $cts->add_paragraph("<b>Une erreur est survenue lors de l'ajout du trajet.</b>");
    }


  $site->add_contents ($cts);

  // trajet retour
  if (isset($_REQUEST['retour']))
    {
      $ret = $trajet->create($site->user->id, $varr->id, $vdep->id, "Trajet de retour, ".
                 "voir trajet aller pour de plus amples informations", $type, $ident);

      if ($ret)
    {
      if ($type == TRJ_PCT)
        {
          $frm->add("<h3>Dates pour le trajet de retour</h3>");
          $frm->add_hidden('id_trajet_ret', $trajet->id);
          $frm->add_date_field('date_ret', 'Date de voyage retour');
          $frm->add_submit('step2', 'Ajouter des dates de trajet');

          $cts->add($frm);
        }

    }
      else
    {
      $cts->add_paragraph("<b>Une erreur est survenue lors de l'ajout du trajet de retour.</b>");
    }
    }
  $site->end_page ();
  exit();
}



$cts = new contents("Proposition d'un trajet",
            "A l'aide de cette page, vous allez pouvoir proposer ".
            "un trajet aux autres utilisateurs du site. ".
            "Veuillez remplir le formulaire ci-dessous :<br/><br/>");

$cts->add_paragraph("<b>Attention : Cette page est destinée aux utilisateurs.".
            " prévoyant de réaliser un trajet avec leur voiture, et ".
            "souhaitant proposer ce trajet. Pour rechercher un ".
            "trajet, merci de vous rendre sur la <a href=\".".
            "/search.php\">page prévue à cet effet.</a></b>");

$frm = new form("trip_step1", "propose.php", true);

$ville = new ville($site->db, null, $pgsql);

$frm->add_hidden("type", $_REQUEST['type']);
$frm->add_hidden("id_ent", $_REQUEST['id_ent']);

$frm->add_entity_smartselect("start", "Ville de départ", $ville);
$frm->add_entity_smartselect("stop", "Ville d'arrivée", $ville);

$frm->add_dokuwiki_toolbar('comments');
$frm->add_text_area("comments",
            "Commentaires (facultatif - format DokuWiki)",
            null, 80, 20);

$frm->add_checkbox("retour", "Prévoir un trajet inverse (retour)");


$frm->add_submit('step1', 'En voiture !');

$cts->add_paragraph("Note : Une fois ce trajet établi, vous serez dirigé sur la page suivante qui vous permettra d'ajouter des dates à ce trajet");


$cts->add($frm);
$site->add_contents ($cts);

/* fin page */
$site->end_page ();
?>
