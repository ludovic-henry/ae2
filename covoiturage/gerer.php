<?php
/**
 * @brief Covoiturage : Gestion d'un trajet
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
require_once($topdir . "include/cts/gmap.inc.php");
require_once($topdir . "include/entities/trajet.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");


$site = new site();

$site->start_page ("services", "Covoiturage - Gestion d'un trajet");
$accueil = new contents("Covoiturage - Gestion d'un trajet",
      "");

$trajet = new trajet($site->db, $site->dbrw, null);

$trajet->load_by_id($_REQUEST['id_trajet']);

/* edition du commentaire */
if ($_REQUEST['action'] == "commitnewcomm")
{
  if ($site->user->id == $trajet->id_utilisateur)
  {
    $ret = $trajet->set_comment($_REQUEST['comments']);
    if (! $ret)
      $accueil->add_paragraph("<b>Erreur lors de la modification du commentaire.</b>");
  }
}
if ($_REQUEST['action'] == "modcomm")
{
  $accueil->add_paragraph("Vous pouvez modifier le commentaire à l'aide du formulaire suivant :");

  $frm = new form('modify_comment', 'gerer.php?action=commitnewcomm&id_trajet=' .$trajet->id, true);
  $frm->add_dokuwiki_toolbar('comments');
  $frm->add_text_area('comments', 'Commentaires (Syntaxe DokuWiki)', $trajet->commentaires, 80, 20);
  $frm->add_submit('commit_modification_comment', 'Modifier');
  $accueil->add($frm);
  $site->add_contents($accueil);
  $site->end_page();
  exit();
}


/* suppression d'une date de trajet */
if ($_REQUEST['action'] == "delete")
{
  if ($trajet->id_utilisateur == $site->user->id)
  {
    if (isset($_REQUEST['ids']))
      $dates = &$_REQUEST['ids'];
    else
      $dates[] = $_REQUEST['id'];

    if (count($dates) > 0)
    {
      $accueil->add_title(2, "Suppresion de dates");

      foreach ($dates as $date)
      {
        $ret = $trajet->delete_date($site->user->id, $date);

        if ($ret == true)
        {
          $accueil->add_paragraph("<b>Date  du ".
                HumanReadableDate($date, "", false, true)
                ."supprimée avec succès.</b>");

        }
      }
    }
  }

  /* rechargement du trajet */
  $trajet->load_by_id($trajet->id);
}

/* Acceptation / refus */
if ($_REQUEST['action'] == "accept")
{
  $accueil->add_title(2, "Accpetation de l'étape");
  if ($trajet->accept_step($_REQUEST['id_etape'], $_REQUEST['date']))
    $accueil->add_paragraph("Etape acceptée avec succès.");
  else
    $accueil->add_paragraph("Erreur lors de l'acceptation de l'étape.");

   /* options */
  $accueil->add_title(2, "Autres options");
  $opts[] = "<a href=\"./\">Retour à la page d'accueil du covoiturage</a>";
  $opts[] = "<a href=\"./propose.php\">Proposer un trajet</a>";
  $opts[] = "<a href=\"./search.php\">Rechercher un trajet</a>";

  $options = new itemlist(false, false, $opts);
  $accueil->add($options);

  $site->add_contents($accueil);
  $site->end_page();

  exit();
}

else if ($_REQUEST['action'] == "refuse")
{
  $accueil->add_title(2, "Acceptation de l'étape");


  if ($trajet->refuse_step($_REQUEST['id_etape'], $_REQUEST['date']))
    $accueil->add_paragraph("Etape refusée avec succès !");
  else
    $accueil->add_paragraph("Erreur lors du refus de l'étape.");

  /* options */
  $accueil->add_title(2, "Autres options");
  $opts[] = "<a href=\"./\">Retour à la page d'accueil du covoiturage</a>";
  $opts[] = "<a href=\"./propose.php\">Proposer un trajet</a>";
  $opts[] = "<a href=\"./search.php\">Rechercher un trajet</a>";

  $options = new itemlist(false, false, $opts);
  $accueil->add($options);

  $site->add_contents($accueil);
  $site->end_page();
  exit();
}

/* modération des étapes */
if ($_REQUEST['action'] == "moderer")
{
  $accueil->add_title(2, "Modération des étapes");

  $trajet->load_steps();

  $step = $trajet->get_step_by_id($_REQUEST['id_etape'], $_REQUEST['date_trajet']);

  $propusr = new utilisateur ($site->db);
  $propusr->load_by_id($step['id_utilisateur']);

  if ($step['ville'] > 0)
  {
    $villeetp = new ville($site->db);
    $villeetp->load_by_id($step['ville']);
  }
  else
    $villeetp = NULL;

  if ($villeetp != NULL)
  {
    $accueil->add_paragraph("<b><center>".$propusr->get_html_link() . " souhaiterait faire partie du trajet pour le ".
            HumanReadableDate($step['date_etape'], "", false, true) .", et demande un passage via ".
            $villeetp->nom . ".</center></b><br/><br/>");
  }
  else
    $accueil->add_paragraph("<b><center>".$propusr->get_html_link() . " souhaiterait faire partie du trajet pour le ".
          HumanReadableDate($step['date_etape'], "", false, true) .".</center></b><br/><br/>");

  if (strlen($step['comments']) > 0)
  {
    $accueil->add_paragraph("L'utilisateur a laissé le commentaire suivant :<br/>" .
            "<div class=\"comment\">".
            doku2xhtml($step['comments']).
            "</div>");
  }

  if ($villeetp != NULL)
  {
    $accueil->add_paragraph("Ci-dessous un rendu du trajet en prenant en compte cette étape (la ville concernée apparaît en rouge) :");
    $trajet->load_steps();
    $fville = new ville($site->db);
    $fville->load_by_id($trajet->ville_depart->id);
    $tville = new ville($site->db);
    $tville->load_by_id($trajet->ville_arrivee->id);
    $etapes=array();
    $etapes[]=$fville;
    foreach($trajet->etapes as $etape)
    {
      if($etape['etat']==1)
      {
        $v = new ville($site->db);
        $v->load_by_id($etape['ville']);
        $etapes[]=$v;
      }
    }
    if(count($etapes)<24)
      $etapes[]=$tville;
    else
    {
      $etapes=array();
      $etapes[]=$fville;
      $etapes[]=$tville;
    }
    $map = new gmap("map");
    $map->add_geopoint_path('Chemin',$etapes);
    $accueil->add($map);
  }

  $accueil->add_paragraph("Cliquez sur les liens ci-dessous pour accepter ou refuser l'étape. Vous pouvez en outre prendre contact avec ".
        "l'utilisateur afin de vous arranger à l'amiable");

  $lnkaccept = "gerer.php?action=accept&id_trajet=".$trajet->id."&amp;date=".$step['date_etape']."&amp;id_etape=".$step['id'];
  $lnkrefuse = "gerer.php?action=refuse&id_trajet=".$trajet->id."&amp;date=".$step['date_etape']."&amp;id_etape=".$step['id'];
  $accueil->add_paragraph("<center><a href=\"".$lnkaccept."\">ACCEPTER</a> | <a href=\"".$lnkrefuse."\">REFUSER</a></center>");

  $site->add_contents($accueil);


  $site->end_page();

  exit();
}

/* infos sur le trajet */
$accueil->add_paragraph("Trajet ".$trajet->ville_depart->nom." / ".$trajet->ville_arrivee->nom);

$accueil->add_paragraph("<span style=\"float: right;\">".
      "<a href=\"./gerer.php?action=modcomm&id_trajet=". $trajet->id
      ."\">Modifier</a></span>");

$accueil->add_paragraph("Vous avez laissé le commentaire suivant :".
      "<div class=\"comment\">"
      .doku2xhtml($trajet->commentaires)."</div>");


/* évidemment, seul le responsable du trajet peut ajouter une date */
$accueil->add_title(2, "Dates du trajet");

if ($trajet->id_utilisateur == $site->user->id)
{
  if (isset($_REQUEST['add_date']))
  {
    $ret = $trajet->add_date($_REQUEST['date']);
    if ($ret)
    {
      $accueil->add_paragraph("<b>Date ajoutée avec succès.</b>");
      $trajet->load_dates();
    }
    else
      $accueil->add_paragraph("<b>Erreur lors de l'ajout de la date.</b>");
  }

  $accueil->add_paragraph("Vous pouvez ajouter une date à l'aide du formulaire ci-dessous");

  $frm = new form('trip_adddate', "gerer.php", true);
  $frm->add_hidden('id_trajet', $trajet->id);
  $frm->add_date_field('date', 'Date de voyage proposée');
  $frm->add_submit('add_date', 'Ajouter des dates de trajet');
  $accueil->add($frm);
}

if (count($trajet->dates))
{
  $accueil->add_paragraph("Ci-dessous la liste des dates de trajet actuellement renseignées :");

  $datetrj = array();

  foreach($trajet->dates as $date)
  {
    $datetrj[] = array("id"  => $date,
                       "dates" =>  "Le " . HumanReadableDate($date, "", false, true));
  }

  $lst = new sqltable("managedatestrj",
          "Dates du trajet enregistrées",
          $datetrj,
          "./gerer.php?id_trajet=".$trajet->id,
          "id",
          array("dates" => "Dates de trajet"),
          array("delete" => "Supprimer"),
          array("delete" => "Supprimer"));

  $accueil->add($lst);
}


$accueil->add_title(2, "Etapes acceptées");

$trajet->load_steps();

if (count($trajet->etapes))
{
  foreach ($trajet->etapes as $etape)
  {
    /* date de trajet supprimée */
    if (! in_array($etape['date_etape'], $trajet->dates))
    {
      $trajet->mark_as_deleted_step($etape['id'], $etape['date_etape']);
      continue;
    }

    if ($etape['ville'] > 0)
    {
      $obville = new ville($site->db);
      $obville->load_by_id($etape['ville']);
    }
    else
      $obville = NULL;

    $propuser = new utilisateur($site->db);
    $propuser->load_by_id($etape['id_utilisateur']);

    if ($etape['etat'] == STEP_ACCEPTED)
    {
      if ($obville != NULL)
      {
        $str = "Passage par <b>" . $obville->nom . "</b> suggéré par " .
               $propuser->get_html_link() . " le " . HumanReadableDate($etape['date_proposition'], "", true) .
               " pour le trajet du <b>" . HumanReadableDate($etape['date_etape'], "", false, true)."</b>";
      }
      else
        $str = $propuser->get_html_link() .
               " accepté pour le trajet du <b>" . HumanReadableDate($etape['date_etape'], "", false, true)."</b>";

      $accepted[] = $str;
    }

    /* en attente */
    else if ($etape['etat'] == STEP_WAITING)
    {
      if ($obville != NULL)
      {
        $str = "Passage par <b>" .
              $obville->nom . "</b> suggéré par " .
              $propuser->get_html_link() . " le " . HumanReadableDate($etape['date_proposition'], "", true) .
              " pour le trajet du <b>" . HumanReadableDate($etape['date_etape'], "", false, true).
              "</b> | <a href=\"./gerer.php?action=moderer&amp;id_trajet=".$trajet->id ."&amp;date_trajet=".
              $etape['date_etape']
              ."&amp;id_etape=".$etape['id']."\">Gérer la demande</a>";
      }
      else
      {
        $str = $propuser->get_html_link() . " en attente d'acceptation ".
               " pour le trajet du <b>" . HumanReadableDate($etape['date_etape'], "", false, true).
               "</b> | <a href=\"./gerer.php?action=moderer&amp;id_trajet=".$trajet->id ."&amp;date_trajet=".
               $etape['date_etape']
               ."&amp;id_etape=".$etape['id']."\">Gérer la demande</a>";
      }
      $proposed[] = $str;
    }
  }

}

if (count($accepted))
{
  $accueil->add(new itemlist(false, false, $accepted));
}
else
{
  $accueil->add_paragraph("<b>Aucune étape n'a encore été acceptée.</b>");
}

$accueil->add_title(2, "Etapes en attente d'acceptation");

if (count($proposed))
{
  $accueil->add(new itemlist(false, false, $proposed));
}
else
{
  $accueil->add_paragraph("<b>Aucune étape en attente de validation.</b>");
}

$site->add_contents($accueil);

$accueil = new contents("Récapitulatif des trajets par dates");

//$accueil->add_title(2, "Récapitulatif des trajets par dates");

if (count($trajet->dates))
{
  foreach ($trajet->dates as $date)
  {

    $idusers = $trajet->get_users_by_date($date);
    if ($idusers != false)
    {
      $accueil->add_title(3, "Trajet du ". HumanReadableDate($date, "", false, true));
      $trajet->load_steps();
      $fville = new ville($site->db);
      $fville->load_by_id($trajet->ville_depart->id);
      $tville = new ville($site->db);
      $tville->load_by_id($trajet->ville_arrivee->id);
      $etapes=array();
      $etapes[]=$fville;
      foreach($trajet->etapes as $etape)
      {
        if($etape['etat']==1)
        {
          $v = new ville($site->db);
          $v->load_by_id($etape['ville']);
          $etapes[]=$v;
        }
      }
      if(count($etapes)<24)
        $etapes[]=$tville;
      else
      {
        $etapes=array();
        $etapes[]=$fville;
        $etapes[]=$tville;
      }
      $map = new gmap("map");
      $map->add_geopoint_path('Chemin',$etapes);
      $accueil->add($map);

      $accueil->add_paragraph(count($idusers) . " utilisateur(s) intéressé(s) par le trajet");
      $passager = new utilisateur($site->db);

      $lstp = array();

      foreach ($idusers as $idusr)
      {
        $passager->load_by_id($idusr);
        $lstp[] = $passager->get_html_link();

      }
      $accueil->add(new itemlist(false, false, $lstp));
    }
  }
}

/* options */
$accueil->add_title(2, "Autres options");
$opts[] = "<a href=\"./\">Retour à la page d'accueil du covoiturage</a>";
$opts[] = "<a href=\"./propose.php\">Proposer un trajet</a>";
$opts[] = "<a href=\"./search.php\">Rechercher un trajet</a>";

$options = new itemlist(false, false, $opts);
$accueil->add($options);


$site->add_contents ($accueil);


/* fin page */
$site->end_page ();
?>
