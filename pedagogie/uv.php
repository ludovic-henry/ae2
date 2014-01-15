<?
/* Copyright 2007,2010
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Mathieu Briand <briandmathieu AT hyprua DOT org>
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

$path = "<a href=\"./\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";

$admin = $site->user->is_in_group("gestion_ae");

/* compatibilite sqltable bleh */
if(isset($_REQUEST['id_uv']))
  $_REQUEST['id'] = $_REQUEST['id_uv'];

/***********************************************************************
 * Actions
 */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'save')
{
  $uv = new uv($site->db, $site->dbrw);
  $is_admin = $site->user->is_in_group("pedag_admin");

  /**
   * nouvelle UV
   */
  if($_REQUEST['magicform']['name']=='newuv'){
    $uv->add($_REQUEST['code'], $_REQUEST['intitule'], $_REQUEST['type'], $_REQUEST['responsable'], $_REQUEST['semestre'], $_REQUEST['tc_avail']);

    if(!$uv->is_valid())
      exit; //ouais faudra trouver mieux :)

    if(isset($_REQUEST['alias_of']) && !empty($_REQUEST['alias_of']))
      $uv->set_alias_of($_REQUEST['alias_of']);

    $site->redirect("./uv.php?id=".$uv->id."&action=edit#guide");
  }

  $uv->load_by_id(intval($_REQUEST['id']));
  if(!$uv->is_valid()){
    exit;
  }

  //  $site->redirect('uv.php');
  /**
   * edition UV
   */
  if($_REQUEST['magicform']['name']=='editmain'){   /* informations principales */
    $uv->update(($is_admin?$_REQUEST['code']:$uv->code),
                $_REQUEST['intitule'],
                $_REQUEST['type'],
                $_REQUEST['responsable'],
                $_REQUEST['semestre'],
                isset($_REQUEST['tc_available']) && $_REQUEST['tc_available']);

    $site->redirect('uv.php?id='.$uv->id);
  }

  if($_REQUEST['magicform']['name']=='editextra'){  /* infos guide des UV */
    $uv->update_guide_infos($_REQUEST['objectifs'],
                            $_REQUEST['programme'],
                            $_REQUEST['c'],
                            $_REQUEST['td'],
                            $_REQUEST['tp'],
                            $_REQUEST['the'],
                            $_REQUEST['credits']);

    $site->redirect('uv.php?id='.$uv->id);
  }

  if($_REQUEST['magicform']['name']=='editrelative'){ /* infos relatives */

    /* enregistrement departements */
    if(isset($_REQUEST['dept_to']) && !empty($_REQUEST['dept_to'])){
      $del = array_diff($uv->get_dept_list(), $_REQUEST['dept_to']);
      $add = array_diff($_REQUEST['dept_to'], $uv->get_dept_list());
    }else{
      $del = $uv->get_dept_list();
      $add = array();
    }
    foreach($del as $d)
      $uv->remove_from_dept($d);
    foreach($add as $a)
      $uv->add_to_dept($a);

    /* enregistrement alias */
    if($_REQUEST['alias_of'] == 0){ /* pas d'alias */
      if($uv->is_alias())
        $uv->unset_alias_of();
    }else if($_REQUEST['alias_of'] != $uv->is_alias()){ /* maj */
      $uv->unset_alias_of();
      $uv->set_alias_of($_REQUEST['alias_of']);
    }
  }

}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'new')
{
  $path .= " / "."Ajouter une UV";
  $cts = new contents($path);
  $cts->add_paragraph("Vous pouvez ici ajouter une UV au guide des UV. Bien
  que quelques vérifications automatiques seront faites, nous vous incitons
  à bien vous assurer que la fiche n'existe pas déjà. De plus, nous vous
  demandons de n'ajouter *que* des UV réelles de l'UTBM, et non pas vos
  permanences de foyer, etc (utilisez pour cela le planning à cet effet).
  Merci de votre participation !");

  $frm = new form("newuv", "uv.php?action=save", true, "post");
  $frm->add_text_field("code", "Code", "", true, 4, false, true, "(format XX00)");
  $frm->add_text_field("intitule", "Intitulé", "", true,40,false,true,null,false,128);
  $frm->add_text_field("responsable", "Responsable","",false,25,false,true,null,false,64);

  $avail_type=array();
  foreach($_TYPE as $type=>$desc)
    $avail_type[$type] = $desc['long'];
  $frm->add_select_field("type", "Catégorie", $avail_type);

  $avail_sem=array();
  foreach($_SEMESTER as $sem=>$desc)
    $avail_sem[$sem] = $desc['long'];
  $frm->add_select_field("semestre", "Semestre(s) d'ouverture", $avail_sem, SEMESTER_AP);

  //$frm->add_text_field("alias_of", "Alias de", "", false, 4, false, true, "(exemple : si vous ajoutez l'UV 'XE03', inscrivez ici 'LE03')");
  $frm->add_entity_smartselect("alias_of", "Alias de", new uv($site->db), true);

  $frm->add_checkbox("tc_avail", "UV ouverte aux TC", true);


  $frm->add_submit("saveuv", "Enregistrer l'UV & éditer la fiche");
  $cts->add($frm);

  $site->add_contents($cts);
  $site->end_page();
  exit;
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit')
{
  $is_admin = $site->user->is_in_group("pedag_admin");
  $uv = new uv($site->db, $site->dbrw, intval($_REQUEST['id']));
  if(!$uv->is_valid())
    $site->redirect('./');

  $uv->load_extra();

  $path .= " / "."<img src=\"".$topdir."images/icons/16/forum.png\" class=\"icon\" />";
  $stop = count($uv->dept);
  for($i=0; $i<$stop; $i++){
    $path .= "<a href=\"uv.php?dept=".$uv->dept[$i]."\"> ".$_DPT[$uv->dept[$i]]['short']."</a>";
    if($i+1 < $stop) $path .= ",";
  }

  $path .= " / "."<a href=\"./uv.php?id=$uv->id\"><img src=\"".$topdir."images/icons/16/emprunt.png\" class=\"icon\" /> $uv->code</a>";
  $path .= " / "."Éditer";
  $cts = new contents($path);
  $cts->add_paragraph("");

  /**
   * informations principales
   */
  $frm = new form("editmain", "uv.php?action=save", true, "post", "Informations principales");
  $frm->add_hidden("id", $uv->id);
  $frm->add_text_field("code", "Code", $uv->code, true, 4, false, $is_admin);
  $frm->add_text_field("intitule", "Intitulé", $uv->intitule, true, strlen($uv->intitule), false,true,null,false,128);
  $frm->add_text_field("responsable", "Responsable", $uv->responsable,true,strlen($uv->responsable),false,true,null,false,64);

  $avail_type=array();
  foreach($_TYPE as $type=>$desc)
    $avail_type[$type] = $desc['long'];
  $frm->add_select_field("type", "Catégorie", $avail_type, $uv->type);

  $avail_sem=array();
  foreach($_SEMESTER as $sem=>$desc)
    $avail_sem[$sem] = $desc['long'];
  $frm->add_select_field("semestre", "Semestre(s) d'ouverture", $avail_sem, $uv->semestre);

  $frm->add_checkbox("tc_avail", "UV ouverte aux TC", $uv->tc_available);

  $frm->add_submit("saveuv", "Enregistrer les modifications");
  $cts->add($frm, true, false, "main", false, true);

  /**
   * infos du guide
   */
  unset($frm);
  $frm = new form("editextra", "uv.php?action=save", true, "post", "Informations du guide des UV");
  $frm->add_hidden("id", $uv->id);
  $frm->add_info("La présence ou l'absence d'heures dans les catégories suivantes sert notamment à la création des emplois du temps.");
    $subfrm = new subform("charge");
    $subfrm->add_text_field("c", "Nombre d'heures de : cours", $uv->guide['c'], false, 2);
    $subfrm->add_text_field("td", "TD", $uv->guide['td'], false, 2);
    $subfrm->add_text_field("tp", "TP", $uv->guide['tp'], false, 2);
    $subfrm->add_text_field("the", "THE", $uv->guide['the'], false, 2);
  $frm->add($subfrm, false, false, false, false, true);

  $frm->add_text_field("credits", "Nombre de crédits ECTS", $uv->credits, true, 2);
  $frm->add_text_area("objectifs", "Objectifs de l'UV", $uv->guide['objectifs']);
  $frm->add_text_area("programme", "Programme de l'UV", $uv->guide['programme']);

  $frm->add_submit("saveuv", "Enregistrer les modifications");
  $cts->add($frm, true, false, "guide", false, true);

  /**
   * Informations relatives
   */
  /* ajout dept, filieres, alias... */
  unset($frm);
  $frm = new form("editrelative", "uv.php?action=save", true, "post", "Informations relatives");
  $frm->add_hidden("id", $uv->id);

  $avail_dept=array();
  $already_dept=array();
  foreach($_DPT as $dept=>$desc){
    if(!empty($uv->dept) && in_array($dept, $uv->dept))
      $already_dept[$dept] = $desc['long'];
    else
      $avail_dept[$dept] = $desc['long'];
  }
  $frm->add(new selectbox("dept", "Départements", $avail_dept, null, null, $already_dept, 120));

  //$sql = new requete($site->db, "SELECT `id_cursus`, `intitule` FROM `pedag_cursus` WHERE ");
  //$frm->add(new selectbox("cursus", "Cursus", null, null, null, null, 120));

  if($uv->is_alias() !== false)
    $alias_val = new uv($site->db, $site->dbrw, $uv->is_alias());
  else
    $alias_val = new uv($site->db);
  $frm->add_entity_smartselect("alias_of", "Alias de", $alias_val, true);

  $frm->add_submit("saveuv", "Enregistrer les modifications");
  $cts->add($frm, true, false, "relative", false, true);
  $site->add_contents($cts);
  $site->end_page();
  exit;
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'new_comment')
{
  require_once("include/uv_comment.inc.php");
  $user = new pedag_user($site->db);
  $user->load_by_id($site->user->id);

  $uv = new uv($site->db, $site->dbrw, $_REQUEST['id']);
  if(!$uv->is_valid())
    $site->redirect('uv.php');

  if(empty($_REQUEST['content'])) /* PAS de commentaires sans texte */
    $site->redirect('uv.php?id='.$uv->id.'&view=commentaires'); /* faudra mettre un message d erreur pour etre moins bourrin */

  $com = new uv_comment($site->db, $site->dbrw);
  $com->add($uv->id, $user->id,
                     $_REQUEST['generale'], $_REQUEST['utilite'], $_REQUEST['interet'], $_REQUEST['enseignement'], $_REQUEST['travail'],
                     $_REQUEST['content']);
  if($com->is_valid)
    $site->redirect('uv.php?id='.$uv->id.'&view=commentaires#cmt_'.$com->id);
  else
    $site->redirect('uv.php?id='.$uv->id.'&view=commentaires');
}
else if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'deletecomm'))
{
  require_once("include/uv_comment.inc.php");
  $user = new pedag_user($site->db);
  $user->load_by_id($site->user->id);

  $com = new uv_comment($site->db, $site->dbrw, $_REQUEST['id_commentaire']);
  if(!$com->is_valid())
    $site->redirect('uv.php');

  // lalala...
  $_REQUEST['id'] = $com->id_uv;
  $_REQUEST['view'] = "commentaires";

  if ($admin || ($com->id_utilisateur == $site->user->id))
    $com->remove();
}
else if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'editcomm'))
{
  require_once("include/uv_comment.inc.php");
  $user = new pedag_user($site->db);
  $user->load_by_id($site->user->id);

  $com = new uv_comment($site->db, $site->dbrw, $_REQUEST['id_commentaire']);
  if(!$com->is_valid())
    $site->redirect('uv.php');

  if ($admin || ($com->id_utilisateur == $site->user->id))
    $com->update(null, null, $_REQUEST['generale'], $_REQUEST['utilite'], $_REQUEST['interet'], $_REQUEST['enseignement'], $_REQUEST['travail'], $_REQUEST['content']);
}
else if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reportabuse'))
{
  require_once("include/uv_comment.inc.php");
  $user = new pedag_user($site->db);
  $user->load_by_id($site->user->id);

  $com = new uv_comment($site->db, $site->dbrw, $_REQUEST['id_commentaire']);
  if(!$com->is_valid())
    $site->redirect('uv.php');

  $com->set_valid(0);

  // lalala...
  $_REQUEST['id'] = $com->id_uv;
  $_REQUEST['view'] = "commentaires";
}
else if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'validcomm'))
{
  require_once("include/uv_comment.inc.php");
  $user = new pedag_user($site->db);
  $user->load_by_id($site->user->id);

  $com = new uv_comment($site->db, $site->dbrw, $_REQUEST['id_commentaire']);
  if(!$com->is_valid())
    $site->redirect('uv.php');

  if ($admin)
    $com->set_valid(2);

  // lalala...
  $_REQUEST['id'] = $com->id_uv;
  $_REQUEST['view'] = "commentaires";
}

/***********************************************************************
 * Affichage detail UV
 */
if(isset($_REQUEST['id']) || isset($_REQUEST['id_commentaire']))
{
  if ($_REQUEST['id'])
    $uv = new uv($site->db, $site->dbrw, $_REQUEST['id']);
  else
  {
    require_once("include/uv_comment.inc.php");
    $com = new uv_comment($site->db, $site->dbrw, $_REQUEST['id_commentaire']);

    if(!$com->is_valid())
      $site->redirect('uv.php');

    $uv = new uv($site->db, $site->dbrw, $com->id_uv);
  }

  if(!$uv->is_valid())
    $site->redirect('./');

  $depts = $uv->get_dept_list();
  $path .= " / "."<img src=\"".$topdir."images/icons/16/forum.png\" class=\"icon\" />";
  if(count($depts) == 1){  /* cas majoritaire */
    $d = $depts[0];
    $path .= "<a href=\"uv.php?dept=$d\"> ".$_DPT[ $d ]['short']."</a>";
  }else{
    $stop = count($uv->dept);
    for($i=0; $i<$stop; $i++){
      $path .= "<a href=\"uv.php?dept=".$uv->dept[$i]."\"> ".$_DPT[$uv->dept[$i]]['short']."</a>";
      if($i+1 < $stop) $path .= ",";
    }
  }
  $path .= " / "."<a href=\"uv.php?id=$uv->id\"><img src=\"".$topdir."images/icons/16/emprunt.png\" class=\"icon\" /> $uv->code</a>";

  $cts = new contents($path);

  $tabs = array(
            array("", "pedagogie/uv.php?id=".$uv->id, "Informations générales"),
            array("commentaires", "pedagogie/uv.php?id=".$uv->id."&view=commentaires", "Commentaires"),
            array("suivi", "pedagogie/uv.php?id=".$uv->id."&view=suivi", "Séances & Élèves"),
            //array("ressources", "pedagogie/uv.php?id=".$uv->id."&view=ressources", "Ressources")
          );
  $view = $_REQUEST['view'];
  if ($view == "editcomm") $view = "commentaires";
  $cts->add(new tabshead($tabs, $view));

  /**
   * onglet commentaires
   */
  if(isset($_REQUEST['view']) && $_REQUEST['view'] == 'commentaires'){
    require_once("include/cts/uv_comment.inc.php");
    require_once("include/uv_comment.inc.php");

    if($uv->load_comments()){
      foreach($uv->comments as $commentid){
        $comment = new uv_comment($site->db, $site->dbrw, $commentid);
        $author = new pedag_user($site->db);
        $author->load_by_id($comment->id_utilisateur);

        $cts->add(new uv_comment_box($comment, $uv, $site->user, $author));
      }
    }else{
      $cts->add_paragraph("Il n'y a pas encore de commentaires associées
        à cette UV, soyez le premier !");
    }

    /* affichage des commentaires des UV alias */
    if($uv->is_alias()){
      $uv_cible = new uv($site->db, $site->dbrw, $uv->is_alias());
      $cts->add_title(3, "Commentaires sur l'UV ".$uv_cible->code);
      if($uv_cible->load_comments())
        foreach($uv_cible->comments as $commentid){
          $comment = new uv_comment($site->db, $site->dbrw, $commentid);
          $author = new pedag_user($site->db);
          $author->load_by_id($comment->id_utilisateur);

          $cts->add(new uv_comment_box($comment, $uv_cible, $site->user, $author));
        }
    }

    /** formulaire d'ajout */
    $frm = new form("add_comment_".$uv->id."", false, true, "POST");
    $frm->add_submit("clic", "+ Ajouter un commentaire");
    $cts->puts("<div onClick=\"javascript:on_off('add_comment_".$uv->id."');\">" . $frm->buffer . "</div>");

    $cts->puts("<div id=\"add_comment_".$uv->id."\" style=\"display: none;\" class=\"add_comment_form\">");
    $frm = new form("new_comment_".$uv->id, "uv.php?action=new_comment", true, "POST");
    $frm->add_hidden("id", $uv->id);
    $frm->add_info("Cette section ayant pour but d'aider les étudiants ".
          "dans leurs choix d'UV, merci de ne pas mettre des notes à la va-vite ".
          "sans la moindre phrase et d'être constructif dans vos commentaires. <br />".
          "Tout message offensant pourra se voir supprimé.");
    $frm->add_select_field('interet','Intéret de l\'UV (pour un ingénieur)', $VAL_INTERET, 3);
    $frm->add_select_field('utilite','Utilité de l\'UV', $VAL_UTILITE, 2);
    $frm->add_select_field('travail','Charge de travail', $VAL_TRAVAIL, 2);
    $frm->add_select_field('enseignement','Qualité de l\'enseignement', $VAL_ENSEIGNEMENT, 2);
    $frm->add_select_field('generale','<b>Note générale</b>', $VAL_GENERALE, 2);
    $frm->add_dokuwiki_toolbar("content");
    $frm->add_text_area("content", "Contenu", false, 80, 10, true);
    $frm->add_submit("send", "Enregistrer");
    $cts->add($frm);

    $cts->puts("</div>");

  }else if(isset($_REQUEST['view']) && $_REQUEST['view'] == 'suivi'){
    if(isset($_REQUEST['semestre']) && check_semester_format($_REQUEST['semestre']))
      $semestre = $_REQUEST['semestre'];
    else
      $semestre = SEMESTER_NOW;

    /* remise en forme des éléments que l'on veut afficher */
    $grp = array();
    foreach($uv->get_groups(null, $semestre) as $g){
      $grp[] = array(
                 'id_groupe' => $g['id_groupe'],
                 'type' => $_GROUP[ $g['type_num'] ]['long'],
                 'num_groupe' => $g['num_groupe'],
                 'jour' => get_day($g['jour']),
                 'debut' => strftime("%H:%M", strtotime($g['debut'])),
                 'fin' => strftime("%H:%M", strtotime($g['fin'])),
                 'salle' => $g['salle'],
                 'freq' => ($g['freq'] == 1)?"":"Une semaine sur 2"
               );
    }

    $cts->add(new sqltable("grouplist", "Séances disponibles pour ".$semestre,
                            $grp, "uv_groupe.php?semestre=".$semestre, "id_groupe",
                            array("type"=>"Type",
                                  "num_groupe"=>"N°",
                                  "jour"=>"Jour",
                                  "debut"=>"De",
                                  "fin"=>"À",
                                  "salle"=>"Salle",
                                  "freq"=>"Fréquence"),
                            array("view"=>"Voir détails",
                                  "done"=>"S'inscrire",
                                  "edit"=>"Corriger",
                                  "delete"=>"Supprimer"),
                            array()), true);
    $cts->puts("<input type=\"button\" onclick=\"location.href='uv_groupe.php?action=new&id_uv=$uv->id';\" value=\"+ Ajouter une séance\" />");

  }else if(isset($_REQUEST['view']) && $_REQUEST['view'] == 'ressources'){
    $cts->add_paragraph("Bientôt ;)");
  }
  else if(isset($_REQUEST['view']) && $_REQUEST['view'] == 'editcomm')
  {
    require_once("include/uv_comment.inc.php");
    $user = new pedag_user($site->db);
    $user->load_by_id($site->user->id);

    $com = new uv_comment($site->db, $site->dbrw, $_REQUEST['id_commentaire']);
    if(!$com->is_valid())
      $site->redirect('uv.php');

    if (!$admin && ($com->id_utilisateur != $site->user->id))
      $site->redirect('uv.php');

    /** formulaire d'ajout */

    $frm = new form("new_comment_".$uv->id, "uv.php?view=commentaires", true, "POST");
    $frm->add_hidden("action", "editcomm");
    $frm->add_hidden("id", $uv->id);
    $frm->add_hidden("id_commentaire", $com->id);
    $frm->add_info("Cette section ayant pour but d'aider les étudiants ".
          "dans leurs choix d'UV, merci de ne pas mettre des notes à la va-vite ".
          "sans la moindre phrase et d'être constructif dans vos commentaires. <br />".
          "Tout message offensant pourra se voir supprimé.");
    $frm->add_select_field('interet','Intéret de l\'UV (pour un ingénieur)', $VAL_INTERET, $com->note_interet);
    $frm->add_select_field('utilite','Utilité de l\'UV', $VAL_UTILITE, $com->note_utilite);
    $frm->add_select_field('travail','Charge de travail', $VAL_TRAVAIL, $com->note_travail);
    $frm->add_select_field('enseignement','Qualité de l\'enseignement', $VAL_ENSEIGNEMENT, $com->note_enseignement);
    $frm->add_select_field('generale','<b>Note générale</b>', $VAL_GENERALE, $com->note_generale);
    $frm->add_dokuwiki_toolbar("content");
    $frm->add_text_area("content", "Contenu", $com->content, 80, 10, true);
    $frm->add_submit("send", "Enregistrer");
    $cts->add($frm);
  }else{
    require_once($topdir."include/cts/board.inc.php");
    $uv->load_extra();

    $cts->add_title(2, $uv->intitule);

    $right = new contents("");
    if($uv->tc_available) $right->add_paragraph("Cette UV est ouverte aux élèves de Tronc Commun");
    if($uv->has_alias()){
      $tmp = "Cette UV possède ".count($uv->aliases)." alias :";
      foreach($uv->aliases as $alias)
        $tmp .= " <a href=\"uv.php?id=".$alias['id']."\"><b>".$alias['code']."</b></a>";
      $right->add_paragraph($tmp.".");
    }
    if($uv->is_alias()){
      $right->add_paragraph("Cette UV est l'alias de <a href=\"uv.php?id=".$uv->alias_of['id']."\"><b>".$uv->alias_of['code']."</b></a>. <br />
                              Les informations de cette page sont celles de l'UV d'origine.");
    }
    if($uv->state == 'MODIFIED') $right->add_paragraph("<i>Cette fiche à été modifiée récemment.</i>");

    /* a partir d'ici, si l'UV est un alias, on affiche les infos de l'UV *cible* */
    unset($alias);
    if($uv->is_alias()){
      $alias = $uv;
      $uv = new uv($site->db, $site->dbrw, $alias->is_alias());
      $uv->load_extra();
    }

    $left = new contents("");
    if($uv->responsable)  $left->add_paragraph("Responsable : ".$uv->responsable);
    if($uv->credits)      $left->add_paragraph("Crédits ECTS : ".$uv->credits);
    if($uv->semestre)     $left->add_paragraph("Semestre(s) d'ouverture : ". $_SEMESTER[ $uv->semestre ]['long']);
    $left->add_paragraph("Composition des enseignements : ");
    $lst = new itemlist("");
      if($uv->guide['c'])   $lst->add($uv->guide['c']." heures de cours");
      if($uv->guide['td'])  $lst->add($uv->guide['td']." heures de TD");
      if($uv->guide['tp'])  $lst->add($uv->guide['tp']." heures de TP");
      if($uv->guide['the']) $lst->add($uv->guide['the']." heures hors emploi du temps");
    $left->add($lst, false);

    $board = new board();
    $board->add($left, false);
    $board->add($right, false);
    $cts->add($board);

    $obj = new contents("Objectifs");
    $obj->add_paragraph(doku2xhtml($uv->guide['objectifs']));
    $prog = new contents("Programme");
    $prog->add_paragraph(doku2xhtml($uv->guide['programme']));
    $board = new board();
    $board->add($obj, true);
    $board->add($prog, true);
    $cts->add($board);

    /* si on etait en mode 'alias' on revient normalement */
    if(isset($alias) && $alias instanceof uv)
      $uv = $alias;

    $cts->puts("<input type=\"button\" onclick=\"location.href='uv.php?action=edit&id=$uv->id';\" value=\"Corriger la fiche\" style=\"float:right;\"/>");
  }

  $site->add_contents($cts);
  $site->end_page();
  exit;
}

/***********************************************************************
 * Affichage guide des UV
 */

$tabs = array(array("", "pedagogie/uv.php", "Guide des UV"));
foreach($_DPT as $dpt=>$desc)
  $tabs[] = array($dpt, "pedagogie/uv.php?dept=".$dpt, $desc['short']);

/**
 * Affichage 'sommaire' par departement
 */
if($_REQUEST['dept'])
{
  if(array_key_exists($_REQUEST['dept'], $_DPT))
    $dept = $_REQUEST['dept'];
  else
    $site->redirect('./');

  $path .= " / "."<a href=\"".$topdir."pedagogie/uv.php?dept=$dept\"><img src=\"".$topdir."images/icons/16/forum.png\" class=\"icon\" /> ".$_DPT[$dept]['short']." </a>";
  $cts = new contents($path);
  $cts->add(new tabshead($tabs, $_REQUEST['dept']));
  $cts->add_paragraph("");

  $uvlist = uv::get_list($site->db, null, $dept);
  $cts->add(new uv_dept_table($uvlist));

  $cts->add(new sqltable("uvlist_".$dept, "UV de ".$_DPT[$dept]['long'], $uvlist, "", 'id_uv',
                          array("code"=>"Code",
                                "intitule"=>"Intitulé",
                                "type"=>"Type",
                                "responsable"=>"Responsable",
                                "semestre"=>"Ouverture"),
                          array(), array()
                          ), true);

  $site->add_contents($cts);
  $site->end_page();
  exit;
}

/* affichage par defaut de la page : guide des UV */
$path .= " / "."Guide des UV";
$cts = new contents($path);
$cts->add(new tabshead($tabs, $_REQUEST['dept']));

$cts->add_paragraph("Bienvenue sur la version \"site AE\" du guide des UV.
Nous vous rappelons que tout comme le reste de la partie pédagogie, toutes
les informations que vous pouvez trouver ici sont fournies uniquement à
titre indicatif et que seules les informations issues des documents
officiels de l'UTBM (notamment le guide des UV et le récapitulatif
de vos crédits) font foi.");

$cts->add_paragraph("Nous ne sommes pour le moment pas en mesure d'assurer
la parfaite synchronisation des données avec le guide des UV, les informations
que vous pouvez trouver peuvent donc être dépassées, voire des UV manquer.
Vous êtes invités à contribuer à l'utilité de ce site en mettant à jour
les fiches d'UV (bouton `modifier`) et/ou en ajoutant les UV manquantes :");
$cts->puts("<input type=\"button\" onclick=\"location.href='uv.php?action=new';\" value=\"+ Ajouter une UV\" />");

foreach($_DPT as $dept=>$desc){
  $cts->add_title(2,"<a id=\"dept_".$dept."\" href=\"./uv.php?dept=$dept\">".$desc['long']."</a>");

  $uvlist = uv::get_list($site->db, null, $dept);
  $cts->add(new uv_dept_table($uvlist));
}
$orphans = uv::get_list($site->db, null, null);
if(!empty($orphans))
{
  $cts->add_title(2,"UVs sans département");

  $cts->add(new uv_dept_table($orphans));
}
$site->add_contents($cts);
$site->end_page();
?>
