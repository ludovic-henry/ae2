<?php

/* Copyright 2007
 * - Simon Lopez <simon POINT lopez CHEZ ayolo POINT org>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

$topdir = "./";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/newsflow.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
$site = new site ();

if ( !$site->user->is_valid() )
  $site->error_forbidden("accueil");

$cpg = new campagne($site->db,$site->dbrw);

if (!isset($_REQUEST['id_campagne']))
{
  $cpg->load_lastest();
}
else
{
  $cpg->load_by_id($_REQUEST['id_campagne']);
}

if ( $cpg->id > 0 && $site->user->is_in_group_id($cpg->group) && !$cpg->a_repondu($site->user->id) && isset($_REQUEST["answord"]) )
{
  if(isset($_REQUEST["discard"]) )
    $_REQUEST["reponses"]="";

  if ( $_REQUEST["id_campagne"] == $cpg->id )
    $cpg->repondre($site->user->id,$_REQUEST["reponses"]);

  $res = new contents("Merci","Merci de votre participation.");

  $site->add_contents($res);
    
  unset($_REQUEST['id_campagne']);
}

if ( isset($_REQUEST["id_campagne"]) && $cpg->id == $_REQUEST["id_campagne"] && $site->user->is_in_group_id($cpg->group) && !$cpg->a_repondu($site->user->id) )
{
  $questions = $cpg->get_questions();
  if (!empty($questions))
  {
    $site->start_page("accueil","Campagne");

    $cts = new contents("Campagne : ".$cpg->nom);
    $cts->add_paragraph("La campagne se terminera le ".date("d/m/y",strtotime($cpg->end_date)));

    $frm = new form("discard","campagne.php",true,"POST",false);
    $frm->add_hidden("answord","true");
    $frm->add_hidden("id_campagne",$cpg->id);
    $frm->add_checkbox ( "discard", "Je ne souhaite pas participer.", false );
    $frm->add_submit("save","Enregistrer");

    $cts->add($frm,true);

    $frm = new form("apply","campagne.php",true,"POST","Formulaire d'inscription");
    $frm->add_hidden("answord","true");
    $frm->add_hidden("id_campagne",$cpg->id);
    $frm->add_info($cpg->description);
    foreach($questions as $id => $question)
    {
      if($question["type"]=="text")
      {
        $frm->add_info("<b>".$question["nom"]."</b><br />");
        $frm->add_info("<i>".$question["description"]."</i><br />");
        $frm->add_text_field("reponses[$id]","","",false,80,false,true,null,false,500);
        $frm->add_info("<br />");
      }
      elseif($question["type"]=="textarea")
      {
        $frm->add_info("<b>".$question["nom"]."</b><br />");
        $frm->add_info("<i>".$question["description"]."</i><br />");
        $frm->add_text_area("reponses[$id]","");
        $frm->add_info("<br />");
      }
      elseif($question["type"]=="list")
      {
        $frm->add_info("<b>".$question["nom"]."</b><br />");
        $frm->add_info("<i>".$question["description"]."</i><br />");
        $values=explode(";",$question["reponses"]);
        $keys=array();
        foreach($values as $value)
        {
          $value=explode("|", $value,2);
          $keys[$value[0]]=$value[1];
        }
        $frm->add_select_field( "reponses[$id]", "", $keys);
        $frm->add_info("<br />");
      }
      elseif($question["type"]=="radio")
      {
        $frm->add_info("<b>".$question["nom"]."</b><br />");
        $frm->add_info("<i>".$question["description"]."</i><br />");
        $values=explode(";",$question["reponses"]);
        foreach($values as $value)
        {
          $keys=array();
          $value=explode("|", $value, 2);
          $keys[$value[0]]=$value[1];
          $frm->add_radiobox_field( "reponses[$id]", "", $keys, "", false, false );
        }
        $frm->add_info("<br />");
      }
      elseif($question["type"]=="checkbox")
      {
        $frm->add_info("<b>".$question["nom"]."</b><br />");
        $frm->add_info("<i>".$question["description"]."</i><br />");
        $frm->add_checkbox( "reponses[$id]","");
        $frm->add_info("<br />");
      }

    }

    $frm->add_submit("save","Enregistrer");
    $cts->add($frm,true);

    $site->add_contents($cts);

    $site->end_page();
    exit();
  }
}

$site->start_page("accueil","Campagne");


$req = new requete($site->db, "SELECT * FROM `cpg_campagne` ORDER BY date_debut_campagne");

$site->add_contents(new sqltable(
                                 "listcampagnes",
                                 "Archives", $req, "../campagne.php",
                                 "id_campagne",
                                 array(
                                       "nom_campagne"=>"Campagne",
                                       "date_debut_campagne"=>"Du",
                                       "date_fin_campagne"=>"Au"
                                      ),
                                 array(),
                                 array(),
                                 array()
                                ));

$site->end_page();

?>
