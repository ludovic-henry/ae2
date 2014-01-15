<?php
/**
 * Copyright 2008
 * - Manuel Vonthron  <manuel DOT vonthron AT acadis DOT org>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/cts/selectbox.inc.php");
require_once("include/pedagogie.inc.php");
require_once("include/uv.inc.php");
require_once("include/pedag_user.inc.php");
require_once("include/cts/pedagogie.inc.php");
require_once("include/uv_parser.inc.php");

$site = new site();
$site->add_js("pedagogie/pedagogie.js");
$site->allow_only_logged_users("services");

$site->start_page("services", "AE Pédagogie");
$user = new pedag_user($site->db, $site->dbrw);
if(isset($_REQUEST['id_utilisateur']))
{
  $user->load_by_id($_REQUEST['id_utilisateur']);
  if(!$user->is_valid())
    $user->load_by_id($site->user->id);
}
else
  $user->load_by_id($site->user->id);

$path = "<a href=\"./\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
$path .= " / "."<a href=\"./edt.php\"><img src=\"".$topdir."images/icons/16/user.png\" class=\"icon\" /> Emploi du temps </a>";

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'new')
{
if(isset($_REQUEST['method']) && $_REQUEST['method'] == 'auto')
{

  if(isset( $_REQUEST['finaledtauto']) ) {
    $splt = split('_', htmlentities($_REQUEST['liste_uvs']));
    $semestre = htmlentities($_REQUEST['semestre']);

    while( list(, $txt) = each($splt) ) {
      $uvs = new UVparser($site->db, $semestre);
      $uvs->load_by_text($txt, true);

      $uv = new uv($site->db, $site->dbrw, $uvs->get_id_uv());
      if( !$uv->is_valid() )
        continue;

      $freq = htmlentities($_REQUEST[$txt]);

      if( preg_match('/^[A|B]$/', $freq) ) {
        $id_grp = $uvs->get_id_group();

        if( !$id_grp ) {
          list($type, $num, $freq, $sem, $day, $begin, $end, $room) = $uvs->get_info_add_group();
          $id_grp = $uv->add_group($type, $num, $freq, $sem, $day, $begin, $end, $room);
          if( !$id_grp )
            continue;
        }

        $user->join_uv_group( $id_grp, $freq );
      }
    }

    $site->redirect("edt.php?semestre=".$semestre."&action=view&id_utilisateur=".$user->id);

  } else if( isset($_REQUEST['newedtauto']) ) {

    $cts = new contents($path." / Finalisation");

    $semestre = htmlentities($_REQUEST['semestre']);

    if(in_array($semestre, $user->get_edt_list())) {
      if ($site->is_sure("", "Attention, vous avez déjà un emploi du temps
            d'enregistré pour le semestre <a href=\"edt.php?semestre=$semestre&action=view&id_utilisateur=".$user->id."\">$semestre</a>.
            Il n'est possible de n'en faire qu'un seul par semestre. Vous allez supprimer l'emploi du temps actuel."))
        $user->delete_edt($semestre);
      else
        $site->redirect('edt.php');
    }

    $uvs = new UVParser($site->db, $semestre);
    $uvs->load_by_text(htmlentities($_REQUEST['vrac']));

    $freq2_uvs = array();
    while ( $uvs->load_next() ) {
      // add user to group
      if( is_null($uvs->get_id_uv()) )
        continue;

      $uv = new uv($site->db, $site->dbrw, $uvs->get_id_uv());
      if( !$uv->is_valid() )
        continue;

      $id_grp = $uvs->get_id_group();
      if( $uvs->is_weekly() ) {

        if( is_null($id_grp) ) {
          list($type, $num, $freq, $sem, $day, $begin, $end, $room) = $uvs->get_info_add_group();
          $id_grp = $uv->add_group($type, $num, $freq, $sem, $day, $begin, $end, $room);
          if( !$id_grp )
            continue;
        }

        $user->join_uv_group( $id_grp );


      } else
        $freq2_uvs[$uvs->get_text()] = $uvs->get_nice_print();
    }

    if( empty($freq2_uvs) )
      $site->redirect("edt.php?semestre=".$semestre."&action=view&id_utilisateur=".$user->id);


    $cts->add_paragraph("<b>Pour finaliser votre emploi du temps, merci de renseigner quelques informations supplémentaires.</b>");

    $frm = new form('newedt', 'edt.php?action=new&method=auto', true, 'post', 'Finaliser l\'emploi du temps');

    $foo = '';
    while( list($txt, $msg) = each($freq2_uvs) ) {
      $frm->add_info($msg);
      $frm->add_select_field($txt, 'Choix de la semaine', array('A' => 'semaine A', 'B' => 'semaine B'));
      $foo .= empty($foo) ? $txt : '_'.$txt;
    }

    $frm->add_hidden('liste_uvs', $foo);
    $frm->add_hidden('semestre', $semestre);


    $frm->add_submit('finaledtauto', 'Finaliser cet emploi du temps');
    $cts->add($frm);

  } else {

    $cts = new contents($path.' / Ajouter un emploi du temps');

    $cts->add_paragraph('Vous pouvez ajouter ici un nouvel emploi du temps pour le site de l\'AE');
    $cts->add_paragraph('Notez que vous ne pouvez créer qu\'un emploi du temps par semestre,
        mais vous aurez la possibilité de l\'éditer.');
    $cts->add_paragraph('<b>/!\ Les emplois du temps des semestres antécédents au semestre d\'automne
        2011 ne sont pas supportés /!\</b>');

    $frm = new form('newedt', 'edt.php?action=new&method=auto', true, 'post', 'Ajouter un nouvel emploi du temps');

    $y = date('Y');
    $sem = array();
    for($i = $y-2; $i <= $y; $i++){
      $sem['P'.$i] = 'Printemps '.$i;
      $sem['A'.$i] = 'Automne '.$i;
    }
    $frm->add_select_field('semestre', 'Semestre concern&eacute;', $sem, SEMESTER_NOW, '', true);

    $frm->add_text_area('vrac', 'Mail du SME', '', 80, 25, true);

    $frm->add_submit('newedtauto', 'Enregistrer cet emploi du temps');
    $cts->add($frm);

  }

} else {

  /**
   * creation edt : etape 2 !
   */
  if(isset($_REQUEST['newedtstep1']))
  {
    if(!isset($_REQUEST['semestre'])  || empty($_REQUEST['semestre']) ||
        !isset($_REQUEST['uvlist_to']) || empty($_REQUEST['uvlist_to']))
      $site->redirect("edt.php?action=new");

    $path .= " / "."Ajouter un emploi du temps (Étape 2/2)";
    $cts = new contents($path);

    $sem = $_REQUEST['semestre'];

    /* on a dit un seul emploi du temps par semestre */
    if(in_array($sem, $user->get_edt_list()))
    {
      if ($site->is_sure("", "Attention, vous avez déjà un emploi du temps
        d'enregistré pour le semestre <a href=\"edt.php?semestre=$sem&action=view&id_utilisateur=".$user->id."\">$sem</a>.
        Il n'est possible de n'en faire qu'un seul par semestre. Vous allez supprimer l'emploi du temps actuel.")){
        $user->delete_edt($sem);
      }
      else
        $site->redirect('edt.php');
    }

    $cts->add_paragraph("Vous ajoutez un emploi du temps pour le semestre <b>$sem</b>");
    $cts->add_paragraph("Pour chacune de vos UV, choisissez à présent
    les séances auxquelles vous êtes inscrit, si la séance n'apparait pas
    dans la liste proposée, c'est que vous êtes le premier à l'entrer
    sur le site, cliquez alors sur \"Ajouter une séance manquante\" pour
    poursuivre.");

    $frm = new form("newedt", "edt.php?action=save", true, "post", "Ajouter un nouvel emploi du temps   (Étape 2/2)");
    $frm->add_hidden("semestre", $sem);

    foreach($_REQUEST['uvlist_to'] as $iduv){
      $uv = new uv($site->db, $site->dbrw, $iduv);
      if($uv->is_valid())
        $frm->add(new add_uv_edt_box($uv, $sem), false, false, false, false, false, true);
    }

    $frm->add_submit("newedtstep2", "Enregistrer l'emploi du temps");

    $cts->add($frm);
  }
  /**
   * sinon etape 1
   */
  else
  {
    $path .= " / "."Ajouter un emploi du temps (Étape 1/2)";
    $cts = new contents($path);

    $cts->add_paragraph("Vous pouvez ici créer d'un nouvel emploi du temps
    sur le site de l'AE.");
    $cts->add_paragraph("Choisissez pour commencer les UV auxquelles vous
    vous êtes inscrit (y compris les UV hors emploi du temps), le semestre
    concerné (par défaut il s'agit du semestre courant) et appuyez sur \"Passer
    à l'étape suivante\".");
    $cts->add_paragraph("Notez que vous ne pouvez créer qu'un emploi du
    temps par semestre, mais vous aurez la possibilité de l'éditer.");

    $frm = new form("newedt", "edt.php?action=new", true, "post", "Ajouter un nouvel emploi du temps   (Étape 1/2)");
    $frm->add_hidden("step", "1");

    $tab = array();
    foreach(uv::get_list($site->db) as $uv)
      $tab[ $uv['id_uv'] ] = $uv['code']." - ".stripslashes($uv['intitule']);

    $frm->add(new selectbox('uvlist', 'Choisissez les UV de ce nouvel emploi du temps', $tab, '', 'UV'));
    /* semestre */
    $y = date('Y');
    $sem = array();
    for($i = $y-2; $i <= $y; $i++){
      $sem['P'.$i] = 'Printemps '.$i;
      $sem['A'.$i] = 'Automne '.$i;
    }
    $frm->add_select_field("semestre", "Semestre concern&eacute;", $sem, SEMESTER_NOW);
    $frm->add_submit("newedtstep1", "Passer à l'étape suivante");
    $cts->add($frm);
  }

}
  $site->add_contents($cts);
  $site->end_page();
  exit;
}


/**
 * recuperation de la liste des seances d une UV pour affichage dans la creation des EDT/2
 *  ** appel ajax **
 */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'get_seances_as_options')
{
  $uv = new uv($site->db, $site->dbrw, $_REQUEST['id_uv']);
  if(!$uv->is_valid()) exit;

  $type = $_REQUEST['type'];
  $sem = $_REQUEST['semestre'];
  $groups = $uv->get_groups($type, $sem);

  $buffer = "      <option value=\"none\">S&eacute;lectionnez votre s&eacute;ance</option>\n";
  foreach($groups as $group){
    $buffer .= "      <option value=\"".$group['id_groupe']."\" onclick=\"edt.disp_freq_choice('".$uv->id."_".$type."', ".$group['freq'].", ".$uv->id.", ".$type.");\">"
                        .$_GROUP[$type]['long']." n°".$group['num_groupe']." du ".get_day($group['jour'])." de ".strftime("%H:%M", strtotime($group['debut']))." &agrave; ".strftime("%H:%M", strtotime($group['fin']))." en ".$group['salle']
                        ."</option>\n";
  }
  $buffer .= "      <option value=\"add\" style=\"font-weight: bold;\" onclick=\"edt.add_uv_seance(".$uv->id.", ".$type.", '".$sem."', '".$sel_id."');\">Ajouter une s&eacute;ance manquante...</option>\n";

  echo $buffer;
  exit;
}

/**
 * recuperation d'une boite de seance complete pour appel ajax
 * @see uv alias
 */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'get_uv_edt_box')
{
  $uv = new uv($site->db, $site->dbrw, $_REQUEST['id_uv']);
  if(!$uv->is_valid()) exit;

  if(isset($_REQUEST['semestre']) && check_semester_format($_REQUEST['semestre']))
    $sem = $_REQUEST['semestre'];
  else
    $sem = SEMESTER_NOW;

  $box = new add_uv_edt_box($uv, $sem);
  $buffer  = "<div class=\"formrow\" name=\"".$uv->code."_row\" id=\"".$uv->code."_row\">\n";
  $buffer .= "  <div class=\"fullrow\">\n";
  $buffer .= "    <div class=\"subformlabel\">\n";
  $buffer .= "      <a href=\"#\" onclick=\"on_off_icon('".$uv->code."','../'); return false;\"><img src=\"../images/fld.png\" alt=\"togle\" class=\"icon\" id=\"".$uv->code."_icon\" /> ".$uv->code." - ".$uv->intitule."</a>";
  $buffer .= "    </div>\n";
  $buffer .= "    <div class=\"subform\" id=\"".$uv->code."_contents\">\n";
  $buffer .= "      ".$box->buffer;
  $buffer .= "    </div>\n";
  $buffer .= "  </div>\n";
  $buffer .= "</div>\n";
  //echo $box->html_render();
  echo $buffer;
  exit;
}

/**
 * enregistrement effectif du nouvel emploi du temps
 */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'save')
{
  if(!isset($_REQUEST['newedtstep2']))
    $site->redirect('edt.php?action=new');

  if(!check_semester_format($_REQUEST['semestre']))
    $site->redirect('edt.php?action=new');
  else
    $semestre = $_REQUEST['semestre'];


  $freq = array(); //tableau des frequences envoyees
  $seances = array(); //tableau des seances
  foreach($_REQUEST as $arg=>$value){
    if(preg_match("/^freq/", $arg) && ($value == 'A' || $value == 'B')){
      list(, $uv, $type) = explode("_", $arg);
      $freq[$uv][$_GROUP[$type]['short']] = $value;
    }

    if(preg_match("/^seance/", $arg) && $value){
      list(, $uv, $type) = explode("_", $arg);
      $seances[$uv][$type] = $value;
    }
  }

  if(empty($seances))
    $site->redirect('edt.php?action=new');

  foreach($seances as $iduv=>$types){
    $uv = new uv($site->db, $site->dbrw, $iduv);
    if(!$uv->is_valid())
      continue;

    foreach($types as $type => $val){
      if($val == 'add' || $val == 'none')
        continue;

      if($type == 'the'){
        $sql = new requete($site->db, "SELECT `id_groupe` FROM `pedag_groupe` WHERE `id_uv` = $uv->id AND `type` = 'THE' AND semestre='".$semestre."'");
        if(!$sql->is_success())
          continue;
        if($sql->lines == 0)
          $idgroup = $uv->add_group(GROUP_THE, 1, 1, $semestre, 0, '00:00', '00:00');
        else{
          $row = $sql->get_row();
          $idgroup = $row[0];
        }

        $user->join_uv_group($idgroup);
      }
      else if($uv->has_group(intval($val), $type, $semestre)){
        if(isset($freq[$uv->id]) && isset($freq[$uv->id][$type]))
          $semaine = $freq[$uv->id][$type];
        else
          $semaine = null;

        $user->join_uv_group($val, $semaine);
      }
    }
  }

  $site->redirect("edt.php?semestre=".$semestre."&action=view&id_utilisateur=".$user->id);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete')
{
  if(!isset($_REQUEST['semestre']))
    $site->redirect('edt.php');

  /** confirmation anti boulets */
  if(isset($_REQUEST['sure']) && $_REQUEST['sure'] == 'yes')
  {
    $user->delete_edt($_REQUEST['semestre']);
    $site->redirect('edt.php');
  }
  else
  {
    $path .= " / "."Suppression emploi du temps ".$_REQUEST['semestre'];
    $cts = new contents($path);

    $cts->add_paragraph("<b>Vous vous apprêtez à supprimer l'emploi du temps
    du semestre ".$_REQUEST['semestre'].". Êtes vous absolument sûr ?</b>");

    $frm = new form("iwantit", "edt.php?action=delete", true, "post", "");
    $frm->add_hidden("semestre", $_REQUEST['semestre']);
    $frm->add_hidden("sure", "yes");
    $frm->add_submit("send", "Supprimer ".$_REQUEST['semestre']);
    $cts->add($frm);

    $cts->add_paragraph("<a href=\"edt.php\">Annuler</a>");

    $site->add_contents($cts);
    $site->end_page();
    exit;
  }

}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'print')
{
  if(isset($_REQUEST['semestre']) && check_semester_format($_REQUEST['semestre']))
    $semestre = $_REQUEST['semestre'];
  else
    $semestre = SEMESTER_NOW;

  if(!in_array($semestre, $user->get_edt_list()))
    $site->redirect('edt.php');

  require_once ("include/cts/edt_render.inc.php");

  $groups = $user->get_groups_detail($semestre);
  if(empty($groups))
    $site->redirect('edt.php');

  $lines = array();

  foreach($groups as $group){
    $lines[] = array(
                "semaine_seance" => $group['semaine'],
                "hr_deb_seance" => substr($group['debut'], 0,5),
                "hr_fin_seance" => substr($group['fin'], 0, 5),
                "jour_seance" => get_day($group['jour']),
                "type_seance" => $group['type'],
                "grp_seance" => $group['num_groupe'],
                "nom_uv" => $group['code'],
                "salle_seance" => $group['salle']
               );
  }

  $edt = new edt_img($user->get_display_name()." - ".$semestre, $lines);
  $edt->generate(false);
  exit;
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'view')
{
  $path .= " / ".$_REQUEST['semestre'];
  $cts = new contents($path);

  if(isset($_REQUEST['semestre']) && check_semester_format($_REQUEST['semestre']))
    $semestre = $_REQUEST['semestre'];
  else
    $semestre = SEMESTER_NOW;

  if(!in_array($semestre, $user->get_edt_list()))
    $site->redirect('edt.php');

  $uvs = $user->get_edt_detail($semestre);

  $cts->add(new sqltable("edtdetail", "Liste des UV pour ".$semestre, $uvs, "uv_groupe.php", 'id_uv',
                          array("code"=>"Code",
                                "intitule"=>"Intitulé",
                                "type"=>"Type",
                                "responsable"=>"Responsable"),
                          array(),array()), true);

  $url = "recherche_creneau.php?id_utilisateur[0]=".$site->user->id;
  if ($site->user->id != $user->id)
    $url .= "&id_utilisateur[1]=".$user->id;
  $cts->add_paragraph("<a href=\"".$url."\">Chercher les compatibilités d'emploi du temps</a>");

  $cts->add_title(3, "Version graphique");
  $cts->add_paragraph("<center><img src=\"edt.php?semestre=$semestre&action=print&id_utilisateur=".$user->id."\" alt=\"Emploi du temps ".$semestre."\" /></center>");
  $cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" "
                    ."value=\"Version graphique seule\" "
                    ."onclick=\"location.href='edt.php?semestre=$semestre&action=print&id_utilisateur=".$user->id."';\" />");
  $site->add_contents($cts);
  $site->end_page();
  exit;
}

/**
 * Export de l'emploi du temps au format iCal
 */
if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'schedule' || $_REQUEST['action'] == 'ical'))
{
  if(isset($_REQUEST['semestre']) && check_semester_format($_REQUEST['semestre']))
    $semestre = $_REQUEST['semestre'];
  else
    $semestre = SEMESTER_NOW;

  if(!in_array($semestre, $user->get_edt_list()))
    $site->redirect('edt.php');

  $groups = $user->get_groups_detail($semestre);
  if(empty($groups))
    $site->redirect('edt.php');

  $shortdays = array("MO", "TU", "WE", "TH", "FR", "SA", "SU");


  header("Content-Type: text/calendar; charset=utf-8");
  header("Content-Disposition: filename=edt.ics");

  echo "BEGIN:VCALENDAR\n";
  echo "VERSION:2.0\n";
  echo "PRODID:ae.utbm.fr\n";
  echo "X-WR-TIMEZONE:Europe/Paris\n";

  foreach($groups as $group)
  {
    if ($group['type'] == "THE")
      continue;

    $time_deb = mktime(substr($group['debut'], 0, 2), substr($group['debut'], 3, 2));
    $time_fin = mktime(substr($group['fin'], 0, 2), substr($group['fin'], 3, 2));

    /* automne : septembre -> mi janvier */
    if ($group['semestre'][0] == "A")
    {
      $start = substr($group['semestre'], 1)."0901T";
      $until = (substr($group['semestre'], 1) + 1)."0115T000000";
    }
    /* printemps : mi février -> fin juin */
    else
    {
      $start = substr($group['semestre'], 1)."0215T";
      $until = (substr($group['semestre'], 1))."0701T000000";
    }
    $start .= substr($group['debut'], 0, 2).substr($group['debut'], 3, 2)."00";

    echo "BEGIN:VEVENT\n";
    echo "DTSTART:".$start."\n";
    echo "EXDATE:".$start."\n";

    if ($group['type'] == "C")
      echo "SUMMARY:".$group['code'].": Cours\n";
    else
      echo "SUMMARY:".$group['code'].": ".$group['type']."\n";

    echo "LOCATION:".$group['salle']."\n";
    echo "DURATION:T".(($time_fin - $time_deb) / 3600)."H"
          .((($time_fin - $time_deb) / 60) % 60)."M\n";
    echo "RRULE:FREQ=WEEKLY;UNTIL=".$until.";WKST=MO;BYDAY="
          .$shortdays[$group['jour']-1]."\n";
    echo "END:VEVENT\n";
  }

  echo "END:VCALENDAR";

  exit;
}


if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit')
{
  $path .= " / "." Édition ".$_REQUEST['semestre'];
  $cts = new contents($path);

  $cts->add_paragraph("L'édition 'massive' de l'emploi du temps n'est pas
    encore disponible, cependant vous avez la possibilité de le corriger
    en vous rendant sur les UV concernées et en réglant les séances auxquelles
    vous êtes inscrit depuis l'onglet \"Séances et Élèves\".");

  $site->add_contents($cts);
  $site->end_page();
  exit;
}
/**
 * Contenu défaut page
 */

/* recap edt */
$cts = new contents($path);
$tab = array();
$edts = $user->get_edt_list();
if(!empty($edts))
{
  foreach($edts as $edt)
  {
    $tab[$edt]['semestre'] = $edt;
    $tab[$edt]['semestre_bold'] = "<b>".$edt."</b>";
    $i=0;
    foreach($user->get_edt_detail($edt) as $uv){
      $tab[$edt]['code_'.++$i] = $uv['code'];
      $tab[$edt]['id_uv_'.$i] = $uv['id_uv'];
    }
  }
}

if(count($tab) > 1)
  sort_by_semester($tab, 'semestre');

$cts->add(new sqltable("edtlist", "Liste des emplois du temps", $tab, "edt.php", 'semestre',
                        array("semestre_bold"=>"Semestre",
                              "code_1" => "UV 1",
                              "code_2" => "UV 2",
                              "code_3" => "UV 3",
                              "code_4" => "UV 4",
                              "code_5" => "UV 5",
                              "code_6" => "UV 6",
                              "code_7" => "UV 7"),
                        array("view" => "Voir détails",
                              "print" => "Format imprimable",
                              "schedule" => "Export iCal",
                              "edit" => "Éditer",
                              "delete" => "Supprimer"),
                        array(), array(), false), true);
$cts->add_paragraph("<a href=\"edt.php?action=new&method=auto\">+ Ajouter un emploi du temps</a>");
$cts->add_paragraph("<a href=\"edt.php?action=new\">+ Ajouter un emploi du temps manuellement</a>");
/*
$cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" "
                    ."value=\"+ Ajouter un emploi du temps\" "
                    ."onclick=\"edt.add();\" "
                    ."name=\"add_edt\" id=\"add_edt\"/>");
*/
$site->add_contents($cts);

$site->end_page();
?>
