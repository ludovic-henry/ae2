<?php
/** @file
 *
 * @brief Page de création d'emplois du temps
 *
 */

/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";

include($topdir. "include/site.inc.php");

require_once ($topdir . "include/entities/uv.inc.php");
require_once ($topdir . "include/entities/edt.inc.php");
require_once ($topdir . "include/cts/edt_img.inc.php");


$site = new site();

  $site->redirect("/pedagogie/");

$site->start_page("services", "Emploi du temps");

$cts = new contents("Pédagogie : Maintenance");
$cts->add_paragraph("La partie pédagogie est partiellement fermée pour une refonte complète.");
$cts->add_paragraph("Pour tout bug ou demande de fonctionnalité, contactez <a href=\"http://ae.utbm.fr/user.php?id_utilisateur=1956\">Gliss</a>.");
$site->add_contents($cts);

$site->end_page();
exit;

$edt = new edt($site->db, $site->dbrw);

if (!$site->user->utbm)
{
  $site->error_forbidden("services","reservedutbm");
}
if (!$site->user->is_valid())
{
  $site->error_forbidden("services");
}
/** STEP 3 : on enregistre les infos saisies */
if ($_REQUEST['step'] == 3)
{

  $cts = new contents("Traitement de l'emploi du temps", "");

  $semestre = (date("m") > 6 ? "A" : "P") . date("y");

  if(!empty($_REQUEST['uv']))
  {
    foreach($_REQUEST['uv'] as $uv => $value)
    {

      $req = new requete($site->db, "SELECT id_uv FROM edu_uv WHERE code_uv = '".mysql_real_escape_string($uv)."'");
      $rs = $req->get_row();

      $id_uv = $rs['id_uv'];
      if (isset($value['C']))
  {
    $cts->add_paragraph("<h4>$uv : Séance de cours</h4><br/>");
    $value['C']['freq'] == '1' ? $sem = 'AB' : $sem = $value['C']['semaine'];

    /* l'utilisateur a selectionné une séance de cours existante */
    if ($value['C']['selectlst'] > 0)
      {
        $edt->assign_etu_to_grp($site->user->id, $value['C']['selectlst'], $sem);
        $cts->add_paragraph("Séance ajoutée à l'emploi du temps avec succès !");
      }
    else
      {

        $ret = $edt->create_grp($id_uv,
              "C",
              $value['C']['numgrp'],
              $value['C']['hdeb'] . ":" . $value['C']['mdeb'].':00',
              $value['C']['hfin'] . ":" . $value['C']['mfin'].':00',
              $value['C']['jour'],
              $value['C']['freq'] == 0 ? 1 : $value['C']['freq'], /* une seance sera par défaut hebdomadaire */
              $semestre,
              $value['C']['salle']);

        if ($ret >=0)
    {
      $edt->assign_etu_to_grp($site->user->id, $ret, $sem);
      $cts->add_paragraph("Séance créée et ajoutée à l'emploi du temps avec succès !");
    }
      }
  }

      if (isset($value['TD']))
  {
    $cts->add_paragraph("<h4>$uv : Séance de TD</h4><br/>");
    $value['TD']['freq'] == '1' ? $sem = 'AB' : $sem = $value['TD']['semaine'];

    if ($value['TD']['selectlst'] > 0)
      {
        $edt->assign_etu_to_grp($site->user->id, $value['TD']['selectlst'], $sem);
        $cts->add_paragraph("Séance ajoutée à l'emploi du temps avec succès !");
      }

    else
      {

        $ret = $edt->create_grp($id_uv,
              "TD",
              $value['TD']['numgrp'],
              $value['TD']['hdeb'] . ":" . $value['TD']['mdeb'].':00',
              $value['TD']['hfin'] . ":" . $value['TD']['mfin'].':00',
              $value['TD']['jour'],
              $value['TD']['freq'],
              $semestre,
              $value['TD']['salle']);

        if ($ret >=0)
    {
      $edt->assign_etu_to_grp($site->user->id, $ret, $sem);
      $cts->add_paragraph("Séance créée et ajoutée à l'emploi du temps avec succès !");
    }
      }
  }

      if (isset($value['TP']))
  {
    $cts->add_paragraph("<h4>$uv : Séance de TP</h4><br/>");
    $value['TP']['freq'] == '1' ? $sem = 'AB' : $sem = $value['TP']['semaine'];


    if ($value['TP']['selectlst'] > 0)
      {
        $value['TP']['freq'] == '1' ? $sem = 'AB' : $sem = $value['TP']['semaine'];
        $edt->assign_etu_to_grp($site->user->id, $value['TP']['selectlst'], $sem);
        $cts->add_paragraph("Séance ajoutée à l'emploi du temps avec succès !");
      }

    else
      {
        $ret = $edt->create_grp($id_uv,
              "TP",
              $value['TP']['numgrp'],
              $value['TP']['hdeb'] . ":" . $value['TP']['mdeb'].':00',
              $value['TP']['hfin'] . ":" . $value['TP']['mfin'].':00',
              $value['TP']['jour'],
              $value['TP']['freq'],
              $semestre,
              $value['TP']['salle']);

        if ($ret >=0)
    {
      $edt->assign_etu_to_grp($site->user->id, $ret, $sem);
      $cts->add_paragraph("Séance créée et ajoutée à l'emploi du temps avec succès !");
    }
      }
  }
      }
    }

  $site->add_contents($cts);


  $site->add_contents(new contents("Emploi du temps graphique", "<center>".
           "<img src=\"./edt.php?render=1\" alt=\"Emploi du temps\" /></center>"));

  unset($_SESSION['edu_uv_subscr']);

  $site->end_page();
  exit();
}


/** STEP 2 : En fonction des formats horaires, on pond un formulaire de renseignement sur les séances */

if ($_REQUEST['step'] == 2)
{
  $cts = new contents("Renseignement sur les séances", "");
  if (count($_SESSION['edu_uv_subscr']) == 0)
    $cts->add_paragraph("Vous n'avez pas sélectionné d'UV à <a href=\"./create.php\">l'étape 1</a>".
      ". Merci de recommencer cette étape ".
      "avant de remplir les différentes informations sur les formats horaires");
  else
    {
      $lst = new itemlist("Liste des UVs", false, $_SESSION['edu_uv_subscr']);
      $cts->add($lst);

      $frm = new form('frm', 'create.php?step=3');

      $frm->puts(
     "<script language=\"javascript\">
function togglesellist(obj, uv, type)
{
  sellist = document.getElementsByName('uv[' +uv+ '][' +type+ '][semaine]')[0];

  if (obj.selectedIndex == '1')
  {
    sellist.parentNode.previousSibling.style.display = 'none';
    sellist.style.display = 'none';
  }
  else
  {
   sellist.parentNode.previousSibling.style.display = 'block';
   sellist.style.display = 'block';
  }
}

function toggleknownseance(obj, uv,type)
{
  ngrp =  document.getElementsByName('uv[' +uv+ '][' +type+ '][numgrp]')[0];
  jour =  document.getElementsByName('uv[' +uv+ '][' +type+ '][jour]')[0];
  hdeb =  document.getElementsByName('uv[' +uv+ '][' +type+ '][hdeb]')[0];
  mdeb =  document.getElementsByName('uv[' +uv+ '][' +type+ '][mdeb]')[0];
  hfin =  document.getElementsByName('uv[' +uv+ '][' +type+ '][hfin]')[0];
  mfin =  document.getElementsByName('uv[' +uv+ '][' +type+ '][mfin]')[0];
  freq =  document.getElementsByName('uv[' +uv+ '][' +type+ '][freq]')[0];
  sall =  document.getElementsByName('uv[' +uv+ '][' +type+ '][salle]')[0];


  if (obj.selectedIndex <= 0)
    {
      ngrp.parentNode.previousSibling.style.display = 'block';
      jour.parentNode.previousSibling.style.display = 'block';
      hdeb.parentNode.previousSibling.style.display = 'block';
      mdeb.parentNode.previousSibling.style.display = 'block';
      hfin.parentNode.previousSibling.style.display = 'block';
      mfin.parentNode.previousSibling.style.display = 'block';
      freq.parentNode.previousSibling.style.display = 'block';
      sall.parentNode.previousSibling.style.display = 'block';
      ngrp.style.display = 'block';
      jour.style.display = 'block';
      hdeb.style.display = 'block';
      mdeb.style.display = 'block';
      hfin.style.display = 'block';
      mfin.style.display = 'block';
      freq.style.display = 'block';
      sall.style.display = 'block';


    }
  else
    {
      ngrp.parentNode.previousSibling.style.display = 'none';
      jour.parentNode.previousSibling.style.display = 'none';
      hdeb.parentNode.previousSibling.style.display = 'none';
      mdeb.parentNode.previousSibling.style.display = 'none';
      hfin.parentNode.previousSibling.style.display = 'none';
      mfin.parentNode.previousSibling.style.display = 'none';
      freq.parentNode.previousSibling.style.display = 'none';
      sall.parentNode.previousSibling.style.display = 'none';

      ngrp.style.display = 'none';
      jour.style.display = 'none';
      hdeb.style.display = 'none';
      mdeb.style.display = 'none';
      hfin.style.display = 'none';
      mfin.style.display = 'none';
      freq.style.display = 'none';
      sall.style.display = 'none';
    }

}

</script>\n");

      global $jour;

      /* horaires */
      for ($i = 0; $i < 24; $i++)
  {
    $tmp = sprintf("%02d", $i);
    $hours[$tmp] = $tmp;
  }

      for ($i = 0; $i < 60; $i++)
  {
    $tmp = sprintf("%02d", $i);
    $minut[$tmp] = $tmp;
  }

  $semestre = (date("m") > 6 ? "A" : "P") . date("y");

      foreach($_SESSION['edu_uv_subscr'] as $uv)
  {
    $frm->puts("<h1>$uv</h1>");
    $req = new requete($site->db, "SELECT `cours_uv`, `td_uv`, `tp_uv`, `id_uv`
  FROM   `edu_uv`
  WHERE `code_uv` = '".mysql_real_escape_string($uv) . "'");
     $rs = $req->get_row();
    $c    = $rs['cours_uv'];
    $td   = $rs['td_uv'];
    $tp   = $rs['tp_uv'];
    $iduv = $rs['id_uv'];

    if (($c==0) && ($td == 0) && ($tp == 0))
      $frm->puts("<b>UV hors emploi du temps. En conséquence, elle n'apparaitra pas sur l'Emploi du temps.</b>");

    /* cours */
    if ($c == 1)
      {
        $frm->puts("<h2>Cours</h2>");

        $req = new requete($site->db,
        "SELECT `id_uv_groupe`, `numero_grp`, `jour_grp`, `heure_debut_grp`, `heure_fin_grp`
  FROM `edu_uv_groupe`
  WHERE `id_uv` = $iduv AND `type_grp` = 'C' AND `semestre_grp` = '".$semestre."'");

        if ($req->lines <= 0)
    $frm->puts("<p>Aucun groupe de cours connu pour cette UV. Vous êtes donc amené à ".
         "en renseigner les caractéristiques<br/></p>");
        else
    {
      $sccours = array(-1 => "--");
      while ($rs = $req->get_row())
        $sccours[$rs['id_uv_groupe']] = 'Cours N°'.$rs['numero_grp']." du ".
          $jour[$rs['jour_grp']] . " de ".$rs['heure_debut_grp']." à ".$rs['heure_fin_grp'];
      $frm->add_select_field("uv[$uv][C][selectlst]",
                                         'Séances de cours connues',
                                         $sccours,
                                         false,
                                         "", false, true,
                                         "javascript:toggleknownseance(this, '".$uv."', 'C')");
    }
        add_seance_form($frm, $uv, 'C');
      }

    /* td */
    if ($td == 1)
      {
        $frm->puts("<h2>TD</h2>");

        $req = new requete($site->db,
        "SELECT `id_uv_groupe`, `numero_grp`,  `jour_grp`, `heure_debut_grp`, `heure_fin_grp`
  FROM `edu_uv_groupe`
  WHERE `id_uv` = $iduv AND `type_grp` = 'TD' AND `semestre_grp` = '".$semestre."'");
        if ($req->lines <= 0)
    $frm->puts("<p>Aucun groupe de TD connu pour cette UV. Vous êtes donc amené à ".
            "en renseigner les caractéristiques<br/></p>");
        else
    {
      $sctd = array(-1 => "--");
      while ($rs = $req->get_row())
        $sctd[$rs['id_uv_groupe']] = 'TD N°'.$rs['numero_grp'] . " du ".
          $jour[$rs['jour_grp']] . " de ".$rs['heure_debut_grp']." à ".$rs['heure_fin_grp'];
      $frm->add_select_field("uv[$uv][TD][selectlst]",
                                         'Séances de TD connues',
                                         $sctd,
                                         false,
                                         "", false, true,
                                         "javascript:toggleknownseance(this, '".$uv."', 'TD')");
    }
        add_seance_form($frm, $uv, 'TD');
      }


    /* tp */
    if ($tp == 1)
      {
        $frm->puts("<h2>TP</h2>");
        $req = new requete($site->db,
        "SELECT `id_uv_groupe`, `numero_grp`,  `jour_grp`, `heure_debut_grp`, `heure_fin_grp`
  FROM `edu_uv_groupe`
  WHERE `id_uv` = $iduv AND `type_grp` = 'TP' AND `semestre_grp` = '".$semestre."'");
        if ($req->lines <= 0)
    $frm->puts("<p>Aucun groupe de TP connu pour cette UV. Vous êtes donc amené à ".
             "en renseigner les caractéristiques<br/></p>");
        else
    {
      $sctp = array(-1 => "--");
      while ($rs = $req->get_row())
        $sctp[$rs['id_uv_groupe']] = 'TP N°'.$rs['numero_grp']. " du ".
          $jour[$rs['jour_grp']] . " de ".$rs['heure_debut_grp']." à ".$rs['heure_fin_grp'];

                      $frm->add_select_field("uv[$uv][TP][selectlst]",
                                             'Séances de TP connues',
                                             $sctp,
                                             false,
                                             "", false, true,
                                             "javascript:toggleknownseance(this, '".$uv."', 'TP')");

    }
        add_seance_form($frm, $uv, 'TP');

      }
    $frm->puts("<br/>");
  } // fin foreach
      $frm->add_submit("step2_sbmt", "Envoyer");
      $cts->add($frm);
    } // else



  $site->add_contents($cts);

  $site->end_page();
  exit();
}

/** fonction affichant un formulaire de saisie */
function add_seance_form($formcts, $uv, $type)
{
  $formcts->puts("<h3>Ajout d'une séance horaire</h3>");

  /* numéro groupe */
  $formcts->add_text_field("uv[$uv][$type][numgrp]",
         'Numéro de groupe',
         '1', false, 1);
  /* jour */
  global $jour;
  $formcts->add_select_field("uv[$uv][$type][jour]",
           'jour',
           $jour);

  /* horaires debut / fin */
  global $hours, $minut;
  $formcts->add_select_field("uv[$uv][$type][hdeb]",
           'Heure de début', $hours);

  $formcts->add_select_field("uv[$uv][$type][mdeb]",
           'Minutes de début', $minut);


  $formcts->add_select_field("uv[$uv][$type][hfin]",
           'Heure de fin', $hours);

  $formcts->add_select_field("uv[$uv][$type][mfin]",
           'Minutes de fin', $minut);

  $formcts->add_select_field("uv[$uv][$type][freq]",
           'Fréquence',
           array("0" => "--",
                                   "1" => "Hebdomadaire",
           "2" => "Bimensuelle"),
           false,
           "",
           false,
           true,
           "javascript:togglesellist(this, '".$uv."', '".$type."')");

  $formcts->add_select_field("uv[$uv][$type][semaine]",
           'Semaine',
           array("AB" => "Toutes les semaines",
                                   "A" => "Semaine A",
           "B" => "Semaine B"));

  $formcts->add_text_field("uv[$uv][$type][salle]",
                           'salle <b>sans espace, ex : "P103")</b>',
                           "", false, 4);
}



/*** STEP 1 : étape initiale, choix des uvs, ajout et modification d'un format horaire */


if (isset($_REQUEST['emptylist']))
{
  unset($_SESSION['edu_uv_subscr']);
  exit();
}

if (isset($_REQUEST['modform']))
{
  $uv = intval($_REQUEST['iduv']);

  if ($uv <= 0)
    exit();

  $rq = new requete($site->db,
        "SELECT
                                `code_uv`
                                , `cours_uv`
                                , `td_uv`
                                , `tp_uv`
                     FROM
                                `edu_uv`
                     WHERE
                                `id_uv` = " . $uv);
  $res = $rq->get_row();

  ($res['cours_uv'] == 1) ?  $cours = true : $cours = false;
  ($res['td_uv'] == 1)    ? $td = true    : $td = false;
  ($res['tp_uv'] == 1)    ? $tp = true    : $tp = false;

  echo "<h1>Modification d'UV</h1>";
  echo "<p>A l'aide de ce formulaire, vous pouvez modifier le format horaire de l'UV ".$res['code_uv']."</p>";

  $moduv = new form("moduv",
        "create.php",
        false,
        "post",
        "Modification d'une UV");
  $moduv->add_hidden('modifyuv', 1);
  $moduv->add_hidden('mod_iduv', $uv);
  $moduv->add_checkbox('mod_cours', 'Cours', $cours);
  $moduv->add_checkbox('mod_td', 'TD', $td);
  $moduv->add_checkbox('mod_tp', 'TP', $tp);

  $moduv->add_submit('moduv_sbmt', 'Modifier le format');

  echo $moduv->html_render();

  exit();

}

/* modification "basique" (format horaire) d'une uv, le reste ne peut
 etre fait que par les personnes accréditées (gestion-ae ou autres) */

if (isset($_REQUEST['modifyuv']))
{

  ($_REQUEST['mod_cours'] == 1) ? $c = 1 : $c = 0;
  ($_REQUEST['mod_td']    == 1) ? $td = 1 : $td = 0;
  ($_REQUEST['mod_tp']    == 1) ? $tp = 1 : $tp = 0;

  $uv = intval($_REQUEST['mod_iduv']);

  $rq = new update($site->dbrw,
       'edu_uv',
       array ('cours_uv' => $c,
        'td_uv' => $td,
        'tp_uv' => $tp),
       array ('id_uv' => $uv));

  if ($rq->lines  == 1)
    $retmod = true;
  else
    $retmod = false;
}


/* l'utilisateur a demandé l'inscription à une UV */
if (isset($_REQUEST['subscr']))
{
  $uv = $_REQUEST['subscr'];

  if (! array_key_exists($uv, $_SESSION['edu_uv_subscr']))
    {
      $rq = new requete($site->db,
                            "SELECT
                                `id_uv`
                                , `code_uv`
                             FROM
                                `edu_uv`
                             WHERE
                                `id_uv` = " . intval($uv));
      $res = $rq->get_row();

      if ($res['cours_uv'] == 1)
        $format_h[] = "Cours";
      if ($res['td_uv'] == 1)
        $format_h[] =  "TD";
      if ($res['tp_uv'] == 1)
        $format_h[] = "TP";

      if (count($format_h) == 0)
        $format_h = "HET";
      else
        $format_h = implode(" / ", $format_h);

      $_SESSION['edu_uv_subscr'][$uv] = $res['code_uv'];
    }
  exit();
}

if (isset($_REQUEST['refreshlistuv']))
{
  echo "<h1>Liste des UVs dans lesquelles vous êtes inscrit</h1>\n";

  if (is_array($_SESSION['edu_uv_subscr']))
    {

      echo "<ul>\n";

      foreach($_SESSION['edu_uv_subscr'] as $key => $value)
  {
    echo "<li>".$value."</li>\n";
  }
      echo "</ul>\n";

      echo "<p><b>passer à ".
  "<a href=\"./create.php?step=2\">la deuxième étape</a></b>\n";
    }
  else
    echo "<b>Vous n'avez pour l'instant selectionné aucune UV.</b>";


  exit();
}


/** real code begins here */

$_SESSION['edu_uv_subscr'] = array();


/* juste verifier que l'utilisateur ne tente pas
 * de rentrer un nouvel emploi du temps ...
 */
$semestre = (date("m") > 6 ? "A" : "P") . date("y");

$edt->load_by_etu_semestre($site->user->id, $semestre);

$cts = new contents("Emploi du temps",
        "Sur cette page, vous allez pouvoir ".
        "créer votre emploi du temps.");
if (count($edt->edt_arr) > 0)
{
  $cts->add_paragraph("Il semblerait que vous ayez déjà saisi votre
                       emploi du temps du semestre.<br/><br/>
                       Peut-être souhaitez-vous simplement <a href=\"".
  $topdir."uvs/edit.php?semestre=".$semestre."\">l'éditer</a> ?");

  $site->add_contents($cts);
  $site->end_page();

  exit();
}



if (isset($retmod))
{
  if ($retmod == true)
    {
      $cts->add_title(1, "Modification d'UV");
      $cts->add_paragraph("Le format horaire a été modifié avec succès.");
    }
  else
    {
      $cts->add_title(1, "Modification d'UV");
      $cts->add_paragraph("<b>Erreur lors de la modification du format horaire.</b>");
    }
}

if (isset($creationuv))
{
  if ($creationuv == true)
    {
      $cts->add_title(1, "Création d'UV");
      $cts->add_paragraph("L'UV a été créée avec succès.");
    }
  else
    {
      $cts->add_title(1, "Création d'UV");
      $cts->add_paragraph("<b>Erreur lors de la création de l'UV.</b>");
    }
}

$cts->add_title(2, "Sélection des UVs");

$selectuv = new form("seluv", "create.php", true, "post", "Sélection des  UVs");

$rq = new requete($site->db,
      "SELECT
                            `id_uv`
                            , `code_uv`
                            , `intitule_uv`
                            , `cours_uv`
                            , `td_uv`
                            , `tp_uv`
                   FROM
                            `edu_uv`
                   ORDER BY
                            `code_uv`");

if ($rq->lines > 0)
{
  while ($rs = $rq->get_row())
    {
      $format_h = array();
      if ($rs['cours_uv'] == 1)
  $format_h[] = "Cours";
      if ($rs['td_uv'] == 1)
  $format_h[] =  "TD";
      if ($rs['tp_uv'] == 1)
  $format_h[] = "TP";
      if (count($format_h) == 0)
  $format_h = "HET";
      else
  $format_h = implode(" / ", $format_h);

      $uvs[$rs['id_uv']] = $rs['code_uv'] . " - " . $rs['intitule_uv'] . " - " . $format_h;
    }

  /* javascript code begins here ! */

  $js =
    "
<script language=\"javascript\">
function addUV(obj)
{
 selected = document.getElementsByName('uv_sl')[0];
 evalCommand('create.php', 'subscr=' + selected.value);
 openInContents('cts2', 'create.php', 'refreshlistuv');
}

function emptylistuv()
{
  evalCommand('create.php', 'emptylist');
  openInContents('cts2', 'create.php', 'refreshlistuv');
}

function modifyuv()
{
  mod_iduv  = document.getElementsByName('mod_iduv')[0].value;
  mod_cours = document.getElementsByName('magicform[boolean][mod_cours]')[0].checked;
  mod_td    = document.getElementsByName('magicform[boolean][mod_td]')[0].checked;
  mod_tp    = document.getElementsByName('magicform[boolean][mod_tp]')[0].checked;
  alert(mod_cours + mod_td + mod_tp);

  evalCommand('create.php', 'modifyuv=1&mod_cours='+mod_cours+'&mod_td='+mod_td+'&mod_tp='+mod_tp);
  openInContents('cts3','create.php' ,'modifyuv=1&iduv='+mod_iduv+'&mod_cours='+mod_cours+'&mod_td='+mod_td+'&mod_tp='+mod_tp);

}
function updatemodifpanel()
{
  selected = document.getElementsByName('uv_sl')[0].value;
  moduv = document.getElementById('cts3');
  openInContents('cts3', 'create.php', 'modform=1&iduv='+selected);
  moduv.style.display = 'block';

}
</script>\n";

  $selectuv->puts($js);
  $selectuv->add_select_field('uv_sl', "UV", $uvs);
  $selectuv->add_button("adduv_existing", "Ajouter l'UV à la liste", "javascript:addUV(parent)");
  $selectuv->add_button("emptylist", "Réinitialiser la liste", "javascript:emptylistuv()");
  $selectuv->add_button("reqmodiffmth", "Modifier le format horaire", "javascript:updatemodifpanel()");

}
$cts->add($selectuv);

$cts->add_paragraph("Une fois la liste des UVs suivies renseignées, <b>vous pouvez passer à ".
"<a href=\"./create.php?step=2\">la deuxième étape</a></b>");


$site->add_contents($cts);

$uvs = "";



if (is_array($_SESSION['edu_uv_subscr']))
    {

      $uvs .= "<ul>\n";

      foreach($_SESSION['edu_uv_subscr'] as $key => $value)
  {
    $uvs .= "<li>".$value."</li>\n";
  }
      $uvs .= "</ul>\n";
    }

else
    $uvs .= "<b>Vous n'avez pour l'instant selectionné aucune UV.</b>";

$site->add_contents(new contents('Liste des UVs dans lesquelles vous êtes '.
                        'inscrit',$uvs));

$cts = new contents("Modification d'UV","");
$cts->puts("<script language=\"javascript\">
document.getElementById('cts3').style.display = 'none';
</script>");

$cts->add_title(2, "Modification d'UV");
$cts->add_paragraph("A l'aide de ce formulaire, vous pouvez ".
                    "modifier le format horaire d'une UV");

$moduv = new form("moduv",
                  "create.php",
                  true,
                  "post",
                  "Modification d'une UV");

$moduv->add_hidden('mod_iduv', -1);
$moduv->add_checkbox('mod_cours', 'Cours', true);
$moduv->add_checkbox('mod_td', 'TD', true);
$moduv->add_checkbox('mod_tp', 'TP', true);

$moduv->add_button('moduv', 'Modifier le format', 'javascript:modifyuv();');

$cts->add($moduv);


$site->add_contents($cts);


$site->end_page();

?>
