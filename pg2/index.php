<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des Etudiants de
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
require_once("include/site.inc.php");
require_once($topdir."include/entities/pgfiche.inc.php");
require_once($topdir."include/entities/rue.inc.php");
require_once($topdir."include/entities/ville.inc.php");
require_once($topdir."include/entities/entreprise.inc.php");
require_once($topdir."include/entities/bus.inc.php");
require_once($topdir."include/entities/pgtype.inc.php");

require_once($topdir."include/cts/board.inc.php");
require_once($topdir."include/cts/pg.inc.php");
require_once($topdir."include/cts/gmap.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");

$site = new pgsite();

$fiche = new pgfiche($site->db,$site->dbrw);
$category = new pgcategory($site->db,$site->dbrw);

if ( isset($_REQUEST["id_pgfiche"]) )
{
  if ( $fiche->load_by_id($_REQUEST["id_pgfiche"]) )
    $category->load_by_id($fiche->id_pgcategory);
}
elseif ( isset($_REQUEST["id_pgcategory"]) )
  $category->load_by_id($_REQUEST["id_pgcategory"]);

if ( $fiche->is_valid() && $site->is_admin() && $_REQUEST["action"] == "delete" )
{
  if ( $site->is_sure ( "","Suppression de la fiche ".$fiche->nom,"pgfiche".$fiche->id, 1 ) )
  {
    $fiche->delete();
    $fiche->id=null;
  }
}
else if ( $category->is_valid() && $site->is_admin() && $_REQUEST["action"] == "delete" )
{
  if ( $category->id_pgcategory_parent &&
       $site->is_sure ( "","Suppression de la catégorie ".$category->nom,"pgcategory".$category->id, 1 )  )
  {
    $category->delete();
    $category->load_by_id($category->id_pgcategory_parent);
  }
}

if ( $category->is_valid() )
{
  $title_path = $category->nom;

  if ( $category->id_pgcategory_parent == 1 )
  {
    $id_pgcategory1 = $category->id;
    $path = "&nbsp;";
  }
  else
  {
    $path = $category->get_html_link();
    $parent = new pgcategory($site->db);
    $parent->id_pgcategory_parent = $category->id_pgcategory_parent;

    while ( !is_null($parent->id_pgcategory_parent)
            && $parent->id_pgcategory_parent != 1
            && $parent->load_by_id($parent->id_pgcategory_parent) )
    {
      if ( $parent->id_pgcategory_parent == 1 )
        $id_pgcategory1 = $parent->id;
      else
        $path = $parent->get_html_link()." / ".$path;

      $title_path = $parent->nom." / ".$title_path;
    }
  }
  $title_path = "Petit géni / ".$title_path;
}

if ( $category->is_valid() && $site->is_admin() && $_REQUEST["action"] == "createfiche" )
{
  $ent = new entreprise($site->db);
  $rue = new rue($site->db,$site->dbrw);
  $typerue = new typerue($site->db);
  $ville = new ville($site->db);

  $ent->load_by_id($_REQUEST["id_entreprise"]);
  $typerue->load_by_id($_REQUEST["id_typerue"]);
  $ville->load_by_id($_REQUEST["id_ville"]);

  $rue->load_or_create ( $typerue->id, $ville->id, $_REQUEST["nom_rue"] );

  $fiche->create (
    $ville->id, $_REQUEST["nom"], $_REQUEST["lat"], $_REQUEST["long"], 0,
    $category->id, $rue->id, $ent->id, $_REQUEST["description"],
    $_REQUEST["longuedescription"], $_REQUEST["tel"], $_REQUEST["fax"],
    $_REQUEST["email"], $_REQUEST["website"], $_REQUEST["numrue"],
    $_REQUEST["adressepostal"], isset($_REQUEST["placesurcarte"]),
    isset($_REQUEST["contraste"]), null, null, $_REQUEST["infointerne"],
    time(), $_REQUEST["date_validite"], $site->user->id );
  $fiche->set_tags($_REQUEST["tags"]);

  $_REQUEST["page"]="edit";
}

if ( $fiche->is_valid() )
{
  if ( $site->is_admin() )
  {
    if ( $_REQUEST["action"] == "save" )
    {
      $ent = new entreprise($site->db);
      $rue = new rue($site->db,$site->dbrw);
      $typerue = new typerue($site->db);
      $ville = new ville($site->db);

      $ent->load_by_id($_REQUEST["id_entreprise"]);
      $typerue->load_by_id($_REQUEST["id_typerue"]);
      $ville->load_by_id($_REQUEST["id_ville"]);

      $rue->load_or_create ( $typerue->id, $ville->id, $_REQUEST["nom_rue"] );

      $fiche->update (
        $ville->id, $_REQUEST["nom"], $_REQUEST["lat"], $_REQUEST["long"], 0,
        $category->id, $rue->id, $ent->id, $_REQUEST["description"],
        $_REQUEST["longuedescription"], $_REQUEST["tel"], $_REQUEST["fax"],
        $_REQUEST["email"], $_REQUEST["website"], $_REQUEST["numrue"],
        $_REQUEST["adressepostal"], isset($_REQUEST["placesurcarte"]),
        isset($_REQUEST["contraste"]), null, null, $_REQUEST["infointerne"],
        time(), $_REQUEST["date_validite"], $site->user->id );
      $fiche->set_tags($_REQUEST["tags"]);
    }
    elseif ( $_REQUEST["action"] == "addarretbus" )
    {
      if ( $_REQUEST["id_arretbus"] )
        $fiche->add_arretbus ( $_REQUEST["id_arretbus"] );
    }
    elseif ( $_REQUEST["action"] == "deletearretbus" )
    {
      $fiche->delete_arretbus ( $_REQUEST["id_arretbus"] );
    }
    elseif ( $_REQUEST["action"] == "addextrapgcategory" )
    {
      if ( $_REQUEST["id_pgcategory"] )
        $fiche->add_extra_pgcategory ( $_REQUEST["id_pgcategory"], $_REQUEST["titre"],
          $_REQUEST["soustitre"] );
    }
    elseif ( $_REQUEST["action"] == "deleteextrapgcategory" )
    {
      $fiche->delete_extra_pgcategory ( $_REQUEST["id_pgcategory"] );
    }
    elseif ( $_REQUEST["action"] == "addtarif" )
    {
      if ( $_REQUEST["id_typetarif"] )
        $fiche->add_tarif ( $_REQUEST["id_typetarif"], $_REQUEST["min_tarif"],
          $_REQUEST["max_tarif"], $_REQUEST["commentaire"], time(), $_REQUEST["date_validite"] );
    }
    elseif ( $_REQUEST["action"] == "deletetarif" )
    {
      $fiche->delete_tarif ( $_REQUEST["id_typetarif"] );
    }
    elseif ( $_REQUEST["action"] == "addreduction" )
    {
      if ( $_REQUEST["id_typereduction"] )
        $fiche->add_reduction ( $_REQUEST["id_typereduction"], $_REQUEST["valeur"],
          $_REQUEST["unite"], $_REQUEST["commentaire"], time(), $_REQUEST["date_validite"] );
    }
    elseif ( $_REQUEST["action"] == "deletereduction" )
    {
      $fiche->delete_reduction ( $_REQUEST["id_typereduction"] );
    }
    elseif ( $_REQUEST["action"] == "addservice" )
    {
      if ( $_REQUEST["id_service"] )
        $fiche->add_service ( $_REQUEST["id_service"], $_REQUEST["commentaire"],
          time(), $_REQUEST["date_validite"] );
    }
    elseif ( $_REQUEST["action"] == "deleteservice" )
    {
      $fiche->delete_service ( $_REQUEST["id_service"] )  ;
    }
  }







  $path .= " / ".$fiche->get_html_link();
  $title_path .= " / ".$fiche->nom;
  $site->start_page("pg",$title_path);

  $site->add_alternate_geopoint($fiche);
  $site->set_meta_information($fiche->get_tags(),$fiche->description);

  $cts = new contents("<a href=\"index.php\">Le Guide</a>");
  $cts->add(new pgtabshead($site->db,$id_pgcategory1));


  if ( $site->is_admin() && $_REQUEST["page"] == "edit" )
  {
    $cts->add_paragraph($path." / Editer");

    $ent = new entreprise($site->db);
    $rue = new rue($site->db,$site->dbrw);
    $typerue = new typerue($site->db);
    $ville = new ville($site->db);

    $ent->load_by_id($fiche->id_entreprise);
    $rue->load_by_id($fiche->id_rue);
    $typerue->load_by_id($rue->id_typerue);
    $ville->load_by_id($fiche->id_ville);

    if ( !isset($_REQUEST["action"]) )
    {
      $frm = new form("editfiche","index.php?id_pgfiche=".$fiche->id,false,"POST","Informations essentielles");
      $frm->add_hidden("action","save");

      $sfrm = new subform("desc","Description");
      $sfrm->add_text_field("nom","Nom",$fiche->nom);
      $sfrm->add_text_area("description","Description courte",$fiche->description);
      $sfrm->add_text_area("longuedescription","Description longue",$fiche->longuedescription);
      $sfrm->add_text_field("tags","Tags",$fiche->get_tags());
      $frm->addsub($sfrm);

      $sfrm = new subform("contact","Contacts clients");
      $sfrm->add_text_field("tel","Telephone",telephone_display($fiche->tel));
      $sfrm->add_text_field("fax","Fax",telephone_display($fiche->fax));
      $sfrm->add_text_field("email","Email",$fiche->email);
      $sfrm->add_text_field("website","Site internet",$fiche->website);
      $frm->addsub($sfrm);

      $sfrm = new subform("adresse","Addresse");
      $sfrm->add_text_field("numrue","Numéro dans la rue",$fiche->numrue);
      $sfrm->add_entity_smartselect ("id_typerue","Type de la rue", $typerue);
      $sfrm->add_text_field("nom_rue","Nom de la rue",$rue->nom);
      $sfrm->add_entity_smartselect ("id_ville","Ville", $ville,false,true,array("pg_ville"=>1));
      $frm->addsub($sfrm);

      $sfrm = new subform("pos","Positiion");
      $sfrm->add_geo_field("lat","Latidue","lat",$fiche->lat);
      $sfrm->add_geo_field("long","Longitude","long",$fiche->long);
      $frm->addsub($sfrm);

      $sfrm = new subform("adm","Coordonnées administratives");
      $sfrm->add_entity_smartselect ("id_entreprise","Entreprise", $ent);
      $sfrm->add_text_area("adressepostal","Adresse postale complète",$fiche->adressepostal);
      $frm->addsub($sfrm);

      $sfrm = new subform("rendu","Options et validité");
      $sfrm->add_checkbox("placesurcarte","Placer sur la carte",$fiche->placesurcarte);
      $sfrm->add_checkbox("contraste","Mettre en constraste",$fiche->contraste);
      $sfrm->add_date_field("date_validite","Valable jusqu'au",$fiche->date_validite);
      $frm->addsub($sfrm);

      $sfrm = new subform("int","Interne");
      $sfrm->add_text_area("infointerne","Commentaire interne",$fiche->infointerne);
      $frm->addsub($sfrm);

      $frm->add_submit("editfiche","Enregistrer");

      $cts->add($frm,true);
    }
    else
    {
      $cts->add_title(2,"<a href=\"index.php?page=edit&id_pgfiche=".$fiche->id."\">Informations essentielles</a>");
      $cts->add_paragraph("<a href=\"index.php?page=edit&id_pgfiche=".$fiche->id."\">...</a>");
    }

    $cts->add_title(2,"Arrets de bus");
    $req = new requete($site->db,"SELECT * FROM pg_fiche_arretbus ".
      "INNER JOIN geopoint ON(pg_fiche_arretbus.id_arretbus=geopoint.id_geopoint) ".
      "WHERE `id_pgfiche` = '".mysql_real_escape_string($fiche->id)."'");
    $cts->add(new sqltable(
      "listarretbus",null,$req,"index.php?page=edit&id_pgfiche=".$fiche->id,
      "id_arretbus",array("nom_geopoint"=>"Arret"),
      array("deletearretbus"=>"Enlever"),array(), array()));
    $frm = new form("addarretbus","index.php?page=edit&id_pgfiche=".$fiche->id,false);
    $frm->add_hidden("action","addarretbus");
    $frm->add_entity_smartselect ("id_arretbus","Arret de bus", new arretbus($site->db));
    $frm->add_submit("editfiche","Ajouter");
    $cts->add($frm);

    $cts->add_title(2,"Categories complémentaires");
    $req = new requete($site->db,"SELECT * FROM pg_fiche_extra_pgcategory ".
      "INNER JOIN pg_category USING(id_pgcategory) ".
      "WHERE `id_pgfiche` = '".mysql_real_escape_string($fiche->id)."'");
    $cts->add(new sqltable(
      "listextrapgcategory",null,$req,"index.php?page=edit&id_pgfiche=".$fiche->id,
      "id_pgcategory",array("nom_pgcategory"=>"Catégorie"),
      array("deleteextrapgcategory"=>"Enlever"),array(), array()));
    $frm = new form("addextrapgcategory","index.php?page=edit&id_pgfiche=".$fiche->id,false);
    $frm->add_hidden("action","addextrapgcategory");
    $frm->add_entity_smartselect ("id_pgcategory","Catégorie", new pgcategory($site->db));
    $frm->add_text_field("titre","Titre");
    $frm->add_text_field("soustitre","Sous-Titre");
    $frm->add_submit("addextrapgcategory","Ajouter");
    $cts->add($frm);

    $cts->add_title(2,"Tarifs");
    $req = new requete($site->db,"SELECT * FROM pg_fiche_tarif ".
      "INNER JOIN pg_typetarif USING(id_typetarif) ".
      "WHERE `id_pgfiche` = '".mysql_real_escape_string($fiche->id)."'");
    $cts->add(new sqltable(
      "listtarif",null,$req,"index.php?page=edit&id_pgfiche=".$fiche->id,
      "id_typetarif",array("nom_typetarif"=>"Type","min_tarif"=>"Min","max_tarif"=>"Max","commentaire_tarif"=>"Commentaire", "date_validite_tarif"=>"Validite"),
      array("deletetarif"=>"Enlever"),array(), array()));
    $frm = new form("addtarif","index.php?page=edit&id_pgfiche=".$fiche->id,false);
    $frm->add_hidden("action","addtarif");
    $frm->add_entity_smartselect ("id_typetarif","Type", new typetarif($site->db));
    $frm->add_price_field("min_tarif","Prix minimum");
    $frm->add_price_field("max_tarif","Prix maximum");
    $frm->add_text_field("commentaire","Commentaire");
    $frm->add_date_field("date_validite","Valable jusqu'au",$fiche->date_validite);
    $frm->add_submit("addtarif","Ajouter");
    $cts->add($frm);

    $cts->add_title(2,"Reductions");
    $req = new requete($site->db,"SELECT * FROM pg_fiche_reduction ".
      "INNER JOIN pg_typereduction USING(id_typereduction) ".
      "WHERE `id_pgfiche` = '".mysql_real_escape_string($fiche->id)."'");
    $cts->add(new sqltable(
      "listreduction",null,$req,"index.php?page=edit&id_pgfiche=".$fiche->id,
      "id_typereduction",array("nom_typereduction"=>"Type","valeur_reduction"=>"Valeur","unite_reduction"=>"Unite", "commentaire_reduction"=>"Commentaire","date_validite_reduction"=>"Validite"),
      array("deletereduction"=>"Enlever"),array(), array()));
    $frm = new form("addreduction","index.php?page=edit&id_pgfiche=".$fiche->id,false);
    $frm->add_hidden("action","addreduction");
    $frm->add_entity_smartselect ("id_typereduction","Type", new typereduction($site->db));
    $frm->add_text_field("valeur","Valeur");
    $frm->add_text_field("unite","Unite");
    $frm->add_text_field("commentaire","Commentaire");
    $frm->add_date_field("date_validite","Valable jusqu'au",$fiche->date_validite);
    $frm->add_submit("addreduction","Ajouter");
    $cts->add($frm);

    $cts->add_title(2,"Services");
    $req = new requete($site->db,"SELECT * FROM pg_fiche_service ".
      "INNER JOIN pg_service USING(id_service) ".
      "WHERE `id_pgfiche` = '".mysql_real_escape_string($fiche->id)."'");
    $cts->add(new sqltable(
      "listservice",null,$req,"index.php?page=edit&id_pgfiche=".$fiche->id,
      "id_service",array("nom_service"=>"Service","commentaire_service"=>"Commentaire","date_validite_service"=>"Validite"),
      array("deleteservice"=>"Enlever"),array(), array()));
    $frm = new form("addservice","index.php?page=edit&id_pgfiche=".$fiche->id,false);
    $frm->add_hidden("action","addservice");
    $frm->add_entity_smartselect ("id_service","Service", new service($site->db));
    $frm->add_text_field("commentaire","Commentaire");
    $frm->add_date_field("date_validite","Valable jusqu'au",$fiche->date_validite);
    $frm->add_submit("addservice","Ajouter");
    $cts->add($frm);




  }
  else
  {
    $cts->add_paragraph($path);
    if ( $site->is_admin() )
    {
      $cts->add_paragraph("<a href=\"index.php?page=edit&amp;id_pgfiche=".$fiche->id."\">Editer</a>");
      $cts->add_paragraph("<a href=\"index.php?action=delete&amp;id_pgfiche=".$fiche->id."\">Supprimer</a>");
    }
    $cts->add(new pgfichefull($fiche),true);
  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( $category->is_valid() && ($category->id != 1 || $site->is_admin()) )
{
  if ( $site->is_admin() && $_REQUEST["action"] == "save" )
  {
    $category->update ( $category->id_pgcategory_parent, $_REQUEST["nom"], $_REQUEST["description"], $_REQUEST["ordre"], $_REQUEST["couleur_bordure_web"], $_REQUEST["couleur_titre_web"],$_REQUEST["couleur_contraste_web"], $_REQUEST["couleur_bordure_print"], $_REQUEST["couleur_titre_print"], $_REQUEST["couleur_contraste_print"] );
    $category->set_tags($_REQUEST["tags"]);
  }
  elseif ( $site->is_admin() && $_REQUEST["action"] == "createcategory" )
  {
    $category->create ( $category->id, $_REQUEST["nom"], $_REQUEST["description"], $_REQUEST["ordre"], $_REQUEST["couleur_bordure_web"], $_REQUEST["couleur_titre_web"],$_REQUEST["couleur_contraste_web"], $_REQUEST["couleur_bordure_print"], $_REQUEST["couleur_titre_print"], $_REQUEST["couleur_contraste_print"] );
    $category->set_tags($_REQUEST["tags"]);

    $path .= " / ".$category->get_html_link();
    $title_path .= " / ".$category->nom;
  }

  $site->set_meta_information($category->get_tags(),$category->description);
  $site->start_page("pg",$title_path);
  $cts = new contents("<a href=\"index.php\">Le Guide</a>");

  $cts->add(new pgtabshead($site->db,$id_pgcategory1));

  if ( $site->is_admin() && $_REQUEST["page"] == "ajoutfiche" )
  {
    $cts->add_paragraph($path." / Ajouter une fiche");

    $ent = new entreprise($site->db);
    $rue = new rue($site->db);
    $typerue = new typerue($site->db);
    $ville = new ville($site->db);

    /*$ent->load_by_id($fiche->id_entreprise);
    $rue->load_by_id($fiche->id_rue);
    $typerue->load_by_id($rue->id_typerue);
    $ville->load_by_id($fiche->id_ville);*/

    $frm = new form("editfiche","index.php?id_pgcategory=".$category->id,true,"POST","Informations essentielles");
    $frm->add_hidden("action","createfiche");

    $sfrm = new subform("desc","Description");
    $sfrm->add_text_field("nom","Nom",$fiche->nom);
    $sfrm->add_text_area("description","Description courte",$fiche->description);
    $sfrm->add_text_area("longuedescription","Description longue",$fiche->longuedescription);
    $frm->addsub($sfrm);

    $sfrm = new subform("contact","Contacts clients");
    $sfrm->add_text_field("tel","Telephone",telephone_display($fiche->tel));
    $sfrm->add_text_field("fax","Fax",telephone_display($fiche->fax));
    $sfrm->add_text_field("email","Email",$fiche->email);
    $sfrm->add_text_field("website","Site internet",$fiche->website);
    $sfrm->add_text_field("tags","Tags",$category->get_tags());
    $frm->addsub($sfrm);

    $sfrm = new subform("adresse","Addresse");
    $sfrm->add_text_field("numrue","Numéro dans la rue",$fiche->numrue);
    $sfrm->add_entity_smartselect ("id_typerue","Type de la rue", $typerue);
    $sfrm->add_text_field("nom_rue","Nom de la rue",$rue->nom);
    $sfrm->add_entity_smartselect ("id_ville","Ville", $ville,false,true,array("pg_ville"=>1));
    $frm->addsub($sfrm);

    $sfrm = new subform("pos","Positiion");
    $sfrm->add_geo_field("lat","Latidue","lat",$fiche->lat);
    $sfrm->add_geo_field("long","Longitude","long",$fiche->long);
    $frm->addsub($sfrm);

    $sfrm = new subform("adm","Coordonnées administratives");
    $sfrm->add_entity_smartselect ("id_entreprise","Entreprise", $ent);
    $sfrm->add_text_area("adressepostal","Adresse postale complète",$fiche->adressepostal);
    $frm->addsub($sfrm);

    $sfrm = new subform("rendu","Options et validité");
    $sfrm->add_checkbox("placesurcarte","Placer sur la carte",$fiche->placesurcarte);
    $sfrm->add_checkbox("contraste","Mettre en constraste",$fiche->contraste);
    $sfrm->add_date_field("date_validite","Valable jusqu'au",$fiche->date_validite);
    $frm->addsub($sfrm);

    $sfrm = new subform("int","Interne");
    $sfrm->add_text_area("infointerne","Commentaire interne",$fiche->infointerne);
    $frm->addsub($sfrm);

    $frm->add_submit("createfiche","Suivant");

    $cts->add($frm,true);
    //
  }
  elseif ( $site->is_admin() && $_REQUEST["page"] == "ajoutcat" )
  {
    $cts->add_paragraph($path." / Ajouter une catégorie");

    $frm = new form("editcategory","index.php?id_pgcategory=".$category->id,true,"POST","Ajouter");
    $frm->add_hidden("action","createcategory");

    $sfrm = new subform("desc","Description");
    $sfrm->add_text_field("nom","Nom","");
    $sfrm->add_text_area("description","Description courte","");
    $sfrm->add_text_field("ordre","Numéro d'ordre","0");
    $sfrm->add_text_field("tags","Tags","");
    $frm->addsub($sfrm);

    $sfrm = new subform("web","Couleurs Web");
    $sfrm->add_color_field("couleur_bordure_web","rgb","Bordure",$category->couleur_bordure_web);
    $sfrm->add_color_field("couleur_titre_web","rgb","Titre",$category->couleur_titre_web);
    $sfrm->add_color_field("couleur_contraste_web","rgb","Contraste",$category->couleur_contraste_web);
    $frm->addsub($sfrm);

    $sfrm = new subform("print","Couleurs Impression");
    $sfrm->add_color_field("couleur_bordure_print","ymck","Bordure",$category->couleur_bordure_print);
    $sfrm->add_color_field("couleur_titre_print","ymck","Titre",$category->couleur_titre_print);
    $sfrm->add_color_field("couleur_contraste_print","ymck","Contraste",$category->couleur_contraste_print);
    $frm->addsub($sfrm);

    $frm->add_submit("editcategory","Enregistrer");

    $cts->add($frm,true);
  }
  elseif ( $site->is_admin() && $_REQUEST["page"] == "edit" )
  {
    $cts->add_paragraph($path." / Editer");

    $frm = new form("savecategory","index.php?id_pgcategory=".$category->id,true,"POST","Ajouter");
    $frm->add_hidden("action","save");

    $sfrm = new subform("desc","Description");
    $sfrm->add_text_field("nom","Nom",$category->nom);
    $sfrm->add_text_area("description","Description courte",$category->description);
    $sfrm->add_text_field("ordre","Numéro d'ordre",$category->ordre);
    $sfrm->add_text_field("tags","Tags",$category->get_tags());
    $frm->addsub($sfrm);

    $sfrm = new subform("web","Couleurs Web");
    $sfrm->add_color_field("couleur_bordure_web","rgb","Bordure",$category->couleur_bordure_web);
    $sfrm->add_color_field("couleur_titre_web","rgb","Titre",$category->couleur_titre_web);
    $sfrm->add_color_field("couleur_contraste_web","rgb","Contraste",$category->couleur_contraste_web);
    $frm->addsub($sfrm);

    $sfrm = new subform("print","Couleurs Impression");
    $sfrm->add_color_field("couleur_bordure_print","ymck","Bordure",$category->couleur_bordure_print);
    $sfrm->add_color_field("couleur_titre_print","ymck","Titre",$category->couleur_titre_print);
    $sfrm->add_color_field("couleur_contraste_print","ymck","Contraste",$category->couleur_contraste_print);
    $frm->addsub($sfrm);

    $frm->add_submit("savecategory","Enregistrer");

    $cts->add($frm,true);
  }
  else
  {
    $cts->add_paragraph($path);
    if ( $site->is_admin() )
    {
      $cts->add_paragraph("<a href=\"index.php?page=ajoutfiche&amp;id_pgcategory=".$category->id."\">Ajouter une fiche</a>");
      $cts->add_paragraph("<a href=\"index.php?page=ajoutcat&amp;id_pgcategory=".$category->id."\">Ajouter une catégorie</a>");
      $cts->add_paragraph("<a href=\"index.php?page=edit&amp;id_pgcategory=".$category->id."\">Editer</a>");

      if ( $category->id_pgcategory_parent )
        $cts->add_paragraph("<a href=\"index.php?action=delete&amp;id_pgcategory=".$category->id."\">Supprimer</a>");
    }

    $req = new requete($site->db,
      "SELECT id_pgcategory, nom_pgcategory ".
      "FROM pg_category ".
      "WHERE id_pgcategory_parent='".mysql_real_escape_string($category->id)."' ".
      "ORDER BY ordre_pgcategory, nom_pgcategory");

    if ( $req->lines > 0 )
    {
      $sscts = new pgcatlist($category->couleur_bordure_web);
      while ( $row = $req->get_row() )
        $sscts->add($row["id_pgcategory"],$row["nom_pgcategory"]);
      $cts->add($sscts);
    }



    $cts->add(new pgfichelistcat($category));
  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("pg","Petit Géni 2.0");
$cts = new board("Bienvenue");

$scts = new contents("Le Guide");
$req = new requete($site->db,
  "SELECT cat1.id_pgcategory AS id, cat1.nom_pgcategory AS nom, cat1.couleur_bordure_web_pgcategory AS couleur, ".
  "cat2.id_pgcategory AS id2, cat2.nom_pgcategory AS nom2 ".
  "FROM pg_category AS cat1 ".
  "LEFT JOIN pg_category AS cat2 ON (cat1.id_pgcategory=cat2.id_pgcategory_parent) ".
  "WHERE cat1.id_pgcategory_parent='1' ".
  "ORDER BY cat1.ordre_pgcategory, cat2.ordre_pgcategory, cat2.nom_pgcategory");

$prev_cat=null;
$sscts=null;

while ( $row = $req->get_row() )
{
  if ( $prev_cat != $row["id"] )
  {
    if ( !is_null($sscts) )
      $scts->add($sscts);
    $sscts = new pgcatminilist($row["id"],$row["nom"],$row["couleur"]);
    $prev_cat = $row["id"];
  }
  $sscts->add($row["id2"],$row["nom2"]);
}

if ( !is_null($sscts) )
  $scts->add($sscts);

if ( $site->is_admin() )
  $scts->add_paragraph("<a href=\"index.php?page=ajoutcat&amp;id_pgcategory=1\">Ajouter une catégorie</a>");

$cts->add($scts,true);

$scts = new contents("Rechercher");
$cts->add($scts,true);

$scts = new contents("Agenda");
$cts->add($scts,true);

$scts = new contents("Bons plans");
$cts->add($scts,true);

$scts = new contents("Le Petit Géni");
$cts->add($scts,true);

$site->add_contents($cts);
$site->end_page();

?>
