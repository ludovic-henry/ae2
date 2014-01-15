<?
/* Copyright 2007
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Softwareus
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */


$topdir = "../";

require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/cts/selectbox.inc.php");
require_once("include/pedagogie.inc.php");
require_once("include/uv.inc.php");
require_once("include/pedag_user.inc.php");
require_once("include/cts/pedagogie.inc.php");

$site = new site();
$site->allow_only_logged_users("services");
$site->add_js("pedagogie/pedagogie.js");
$site->add_css("css/pedagogie.css");
$site->start_page("services", "AE Pédagogie");

$uv = new uv($site->db, $site->dbrw);
if(isset($_REQUEST['id_groupe']) && !empty($_REQUEST['id_groupe'])){
  $uv->load_by_group_id($_REQUEST['id_groupe']);
  $groupid = $_REQUEST['id_groupe'];
}else if(isset($_REQUEST['id'])){
  $uv->load_by_group_id($_REQUEST['id']);
  $groupid = $_REQUEST['id'];
}else if(!isset($_REQUEST['action'])){
  $site->redirect("uv.php");
}

if(isset($_REQUEST['id_uv'])){
  $uv->load_by_id($_REQUEST['id_uv']);
}

if(isset($_REQUEST['semestre']) && check_semester_format($_REQUEST['semestre']))
  $semestre = $_REQUEST['semestre'];
else
  $semestre = SEMESTER_NOW;


$user = new pedag_user($site->db, $site->dbrw);
$user->load_by_id($site->user->id);

/* ouais enfin c'est mieux si l'UV existe */
if(!$uv->is_valid())
  $site->redirect("uv.php");

$path = "<a href=\"./\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
$path .= " / "."<a href=\"./uv.php?id=$uv->id&view=suivi\"><img src=\"".$topdir."images/icons/16/emprunt.png\" class=\"icon\" /> $uv->code</a>";
$path .= " / "."Séances";

$cts = new contents($path);

/***********************************************************************
 * Actions
 */

/* ajout/modification effectif des actions ajouts/editions */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'save')
{
  $id_groupe = $_REQUEST['id_groupe'];
  $type = $_REQUEST['type'];
  $num = $_REQUEST['num'];
  $freq = $_REQUEST['freq'];
  $semestre = $_REQUEST['semestre'];
  $jour = $_REQUEST['jour'];
  $debut = $_REQUEST['hdebut'].":".$_REQUEST['mdebut'];
  $fin = $_REQUEST['hfin'].":".$_REQUEST['mfin'];
  $salle = strtoupper($_REQUEST['salle']);

  /* edition d'un groupe */
  if(isset($_REQUEST['editmode'])){
    $r = $uv->update_group($id_groupe, $type, $num, $freq, $semestre, $jour, $debut, $fin, $salle);
    if($r)
      $site->redirect("uv_groupe.php?id=".$id_groupe."&action=view");
  /* ajout d'un nouveau groupe */
  }else{
    /* le groupe en question existe deja banane */
    if($uv->search_group($num, $type, $semestre)){
      $cts->add_paragraph("Le groupe de ".$_GROUP[ $type ]['long']." n°".$num." existe déjà pour ".$uv->code." !");
      $cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" value=\"Revenir en arrière\" onclick=\"history.go(-1);\" />");
    /* OK on enregistre */
    }else{
      $id_groupe =  $uv->add_group($type, $num, $freq, $semestre, $jour, $debut, $fin, $salle);
      if($id_groupe < 0)
        $site->redirect("uv.php?id=".$uv->id);

      if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'popup'){
        $sel_id = "seance_".$uv->id."_".$_GROUP[$type]['short'];
        $cts->add_paragraph("Votre séance de ".$_GROUP[$type]['long']." de ".$uv->code." du ".get_day($jour)." à bien été modifiée.");
        $cts->add_paragraph("Merci de votre participation.");
        $cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" value=\"Continuer\" onclick=\"window.opener.openInContents('$sel_id', 'edt.php', 'action=get_seances_as_options&id_uv=$uv->id&type=$type&semestre=$semestre'); self.close();\"/>");
      }else
        $site->redirect("uv_groupe.php?id=".$id_groupe."&action=view");
    }
  }
}

/* inscription d'un utilisateur a une seance (nom 'done' choisi pour l'icone uniquement */
if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'join' || $_REQUEST['action'] == 'done'))
{
  if(isset($_REQUEST['freq']) && ($_REQUEST['freq'] == 'A' || $_REQUEST['freq'] == 'B'))
    $freq = $_REQUEST['freq'];
  else
    $freq = null;

  if(!$user->is_attending_uv_group($groupid))
    $user->join_uv_group($groupid, $freq);
  $site->redirect("uv_groupe.php?id=$groupid&action=view");
}

/* inscription d'un utilisateur a une seance (nom 'done' choisi pour l'icone uniquement */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'leave')
{
  if($user->is_attending_uv_group($groupid))
    $user->leave_uv_group($groupid);
  $site->redirect("uv_groupe.php?id=$groupid&action=view");
}
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'change_week')
{
  $details = $uv->get_groups(null, null, $groupid, $user->id);
  $user->leave_uv_group($groupid);
  if ($details[0]['semaine'] == 'A')
    $user->join_uv_group($groupid, 'B');
  else
    $user->join_uv_group($groupid, 'A');
  $site->redirect("uv_groupe.php?id=".$groupid."&action=view");
}

/* ajout d'une nouvelle séance */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'new')
{
  /** formulaire d ajout */
  if(isset($_REQUEST['type']))  $type = $_REQUEST['type'];
  else                          $type = null;

  if(isset($_REQUEST['semestre']))  $semestre = $_REQUEST['semestre'];
  else                              $semestre = SEMESTER_NOW;

  $cts->add(new add_seance_box($uv->id, $type, $semestre), false, false, "seance_".$uv->code, "popup_add_seance");
}

/* modification d'une séance existante */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit')
{
  /* normalement ne peut pas echouer maintenant */
  $sql = new requete($site->db, "SELECT *, `type`+0 as `type` FROM `pedag_groupe` WHERE `id_groupe` = ".intval($groupid));
  $data = $sql->get_row();

  $cts->add(new add_seance_box($uv->id, $data['type'], $data['semestre'], $data), false, false, "seance_".$uv->code, "popup_add_seance");
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete')
{
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'view')
{
  if(!isset($groupid))
    $site->redirect("uv.php");

  /***********************************************************************
   * Affichage detail groupe
   */
  $details = $uv->get_groups(null, null, $groupid);
  $details = $details[0]; //une seule ligne dtf
    $type = $_GROUP[ $details['type_num'] ]['long'];
    $jour = get_day($details['jour']);
    $debut = strftime("%H:%M", strtotime($details['debut']));
    $fin = strftime("%H:%M", strtotime($details['fin']));
    $freq = ($details['freq'] != 2)?"Toutes les semaines":"Une semaine sur deux";

  $cts->add_paragraph("<b>Séance de ".$type." de ".$uv->code." du ".$jour." de ".$debut." à ".$fin." en ".$details['salle']."</b>.");
  $cts->add_paragraph("Fréquence : ".$freq.".");

  $sql = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`,
                                  CONCAT(`prenom_utl`,' ',`nom_utl`) AS `nom_utilisateur`, `semaine`
                                  FROM `pedag_groupe_utl`
                                  LEFT JOIN `utilisateurs`
                                    ON `pedag_groupe_utl`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`
                                  WHERE `id_groupe` = ".intval($groupid)."
                                  ORDER BY `utilisateurs`.`nom_utl`");
  if(!$sql->is_success())
    $site->redirect("uv.php");

  $cts->add(new sqltable("seance_utl", "Élèves inscrits ce semestre", $sql, "", 'id_utilisateur',
                         array("nom_utilisateur"=>"Élève", "semaine"=>"Semaine"),
                         array(), array()), true);
  if($user->is_attending_uv_group($groupid))
  {
    $cts->add_paragraph("<input type=\"button\" onclick=\"location.href='uv_groupe.php?action=leave&id=$groupid';\" value=\"Se désinscrire\"/>");
    if($details['freq'] == 2)
      $cts->add_paragraph("<input type=\"button\" onclick=\"location.href='uv_groupe.php?action=change_week&id_groupe=$groupid&semestre=$semestre';\" value=\"Changer de semaine\"/>");
  }
  else{
    if($details['freq'] == 2){
      $buf = "<input type=\"button\" onclick=\"var f = document.getElementById('freq').value; location.href='uv_groupe.php?action=join&id=$groupid&freq='+f;\" value=\"S'inscrire\"/>";
      $buf .= "<select name=\"freq\" id=\"freq\">
                <option value=\"A\">Semaine A</option>
                <option value=\"B\">Semaine B</option>
              </select>";
    }else{
      $buf = "<input type=\"button\" onclick=\"location.href='uv_groupe.php?action=join&id=$groupid';\" value=\"S'inscrire\"/>";
    }
    $cts->add_paragraph($buf);
  }


  $cts->puts("<input type=\"button\" onclick=\"location.href='uv_groupe.php?action=edit&id=$groupid';\" value=\"Corriger la séance\" style=\"float:right;\"/>");
}

$site->add_contents($cts);

if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'popup')
  $site->popup_end_page();
else
  $site->end_page();
?>
