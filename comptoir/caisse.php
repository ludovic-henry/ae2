<?php

/* Copyright 2009
 * - Mathieu Briand <briandmathieu CHEZ hyprua POINT org>
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
 * Interface permettant de réaliser le compte de l'argent présent dans les
 * caisses des bars
 *
 */

$topdir="../";
require_once("include/comptoirs.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir. "include/localisation.inc.php");

$site = new sitecomptoirs(true );
$site->start_page("services","Releves de caisses");

$caisse = new CaisseComptoir($site->db,$site->dbrw);


if (($_REQUEST['action'] == "view") && ($site->user->is_in_group("gestion_syscarteae")))
{
  $caisse->load_by_id($_REQUEST["id_cpt_caisse"]);
}
elseif (($_REQUEST['action'] == "newreleve") && $GLOBALS["svalid_call"])
{
  if (! $site->comptoir->ouvrir($_REQUEST["id_comptoir"]))
    $site->error_not_found("services");

  if (!$site->comptoir->is_valid())
    $site->error_not_found("services");

  if ($site->comptoir->type != 0)
    $site->error_forbidden("services","invalid");

  if (! $site->comptoir->rechargement)
    $site->error_forbidden("services","invalid");

  if ((get_localisation() != $site->comptoir->id_salle) && (! $site->user->is_in_group("gestion_syscarteae")))
    $site->error_forbidden("services","wrongplace");

  if ((count($site->comptoir->operateurs) == 0) && (! $site->user->is_in_group("gestion_syscarteae")))
  {
    $cts->add_paragraph("En attente de la connexion d'un barman");
  }
  else
  {
    if ( $site->user->is_in_group("gestion_syscarteae"))
      $user = $site->user;
    else
      $user = first($site->comptoir->operateurs);

    $especes = array();
    foreach ($_REQUEST["espece_nb"] as $val=>$nb)
      if (intval($nb) > 0)
        $especes[intval($val)] = intval($nb);

    $cheques = array();
    foreach ($_REQUEST["cheque_val"] as $i=>$val)
      if (intval($_REQUEST["cheque_nb"][$i]) > 0)
        $cheques[intval($val)] += intval($_REQUEST["cheque_nb"][$i]);

    $caisse_videe = false;
    if (($user->is_in_group("gestion_syscarteae")) && ($_REQUEST['caisse_videe']))
      $caisse_videe = true;

    $caisse->ajout($user->id, $site->comptoir->id, $especes, $cheques, $caisse_videe, $_REQUEST['comment']);
  }
}
elseif (($_REQUEST['action'] == "updatecomment") && $GLOBALS["svalid_call"])
{
  $caisse->load_by_id($_REQUEST["id_cpt_caisse"]);

  /* Si l'utilisateur n'est pas gestion_syscartae, on vérifie que le barman
   est le même que celui qui a créé le relevé */
  if (! $site->user->is_in_group("gestion_syscarteae"))
  {
    if (! $site->comptoir->ouvrir($_REQUEST["id_comptoir"]))
      $site->error_not_found("services");

    if (!$site->comptoir->is_valid())
      $site->error_not_found("services");

    if ($site->comptoir->type != 0)
      $site->error_forbidden("services","invalid");

    if (! $site->comptoir->rechargement)
      $site->error_forbidden("services","invalid");

    if (get_localisation() != $site->comptoir->id_salle)
      $site->error_forbidden("services","wrongplace");

    if (first($site->comptoir->operateurs)->id != $caisse->id_utilisateur)
      $site->error_forbidden("services","invalid");
  }

  $caisse->update_comment($_REQUEST['comment']);
}
elseif (($_REQUEST['action'] == "passagebanque") && ($site->user->is_in_group("gestion_syscarteae")))
  $caisse->passage_banque($_REQUEST['date_passage']);



if (in_array($_REQUEST['action'], array("view", "newreleve", "updatecomment")) && $caisse->is_valid())
{
  $req = new requete($site->db, "SELECT nom_cpt FROM cpt_comptoir WHERE id_comptoir = ".$caisse->id_comptoir);
  if ( $req->lines == 1 )
    $row = $req->get_row();

  $user = new utilisateur($site->db);
  $user->load_by_id($caisse->id_utilisateur);

  if ($site->user->is_in_group("gestion_syscarteae"))
    $cts = new contents("<a href=\"caisse.php\">Relevés</a> /
        <a href=\"caisse.php?id_comptoir=".$caisse->id_comptoir."\">".$row['nom_cpt']."</a> /
        ".date("d/m/Y H:i:s", $caisse->date_releve));
  else
    $cts = new contents();

  $tbl = new table("Releve effectué le ".date("d/m/Y H:i:s", $caisse->date_releve).", ".$row['nom_cpt']." par ".$user->get_html_link(), "sqltable");
  $tbl->add_row(array("Type", "Qté"), "head");
  foreach($caisse->especes as $valeur=>$nombre)
    $tbl->add_row(array("Espèce ".number_format($valeur/100, 2)." €", $nombre), "ln1");
  foreach($caisse->cheques as $valeur=>$nombre)
    $tbl->add_row(array("Chèques ".number_format($valeur/100, 2)." €", $nombre), "ln1");

  $cts->add($tbl,true);

  if ($caisse->caisse_videe)
    $cts->add_paragraph("La caisse a été vidée après ce relevé");

  $frm = new form ("updatecomment","caisse.php",true,"POST");
  $frm->add_hidden("action","updatecomment");
  $frm->add_hidden("id_cpt_caisse",$caisse->id);

  if ($site->comptoir->is_valid())
    $frm->add_hidden("id_comptoir",$site->comptoir->id);

  $frm->allow_only_one_usage();
  $frm->add_text_area("comment", "Commentaire", $caisse->commentaire, 60, 12);
  $frm->add_submit("valid","Modifier");
  $cts->add($frm,true);
}

elseif ($_REQUEST['action'] == "new")
{
  if (! $site->comptoir->ouvrir($_REQUEST["id_comptoir"]))
    $site->error_not_found("services");

  if (!$site->comptoir->is_valid())
    $site->error_not_found("services");

  if ($site->comptoir->type != 0)
    $site->error_forbidden("services","invalid");

  if (! $site->comptoir->rechargement)
    $site->error_forbidden("services","invalid");

  if ((get_localisation() != $site->comptoir->id_salle) && (! $site->user->is_in_group("gestion_syscarteae")))
    $site->error_forbidden("services","wrongplace");

  if (( count($site->comptoir->operateurs) == 0 ) && (! $site->user->is_in_group("gestion_syscarteae")))
  {
    $cts = new contents($site->comptoir->nom);
    $cts->add_paragraph("En attente de la connexion d'un barman");
  }
  else
  {
    if ( $site->user->is_in_group("gestion_syscarteae"))
      $user = $site->user;
    else
      $user = first($site->comptoir->operateurs);

    $cts = new contents("Nouveau releve de caisse");
    $frm = new form ("newreleve","caisse.php",true,"POST");
    $frm->add_hidden("action","newreleve");
    $frm->add_hidden("id_comptoir",$site->comptoir->id);
    $frm->allow_only_one_usage();

    $esp = array(
      10 => "Pièces de 10 centimes ",
      20 => "Pièces de 20 centimes ",
      50 => "Pièces de 50 centimes ",
      100 => "Pièces de 1 € ",
      200 => "Pièces de 2 € ",
      500 => "Billets de 5 € ",
      1000 => "Billets de 10 € ",
      2000 => "Billets de 20 € ",
      5000 => "Billets de 50 € ",
      10000 => "Billets de 100 € ",
    );

    foreach( $esp as $val => $txt)
    {
      /* On utilise des subform uniquement pour être en harmonie avec la suite... */
      $subfrm = new subform("espece[$val]");
      $subfrm->add_text_field("espece_nb[$val]", $txt, "",false, 5);
      $frm->addsub($subfrm, false, true);
    }

    for($i=0; $i<15; $i++)
    {
      $subfrm = new subform("cheque[".$i."]");
      $subfrm->add_price_field("cheque_val[".$i."]","Chèques de : ","",false, "€", 5);
      $subfrm->add_text_field("cheque_nb[".$i."]","Nombre de cheques : ","",false, 5);
      $frm->addsub($subfrm, false, true);
    }

    if ($user->is_in_group("gestion_syscarteae"))
    {
      $frm->add_checkbox("caisse_videe", "Caisse vidée");
    }

    $frm->add_text_area("comment", "Commentaire", "", 60, 12);

    $frm->add_submit("valid","Valider");
    $cts->add($frm,true);
  }
}
elseif ($site->user->is_in_group("gestion_syscarteae"))
{
  if (! isset($_REQUEST['id_comptoir']))
  {
    $req = new requete($site->db,"SELECT id_comptoir, nom_cpt
               FROM `cpt_comptoir`
               WHERE `rechargement`='1'");

    $comptoirs = array();
    while($row = $req->get_row())
    {
      $comptoirs[] = "<a href=\"caisse.php?id_comptoir=".$row['id_comptoir']."\">".$row['nom_cpt']."</a>";
    }
    $list = new itemlist("Comptoirs", false, $comptoirs);
    $site->add_contents($list);

    $cts = new contents("Releves de caisses");
  }
  else
  {
    $req = new requete($site->db,"SELECT id_comptoir, nom_cpt
               FROM `cpt_comptoir`
               WHERE `id_comptoir`='".$_REQUEST['id_comptoir']."'");
    $row = $req->get_row();
    $cts = new contents("<a href=\"caisse.php\">Relevés</a> /
        <a href=\"caisse.php?id_comptoir=".$row['id_comptoir']."\">".$row['nom_cpt']."</a>");
  }

  $req = new requete($site->db,"SELECT
            ROUND(SUM(IF(cheque_caisse='0', valeur_caisse*nombre_caisse, 0))/100, 2) as somme_especes,
            ROUND(SUM(IF(cheque_caisse='1', valeur_caisse*nombre_caisse, 0))/100, 2) as somme_cheques
            FROM `cpt_caisse`
            LEFT JOIN `cpt_caisse_sommes` USING (id_cpt_caisse)
            WHERE caisse_videe='1'
            AND date_releve > (
              SELECT date_passage
              FROM cpt_caisse_banque
              ORDER BY date_passage DESC
              LIMIT 1
            )");

  $row = $req->get_row();

  $cts->add_title(2,"Sommes théoriques présentes dans les caisses");

  $caisse = new requete($site->db, "SELECT `nom_cpt`, ROUND(SUM(`montant_rech`)/100,2) as `somme`
            FROM (
              SELECT DISTINCT `id_comptoir`, `nom_cpt`,  MAX(`date_releve`) `date_releve`, `caisse_videe`
              FROM (
                 SELECT DISTINCT `id_comptoir`,`nom_cpt`
                 FROM `cpt_comptoir`) comptoir
              INNER JOIN `cpt_caisse`
              USING (id_comptoir)
              WHERE `caisse_videe` = '1'
              GROUP BY `id_comptoir`) caisse
            INNER JOIN `cpt_rechargements`
            USING (id_comptoir)
            WHERE `date_releve` < `date_rech`
            GROUP BY `id_comptoir`");
  $liste = new itemlist();

  while(list($comptoir, $somme) = $caisse->get_row()) {
    $liste->add($comptoir." : ".$somme." euros");
  }

  $cts->add($liste);

  $cts->add_title(2,"Sommes à ammener à la banque");
  if (is_null($row['somme_especes']) && is_null($row['somme_cheques']))
    $cts->add_paragraph("Pas d'argent à ammener à la banque");
  else
    $cts->add_paragraph("Argent à ammener à la banque : ".$row['somme_especes']." € en espèce et ".
                        $row['somme_cheques']." € en chèques");

  $frm = new form ("passagebanque","");
  $frm->add_hidden("action","passagebanque");
  $frm->add_datetime_field("date_passage","Passage à la banque effectué le",time());
  $frm->add_submit("valid","valider");
  $cts->add($frm);

  $cts->add_title(2, "Relevés");

  if (! isset($_REQUEST['showall']))
  {
    if(isset($_REQUEST['id_comptoir']))
    {
      $cts->add_paragraph("<a href=\"caisse.php?id_comptoir=".$_REQUEST['id_comptoir']
          ."&amp;showall\">Afficher tous les relevés</a>");
      $cts->add_paragraph("<a href=\"caisse.php?id_comptoir=".$_REQUEST['id_comptoir']
          ."&amp;action=new\">Nouveau relevé</a>");
    }
    else
      $cts->add_paragraph("<a href=\"caisse.php?showall\">Afficher tous les relevés</a>");
  }

  $where = $limit = "";

  if ((isset($_REQUEST['id_comptoir'])) && (! isset($_REQUEST['showall'])))
  {
    $req = new requete($site->db,
      "SELECT MAX(`date_releve`) `date_releve`
        FROM `cpt_caisse`
        WHERE `id_comptoir`='".intval($_REQUEST['id_comptoir'])."'
        AND `caisse_videe` = '1'
      ");

    if ( $req->lines == 1 )
      $row = $req->get_row();
    else
      $row = array('date_releve' => 0);

    $req = new requete($site->db,
      "SELECT id_cpt_caisse, date_releve, releves.id_utilisateur,
        releves.id_comptoir, somme_especes, somme_cheques,
        nom_cpt, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`,
        ROUND(SUM(IF(type_paiement_rech='1', montant_rech, 0))/100, 2) as somme_especes_th,
        ROUND(SUM(IF(type_paiement_rech='0', montant_rech, 0))/100, 2) as somme_cheques_th
      FROM (
        SELECT id_cpt_caisse, date_releve, id_utilisateur, id_comptoir,
          ROUND(SUM(IF(cheque_caisse='0', valeur_caisse*nombre_caisse, 0))/100, 2) as somme_especes,
          ROUND(SUM(IF(cheque_caisse='1', valeur_caisse*nombre_caisse, 0))/100, 2) as somme_cheques
        FROM cpt_caisse
        LEFT JOIN cpt_caisse_sommes USING ( id_cpt_caisse )
        WHERE cpt_caisse.id_comptoir=".intval($_REQUEST['id_comptoir'])."
        AND cpt_caisse.date_releve > '".$row['date_releve']."'
        GROUP BY id_cpt_caisse
        ) releves
      INNER JOIN utilisateurs USING (id_utilisateur)
      INNER JOIN cpt_comptoir USING (id_comptoir)
      LEFT JOIN cpt_rechargements ON (
        cpt_rechargements.date_rech > '".$row['date_releve']."'
        AND cpt_rechargements.date_rech <= releves.date_releve
        AND cpt_rechargements.id_comptoir=releves.id_comptoir
      )
      GROUP by releves.id_cpt_caisse
      ORDER BY date_releve DESC
      ");

    $cts->add(new sqltable(
      "",
      "Releves", $req, "caisse.php",
      "id_cpt_caisse",
      array(
        "date_releve" => "Date du relevé",
        "nom_utilisateur" => "Vendeur",
        "nom_cpt" => "Lieu",
        "somme_especes" => "Total espèce",
        "somme_cheques" => "Total cheques",
        "somme_especes_th" => "Total théorique espèce",
        "somme_cheques_th" => "Total théorique cheques"),
      array("view" => "Voir le relevé"),
      array()
      ));
  }
  else
  {
    if (isset($_REQUEST['id_comptoir']))
      $where = "WHERE id_comptoir=".intval($_REQUEST['id_comptoir'])." ";
    elseif(! isset($_REQUEST['showall']))
      $limit = "LIMIT 100";

    $req = new requete($site->db,
      "SELECT id_cpt_caisse, date_releve, id_utilisateur, id_comptoir, nom_cpt,
        CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`,
        ROUND(SUM(IF(cheque_caisse='0', valeur_caisse*nombre_caisse, 0))/100, 2) as somme_especes,
        ROUND(SUM(IF(cheque_caisse='1', valeur_caisse*nombre_caisse, 0))/100, 2) as somme_cheques,
        IF(caisse_videe='1', 'Oui', '') as caisse_videe
      FROM `cpt_caisse` LEFT JOIN `cpt_caisse_sommes` USING(`id_cpt_caisse`)
      INNER JOIN `utilisateurs` USING(id_utilisateur)
      INNER JOIN `cpt_comptoir` USING(id_comptoir) " .
      $where
      ." GROUP BY id_cpt_caisse
      ORDER BY date_releve DESC
      $limit
      ");

    $cts->add(new sqltable(
    "",
    "Releves", $req, "caisse.php",
    "id_cpt_caisse",
    array(
      "date_releve" => "Date du relevé",
      "nom_utilisateur" => "Vendeur",
      "nom_cpt" => "Lieu",
      "somme_especes" => "Total espèce",
      "somme_cheques" => "Total cheques",
      "caisse_videe" => "Caisse videe"),
    array("view" => "Voir le relevé"),
    array()
    ));
  }
}
else
  $site->error_forbidden("services","invalid");

$site->add_contents($cts);
unset($cts);


if ($site->comptoir->is_valid())
{
  // Boite sur le coté
  $cts = new contents("Comptoir");

  $cts->add_paragraph("<a href=\"index.php\">Autre comptoirs</a>");

  if ($site->comptoir->rechargement)
    $cts->add_paragraph("<a href=\"caisse.php?action=new&id_comptoir=".$site->comptoir->id."\">Faire un relevé de caisse</a>");

  $lst = new itemlist();
  foreach( $site->comptoir->operateurs as $op )
    $lst->add(
      "<a href=\"comptoir.php?id_comptoir=".$site->comptoir->id."&amp;".
      "action=unlogoperateur&amp;id_operateur=".$op->id."\">". $op->prenom.
      " ".$op->nom."</a>");
  $cts->add($lst);

  $frm = new form ("logoperateur","comptoir.php?id_comptoir=".$site->comptoir->id);
  if ( $opErreur )
    $frm->error($opErreur);
  $frm->add_hidden("action","logoperateur");
  $frm->add_text_field("adresse_mail","Adresse email","prenom.nom@utbm.fr");
  $frm->add_text_field("code_bar_carte","Carte AE");
  $frm->add_password_field("password","Mot de passe");
  $frm->add_submit("valid","valider");
  $cts->add($frm);

  $site->add_box("comptoir",$cts);
  $site->set_side_boxes("right",array("comptoir"));
  unset($cts);
}
else
  $site->set_side_boxes("right",array());

$site->end_page();
?>
