<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
 * Copyright 2008
 * - Simon lopez < simon dot lopez at ayolo dot org >
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

require_once("include/site.inc.php");
$site->allow_only_logged_users("");

$cpg = new campagne($site->db,$site->dbrw);
if(isset($_REQUEST["id"]))
  $cpg->load_by_id($_REQUEST["id"]);

if ( $site->is_user_admin() )
{

  if(!is_null($cpg->id) && isset($_REQUEST["action"]) && $_REQUEST["action"]=="delete")
  {
    if($cpg->asso==$site->asso->id)
    {
      new delete($site->dbrw,"cpg_campagne",array("id_campagne"=>$_REQUEST["id"]));
      new delete($site->dbrw,"cpg_participe",array("id_campagne"=>$_REQUEST["id"]));
      new delete($site->dbrw,"cpg_question",array("id_campagne"=>$_REQUEST["id"]));
      new delete($site->dbrw,"cpg_reponse",array("id_campagne"=>$_REQUEST["id"]));
    }
    unset($_REQUEST["action"]);
    unset($_REQUEST["id"]);
  }
  if (isset($_REQUEST["addcpg"]) && isset($_REQUEST["nom"]) && !empty($_REQUEST["nom"]) && isset($_REQUEST["description"]) && isset($_REQUEST["questions"]) )
  {
    if(!isset($_REQUEST["end_date"]) || empty($_REQUEST["end_date"]))
      $_REQUEST["end_date"]='00/00/0000';
    $cts = new contents("Formulaire ajoutée avec succès");
    $cpg->new_campagne($_REQUEST["nom"], $_REQUEST["description"], $_REQUEST["end_date"],'-1',$site->asso->id);
    foreach ( $_REQUEST["questions"] as $rep )
    {
      if ( isset($rep['nom_question']) && !empty($rep['nom_question']) && isset($rep['type_question']))
      {
        if(empty($rep['description_question']))
          $rep['description_question']==$rep['nom_question'];
        if (($rep['type_question'] == "list" || $rep['type_question'] =="radio") && !empty($rep['reponses_question']))
        {
          $reponses=$rep['reponses_question'];
          $values=explode(";",$reponses,2);
          foreach($values as $value)
          {
            $value=explode("|", $value, 2);
            $c=count($value);
            if( $c!= 2 || empty($value[0]) || empty($value[1]))
            {
              $rep['type_question']="text";
              $reponses="";
            }
          }
          $cpg->add_question($rep['nom_question'],$rep['description_question'],$rep['type_question'],$reponses);
        }
        elseif ($rep['type_question'] == "text" || $rep['type_question'] == "checkbox" )
          $cpg->add_question($rep['nom_question'],$rep['description_question'],$rep['type_question']);
      }
    }
    $cts->add_paragraph("<img src=\"".$wwwtopdir."images/actions/done.png\">&nbsp;Le formulaire \"".$cpg->nom."\" a bien été ajouté.");
    $site->add_contents($cts,true);
    unset($_REQUEST["nom"]);
    unset($_REQUEST["description"]);
    unset($_REQUEST["questions"]);
    unset($_REQUEST["end_date"]);
  }

  if ( $_REQUEST['form'] == "new" || isset($Erreur) )
  {
    $default_valid = time() + (15 * 24 * 60 * 60);
    $site->start_page("none","Nouveau formulaire");
    $frm = new form("new","form.php",true,"POST","Nouveau formulaire");
    $frm->add_hidden("action","new");
    if ( isset($Erreur) )
      $frm->error($Erreur);
    if ($_REQUEST["end_date"])
      $frm->add_date_field("end_date", "Date de fin de validite : ",$_REQUEST["end_date"]);
    else
      $frm->add_date_field("end_date", "Date de fin de validite : ");
    $frm->add_text_field("nom", "Nom du formulaire",$_REQUEST["nom"],true,80);

    $frm->add_text_area("description", "Description du formulaire",$_REQUEST["description"]);
    $frm->add_info("Pour supprimer une question, il suffit de laisser son nom vide !<br />");
    $frm->add_info("Pour une question de type liste ou bouton radio, complétez impérativement le champ \"Réponses possibles\".");
    $frm->add_info("Formatage du champ \"Réponses possibles\" : valeur_1|La valeur 1;valeur_2|La valeur 2;...;valeur_z|La dernière valeur");
    if (isset($_REQUEST["questions"]))
    {
      $n = 1;
      foreach ( $_REQUEST["questions"] as $num=>$question )
      {
        if ( !empty($question) )
        {
          $subfrm = new form("questions".$num,null,null,null,"Question $n");
          $subfrm->add_text_field("questions[$num][nom_question]", "Nom question",$question["nom_question"],false,80);
          $subfrm->add_text_area("questions[$num][description_question]", "Description",$question["description_question"]);
          if(isset($question["type_question"]))
            $type = $question["type_question"];
          else
            $type='text';
          $subfrm->add_select_field("questions[$num][type_question]","Type de question",array("text"=>"Texte","checkbox"=>"Boite à cocher","list"=>"Liste", "radio"=>"Bouton radio"),$type);
          $subfrm->add_text_field("questions[$num][reponses_question]", "Réponses possibles",$question["reponses_question"],false,80);
          $frm->add ( $subfrm, false, false, false, false, false, false, true );
          $n++;
        }
      }
      if (isset($_REQUEST["newques"]))
      {
        $i=$n-1;
        $subfrm = new form("questions".$i,null,null,null,"Question $n");
        $subfrm->add_text_field("questions[$i][nom_question]", "Nom question","",false,80);
        $subfrm->add_text_area("questions[$i][description_question]", "Description");
        $subfrm->add_select_field("questions[$i][type_question]","Type de question",array("text"=>"Texte","checkbox"=>"Boite à cocher","list"=>"Liste", "radio"=>"Bouton radio"),"text");
        $subfrm->add_text_field("questions[$i][reponses_question]", "Réponses possibles","",false,80);
        $frm->add ( $subfrm, false, false, false, false, false, false, true );
      }
    }
    else
    {
      $n=1;
      for($i=0;$i<6;$i++)
      {
        $subfrm = new form("questions".$i,null,null,null,"Question $n");
        $subfrm->add_text_field("questions[$i][nom_question]", "Nom question","",false,80);
        $subfrm->add_text_area("questions[$i][description_question]", "Description");
        $subfrm->add_select_field("questions[$i][type_question]","Type de question",array("text"=>"Texte","checkbox"=>"Boite à cocher","list"=>"Liste", "radio"=>"Bouton radio"),"text");
        $subfrm->add_text_field("questions[$i][reponses_question]", "Réponses possibles","",false,80);
        $frm->add ( $subfrm, false, false, false, false, false, false, true );
        $n++;
      }
    }
    $frm->add_hidden("text_submit",$text_submit);
    $frm->add_hidden("date_form",$_REQUEST['date_form']);
    $frm->add_submit("newques","Question supplémentaire");
    $frm->add_submit("addcpg","Ajouter");

    $site->add_contents($frm);
    $site->end_page();
    exit();
  }
}

$site->start_page("none","Liste des formulaires");
if(!is_null($cpg->id) && $cpg->asso==$site->asso->id) // on affiche le formulaire
{
  if ( $cpg->id > 0 && !$cpg->a_repondu($site->user->id) && isset($_REQUEST["answord"]) && $cpg->id==isset($_REQUEST["answord"]))
  {
    $cpg->repondre($site->user->id,$_REQUEST["reponses"]);
    $res = new contents("Merci","Votre participation.");
    $res->add_paragraph('Vos réponses ont été enregistrée.');
    $site->add_contents($res);
    unset($_REQUEST['id']);
  }
  if ( isset($_REQUEST["id"]) && !$cpg->a_repondu($site->user->id) )
  {
    $questions = $cpg->get_questions();
    if (!empty($questions))
    {
      $site->start_page("formulaire","Formulaire");
      $cts = new contents($cpg->nom);
      if($cpg->end_date!='1970-01-01')
        $cts->add_paragraph("Le formulaire se terminera le ".date("d/m/y",strtotime($cpg->end_date)));
      $frm = new form("apply","form.php",true,"POST","Formulaire d'inscription");
      $frm->add_hidden("answord",$cpg->id);
      $frm->add_hidden("id",$cpg->id);
      $frm->add_info($cpg->description);
      foreach($questions as $id => $question)
      {
        if($question["type"]=="text")
        {
          $frm->add_info("<b>".$question["nom"]."</b><br />");
          $frm->add_info("<i>".$question["description"]."</i><br />");
          $frm->add_text_field("reponses[$id]","","",false,80);
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
    }
  }
}

if(!isset($_REQUEST["id"]))// on liste les formulaires en cours
{
  $req=new requete($site->db,
                   'SELECT nom_campagne, id_campagne as id FROM `cpg_campagne` '.
                   'WHERE (`date_fin_campagne`>=NOW() OR `date_fin_campagne`=\'1970-01-01\') '.
                   'AND id_asso='.$site->asso->id.' '.
                   'AND id_groupe=\'-1\'');
  if($req->lines>0)
  {
    if($site->is_user_admin())
      $act=array("answer"=>"Répondre","results"=>"Résultats","delete"=>"Supprimer");
    else
      $act=array("answer"=>"Répondre");
    require_once($topdir. "include/cts/sqltable.inc.php");
    $tbl = new sqltable("listform",
                        "Formulaires",
                        $req,
                        "form.php",
                        "id",
                        array("nom_campagne"=>"Intitulé"),
                        $act,
                        array(),
                        array());
    $site->add_contents($tbl);
  }
  else
  {
    $cts = new contents("Pas de formulaires","Pas de formulaires");
    $cts->add_paragraph('Aucun formulaire n\'est actuellement disponible.');
    $site->add_contents($cts);
  }
}

$site->end_page();

?>
