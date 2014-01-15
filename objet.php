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
require_once($topdir. "include/entities/files.inc.php");
$site = new site ();

$site->allow_only_logged_users("services");

$objet = new objet($site->db,$site->dbrw);
$asso_prop = new asso($site->db);
$asso_gest = new asso($site->db);
$objtype = new objtype($site->db);
$sitebat = new sitebat($site->db);
$bat = new batiment($site->db);
$salle = new salle($site->db);



if ( isset($_REQUEST["id_objtype"]) )
  $objtype->load_by_id($_REQUEST["id_objtype"]);

if ( isset($_REQUEST["id_objet"]) )
{
  $objet->load_by_id($_REQUEST["id_objet"]);
  if ( $objet->id < 1 )
  {
    $site->error_not_found("services");
    exit();
  }
  $can_admin=false;

  $asso_gest->load_by_id($objet->id_asso);

  if ( ($asso_gest->id > 0 && $asso_gest->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU)) ||
        $site->user->is_in_group("gestion_ae")  )
    $can_admin=true;


  if ( $_REQUEST["action"] == "saveobjet" && $can_admin)
  {
    $objtype->load_by_id($_REQUEST["id_objtype"]);
    $asso_gest->load_by_id($_REQUEST["id_asso"]);
    $asso_prop->load_by_id($_REQUEST["id_asso_prop"]);
    $salle->load_by_id($_REQUEST["id_salle"]);

    $objet->save_objet (
        $asso_gest->id, $asso_prop->id, $salle->id, $objtype->id, $objet->id_op,
        empty($_REQUEST["id_photo"]) ? null : $_REQUEST["id_photo"][0]->id, $_REQUEST["nom"],
        $_REQUEST["num_serie"], $_REQUEST["prix"], $_REQUEST["caution"],
        $_REQUEST["prix_emprunt"], $_REQUEST["empruntable"],
        $_REQUEST["en_etat"], $_REQUEST["date_achat"], $_REQUEST["notes"], $_REQUEST["cbar"], $_REQUEST["archive"] );
  }
  elseif ( $_REQUEST["page"] == "edit" && $can_admin)
  {
    $objtype->load_by_id($objet->id_objtype);
    $photo = new dfile($site->db);
    if ($objet->id_photo != null)
        $photo->load_by_id ($objet->id_photo);
    $path = "<a href=\"objtype.php\">Inventaire</a> / ".$objtype->get_html_link()." / ".$objet->get_html_link();

    $site->start_page("services","Objet ".$objet->nom." ".$objtype->code.$objet->num);

    $frm = new form("saveobjet","objet.php?id_objet=".$objet->id,true,"POST",$path. " / Modifier");
    $frm->add_hidden("action","saveobjet");
    $frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $objet->id_objtype);
    $frm->add_text_field("cbar","Code barre", $objet->cbar);
    $frm->add_text_field("nom","Nom", $objet->nom);
    $frm->add_text_field("num_serie","Numéro de série", $objet->num_serie);
    $frm->add_date_field("date_achat","Date d'achat", $objet->date_achat);
    $frm->add_attached_files_field("id_photo", "Photo de l'objet", $objet->id_photo == null ? array() : array($photo), $objet->id_asso);
    $frm->add_entity_select("id_asso_prop", "Propriétaire", $site->db, "asso", $objet->id_asso_prop, false, array("id_asso_parent"=>NULL));
    $frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso",$objet->id_asso);
    $frm->add_entity_select("id_salle", "Salle", $site->db, "salle",$objet->id_salle);
    $frm->add_price_field("prix","Prix d'achat",$objet->prix);
    $frm->add_price_field("caution","Prix de la caution",$objet->caution);
    $frm->add_price_field("prix_emprunt","Prix d'un emprunt",$objet->prix_emprunt);
    $frm->add_checkbox("empruntable","Reservable via le site internet",$objet->empruntable);
    $frm->add_checkbox("en_etat","En etat",$objet->en_etat);
    $frm->add_checkbox("archive", "Archive",$objet->archive);
    $frm->add_text_area("notes","Notes",$objet->notes);
    $frm->add_submit("valide","Enregistrer");
    $site->add_contents($frm);

    $site->end_page();
    exit();
  }
  elseif ( $_REQUEST["action"] == "preter" && $can_admin)
  {
    $asso = new asso($site->db);
    $user = new utilisateur($site->db);

    if ( $_REQUEST["asso"] == "asso" )
      $asso->load_by_id($_REQUEST["id_asso"]);

    if ( $_REQUEST["emp"] == "moi" )
      $user = $site->user;
    elseif ( $_REQUEST["emp"] == "carte" )
      $user->load_by_carteae($_REQUEST["carte"]);
    elseif ( $_REQUEST["emp"] == "email" )
      $user->load_by_id($_REQUEST["id_utilisateur"]);

    if ( $_REQUEST["emp"] != "ext" && $user->id < 1 )
      $Error="Utilisateur inconnu";
    elseif ( $_REQUEST["asso"] == "asso" && $asso->id < 1 )
      $Error="Association inconnue";
    elseif ( $_REQUEST["endtime"] <= time() )
      $Error="Date et heure de fin invalide";
    else
    {
      $emp = new emprunt ( $site->db, $site->dbrw );

      $emp->add_emprunt ( $user->id, $asso->id, $_REQUEST["emprunteur_ext"], time(), $_REQUEST["endtime"] );

      $emp->add_object($objet->id);

      $emp->retrait ( $site->user->id, $_REQUEST["caution"], $_REQUEST["prix_emprunt"], $_REQUEST["notes"] );

      $Sucess = new contents("Pret enregistré",$emp->get_html_link()." : <a href=\"emprunt.php?action=print&amp;id_emprunt=".$emp->id."\">Imprimer</a>");

    }


  }

  $asso_prop->load_by_id($objet->id_asso_prop);
  $objtype->load_by_id($objet->id_objtype);
  $salle->load_by_id($objet->id_salle);
  $bat->load_by_id($salle->id_batiment);
  $sitebat->load_by_id($bat->id_site);

  $path = "<a href=\"objtype.php\">Inventaire</a> / ".$objtype->get_html_link()." / ".$objet->get_html_link();

  $site->start_page("services","Objet ".$objet->nom." ".$objtype->code.$objet->num);

  $cts = new contents($path);

  if ( $can_admin )
    $cts->set_toolbox(new toolbox(array("objet.php?page=edit&id_objet=".$objet->id=>"Editer")));


  $tabs=array(
        array("","objet.php?id_objet=".$objet->id, "Informations"),
        array("hist","objet.php?id_objet=".$objet->id."&view=hist", "Historique")
        );

  if ( $can_admin )
    $tabs[] = array("borrow","objet.php?id_objet=".$objet->id."&view=borrow", "Preter");


  $cts->add(new tabshead($tabs,$_REQUEST["view"]));

  if ( $_REQUEST["view"] == "borrow" )
  {
    if ( $Sucess )
      $cts->add($Sucess,true);

    $frm = new form("emprunter","objet.php?id_objet=".$objet->id."&view=borrow",!isset($Error),"POST","Preter");

    $frm->add_hidden("action","preter");

    if ( $Error )
      $frm->error($Error);

    $frm->add_datetime_field("endtime","Fin de l'emprunt");

    $ssfrm = new form("mtf",null,null,null,"Cadre");

    $sfrm = new form("asso",null,null,null,"A titre personnel");
    $ssfrm->add($sfrm,false,true,$_REQUEST["id_asso"]==0,"nasso",true);

    $sfrm = new form("asso",null,null,null,"Pour une association");
    $sfrm->add_entity_select("id_asso"," : ",$site->db,"asso",$_REQUEST["id_asso"]);
    $ssfrm->add($sfrm,false,true,$_REQUEST["id_asso"]>0,"asso",true);

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

    $frm->add_price_field("caution","Caution",$caution);
    $frm->add_price_field("prix_emprunt","Prix",$prix);
    $frm->add_text_area("notes","Notes");

    $frm->add_submit("valid","Terminer");


    $cts->add($frm);

  }
  elseif ( $_REQUEST["view"] == "hist" )
  {
    $req = new requete($site->db,"SELECT inv_emprunt.*, " .
        "asso.nom_asso, asso.id_asso," .
        "inv_emprunt_objet.retour_effectif_emp AS `date_retour_effectif_emp`, " .
        "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`," .
        "`utilisateurs`.`id_utilisateur` " .
        "FROM inv_emprunt_objet " .
        "INNER JOIN inv_emprunt ON inv_emprunt_objet.id_emprunt=inv_emprunt.id_emprunt " .
        "LEFT JOIN utilisateurs ON utilisateurs.id_utilisateur=inv_emprunt.id_utilisateur " .
        "LEFT JOIN asso ON inv_emprunt.id_asso=asso.id_asso " .
        "WHERE inv_emprunt_objet.id_objet='".$objet->id."' AND inv_emprunt_objet.retour_effectif_emp IS NOT NULL " .
        "ORDER BY inv_emprunt.date_debut_emp");

    $cts->add(new sqltable("histobjet",
        "Historique", $req, "emprunt.php",
        "id_emprunt",
        array("id_emprunt"=>"N° d'emprunt","nom_utilisateur"=>"Qui","nom_asso"=>"Pour","date_debut_emp"=>"Du","date_retour_effectif_emp"=>"Au (effectif)"),
        array("view"=>"Information sur l'emprunt"), array(), array()
        ),true);
  }
  else
  {
  $tbl = new table("Informations");
  $tbl->add_row(array("Nom / Num",$objet->nom." ".$objet->num));
  $tbl->add_row(array("Type",$objtype->get_html_link()));
  $tbl->add_row(array("Propriétaire",$asso_prop->get_html_link()));
  $tbl->add_row(array("Gestionnaire",$asso_gest->get_html_link()));
  $tbl->add_row(array("Emplacement",$salle->get_html_link()." ".$bat->get_html_link() ." ".$sitebat->get_html_link()));
  $tbl->add_row(array("Numéro de série",$objet->num_serie));
  $tbl->add_row(array("Photo de l'objet",'<a href="d.php?id_file='.$objet->id_photo.'">Lien vers la photo</a>'));
  $tbl->add_row(array("Date d'achat",date("d/m/Y",$objet->date_achat)));
  $tbl->add_row(array("Reservable via le site internet",$objet->empruntable?"Oui":"Non"));
  $tbl->add_row(array("En etat",$objet->en_etat?"Oui":"Non"));
  $tbl->add_row(array("Archive (sorti de l'inventaire)",$objet->archive?"Oui":"Non"));
  if ( $can_admin )
  {
    $tbl->add_row(array("Code barre",$objet->cbar));
    $tbl->add_row(array("Prix",$objet->prix/100));
    $tbl->add_row(array("Caution",$objet->caution/100));
    $tbl->add_row(array("Prix emprunt",$objet->prix_emprunt/100));
    $tbl->add_row(array("Notes",$objet->notes));
  }

  $emp = new emprunt($site->db);
  $emp->load_by_objet($objet->id);
  if ( $emp->is_valid() )
  {
    $user = new utilisateur($site->db);
    $user->load_by_id($emp->id_utilisateur);
    $text = $user->get_html_link();
    if ( $emp->id_asso )
    {
      $asso = new asso($site->db);
      $asso->load_by_id($emp->id_asso);
      $text .= " pour ".$asso->get_html_link();
    }
    $text .=" jusqu'au ".date("d/m/Y H:i",$emp->date_fin);
    $text .= " (Emprunt ".$emp->get_html_link().")";
    $tbl->add_row(array("Actuellment emprunté par",$text));
  }

  if ( $objet->is_book() )
  {
    require_once($topdir. "include/entities/books.inc.php");

    $editeur = new editeur($site->db);
    $serie = new serie($site->db);
    $livre = new livre($site->db);
    $auteur = new auteur($site->db);

    $livre->load_by_id($objet->id);
    $editeur->load_by_id($livre->id_editeur);
    $serie->load_by_id($livre->id_serie);

    $req = new requete ( $site->db, "SELECT " .
        "`bk_auteur`.`id_auteur`,`bk_auteur`.`nom_auteur` " .
        "FROM `bk_livre_auteur` " .
        "INNER JOIN `bk_auteur` ON `bk_livre_auteur`.`id_auteur`=`bk_auteur`.`id_auteur` " .
        "WHERE id_objet='".$livre->id."'");

    $auteurs = null;

    while ( $row = $req->get_row() )
    {
      $auteur->_load($row);
      if ( is_null($auteurs) )
        $auteurs .= $auteur->get_html_link();
      else
        $auteurs .= ", ".$auteur->get_html_link();
    }

    $tbl->add_row(array("Special","<b>Cet objet est un livre</b> : <a href=\"biblio/?id_livre=".$livre->id."\">Voir sa fiche livre</a>"));

    $tbl->add_row(array("Livre : Titre",$livre->nom));
    $tbl->add_row(array("Livre : Serie",$serie->get_html_link()));
    $tbl->add_row(array("Livre : N°",$livre->num_livre));
    $tbl->add_row(array("Livre : Auteur(s)",$auteurs));
    $tbl->add_row(array("Livre : Editeur",$editeur->get_html_link()));
    $tbl->add_row(array("Livre : ISBN",$livre->isbn));

  }

  if ( $objet->is_jeu() )
    $tbl->add_row(array("Special","<b>Cet objet est un jeu</b> : <a href=\"biblio/?id_jeu=".$objet->id."\">Voir sa fiche jeu</a>"));

  $cts->add($tbl,true);
  }
  $site->add_contents($cts);

  $site->end_page();
  exit();
}

if ( isset($_REQUEST["id_asso"]))
  $asso_gest->load_by_id($_REQUEST["id_asso"]);

if ( !($asso_gest->id > 0 && $asso_gest->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU)) &&
  !$site->user->is_in_group("gestion_ae")  )
  $site->error_forbidden("services","group",9);

if ( $_REQUEST["action"] == "addobjet" )
{
  $asso_prop->load_by_id($_REQUEST["id_asso_prop"]);
  $salle->load_by_id($_REQUEST["id_salle"]);

  if ( $asso_prop->id > 0 && $asso_gest->id > 0 && $salle->id > 0 && $objtype->id > 0)
  {
    $nb = intval($_REQUEST["nb"]);
    $sucess = new itemlist();
    $sucess->add("Les objets suivants ont bien été ajoutés: ");
    for($i=0;$i<$nb;$i++)
    {
      $objet->add ( $asso_gest->id, $asso_prop->id, $salle->id, $objtype->id, NULL,
                    empty($_REQUEST["id_photo"]) ? null : $_REQUEST["id_photo"][0]->id, $_REQUEST["nom"],
                    $objtype->code, $_REQUEST["num_serie"], $_REQUEST["prix"], $_REQUEST["caution"], $_REQUEST["prix_emprunt"], $_REQUEST["empruntable"],
                    $_REQUEST["en_etat"], $_REQUEST["date_achat"], $_REQUEST["notes"] );

      if ( $_REQUEST["force_cbar"] && $nb == 1 )
        $objet->set_cbar($_REQUEST["force_cbar"]);


      $sucess->add($objet->get_html_link());
    }
    $sucess->add("Retourner au type : ".$objtype->get_html_link());
  }
}

$site->start_page("services","Objets");
//objtype

if ( $sucess )
{
  $site->add_contents($sucess);
}

$frm = new form("addobjet","objet.php",!$sucess,"POST","Ajouter");
$frm->add_hidden("action","addobjet");
$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $objtype->id);
$frm->add_text_field("nb","Nombre d'objets à ajouter","1",true);
$frm->add_text_field("nom","Nom");
$frm->add_attached_files_field("id_photo", "Photo de l'objet", array(), $_REQUEST["id_asso"]);
$frm->add_text_field("num_serie","Numéro de série");
$frm->add_date_field("date_achat","Date d'achat");
$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", false, false, array("id_asso_parent"=>NULL));
$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso",$_REQUEST["id_asso"]);
$frm->add_entity_select("id_salle", "Salle", $site->db, "salle");
$frm->add_price_field("prix","Prix d'achat",$objtype->prix);
$frm->add_price_field("caution","Prix de la caution",$objtype->caution);
$frm->add_price_field("prix_emprunt","Prix d'un emprunt",$objtype->prix_emprunt);
$frm->add_checkbox("empruntable","Reservable via le site internet",$objtype->empruntable);
$frm->add_checkbox("en_etat","En etat",true);
$frm->add_text_area("notes","Notes");
$frm->add_submit("valide","Ajouter");
$site->add_contents($frm);

$site->end_page();

?>
