<?php

/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/objet.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/sitebat.inc.php");
require_once($topdir. "include/entities/batiment.inc.php");
require_once($topdir. "include/entities/salle.inc.php");
$site = new site ();
$emp = new emprunt ( $site->db, $site->dbrw );
$asso = new asso($site->db);
$user = new utilisateur($site->db);


if ( isset($_REQUEST["id_emprunt"]) )
{
  $emp->load_by_id($_REQUEST["id_emprunt"]);
  if ( $emp->id < 1 && !isset($_REQUEST["valid"]) )
  {
    $site->error_not_found("services");
    exit();
  }
  elseif ( $emp->id > 0 )
  {
    $asso->load_by_id($emp->id_asso);

    $can_edit = $site->user->is_in_group("gestion_ae") || ($emp->id_utilisateur == $site->user->id);

    if ( $asso->id > 0 )
      $can_edit = $can_edit || $asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU);

    if ( !$can_edit )
      $site->error_forbidden("services");
  }
}


if ( $can_edit && $_REQUEST["action"] == "delete" && isset($_REQUEST["id_objet"]) && $emp->etat < EMPRUNT_PRIS )
{
  $emp->remove_object($_REQUEST["id_objet"]);
}
elseif ( $can_edit && $_REQUEST["action"] == "delete" && (($emp->etat < EMPRUNT_RETOURPARTIEL && $site->user->is_in_group("gestion_ae")) || ($emp->etat < EMPRUNT_PRIS)) )
{
  $emp->remove_emp();
  $message = new contents("Supprimé","La réservation n°".$emp->id." a été annulée.");
  $emp->id = -1;
}
elseif ( $_REQUEST["action"] == "fullretour" && $site->user->is_in_group("gestion_ae") )
{
  if ( $emp->etat == EMPRUNT_RETOURPARTIEL || $emp->etat == EMPRUNT_PRIS )
    $emp->full_back();
  $emp->id = -1;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Visualisation d'une reservation/emprunt
 */
if ( $emp->id > 0 )
{
  $user_op = new utilisateur($site->db);

  $user->load_by_id($emp->id_utilisateur);
  $user_op->load_by_id($emp->id_utilisateur_op);



  if ( $_REQUEST["action"] == "print" && ($emp->etat == EMPRUNT_PRIS) )
  {
    require_once($topdir. "include/pdf/emprunt.inc.php");

    $req = new requete ( $site->db, "SELECT " .
      "`inv_objet`.`id_objet`," .
      "`inv_objet`.`nom_objet`," .
      "`inv_objet`.`cbar_objet`, " .
      "`inv_type_objets`.`nom_objtype` ".
      "FROM inv_emprunt_objet " .
      "INNER JOIN `inv_objet` ON `inv_objet`.`id_objet`=`inv_emprunt_objet`.`id_objet` " .
      "INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` " .
      "WHERE `inv_emprunt_objet`.`id_emprunt`='".$emp->id."'" );

    $pdf = new pdfemprunt($emp, $user, $asso, $user_op);
    $pdf->objects($req);
    $pdf->Output();
    exit();
  }

  $site->start_page("services","Reservation/Emprunt n°".$emp->id);

  $cts = new contents("Emprunts de matériel");

  $cts->add_title(2,"Emprunt n°".$emp->id);

  if ( $site->user->is_in_group("gestion_ae"))
  {
    $tabs = array(array(false,"ae/modereemp.php", "Modération"),
          array(false,"ae/modereemp.php?view=togo", "A venir"),
          array(false,"ae/modereemp.php?view=out", "Matériel prété"),
          array(false,"emprunt.php", "Reserver"),
          array(false,"emprunt.php?page=retrait", "Preter"),
          array(false,"emprunt.php?page=retour", "Retour")
          );
    $cts->add(new tabshead($tabs,true));
  }

  $cts->add_paragraph("Reservation du ".textual_plage_horraire($emp->date_debut,$emp->date_fin));
  $cts->add_paragraph("Demandé par ".($user->id==-1?$emp->emprunteur_ext:$user->get_html_link())." le ".date("d/m/Y à H:i",$emp->date_demande));
  if ( $asso->id > 0)
    $cts->add_paragraph($asso->get_html_link());
  $cts->add_paragraph("Etat: ".$EmpruntObjetEtats[$emp->etat]);

  if ( $emp->etat == EMPRUNT_MODERE )
  {
    $cts->add_paragraph("Validé par ".$user_op->get_html_link());
    if ( $emp->caution )
      $cts->add_paragraph("Caution fixée à ".($emp->caution/100)." Euros");
    if ( $emp->prix_paye )
      $cts->add_paragraph("Participation aux frais fixé à ".($emp->prix_paye/100)." Euros");
  }
  elseif ( $emp->etat == EMPRUNT_PRIS || $emp->etat == EMPRUNT_RETOURPARTIEL )
  {
    $cts->add_paragraph("Pris le ".date("d/m/Y à H:i",$emp->date_prise));
    $cts->add_paragraph("Delivré par ".$user_op->get_html_link());
    if ( $emp->caution )
      $cts->add_paragraph("Caution de ".($emp->caution/100)." Euros");
    if ( $emp->prix_paye )
      $cts->add_paragraph("Participation aux frais de ".($emp->prix_paye/100)." Euros");
  }
  elseif ( $emp->etat == EMPRUNT_RETOUR )
  {
    $cts->add_paragraph("Restitué en totalité le ".date("d/m/Y à H:i",$emp->date_retour));

  }

  $req = new requete ( $site->db, "SELECT " .
      "`inv_objet`.`id_objet`," .
      "CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
      "`inv_type_objets`.`id_objtype`,`inv_type_objets`.`nom_objtype`, " .
      "`inv_objet`.`caution_objet`, `inv_objet`.`prix_emprunt_objet` " .
      "FROM inv_emprunt_objet " .
      "INNER JOIN `inv_objet` ON `inv_objet`.`id_objet`=`inv_emprunt_objet`.`id_objet` " .
      "INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` " .
      "WHERE `inv_emprunt_objet`.`id_emprunt`='".$emp->id."'" );

  $tbl = new sqltable(
    "listobjets",
    "Objets empruntés", $req, "emprunt.php?id_emprunt=".$emp->id,
    "id_objet",
    array("nom_objet"=>"Objet","nom_objtype"=>"Type"),
    ($can_edit && ($emp->etat < EMPRUNT_PRIS))?array("delete"=>"Enlever"):array(), array(), array()
    );

  $cts->add($tbl);


  if ( $site->user->is_in_group("gestion_ae") )
  {
    if ( $emp->etat == EMPRUNT_RESERVATION )
    {
      $prix =0;
      $caution =0;
      $req->go_first();
      while ( $row = $req->get_row() )
      {
        $caution+=$row['caution_objet'];
        $prix +=$row['prix_emprunt_objet'];
      }
      $frm = new form("validemp","ae/modereemp.php?id_emprunt=".$emp->id,false,"POST","Valider l'emprunt");
      $frm->add_hidden("action","valide");
      $frm->add_price_field("caution","Caution",$caution);
      $frm->add_price_field("prix_emprunt","Prix",$prix);
      $frm->add_text_area("notes","Notes");
      $frm->add_submit("val","Valider");
      $cts->add($frm,true);

      $frm = new form("unvalidemp","ae/modereemp.php?id_emprunt=".$emp->id,false,"POST","Refuser l'emprunt");
      $frm->add_hidden("action","delete");
      $frm->add_submit("ref","Refuser");
      $cts->add($frm,true);
    }
    elseif ( $emp->etat == EMPRUNT_MODERE )
    {
      $req->go_first();
      $ok = true;
      if ( $emp->date_debut-1 > time() )
      {
        $obj = new objet($site->db);
        while ( $row = $req->get_row() )
        {
          $obj->id=$row['id_objet'];
          $ok = $ok && $obj->is_avaible(time(),$emp->date_debut-1);
        }
      }

      if ( !$ok )
      {
        $cts->add_title(2,"Retrait");
        $cts->add_paragraph("Tout le matériel n'est pas encore disponible, ou est réservé avant la date de debut de l'emprunt.");
        $cts->add_paragraph("<a href=\"ae/modereemp.php?view=out\">Matériel prété actuellement</a>");
        $cts->add_paragraph("<a href=\"ae/modereemp.php?view=togo\">Reservations modérés</a>");
        $cts->add_paragraph("<a href=\"ae/modereemp.php\">Reservations en attente de modération</a>");
      }
      else
      {
        $frm = new form("retourobjet","ae/modereemp.php?view=togo",false,"POST","Retrait");
        $frm->add_hidden("action","retrait");
        $frm->add_hidden("id_emprunt",$emp->id);
        $frm->add_price_field("caution","Caution",$emp->caution);
        $frm->add_price_field("prix_emprunt","Prix",$emp->prix_paye);
        $frm->add_text_area("notes","Notes",$emp->notes);
        $frm->add_submit("valid","Proceder au retrait");
        $cts->add($frm);
      }
    }
    elseif ( $emp->etat == EMPRUNT_RETOURPARTIEL || $emp->etat == EMPRUNT_PRIS )
    {
      $frm = new form("retourobjet","emprunt.php?page=retour",false,"POST","Restitué dans sa totalité");
      $frm->add_hidden("action","fullretour");
      $frm->add_hidden("id_emprunt",$emp->id);
      $frm->add_submit("valid","Marquer comme restitué");
      $cts->add($frm);
    }
  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

function add_objet_once( &$list, &$obj )
{
  if (!empty($list))
  {
    foreach( $list as $o )
      if ( $o->id == $obj->id ) return;
  }
  $list[]=$obj;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Interface de retour du matériel
 */
if ( $_REQUEST["page"] == "retour" && $site->user->is_in_group("gestion_ae") )
{
  if ( $_REQUEST["action"] == "retourobjet" )
  {
    $obj = new objet($site->db);
    $obj->load_by_cbar($_REQUEST["cbar"]);
    if ( $obj->id > 0)
    {
      $emp->load_by_objet($obj->id);
      if ( $emp->id > 0 )
        $emp->back_objet($obj->id);
      else
        $Erreur="Cet objet n'est pas actuellement emprunté.";
    }
    else
     $Erreur="Objet inconnu.";
  }

  $site->start_page("services","Retour matériel");

  $cts = new contents("Emprunts de matériel");

  $tabs = array(array(false,"ae/modereemp.php", "Modération"),
        array(false,"ae/modereemp.php?view=togo", "A venir"),
        array(false,"ae/modereemp.php?view=out", "Matériel prété"),
        array(false,"emprunt.php", "Reserver"),
        array(false,"emprunt.php?page=retrait", "Preter"),
        array(true,"emprunt.php?page=retour", "Retour")
        );
  $cts->add(new tabshead($tabs,true));



  if ( $emp->id > 0 )
  {
    if ( $emp->etat == EMPRUNT_RETOURPARTIEL )
      $cts->add_paragraph("<b>Attention: il reste des objets à restituer</b>.");
    elseif ( $emp->etat == EMPRUNT_RETOUR )
      $cts->add_paragraph("Emprunt restitué en totalité.");
  }

  $frm = new form("retourobjet","emprunt.php?page=retour",false,"POST","Objet par objet");
  $frm->add_hidden("action","retourobjet");
  if ( $Error )
    $frm->error($Error);
  $frm->add_text_field("cbar","Code barre");
  $frm->add_submit("valid","Terminer");
  $cts->add($frm,true);

  $frm = new form("goemprunt","emprunt.php?page=retour",false,"POST","Emprunt");
  $frm->add_text_field("id_emprunt","N° d'emprunt");
  $frm->add_submit("valid","Voir");
  $cts->add($frm,true);

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Interface de retrait immédiat
 */
if ( $_REQUEST["page"] == "retrait" && $site->user->is_in_group("gestion_ae") )
{
  $Step = intval($_REQUEST["step"]);
  if ( $_REQUEST["action"] == "gonext" )
  {
    if ( $_REQUEST["step"] == 0 )
    {
      if ( $_REQUEST["asso"] == "asso" )
        $asso->load_by_id($_REQUEST["id_asso"]);

      if ( $_REQUEST["emp"] == "moi" )
        $user = $site->user;
      elseif ( $_REQUEST["emp"] == "carte" )
        $user->load_by_carteae($_REQUEST["carte"]);
      elseif ( $_REQUEST["emp"] == "email" )
        $user->load_by_id($_REQUEST["id_utilisateur"]);

      if ( $_REQUEST["emp"] != "ext" && !$user->is_valid() )
        $Error="Utilisateur inconnu";
      elseif ( $_REQUEST["asso"] == "asso" && !$asso->is_valid() )
        $Error="Association inconnue";
      elseif ( $_REQUEST["endtime"] <= time() )
        $Error="Date et heure de fin invalide";
      else
        $Step=1;

    }
    else  if ( $Step > 0 )
    {
      $asso->load_by_id($_REQUEST["id_asso"]);

      if ( empty($_REQUEST["id_utilisateur"]) )
        $user->id=null;
      else
        $user->load_by_id($_REQUEST["id_utilisateur"]);

      if ( (!$user->is_valid() && !$_REQUEST["emprunteur_ext"]) || $_REQUEST["endtime"] <= time() )
      {
        $Step=0;
        $Error="Erreur de passage de valeurs";
      }
    }

    if ( $Step >= 1 )
    {
      $objets=array();
      if(!empty($_REQUEST["id_objets"]))
      {
        foreach ($_REQUEST["id_objets"] as $id_objet)
        {
          $obj = new objet($site->db);
          $obj->load_by_id($id_objet);
          if ( $obj->id > 0 && $obj->is_avaible(time(),$_REQUEST["endtime"]) )
            $objets[] = $obj;
        }
      }
      if ( $Step > 1 && !count($objets) ) $Step=1;
    }

    if ( $_REQUEST["step"] == 1 )
    {
      if ( count($objets) && !$_REQUEST["cbar"] )
      {
        $Step=2;
      }
      else
      {
        $obj = new objet($site->db);
        $obj->load_by_cbar($_REQUEST["cbar"]);

        if ( !$obj->is_valid() )
          $Error = "Objet inconnu";
        elseif ( !$obj->is_avaible(time(),$_REQUEST["endtime"]))
          $Error = "Objet non disponible jusqu'à la fin de l'emprunt";
        else
          add_objet_once($objets,$obj);
      }
    }

    if ( $_REQUEST["step"] == 2 )
    {
      $Step=3;
      //if ( !$asso->is_valid() ) $asso->id = null;
      //if ( !$user->is_valid() ) $user->id = null;

      $emp->add_emprunt ( $user->id, $asso->id, $_REQUEST["emprunteur_ext"], time(), $_REQUEST["endtime"] );

      if(!empty($objets))
      {
        foreach ( $objets as $objet )
          $emp->add_object($objet->id);
      }

      $emp->retrait ( $site->user->id, $_REQUEST["caution"], $_REQUEST["prix_emprunt"], $_REQUEST["notes"] );

    }
  }


  $site->start_page("services","Emprunt matériel");

  $cts = new contents("Emprunts de matériel");

  $tabs = array(array(false,"ae/modereemp.php", "Modération"),
        array(false,"ae/modereemp.php?view=togo", "A venir"),
        array(false,"ae/modereemp.php?view=out", "Matériel prété"),
        array(false,"emprunt.php", "Reserver"),
        array(true,"emprunt.php?page=retrait", "Preter"),
        array(false,"emprunt.php?page=retour", "Retour")
        );
  $cts->add(new tabshead($tabs,true));



  $frm = new form("stepemprunt","emprunt.php?page=retrait",$Step == 0);
  $frm->add_hidden("step",$Step);
  $frm->add_hidden("action","gonext");
  if ( $Error )
    $frm->error($Error);

  $cts->add_title(2,"1. Identité de l'emprunteur");

  if ( $Step == 0 )
  {
    $frm->add_datetime_field("endtime","Fin de l'emprunt");

    $ssfrm = new form("mtf",null,null,null,"Cadre");

    $sfrm = new form("asso",null,null,null,"A titre personnel");
    $ssfrm->add($sfrm,false,true,true,"nasso",true);

    $sfrm = new form("asso",null,null,null,"Pour une association");
    $sfrm->add_entity_select("id_asso"," : ",$site->db,"asso");
    $ssfrm->add($sfrm,false,true,false,"asso",true);

    $frm->add($ssfrm);

    $ssfrm = new form("qui",null,null,null,"Emprunteur");

    $sfrm = new form("emp",null,null,null,"Moi même");
    $ssfrm->add($sfrm,false,true,false,"moi",true);

    $sfrm = new form("emp",null,null,null,"Le cotisant dont la carte est");
    $sfrm->add_text_field("carte"," : ");
    $ssfrm->add($sfrm,false,true,true,"carte",true);

    $sfrm = new form("emp",null,null,null,"L'utilisateur");
    $sfrm->add_entity_smartselect("id_utilisateur","",new utilisateur($site->db));
    $ssfrm->add($sfrm,false,true,false,"email",true);

    $sfrm = new form("emp",null,null,null,"La personne non inscrite suivante");
    $sfrm->add_text_field("emprunteur_ext"," : ");
    $ssfrm->add($sfrm,false,true,false,"ext",true);

    $frm->add($ssfrm);

    $frm->add_submit("next","Suivant");

    $cts->add($frm);
  }
  elseif( $Step > 0 )
  {
    $frm->add_hidden("endtime",$_REQUEST["endtime"]);
    $frm->add_hidden("id_asso",$asso->id);
    $frm->add_hidden("id_utilisateur",$user->id);
    if ( $user->is_valid() )
      $frm->add_hidden("emprunteur_ext",$_REQUEST["emprunteur_ext"]);

    $cts->add_paragraph("Jusqu'au ".date("d/m/Y H:i",$_REQUEST["endtime"]));

    if ( $asso->id > 0 )
      $cts->add_paragraph("Pour l'association : ".$asso->get_html_link());

    if ( $user->id > 0 )
      $cts->add_paragraph("Emprunteur : ".$user->get_html_link());
    else
      $cts->add_paragraph("Emprunteur : ".$_REQUEST["emprunteur_ext"]);
  }

  $cts->add_title(2,"2. Matériel");

  if ( $Step >= 1 && count($objets) )
  {
    $lst = new itemlist();
    if(!empty($objets))
    {
      foreach ( $objets as $n=>$objet )
      {
        $frm->add_hidden("id_objets[$n]",$objet->id);
        $lst->add($objet->nom." ".$objet->cbar);
      }
      $cts->add($lst);
    }
  }

  if( $Step == 1 )
  {

    $frm->add_text_field("cbar","Code barre de l'objet");
    $frm->add_submit("next","Suivant");

    $cts->add($frm);
  }


  $cts->add_title(2,"3. Caution et eventuel prix");

  if ( $Step == 2 )
  {
    $prix =0;
    $caution =0;
    if(!empty($objets))
    {
      foreach ( $objets as $objet )
      {
        $caution+=$objet->caution;
        $prix +=$objet->prix_emprunt;
      }
    }
    $frm->add_price_field("caution","Caution",$caution);
    $frm->add_price_field("prix_emprunt","Prix",$prix);
    $frm->add_text_area("notes","Notes");
    $frm->add_submit("valid","Terminer");
    $cts->add($frm);
  }
  elseif( $Step > 2 )
  {
    if ( $_REQUEST["caution"] )
      $cts->add_paragraph("Caution : ".($_REQUEST["caution"]/100)." Euros");
    if ( $_REQUEST["prix_emprunt"] )
      $cts->add_paragraph("Prix : ".($_REQUEST["prix_emprunt"]/100)." Euros");
  }

  $cts->add_title(2,"4. Impression bon");

  if ( $Step == 3 )
  {
    $cts->add_paragraph($emp->get_html_link()." : <a href=\"emprunt.php?action=print&amp;id_emprunt=".$emp->id."\">Imprimer</a>");

  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

if (!$site->user->ae)
  $site->error_forbidden();


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Interface de reservation
 */
$Step = intval($_REQUEST["step"]);

if ( $_REQUEST["action"] == "gonextres" )
{
  if ( $_REQUEST["step"] == 0 )
  {
    if ( $_REQUEST["asso"] == "asso" )
      $asso->load_by_id($_REQUEST["id_asso"]);

    if ( $site->user->is_in_group("gestion_ae") )
    {
      if ( $_REQUEST["emp"] == "moi" )
        $user = $site->user;
      elseif ( $_REQUEST["emp"] == "carte" )
        $user->load_by_carteae($_REQUEST["carte"]);
      elseif ( $_REQUEST["emp"] == "email" )
        $user->load_by_id($_REQUEST["id_utilisateur"]);
    }
    else
      $user = &$site->user;

    if ( $user->id < 1 )
      $Error="Utilisateur inconnu";
    elseif ( $_REQUEST["asso"] == "asso" && $asso->id < 1 )
      $Error="Association inconnue";
    elseif ( $_REQUEST["endtime"] <= time() )
      $Error="Date et heure de fin invalide";
    elseif ( $_REQUEST["starttime"] <= time() )
      $Error="Date de début invalide";
    elseif ( $_REQUEST["starttime"] >= $_REQUEST["endtime"] )
      $Error="Dates invalides";
    else
      $Step=1;

  }
  else  if ( $Step > 0 )
  {
    $asso->load_by_id($_REQUEST["id_asso"]);

    if ( $site->user->is_in_group("gestion_ae") )
      $user->load_by_id($_REQUEST["id_utilisateur"]);
    else
      $user = &$site->user;

    if ( $user->id < 1 || $_REQUEST["endtime"] <= time() ||
      $_REQUEST["starttime"] >= $_REQUEST["endtime"]  || $_REQUEST["endtime"] <= time() )
    {
      $Step=0;
      $Error="Erreur de passage de valeurs";
    }
  }

  if ( $Step >= 1 )
  {
    $objets=array();
    if(!empty($_REQUEST["id_objets"]))
    {
      foreach ($_REQUEST["id_objets"] as $id_objet)
      {
        $obj = new objet($site->db);
        $obj->load_by_id($id_objet);
        if ( $obj->id > 0 && $obj->is_avaible($_REQUEST["starttime"],$_REQUEST["endtime"]) )
          $objets[] = $obj;
      }
    }
    if ( $Step > 1 && !count($objets) ) $Step=1;
  }

  if ( $_REQUEST["step"] == 1 )
  {

    if ( isset($_REQUEST["next"]))
    {
      $Step=2;
    }
    elseif ( $_REQUEST["kind"] == "type" )
    {
      $max_num=-1;
      if(!empty($objets))
      {
        foreach ( $objets as $objet )
        {
          if ( $objet->id_objtype == $_REQUEST["id_objtype"] )
            if ( $max_num < $objet->num )
              $max_num = $objet->num;
        }
      }


      $qty = intval($_REQUEST['nombre']);
      ////date_debut_emp > $to || (r).date_fin_emp < $from
      $req = new requete($site->db,"SELECT * FROM `inv_objet` " .
        "WHERE `id_objtype`='" . mysql_real_escape_string($_REQUEST["id_objtype"]) . "'" .
        "AND `num_objet` > $max_num " .
        "AND NOT EXISTS(SELECT * FROM inv_emprunt_objet ".
        "INNER JOIN inv_emprunt ON inv_emprunt.id_emprunt=inv_emprunt_objet.id_emprunt ".
        "WHERE ".
        "(( inv_emprunt.date_debut_emp < '".date("Y-m-d H:i:s",$_REQUEST["endtime"])."' ) AND ".
        "( inv_emprunt.date_fin_emp > '".date("Y-m-d H:i:s",$_REQUEST["starttime"])."' )) ".
        "AND inv_emprunt_objet.id_objet=inv_objet.id_objet ".
        "AND inv_emprunt_objet.retour_effectif_emp IS NULL) " .
        "ORDER BY `num_objet` " .
        "LIMIT $qty");

      if ( $req->lines == 0 )
        $Error = "Il n'y pas de ce type de matériel disponible aux dates demandés.";
      elseif ( $req->lines != $qty )
        $Error = "Il n'y pas assez de ce type de matériel disponible aux dates demandés. Seuls ".$req->lines." sont disponibles.";
      else
      {
        while ( $row = $req->get_row() )
        {
          $obj = new objet($site->db);
          $obj->_load($row);
          add_objet_once($objets,$obj);
        }
      }
    }
    elseif ( $_REQUEST["kind"] == "objet" )
    {
      $obj = new objet($site->db);
      $obj->load_by_id($_REQUEST["id_objet"]);

      if ( $obj->id < 1 )
        $Error="Objet inconnu.";
      elseif ( !$obj->is_avaible($_REQUEST["starttime"],$_REQUEST["endtime"]) )
        $Error="Objet non disponible.";
      else
        add_objet_once($objets,$obj);

    }
    elseif ( $_REQUEST["kind"] == "exact" )
    {
      $obj = new objet($site->db);
      $obj->load_by_cbar($_REQUEST["cbar"]);

      if ( $obj->id < 1 )
        $Error="Objet non inconnu.";
      elseif ( !$obj->is_avaible($_REQUEST["starttime"],$_REQUEST["endtime"]) )
        $Error="Objet non disponible.";
      else
        add_objet_once($objets,$obj);

    }
  }



  if ( $_REQUEST["step"] == 2 )
  {

    $Step=3;
    if ( $asso->id < 1 ) $asso->id = null;

    $emp->add_emprunt ( $user->id, $asso->id, null, $_REQUEST["starttime"], $_REQUEST["endtime"] );

    foreach ( $objets as $objet )
       $emp->add_object($objet->id);

    if ( $site->user->is_in_group("gestion_ae") )
      $emp->modere ( $site->user->id, $_REQUEST["caution"], $_REQUEST["prix_emprunt"], $_REQUEST["notes"] );

  }

}

$site->start_page("services","Emprunts de matériel");
$frm = new form("reserver","emprunt.php",$Step==0);
$frm->add_hidden("step",$Step);
$frm->add_hidden("action","gonextres");

if ( $message )
  $site->add_contents($message);

$cts = new contents("Emprunts de matériel");
if ( $Error )
  $frm->error($Error);

if ( $site->user->is_in_group("gestion_ae"))
{
  $tabs = array(array(false,"ae/modereemp.php", "Modération"),
        array(false,"ae/modereemp.php?view=togo", "A venir"),
        array(false,"ae/modereemp.php?view=out", "Matériel prété"),
        array(true,"emprunt.php", "Reserver"),
        array(false,"emprunt.php?page=retrait", "Preter"),
        array(false,"emprunt.php?page=retour", "Retour")
        );
  $cts->add(new tabshead($tabs,true));
}

$cts->add_title(2,"1. Identité de l'emprunteur");

if ( $Step == 0 )
{
  $frm->add_datetime_field("starttime","Debut de l'emprunt");
  $frm->add_datetime_field("endtime","Fin de l'emprunt");

  $ssfrm = new form("mtf",null,null,null,"Cadre");

  $sfrm = new form("asso",null,null,null,"A titre personnel");
  $ssfrm->add($sfrm,false,true,$_REQUEST["id_asso"]==0,"nasso",true);

  $sfrm = new form("asso",null,null,null,"Pour une association");
  $sfrm->add_entity_select("id_asso"," : ",$site->db,"asso",$_REQUEST["id_asso"]);
  $ssfrm->add($sfrm,false,true,$_REQUEST["id_asso"]>0,"asso",true);

  $frm->add($ssfrm);

  if ( $site->user->is_in_group("gestion_ae"))
  {
    $ssfrm = new form("qui",null,null,null,"Emprunteur");

    $sfrm = new form("emp",null,null,null,"Moi même");
    $ssfrm->add($sfrm,false,true,false,"moi",true);

    $sfrm = new form("emp",null,null,null,"Le cotisant dont la carte est");
    $sfrm->add_text_field("carte"," : ");
    $ssfrm->add($sfrm,false,true,true,"carte",true);

    $sfrm = new form("emp",null,null,null,"L'utilisateur dont l'adresse email est");
    $sfrm->add_entity_smartselect("id_utilisateur","",new utilisateur($site->db));
    $ssfrm->add($sfrm,false,true,false,"email",true);

    $sfrm = new form("emp",null,null,null,"La personne non inscrite suivante");
    $sfrm->add_text_field("emprunteur_ext"," : ");
    $ssfrm->add($sfrm,false,true,false,"ext",true);

    $frm->add($ssfrm);
  }

  $frm->add_submit("next","Suivant");

  $cts->add($frm);
}
elseif( $Step > 0 )
{
  $frm->add_hidden("starttime",$_REQUEST["starttime"]);
  $frm->add_hidden("endtime",$_REQUEST["endtime"]);
  $frm->add_hidden("id_asso",$asso->id);
  $frm->add_hidden("id_utilisateur",$user->id);

  $cts->add_paragraph("Du ".date("d/m/Y H:i",$_REQUEST["starttime"])." jusqu'au ".date("d/m/Y H:i",$_REQUEST["endtime"]));

  if ( $asso->id > 0 )
    $cts->add_paragraph("Pour l'association : ".$asso->get_html_link());

  $cts->add_paragraph("Emprunteur : ".$user->get_html_link());
}

$cts->add_title(2,"2. Matériel");

if ( $Step >= 1 && count($objets) )
{
  $lst = new itemlist();
  if(!empty($objets))
  {
    foreach ( $objets as $n=>$objet )
    {
      $frm->add_hidden("id_objets[$n]",$objet->id);
      $lst->add($objet->nom." ".$objet->cbar);
    }
    $cts->add($lst);
  }



}


if ( $Step == 1 )
{
  $req = new requete($site->db, "SELECT `id_objtype`,`nom_objtype` FROM `inv_type_objets`
          WHERE `empruntable_objtype` = '1'
          ORDER BY `nom_objtype`");
  while(list($id,$nom)=$req->get_row()) $types[$id]=$nom;

  $ids[] = 0;
  if(!empty($objets))
    foreach ( $objets as $objet ) $ids[] = $objet->id;


  $req = new requete($site->db, "SELECT `id_objet`,`nom_objet` FROM `inv_objet`
          INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype`
          WHERE `objet_empruntable` = '1' AND `nom_objet` != '' AND `empruntable_objtype` = '0' AND
          `id_objet` NOT IN (".implode(",",$ids).")
          ORDER BY `nom_objet`");

  while(list($id,$nom)=$req->get_row()) $eobjets[$id]=$nom;



  $sfrm = new form("kind",null,null,null,"Objet(s) du type");
  $sfrm->add_select_field("id_objtype"," : ",$types);
  $sfrm->add_text_field("nombre"," x ","1");
  $frm->add($sfrm,false,true,false,"type",true);

  $sfrm = new form("kind",null,null,null,"L'objet");
  $sfrm->add_select_field("id_objet"," : ",$eobjets);
  $frm->add($sfrm,false,true,true,"objet",true);

  $sfrm = new form("kind",null,null,null,"L'objet dont le code barre est");
  $sfrm->add_text_field("cbar"," : ");
  $frm->add($sfrm,false,true,false,"exact",true);

  $frm->add_submit("add","Ajouter");
  if ( count($objets) )
    $frm->add_submit("next","Etape suivante");
  $cts->add($frm);
}

$cts->add_title(2,"3. Caution");

if ( $Step == 2 )
{
  if ( $site->user->is_in_group("gestion_ae"))
  {
    $prix =0;
    $caution =0;
    if(!empty($objets))
    {
      foreach ( $objets as $objet )
      {
        $caution+=$objet->caution;
        $prix +=$objet->prix_emprunt;
      }
      $frm->add_price_field("caution","Caution",$caution);
      $frm->add_price_field("prix_emprunt","Prix",$prix);
      $frm->add_text_area("notes","Notes");
    }
  }
  else
  {
    $cts->add_paragraph("La caution et l'eventuel prix de l'emprunt seront fixés ultérieurement par un modérateur.");
  }
  $frm->add_submit("next","Terminer");
  $cts->add($frm);
}
elseif( $Step > 2 )
{
  if ( $site->user->is_in_group("gestion_ae"))
  {
    if ( $_REQUEST["caution"] )
      $cts->add_paragraph("Caution : ".($_REQUEST["caution"]/100)." Euros");
    if ( $_REQUEST["prix_emprunt"] )
      $cts->add_paragraph("Prix : ".($_REQUEST["prix_emprunt"]/100)." Euros");
  }
  else
    $cts->add_paragraph("La caution et l'eventuel prix de l'emprunt seront fixés ultérieurement par un modérateur.");
}

$cts->add_title(2,"4. Reçu");

if ( $Step == 3 )
{
  $cts->add_paragraph("N° de reservation : ".$emp->get_html_link());
}
$site->add_contents($cts);
$site->end_page();
?>
