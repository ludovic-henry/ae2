<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */
$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/books.inc.php");
require_once($topdir. "include/entities/jeu.inc.php");
require_once($topdir. "include/entities/objet.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/sitebat.inc.php");
require_once($topdir. "include/entities/batiment.inc.php");
require_once($topdir. "include/entities/salle.inc.php");

/* contextualisation des salles en "lieux" */
$GLOBALS["entitiescatalog"]["salle"][3] = "biblio/";

$site = new site ();

$is_admin=$site->user->is_in_group("gestion_ae") || $site->user->is_in_group("books_admin");

$editeur = new editeur($site->db,$site->dbrw);
$serie = new serie($site->db,$site->dbrw);
$auteur = new auteur($site->db,$site->dbrw);
$livre = new livre($site->db,$site->dbrw);
$jeu = new jeu($site->db,$site->dbrw);
$salle = new salle($site->db);

function get_lieux ()
{
	global $site;
	$req = new requete ( $site->db, "SELECT ".
			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`  " .
			"FROM `inv_objet` " .
			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
			"LEFT JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet` ".
			"LEFT JOIN `inv_jeu` ON `inv_jeu`.`id_objet`=`inv_objet`.`id_objet` ".
			"WHERE `inv_jeu`.`id_objet` IS NOT NULL OR `bk_book`.`id_objet` IS NOT NULL ".
			"GROUP BY `sl_salle`.`id_salle`" );
	$values[0]="-";
	while ( list($id,$nom) = $req->get_row())
		$values[$id]=$nom;

	return $values;
}

if ( isset($_REQUEST["id_editeur"]))
	$editeur->load_by_id($_REQUEST["id_editeur"]);

if ( isset($_REQUEST["id_serie"]))
	$serie->load_by_id($_REQUEST["id_serie"]);

if ( isset($_REQUEST["id_auteur"]))
	$auteur->load_by_id($_REQUEST["id_auteur"]);

if ( isset($_REQUEST["id_livre"]))
	$livre->load_by_id($_REQUEST["id_livre"]);

if ( isset($_REQUEST["id_jeu"]))
	$jeu->load_by_id($_REQUEST["id_jeu"]);

if ( isset($_REQUEST["id_salle"]))
	$salle->load_by_id($_REQUEST["id_salle"]);


if ( $_REQUEST["action"] == "addauteur" && $is_admin)
{
	if ( $_REQUEST["nom"] )
		$auteur->add_auteur($_REQUEST["nom"]);
}
elseif ( $_REQUEST["action"] == "addserie" && $is_admin)
{
	if ( $_REQUEST["nom"] )
		$serie->add_serie($_REQUEST["nom"]);
}
elseif ( $_REQUEST["action"] == "addediteur" && $is_admin)
{
	if ( $_REQUEST["nom"] )
		$editeur->add_editeur($_REQUEST["nom"]);
}
elseif ( $_REQUEST["action"] == "bookback" && $is_admin )
{
	$emp = new emprunt ( $site->db, $site->dbrw );

	$obj = new objet($site->db);
	$obj->load_by_cbar($_REQUEST["cbar"]);

	if ( $obj->is_valid() && ($obj->is_book() || $obj->is_jeu()) )
	{
		$emp->load_by_objet($obj->id);
		if ( $emp->is_valid() )
		{
			$emp->back_objet($obj->id);
			$Message = new contents("Livre restitué",( $emp->etat == EMPRUNT_RETOURPARTIEL ) ? "<p><b>Attention: il reste des objets à restituer</b>.</p>" : "<p>Emprunt restitué en totalité.</p>");
		}
		else
			$ErreurRetour="Cet objet n'est pas actuellement emprunté.";
	}
	else
		$ErreurRetour="Objet inconnu.";

}
elseif ( $_REQUEST["action"] == "borrowbooks" && $is_admin )
{
	$ErreurEmprunt = null;

	function add_objet_once( &$list, &$obj )
	{
		foreach( $list as $o )
			if ( $o->id == $obj->id ) return;
		$list[]=$obj;
	}

	$user = new utilisateur($site->db);
	$emp = new emprunt ( $site->db, $site->dbrw );
	$livres=array();

	if ( $_REQUEST["emp"] == "carte" )
		$user->load_by_carteae($_REQUEST["carte"]);
	elseif ( $_REQUEST["emp"] == "email" )
		$user->load_by_id($_REQUEST["id_utilisateur"]);

	$cbars = explode("\n",$_REQUEST["cbars"]);
	foreach ( $cbars as $cbar)
	{
		if ( $cbar )
		{
			$l = new objet($site->db);
			$l->load_by_cbar(trim($cbar));
			if ( $l->is_valid() && ($l->is_book() || $l->is_jeu()) )
				add_objet_once($livres,$l);
			else
				$ErreurEmprunt = "Un ou plusieurs codes barres sont inconnus.";
		}
	}

	if ( !$user->is_valid() )
		$ErreurEmprunt="Utilisateur inconnu";
	elseif ( $_REQUEST["endtime"] <= time() )
		$ErreurEmprunt="Date et heure de fin invalide";
	elseif ( !$ErreurEmprunt )
	{
		$emp->add_emprunt ( $user->id, null, null, time(), $_REQUEST["endtime"] );
		foreach ( $livres as $objet )
			$emp->add_object($objet->id);
		$emp->retrait ( $site->user->id, $_REQUEST["caution"], 0, $_REQUEST["notes"] );
		$Message = new contents("Pret enregistré.","<p>Pret de matériel n°".$emp->id."</p>");
	}
}
elseif ( $_REQUEST["action"] == "addlivres" && $is_admin )
{
	$objtype = new objtype($site->db);
	$asso = new asso($site->db);
	$asso_prop = new asso($site->db);

	$asso->load_by_id($_POST["id_asso_prop"]);
	$asso_gest->load_by_id($_POST["id_asso"]);
	$objtype->load_by_id($_POST["id_objtype"]);
	$salle->load_by_id($_POST["id_salle"]);

	$lines = explode("\n",$_POST["data"]);

  foreach ( $lines as $line )
  {
    $rows = explode(";",$line);

    if ( $rows[0] != "Serie" )
    {
      $nserie = trim($rows[0]);
      $nom = trim($rows[1]);
      $num = trim($rows[2]);
      $auteurs = trim($rows[3]);
      $nediteur = trim($rows[4]);
      $isbn = trim($rows[5]);

    	$editeur->load_or_create($nediteur);

    	if ( !empty($nserie) )
    	  $serie->load_or_create($nserie);
      else
        $serie->id = null;

      $livre->add_book ( $asso->id, $asso_prop->id, $salle->id, $objtype->id, 0, $nom,
				$objtype->code, "", $_POST["prix"], $_POST["caution"], 0, 0,
				true, $_POST["date_achat"], "",
				$serie->id, $editeur->id, $num, $isbn );

      if ( !empty($auteurs) )
      {
        $auteurs = explode(",",$auteurs);
        foreach ( $auteurs as $nom )
        {
          if ( !empty($nom) )
          {
          	$auteur->load_or_create($nom);
          	$livre->add_auteur($auteur->id);
          }
        }
      }
    }
  }
}
elseif ( $_REQUEST["action"] == "addlivre" && $is_admin )
{

	$objtype = new objtype($site->db);
	$asso = new asso($site->db);
	$asso_prop = new asso($site->db);
  $auteur1 = new auteur($site->db);
  $auteur2 = new auteur($site->db);
  $auteur3 = new auteur($site->db);

	$asso_prop->load_by_id($_POST["id_asso_prop"]);
	$asso->load_by_id($_POST["id_asso"]);
	$objtype->load_by_id($_POST["id_objtype"]);
	$salle->load_by_id($_POST["id_salle"]);
	$editeur->load_by_id($_POST["id_editeur"]);
	$serie->load_by_id($_POST["id_serie"]);
	if ( $serie->id < 1 )
    $serie->id = null;
	$auteur->load_by_id($_POST["id_auteur"]);
	$auteur1->load_by_id($_POST["id_auteur1"]);
	$auteur2->load_by_id($_POST["id_auteur2"]);
	$auteur3->load_by_id($_POST["id_auteur3"]);

  $livre->add_book ( $asso->id, $asso_prop->id, $salle->id, $objtype->id, 0, $_POST["nom"],
				$objtype->code, $_POST["num_serie"], $_POST["prix"], $_POST["caution"], 0, 0,
				$_POST["en_etat"], $_POST["date_achat"], $_POST["notes"],
				$serie->id, $editeur->id, $_POST["num"], $_POST["isbn"] );

	$livre->add_auteur($auteur->id);

	if ( $auteur1->id > 0 )
    $livre->add_auteur($auteur1->id);

	if ( $auteur2->id > 0 )
    $livre->add_auteur($auteur2->id);

	if ( $auteur3->id > 0 )
    $livre->add_auteur($auteur3->id);

}
elseif ( $_REQUEST["action"] == "addjeu" && $is_admin )
{
	$objtype = new objtype($site->db);
	$asso = new asso($site->db);
	$asso_prop = new asso($site->db);

	$asso_prop->load_by_id($_POST["id_asso_prop"]);
	$asso->load_by_id($_POST["id_asso"]);
	$objtype->load_by_id($_POST["id_objtype"]);
	$salle->load_by_id($_POST["id_salle"]);
	$serie->load_by_id($_POST["id_serie"]);

  $jeu->add_jeu ( $asso->id, $asso_prop->id, $salle->id, $objtype->id, 0, $_POST["nom"],
				$objtype->code, $_POST["num_serie"], $_POST["prix"], $_POST["caution"], 0, 0,
				$_POST["en_etat"], $_POST["date_achat"], $_POST["notes"],
				$serie->id, $_POST["etat"], $_POST["nb_joueurs"], $_POST["duree"], $_POST["langue"], $_POST["difficulte"] );

}
elseif ( $_REQUEST["action"] == "addjeux" && $is_admin )
{
	$objtype = new objtype($site->db);
	$asso = new asso($site->db);
	$asso_prop = new asso($site->db);

	$asso_prop->load_by_id($_POST["id_asso_prop"]);
	$asso->load_by_id($_POST["id_asso"]);
	$objtype->load_by_id($_POST["id_objtype"]);
	$salle->load_by_id($_POST["id_salle"]);

	$lines = explode("\n",$_POST["data"]);

  foreach ( $lines as $line )
  {
    $rows = explode(";",$line);

    if ( $rows[0] != "Nom" )
    {
      $nom = trim($rows[0]);
      $nserie = trim($rows[1]);
      $etat = trim($rows[2]);
      $nb_joueurs = trim($rows[3]);
      $duree = trim($rows[4]);
      $langue = trim($rows[5]);
      $difficulte = trim($rows[6]);

      $prix = get_prix(trim($rows[7]));
      $caution = get_prix(trim($rows[8]));

      $reservable = trim($rows[9]) == "o";

    	if ( !empty($nserie) )
    	  $serie->load_or_create($nserie);
      else
        $serie->id = null;

      $jeu->add_jeu ( $asso->id, $asso_prop->id, $salle->id, $objtype->id, 0, $nom,
				$objtype->code, "", $prix, $caution, 0, $reservable,
				true, $_POST["date_achat"], $_POST["notes"],
				$serie->id, $etat, $nb_joueurs, $duree, $langue, $difficulte );
    }
  }

}


$tabs = array(array("","biblio/","Recherche"),
			array("auteurs","biblio/?view=auteurs","Auteurs"),
			array("series","biblio/?view=series","Series"),
			array("editeurs","biblio/?view=editeurs","Editeurs"),
			array("lieux","biblio/?view=lieux","Lieux"),
			array("livres","biblio/?view=livres","Livres"),
			array("jeux","biblio/?view=jeux","Jeux")
			);

if ( $is_admin ) $tabs[] = array("prets","biblio/?view=prets","Prets");

if ( $_REQUEST["action"] == "search" )
{
	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,""));

	$frm = new form("search","./",false,"POST","Recherche d'un livre/BD");
	$frm->add_hidden("action","search");
	$frm->add_text_field("name","Titre",$_REQUEST["name"]);
	$frm->add_entity_select("id_serie", "Serie", $site->db, "serie", $serie->id,true);
	$frm->add_entity_select("id_auteur", "Auteur", $site->db, "auteur", $auteur->id,true);
	$frm->add_entity_select("id_editeur", "Editeur", $site->db, "editeur", $editeur->id,true);
	$frm->add_select_field("id_salle","Lieu",get_lieux());
	$frm->add_submit("valide","Rechercher");
	$cts->add($frm,true);

	if ( $editeur->is_valid() )
		$conds[] = "`bk_book`.`id_editeur`='".$editeur->id."'";

	if ( $auteur->is_valid() )
		$conds[] = "`bk_livre_auteur`.`id_auteur`='".$auteur->id."'";


	if ( $salle->is_valid() )
		$conds[] = "`inv_objet`.`id_salle`='".$salle->id."'";

	if ( $serie->is_valid() )
		$conds[] = "`bk_book`.`id_serie`='".$serie->id."'";

	if ( count($conds) || $_REQUEST["name"] )
	{
		$conds[] = "`inv_objet`.`nom_objet` LIKE '%".mysql_real_escape_string($_REQUEST["name"])."%'";

		$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_livre`," .
				//"`bk_auteur`.`id_auteur`,`bk_auteur`.`nom_auteur`," .
				"`bk_editeur`.`id_editeur`,`bk_editeur`.`nom_editeur`," .
				"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
				"`bk_book`.`num_livre`,".
				"`inv_objet`.`nom_objet` AS `nom_livre`, " .
				"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`  " .
				"FROM `inv_objet` " .
				"INNER JOIN `bk_book` USING(`id_objet`) ".
				"INNER JOIN `bk_livre_auteur` USING(`id_objet`) ".
				"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
				"INNER JOIN `bk_editeur` ON `bk_book`.`id_editeur`=`bk_editeur`.`id_editeur` ".
				"LEFT JOIN `bk_serie` ON `bk_book`.`id_serie`=`bk_serie`.`id_serie` ".
				"WHERE ".implode(" AND ",$conds)." " .
				"ORDER BY `bk_serie`.`nom_serie`,`bk_book`.`num_livre`,`inv_objet`.`nom_objet`" );

		$tbl = new sqltable(
			"listlivres",
			"Livres", $req, "./",
			"id_livre",
			array("nom_livre"=>"Titre","nom_serie"=>"Serie","num_livre"=>"N°"/*,"nom_auteur"=>"Auteur"*/,"nom_editeur"=>"Editeur","nom_salle"=>"Lieu"),
			array(), array(), array()
			);
		$cts->add($tbl,true);
	}

	$site->add_contents($cts);
	$site->end_page();
	exit();
}
elseif ( $_REQUEST["action"] == "searchjeu" )
{
	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,""));

//TODO:recherche/jeu


	$site->add_contents($cts);
	$site->end_page();
	exit();
}
elseif ( $jeu->is_valid() )
{
	$objtype = new objtype($site->db);
	$asso_gest = new asso($site->db);

  if ( $_REQUEST["action"] == "save" && $is_admin )
  {
  	$objtype = new objtype($site->db);
  	$asso = new asso($site->db);
  	$asso_prop = new asso($site->db);

  	$asso_prop->load_by_id($_POST["id_asso_prop"]);
  	$asso->load_by_id($_POST["id_asso"]);
  	$objtype->load_by_id($_POST["id_objtype"]);
  	$salle->load_by_id($_POST["id_salle"]);
  	$serie->load_by_id($_POST["id_serie"]);

    $jeu->save_jeu ( $asso->id, $asso_prop->id, $salle->id, $objtype->id, 0, $_REQUEST["nom"],
  				$_REQUEST["num_serie"], $_REQUEST["prix"], $_REQUEST["caution"], $_REQUEST["prix_emprunt"], isset($_REQUEST["empruntable"]),
  				isset($_REQUEST["en_etat"]), $_REQUEST["date_achat"], $_REQUEST["notes"], $livre->cbar,
  				$serie->id, $_REQUEST["etat"], $_REQUEST["nb_joueurs"], $_REQUEST["duree"], $_REQUEST["langue"], $_REQUEST["difficulte"] );
  }
  elseif ( $_REQUEST["action"] == "edit" && $is_admin )
  {
  	$asso_gest->load_by_id($jeu->id_asso);
  	$objtype->load_by_id($jeu->id_objtype);
  	$salle->load_by_id($jeu->id_salle);
  	$serie->load_by_id($jeu->id_serie);

  	$site->start_page("services","Bibliothéque");
  	$cts = new contents("Bibliothèque");
  	$cts->add(new tabshead($tabs,"jeux"));

  	$cts->add_title(2,$serie->get_html_link()." / ".$jeu->get_html_link());

		$frm = new form("savejeu","./?id_jeu=".$jeu->id,false,"POST","Modifier");
		$frm->add_hidden("action","save");
		$frm->add_text_field("nom","Nom",$jeu->nom);
		$frm->add_entity_select("id_serie", "Serie", $site->db, "serie", $jeu->id_serie, true);
		$frm->add_text_field("etat","Etat",$jeu->etat);
		$frm->add_text_field("nb_joueurs","Nombre de joueurs",$jeu->nb_joueurs);
		$frm->add_text_field("duree","Durée moyenne d'une partie",$jeu->duree);
		$frm->add_text_field("langue","Langue",$jeu->langue);
		$frm->add_text_field("difficulte","Difficultée",$jeu->difficulte);
		$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $jeu->id_objtype);
		$frm->add_text_field("num_serie","Numéro de série",$jeu->num_serie);
		$frm->add_date_field("date_achat","Date d'achat",$jeu->date_achat);
		$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", $jeu->id_asso_prop, false, array("id_asso_parent"=>NULL));
		$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso", $jeu->id_asso);
		$frm->add_entity_select("id_salle", "Salle", $site->db, "salle", $jeu->id_salle);
		$frm->add_price_field("prix","Prix d'achat",$jeu->prix);
		$frm->add_price_field("caution","Prix de la caution",$jeu->caution);
		$frm->add_price_field("prix_emprunt","Prix d'un emprunt",$jeu->prix_emprunt);
		$frm->add_checkbox("empruntable","Reservable via le site internet",$jeu->empruntable);
		$frm->add_checkbox("en_etat","En etat",$jeu->en_etat);
		$frm->add_text_area("notes","Notes",$jeu->notes);
		$frm->add_submit("valide","Enregistrer");
		$cts->add($frm,true);

  	$site->add_contents($cts);
  	$site->end_page();
  	exit();
	}

  $asso_gest->load_by_id($jeu->id_asso);
	$objtype->load_by_id($jeu->id_objtype);
	$salle->load_by_id($jeu->id_salle);
	$serie->load_by_id($jeu->id_serie);

	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,"jeux"));

	$cts->add_title(2,$serie->get_html_link()." / ".$jeu->get_html_link());

	if ( $is_admin )
	{
	  $cts->add_paragraph("<a href=\"../objet.php?id_objet=".$jeu->id."\">Voir fiche objet</a>");
	  $cts->add_paragraph("<a href=\"?action=edit&amp;id_jeu=".$jeu->id."\">Editer</a>");
	}

	$tbl = new table("Informations");
	$tbl->add_row(array("Titre",$jeu->nom));
	$tbl->add_row(array("Type",$objtype->get_html_link()));
	$tbl->add_row(array("Serie",$serie->get_html_link()));
	$tbl->add_row(array("Etat",$jeu->etat));
	$tbl->add_row(array("Nombre de joueurs",$jeu->nb_joueurs));
	$tbl->add_row(array("Durée moyenne d'une partie",$jeu->duree));
	$tbl->add_row(array("Langue",$jeu->langue));
	$tbl->add_row(array("Difficultée",$jeu->difficulte));
	$tbl->add_row(array("Association",$asso_gest->get_html_link()));
	$tbl->add_row(array("Emplacement",$salle->get_html_link()));
	if ($livre->date_achat)
	  $tbl->add_row(array("Date d'achat",date("d/m/Y",$jeu->date_achat)));
	$tbl->add_row(array("En etat",$jeu->en_etat?"Oui":"Non"));
	$tbl->add_row(array("Archive (sorti de l'inventaire)",$jeu->archive?"Oui":"Non"));
	$tbl->add_row(array("Code barre",$jeu->cbar));
	$cts->add($tbl,true);



	$site->add_contents($cts);
	$site->end_page();
	exit();
}
elseif ( $livre->is_valid() )
{
	$objtype = new objtype($site->db);
	$asso_gest = new asso($site->db);

	if ( $_REQUEST["action"] == "delete" && $is_admin && isset($_REQUEST["id_auteur"]) )
  {
    $livre->remove_auteur($_REQUEST["id_auteur"]);
    $_REQUEST["action"] = "edit";
  }
	elseif ( $_REQUEST["action"] == "addauteur" && $is_admin && isset($_REQUEST["id_auteur"]) )
  {
	  $auteur->load_by_id($_POST["id_auteur"]);
	  if ( $auteur->is_valid() )
      $livre->add_auteur($auteur->id);
    $_REQUEST["action"] = "edit";
  }

  if ( $_REQUEST["action"] == "save" && $is_admin )
  {
  	$objtype = new objtype($site->db);
  	$asso = new asso($site->db);
  	$asso_prop = new asso($site->db);

  	$asso_prop->load_by_id($_POST["id_asso_prop"]);
  	$asso->load_by_id($_POST["id_asso"]);
  	$objtype->load_by_id($_POST["id_objtype"]);
  	$salle->load_by_id($_POST["id_salle"]);
  	$editeur->load_by_id($_POST["id_editeur"]);
  	$serie->load_by_id($_POST["id_serie"]);

    $livre->save_book ( $asso->id, $asso_prop->id, $salle->id, $objtype->id, 0, $_REQUEST["nom"],
  				$_REQUEST["num_serie"], $_REQUEST["prix"], $_REQUEST["caution"], $_REQUEST["prix_emprunt"], isset($_REQUEST["empruntable"]),
  				isset($_REQUEST["en_etat"]), $_REQUEST["date_achat"], $_REQUEST["notes"], $livre->cbar,
  				$serie->id, $editeur->id, $_REQUEST["num"], $_REQUEST["isbn"] );
  }
  elseif ( $_REQUEST["action"] == "edit" && $is_admin )
  {

  	$asso_gest->load_by_id($livre->id_asso);
  	$objtype->load_by_id($livre->id_objtype);
  	$salle->load_by_id($livre->id_salle);
  	$editeur->load_by_id($livre->id_editeur);
  	$serie->load_by_id($livre->id_serie);

  	$site->start_page("services","Bibliothéque");
  	$cts = new contents("Bibliothèque");
  	$cts->add(new tabshead($tabs,"livres"));

  	$cts->add_title(2,$serie->get_html_link()." / ".$livre->get_html_link());

		$frm = new form("savelivre","./?id_livre=".$livre->id,false,"POST","Modifier");
		$frm->add_hidden("action","save");
		$frm->add_text_field("nom","Nom",$livre->nom);
		$frm->add_text_field("isbn","ISBN ou EAN13",$livre->isbn);
		$frm->add_text_field("num","Numéro",$livre->num_livre);
		$frm->add_entity_select("id_serie", "Serie", $site->db, "serie",$livre->id_serie,true);
		$frm->add_entity_select("id_editeur", "Editeur", $site->db, "editeur",$livre->id_editeur,true);
		$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $livre->id_objtype);
		$frm->add_text_field("num_serie","Numéro de série",$livre->num_serie);
		$frm->add_date_field("date_achat","Date d'achat",$livre->date_achat);
		$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", $livre->id_asso_prop, false, array("id_asso_parent"=>NULL));
		$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso", $livre->id_asso);
		$frm->add_entity_select("id_salle", "Salle", $site->db, "salle", $livre->id_salle);
		$frm->add_price_field("prix","Prix d'achat",$livre->prix);
		$frm->add_price_field("caution","Prix de la caution",$livre->caution);
		$frm->add_price_field("prix_emprunt","Prix d'un emprunt",$livre->prix_emprunt);
		$frm->add_checkbox("empruntable","Reservable via le site internet",$livre->empruntable);
		$frm->add_checkbox("en_etat","En etat",$livre->en_etat);
		$frm->add_text_area("notes","Notes",$livre->notes);
		$frm->add_submit("valide","Enregistrer");
		$cts->add($frm,true);

  	$req = new requete ( $site->db, "SELECT " .
  			"`bk_auteur`.`id_auteur`,`bk_auteur`.`nom_auteur` " .
  			"FROM `bk_livre_auteur` " .
  			"INNER JOIN `bk_auteur` ON `bk_livre_auteur`.`id_auteur`=`bk_auteur`.`id_auteur` " .
  			"WHERE id_objet='".$livre->id."'");

  	$tbl = new sqltable(
  		"listauteurs",
  		"Modifier les auteurs", $req, "./?id_livre=".$livre->id,
  		"id_auteur",
  		array("nom_auteur"=>"Auteur"),
  		array("delete"=>"Enlever auteur"), array(), array()
  		);
  	$cts->add($tbl,true);

		$frm = new form("addauteur","./?id_livre=".$livre->id,false,"POST","Ajouter un auteur");
		$frm->add_hidden("action","addauteur");
		$frm->add_entity_select("id_auteur", "Auteur", $site->db, "auteur");
		$frm->add_submit("valide","Ajouter auteur");
		$cts->add($frm,true);

  	$site->add_contents($cts);
  	$site->end_page();
  	exit();
	}

	$asso_gest->load_by_id($livre->id_asso);
	$objtype->load_by_id($livre->id_objtype);
	$salle->load_by_id($livre->id_salle);
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


	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,"livres"));

	$cts->add_title(2,$serie->get_html_link()." / ".$livre->get_html_link());

	if ( $is_admin )
	{
	  $cts->add_paragraph("<a href=\"../objet.php?id_objet=".$livre->id."\">Voir fiche objet</a>");
	  $cts->add_paragraph("<a href=\"?action=edit&amp;id_livre=".$livre->id."\">Editer</a>");
	}

	$tbl = new table("Informations");
	$tbl->add_row(array("Titre",$livre->nom));
	$tbl->add_row(array("Type",$objtype->get_html_link()));
	$tbl->add_row(array("Serie",$serie->get_html_link()));
	$tbl->add_row(array("N°",$livre->num_livre));
	$tbl->add_row(array("Auteur(s)",$auteurs));
	$tbl->add_row(array("Editeur",$editeur->get_html_link()));
	$tbl->add_row(array("Association",$asso_gest->get_html_link()));
	$tbl->add_row(array("Emplacement",$salle->get_html_link()));
	if ($livre->date_achat) $tbl->add_row(array("Date d'achat",date("d/m/Y",$livre->date_achat)));
	$tbl->add_row(array("En etat",$livre->en_etat?"Oui":"Non"));
	$tbl->add_row(array("Archive (sorti de l'inventaire)",$livre->archive?"Oui":"Non"));
	$tbl->add_row(array("Code barre",$livre->cbar));
	$tbl->add_row(array("ISBN",$livre->isbn));
	$cts->add($tbl,true);

	$site->add_contents($cts);
	$site->end_page();
	exit();
}
elseif ( $serie->is_valid() )
{
	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,"series"));
	$cts->add_paragraph($serie->get_html_link());


	$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_livre`," .
			//"`bk_auteur`.`id_auteur`,`bk_auteur`.`nom_auteur`," .
			"`bk_editeur`.`id_editeur`,`bk_editeur`.`nom_editeur`," .
			"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
			"`bk_book`.`num_livre`,".
			"`inv_objet`.`nom_objet` AS `nom_livre`, " .
			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`  " .
			"FROM `inv_objet` " .
			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
			"INNER JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet` ".
			//"INNER JOIN `bk_auteur` ON `bk_book`.`id_auteur`=`bk_auteur`.`id_auteur` ".
			"INNER JOIN `bk_editeur` ON `bk_book`.`id_editeur`=`bk_editeur`.`id_editeur` ".
			"LEFT JOIN `bk_serie` ON `bk_book`.`id_serie`=`bk_serie`.`id_serie` ".
			"WHERE `bk_book`.`id_serie`='".$serie->id."' " .
			"ORDER BY `bk_serie`.`nom_serie`,`bk_book`.`num_livre`,`inv_objet`.`nom_objet`" );

	$tbl = new sqltable(
		"listlivres",
		"Livres", $req, "./",
		"id_livre",
		array("nom_livre"=>"Titre","nom_serie"=>"Serie","num_livre"=>"N°"/*,"nom_auteur"=>"Auteur"*/,"nom_editeur"=>"Editeur","nom_salle"=>"Lieu"),
		array(), array(), array()
		);
	$cts->add($tbl,true);

	$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_jeu`," .
			"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
			"`inv_objet`.`nom_objet` AS `nom_jeu`, " .
			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`, " .
			"`inv_jeu`.`nb_joueurs_jeu`, " .
			"`inv_jeu`.`duree_jeu`, " .
			"`inv_jeu`.`difficulte_jeu`, " .
			"`inv_jeu`.`langue_jeu`, " .
	  	"`inv_type_objets`.`nom_objtype`  " .
			"FROM `inv_objet` " .
			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
			"INNER JOIN `inv_jeu` ON `inv_jeu`.`id_objet`=`inv_objet`.`id_objet` ".
	  	"INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` " .
			"LEFT JOIN `bk_serie` ON `inv_jeu`.`id_serie`=`bk_serie`.`id_serie` ".
		  "WHERE `inv_jeu`.`id_serie`='".$serie->id."' " .
			"ORDER BY `inv_type_objets`.`nom_objtype`,`bk_serie`.`nom_serie`,`inv_objet`.`nom_objet`" );

	$tbl = new sqltable(
		"listjeux",
		"Jeux", $req, "./",
		"id_jeu",
		array("nom_objtype"=>"Type","nom_jeu"=>"Titre", "nom_serie"=>"Serie", "nb_joueurs_jeu"=>"Nombre de joueurs", "duree_jeu"=>"Durée partie", "difficulte_jeu"=>"Difficultée", "langue_jeu" => "Langue", "nom_salle"=>"Lieu"),
		array(), array(), array()
		);
	$cts->add($tbl,true);


	$site->add_contents($cts);

	$site->end_page();
	exit();
}
elseif ( $auteur->is_valid() )
{
	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,"auteurs"));
	$cts->add_paragraph($auteur->get_html_link());

	$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_livre`," .
			"`bk_editeur`.`id_editeur`,`bk_editeur`.`nom_editeur`," .
			"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
			"`bk_book`.`num_livre`,".
			"`inv_objet`.`nom_objet` AS `nom_livre`, " .
			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`  " .
			"FROM `bk_livre_auteur` " .
			"INNER JOIN `inv_objet` ON `inv_objet`.`id_objet`=`bk_livre_auteur`.`id_objet` " .
			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
			"INNER JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet` ".
			"INNER JOIN `bk_editeur` ON `bk_book`.`id_editeur`=`bk_editeur`.`id_editeur` ".
			"LEFT JOIN `bk_serie` ON `bk_book`.`id_serie`=`bk_serie`.`id_serie` ".
			"WHERE `bk_livre_auteur`.`id_auteur`='".$auteur->id."' " .
			"ORDER BY `bk_serie`.`nom_serie`,`bk_book`.`num_livre`,`inv_objet`.`nom_objet`" );

	$tbl = new sqltable(
		"listlivres",
		"Livres", $req, "./",
		"id_livre",
		array("nom_livre"=>"Titre","nom_serie"=>"Serie","num_livre"=>"N°","nom_editeur"=>"Editeur","nom_salle"=>"Lieu"),
		array(), array(), array()
		);
	$cts->add($tbl,true);

	$site->add_contents($cts);
	$site->end_page();
	exit();
}
elseif ( $editeur->is_valid() )
{
	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,"editeurs"));
	$cts->add_paragraph($editeur->get_html_link());
	$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_livre`," .
			//"`bk_auteur`.`id_auteur`,`bk_auteur`.`nom_auteur`," .
			"`bk_editeur`.`id_editeur`,`bk_editeur`.`nom_editeur`," .
			"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
			"`bk_book`.`num_livre`,".
			"`inv_objet`.`nom_objet` AS `nom_livre`, " .
			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`  " .
			"FROM `inv_objet` " .
			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
			"INNER JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet` ".
			/*"INNER JOIN `bk_auteur` ON `bk_book`.`id_auteur`=`bk_auteur`.`id_auteur` ".*/
			"INNER JOIN `bk_editeur` ON `bk_book`.`id_editeur`=`bk_editeur`.`id_editeur` ".
			"LEFT JOIN `bk_serie` ON `bk_book`.`id_serie`=`bk_serie`.`id_serie` ".
			"WHERE `bk_book`.`id_editeur`='".$editeur->id."' " .
			"ORDER BY `bk_serie`.`nom_serie`,`bk_book`.`num_livre`,`inv_objet`.`nom_objet`" );

	$tbl = new sqltable(
		"listlivres",
		"Livres", $req, "./",
		"id_livre",
		array("nom_livre"=>"Titre","nom_serie"=>"Serie","num_livre"=>"N°"/*,"nom_auteur"=>"Auteur"*/,"nom_editeur"=>"Editeur","nom_salle"=>"Lieu"),
		array(), array(), array()
		);
	$cts->add($tbl,true);
	$site->add_contents($cts);
	$site->end_page();
	exit();
}
elseif ( $salle->is_valid() )
{
	$site->start_page("services","Bibliothéque");
	$cts = new contents("Bibliothèque");
	$cts->add(new tabshead($tabs,"lieux"));
	$cts->add_paragraph($salle->get_html_link());

	if ( $is_admin )
		$cts->add_paragraph("<a href=\"./?id_salle=".$salle->id."&amp;mode=cbars\">Planche de correspondance code barres-livres</a>");

	if ( $_REQUEST["mode"] == "cbars" )
	{

		$req = new requete ( $site->db, "SELECT " .
				"`inv_objet`.`cbar_objet`," .
				"`inv_objet`.`nom_objet`," .
				"`bk_serie`.`nom_serie`," .
				"`bk_book`.`num_livre`," .
				//"`bk_auteur`.`nom_auteur`," .
				"`bk_editeur`.`nom_editeur` " .
				"FROM `inv_objet` " .
				"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
				"INNER JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet` ".
				//"INNER JOIN `bk_auteur` ON `bk_book`.`id_auteur`=`bk_auteur`.`id_auteur` ".
				"INNER JOIN `bk_editeur` ON `bk_book`.`id_editeur`=`bk_editeur`.`id_editeur` ".
				"LEFT JOIN `bk_serie` ON `bk_book`.`id_serie`=`bk_serie`.`id_serie` ".
				"WHERE `inv_objet`.`id_salle`='".$salle->id."' " .
				"ORDER BY `inv_objet`.`cbar_objet`" );

		$tbl = new sqltable(
			"listlivres",
			"Livres", $req, "./",
			"id_livre",
			array("cbar_objet"=>"Code","nom_objet"=>"Titre","nom_serie"=>"Serie","num_livre"=>"",/*"nom_auteur"=>"Auteur",*/"nom_editeur"=>"Editeur"),
			array(), array(), array()
			);
		$cts->add($tbl,true);

	}
	else
	{

		$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_livre`," .
				//"`bk_auteur`.`id_auteur`,`bk_auteur`.`nom_auteur`," .
				"`bk_editeur`.`id_editeur`,`bk_editeur`.`nom_editeur`," .
				"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
				"`bk_book`.`num_livre`,".
				"`inv_objet`.`nom_objet` AS `nom_livre`, " .
				"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`  " .
				"FROM `inv_objet` " .
				"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
				"INNER JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet` ".
				//"INNER JOIN `bk_auteur` ON `bk_book`.`id_auteur`=`bk_auteur`.`id_auteur` ".
				"INNER JOIN `bk_editeur` ON `bk_book`.`id_editeur`=`bk_editeur`.`id_editeur` ".
				"LEFT JOIN `bk_serie` ON `bk_book`.`id_serie`=`bk_serie`.`id_serie` ".
				"WHERE `inv_objet`.`id_salle`='".$salle->id."' " .
				"ORDER BY `bk_serie`.`nom_serie`,`bk_book`.`num_livre`,`inv_objet`.`nom_objet`" );

		$tbl = new sqltable(
			"listlivres",
			"Livres", $req, "./",
			"id_livre",
			array("nom_livre"=>"Titre","nom_serie"=>"Serie","num_livre"=>"N°"/*,"nom_auteur"=>"Auteur"*/,"nom_editeur"=>"Editeur","nom_salle"=>"Lieu"),
			array(), array(), array()
			);
		$cts->add($tbl,true);

  	$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_jeu`," .
  			"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
  			"`inv_objet`.`nom_objet` AS `nom_jeu`, " .
  			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`, " .
  			"`inv_jeu`.`nb_joueurs_jeu`, " .
  			"`inv_jeu`.`duree_jeu`, " .
  			"`inv_jeu`.`difficulte_jeu`, " .
  			"`inv_jeu`.`langue_jeu`, " .
  	  	"`inv_type_objets`.`nom_objtype`  " .
  			"FROM `inv_objet` " .
  			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
  			"INNER JOIN `inv_jeu` ON `inv_jeu`.`id_objet`=`inv_objet`.`id_objet` ".
  	  	"INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` " .
  			"LEFT JOIN `bk_serie` ON `inv_jeu`.`id_serie`=`bk_serie`.`id_serie` ".
  		  "WHERE `inv_objet`.`id_salle`='".$salle->id."' " .
  			"ORDER BY `inv_type_objets`.`nom_objtype`,`bk_serie`.`nom_serie`,`inv_objet`.`nom_objet`" );

  	$tbl = new sqltable(
  		"listjeux",
  		"Jeux", $req, "./",
  		"id_jeu",
  		array("nom_objtype"=>"Type","nom_jeu"=>"Titre", "nom_serie"=>"Serie", "nb_joueurs_jeu"=>"Nombre de joueurs", "duree_jeu"=>"Durée partie", "difficulte_jeu"=>"Difficultée", "langue_jeu" => "Langue", "nom_salle"=>"Lieu"),
  		array(), array(), array()
  		);
  	$cts->add($tbl,true);

	}
	$site->add_contents($cts);
	$site->end_page();
	exit();
}


$site->start_page("services","Bibliothéque");

if ( $_REQUEST["view"] == "" )
{
	require_once($topdir."include/entities/page.inc.php");
	$page = new page ($site->db);
	$page->load_by_pagename("info:biblio");
	if ( $page->id != -1 )
		$site->add_contents($page->get_contents());
}

$cts = new contents("Bibliothèque");

$cts->add(new tabshead($tabs,$_REQUEST["view"]));

if ( $_REQUEST["view"] == "" )
{
	$frm = new form("search","./",false,"POST","Recherche d'un livre/BD");
	$frm->add_hidden("action","search");
	$frm->add_text_field("name","Titre");
	$frm->add_entity_select("id_serie", "Serie", $site->db, "serie", false,true);
	$frm->add_entity_select("id_auteur", "Auteur", $site->db, "auteur", false,true);
	$frm->add_entity_select("id_editeur", "Editeur", $site->db, "editeur", false,true);
	$frm->add_select_field("id_salle","Lieu",get_lieux());
	$frm->add_submit("valide","Rechercher");
	$cts->add($frm,true);
	/*
  $frm = new form("search","./",false,"POST","Recherche d'un jeu");
	$frm->add_hidden("action","searchjeu");
	$frm->add_text_field("name","Titre",$_REQUEST["name"]);
	$frm->add_entity_select("id_serie", "Serie", $site->db, "serie", $serie->id,true);
	$frm->add_select_field("id_salle","Lieu",get_lieux());
	$frm->add_submit("valide","Rechercher");
	$cts->add($frm,true);
	*/

	$cts->add_title(2,"Jeux");
	$cts->add_paragraph("<a href=\"?view=jeux\">Consulter la liste des jeux</a>");
}
elseif ( $_REQUEST["view"] == "auteurs" )
{
	if ( $is_admin )
	{
		$frm = new form("addauteur","./?view=auteurs",false,"POST","Ajout d'un auteur");
		$frm->add_hidden("action","addauteur");
		$frm->add_text_field("name","Nom");
		$frm->add_submit("valide","Ajouter");
		$cts->add($frm,true);
	}

	$req = new requete($site->db,"SELECT * FROM `bk_auteur` ORDER BY `nom_auteur`");
	$cts->add(new sqltable(
		"listauteurs",
		"Auteurs", $req, "./",
		"id_auteur",
		array("nom_auteur"=>"Nom"),
		array(), array(), array()
		),true);
}
elseif ( $_REQUEST["view"] == "series" )
{
	if ( $is_admin )
	{
		$frm = new form("addserie","./?view=series",false,"POST","Ajout d'une série");
		$frm->add_hidden("action","addserie");
		$frm->add_text_field("name","Nom");
		$frm->add_submit("valide","Ajouter");
		$cts->add($frm,true);
	}

	$req = new requete($site->db,"SELECT * FROM `bk_serie` ORDER BY `nom_serie`");
	$cts->add(new sqltable(
		"listseries",
		"Series", $req, "./",
		"id_serie",
		array("nom_serie"=>"Nom"),
		array(), array(), array()
		),true);
}
elseif ( $_REQUEST["view"] == "editeurs" )
{
	if ( $is_admin )
	{
		$frm = new form("addediteur","./?view=editeurs",false,"POST","Ajout d'un éditeur");
		$frm->add_hidden("action","addediteur");
		$frm->add_text_field("name","Nom");
		$frm->add_submit("valide","Ajouter");
		$cts->add($frm,true);
	}

	$req = new requete($site->db,"SELECT * FROM `bk_editeur` ORDER BY `nom_editeur`");
	$cts->add(new sqltable(
		"listediteurs",
		"Editeurs", $req, "./",
		"id_editeur",
		array("nom_editeur"=>"Nom"),
		array(), array(), array()
		),true);
}

elseif ( $_REQUEST["view"] == "lieux" )
{

	$req = new requete ( $site->db, "SELECT ".
			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`  " .
			"FROM `inv_objet` " .
			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
			"LEFT JOIN `bk_book` ON `bk_book`.`id_objet`=`inv_objet`.`id_objet` ".
			"LEFT JOIN `inv_jeu` ON `inv_jeu`.`id_objet`=`inv_objet`.`id_objet` ".
			"WHERE `inv_jeu`.`id_objet` IS NOT NULL OR `bk_book`.`id_objet` IS NOT NULL ".
			"GROUP BY `sl_salle`.`id_salle`" );

	$cts->add(new sqltable(
		"listelieux",
		"Lieux", $req, "./",
		"id_salle",
		array("nom_salle"=>"Lieu"),
		array(), array(), array()
		),true);

}

elseif ( $_REQUEST["view"] == "livres" )
{
	if ( $_REQUEST["action"] == "detectlivre" )
	{
		require_once($topdir."include/extdb/isbndb.inc.php");

		$res = 0;

		if ( ereg("^([0-9]{13})$",$_REQUEST["cbar"]) )
			$res = isbn_get_infos_from_ean13($_REQUEST["cbar"]);
		else
			$res = isbn_get_infos(str_replace("-","",$_REQUEST["cbar"]));

		if ( !is_array($res) )
		{
			$cts->add(new contents("Erreur","<p>Impossible de trouver des informations sur ".htmlentities($_REQUEST["cbar"]).". Erreur $res</p>"),true);
		}
		else
		{
			$cap=NULL;
			$cts->add(new itemlist("Resultat ISBNDB.COM",false,
					array(
						"ISBN : ".$res["isbn"],
						"Titre : ".$res["title"],
						"Titre long : ".$res["longtitle"],
						"Auteur(s) : ".$res["author"],
						"Editeur : ".$res["editor"])),true);

			$isbn = $res["isbn"];

			// Petit jeu des déductions
			if ( ereg("^(.*),([ ]*)tome([0-9 ]*):(.*)$",$res["title"],$cap) )
			{
				$serie=trim($cap[1]);
				$num=trim($cap[3]);
				$nom=trim($cap[4]);
			}
			elseif ( ereg("^(.*),([ ]*)tome([0-9 ]*):(.*)$",$res["longtitle"],$cap) )
			{
				$serie=trim($cap[1]);
				$num=trim($cap[3]);
				$nom=trim($cap[4]);
			}
			elseif ( ereg("^(.*),([ ]*)tome([0-9 ]*)$",$res["title"],$cap) )
			{
				$serie=trim($cap[1]);
				$num=trim($cap[3]);
				$nom="";
			}
			else
			{
				$serie=NULL;
				$num=NULL;
				$nom=trim($res["title"]);
			}

			if ( ereg("^(.*),([ ]*)$",$res["author"],$cap) )
				$auteur=trim($cap[1]);
			else
				$auteur=trim($res["author"]);

			if ( ereg("^(.*),([ ]*)$",$res["editor"],$cap) )
				$editeur=trim($cap[1]);
			else
				$editeur=trim($res["editor"]);

			$cts->add(new itemlist("Après traitement",false,
					array(
						"ISBN : ".$isbn,
						"Titre : ".$nom,
						"Serie : ".(is_null($serie)?"N/A":$serie),
						"Tome : ".(is_null($num)?"N/A":$num),
						"Auteur(s) : ".$auteur,
						"Editeur : ".$editeur)),true);


		}

		$frm = new form("detectlivre","./?view=livres",false,"POST","Ajout d'un livre par ISBN ou EAN13");
		$frm->add_hidden("action","detectlivre");
		$frm->add_text_field("cbar","ISBN ou EAN13");
		$frm->add_submit("valide","Rechercher");
		$cts->add($frm,true);
	}
	else if ( $is_admin )
	{
		$frm = new form("detectlivre","./?view=livres",false,"POST","Ajout d'un livre par ISBN ou EAN13");
		$frm->add_hidden("action","detectlivre");
		$frm->add_text_field("cbar","ISBN ou EAN13");
		$frm->add_submit("valide","Rechercher");
		$cts->add($frm,true);

		$frm = new form("addlivre","./?view=livres",false,"POST","Ajout d'un livre");
		$frm->add_hidden("action","addlivre");
		$frm->add_text_field("nom","Nom");
		$frm->add_text_field("isbn","ISBN ou EAN13");
		$frm->add_text_field("num","Numéro");
		$frm->add_entity_select("id_serie", "Serie", $site->db, "serie",false,true);
		$frm->add_entity_select("id_auteur", "Auteur", $site->db, "auteur",false,true);
		$frm->add_entity_select("id_auteur1", "Co-auteur", $site->db, "auteur",false,true);
		$frm->add_entity_select("id_auteur2", "Co-auteur", $site->db, "auteur",false,true);
		$frm->add_entity_select("id_auteur3", "Co-auteur", $site->db, "auteur",false,true);
		$frm->add_entity_select("id_editeur", "Editeur", $site->db, "editeur",false,true);
		$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $objtype->id);
		$frm->add_text_field("num_serie","Numéro de série");
		$frm->add_date_field("date_achat","Date d'achat");
		$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", false, false, array("id_asso_parent"=>NULL));
		$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso");
		$frm->add_entity_select("id_salle", "Salle", $site->db, "salle");
		$frm->add_price_field("prix","Prix d'achat");
		$frm->add_price_field("caution","Prix de la caution");
		$frm->add_checkbox("en_etat","En etat",true);
		$frm->add_text_area("notes","Notes");
		$frm->add_submit("valide","Ajouter");
		$cts->add($frm,true);

		$frm = new form("addlivres","./?view=livres",false,"POST","Ajout de livres");
		$frm->add_hidden("action","addlivres");
		$frm->add_date_field("date_achat","Date d'achat");
		$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", false, false, array("id_asso_parent"=>NULL));
		$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso");
		$frm->add_entity_select("id_salle", "Salle", $site->db, "salle");
		$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $objtype->id);
		$frm->add_price_field("prix","Prix d'achat");
		$frm->add_price_field("caution","Prix de la caution");
		$frm->add_text_area("data","Tableau CSV","Serie; Nom; Num; Auteur 1, Auteur 2, Auteur 3; Editeur; ISBN(ou EAN13)\n",80,12);
		$frm->add_submit("valide","Ajouter");
		$cts->add($frm,true);
	}


}
elseif ( $_REQUEST["view"] == "prets" && $is_admin )
{
	if ( $Message )
		$cts->add($Message,true);

	$frm = new form("borrowbooks","./?view=livres",false,"POST","Preter des livres");
	if ( $ErreurEmprunt )
		$frm->error($ErreurEmprunt);
	$frm->add_hidden("action","borrowbooks");
	$frm->add_datetime_field("endtime","Fin de l'emprunt",-1,true);
	$ssfrm = new form("qui",null,null,null,"Emprunteur");
	$sfrm = new form("emp",null,null,null,"Le cotisant dont la carte est");
	$sfrm->add_text_field("carte"," : ");
	$ssfrm->add($sfrm,false,true,true,"carte",true);
	$sfrm = new form("emp",null,null,null,"L'utilisateur");
	$sfrm->add_entity_smartselect("id_utilisateur","",new utilisateur($site->db));

	$ssfrm->add($sfrm,false,true,false,"email",true);
	$frm->add($ssfrm);
	$frm->add_text_area("cbars","Codes barres des livres","",40,3,true);
	$frm->add_price_field("caution","Caution",$caution);
	$frm->add_text_area("notes","Notes");
	$frm->add_submit("valide","Preter");
	$cts->add($frm,true);


	$frm = new form("bookback","./?view=livres",false,"POST","Retour de livres");
	$frm->add_hidden("action","bookback");
	if ( $ErreurRetour )
		$frm->error($ErreurRetour);
	$frm->add_text_field("cbar","Code barre");
	$frm->add_submit("valide","Rendu");
	$cts->add($frm,true);

}
elseif ( $_REQUEST["view"] == "jeux" )
{

	$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet` AS `id_jeu`," .
			"`bk_serie`.`id_serie`,`bk_serie`.`nom_serie`," .
			"`inv_objet`.`nom_objet` AS `nom_jeu`, " .
			"`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`, " .
			"`inv_jeu`.`nb_joueurs_jeu`, " .
			"`inv_jeu`.`duree_jeu`, " .
			"`inv_jeu`.`difficulte_jeu`, " .
			"`inv_jeu`.`langue_jeu`, " .
	  	"`inv_type_objets`.`nom_objtype`  " .
			"FROM `inv_objet` " .
			"INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
			"INNER JOIN `inv_jeu` ON `inv_jeu`.`id_objet`=`inv_objet`.`id_objet` ".
	  	"INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` " .
			"LEFT JOIN `bk_serie` ON `inv_jeu`.`id_serie`=`bk_serie`.`id_serie` ".
			"ORDER BY `inv_type_objets`.`nom_objtype`,`bk_serie`.`nom_serie`,`inv_objet`.`nom_objet`" );

	$tbl = new sqltable(
		"listjeux",
		"Jeux", $req, "./",
		"id_jeu",
		array("nom_objtype"=>"Type","nom_jeu"=>"Titre", "nom_serie"=>"Serie", "nb_joueurs_jeu"=>"Nombre de joueurs", "duree_jeu"=>"Durée partie", "difficulte_jeu"=>"Difficultée", "langue_jeu" => "Langue", "nom_salle"=>"Lieu"),
		array(), array(), array()
		);
	$cts->add($tbl,true);

  if ( $is_admin )
	{

		$frm = new form("addjeu","./?view=jeux",false,"POST","Ajout d'un jeu");
		$frm->add_hidden("action","addjeu");
		$frm->add_text_field("nom","Nom");
		$frm->add_entity_select("id_serie", "Serie", $site->db, "serie",false,true);
		$frm->add_text_field("etat","Etat");
		$frm->add_text_field("nb_joueurs","Nombre de joueurs");
		$frm->add_text_field("duree","Durée moyenne d'une partie");
		$frm->add_text_field("langue","Langue");
		$frm->add_text_field("difficulte","Difficultée");
		$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $objtype->id);
		$frm->add_text_field("num_serie","Numéro de série");
		$frm->add_date_field("date_achat","Date d'achat");
		$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", false, false, array("id_asso_parent"=>NULL));
		$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso");
		$frm->add_entity_select("id_salle", "Salle", $site->db, "salle");
		$frm->add_price_field("prix","Prix d'achat");
		$frm->add_price_field("caution","Prix de la caution");
		$frm->add_checkbox("en_etat","En etat",true);
		$frm->add_text_area("notes","Notes");
		$frm->add_submit("valide","Ajouter");
		$cts->add($frm,true);

		$frm = new form("addjeux","./?view=jeux",false,"POST","Ajout de jeux");
		$frm->add_hidden("action","addjeux");
		$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $objtype->id);
		$frm->add_date_field("date_achat","Date d'achat");
		$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", false, false, array("id_asso_parent"=>NULL));
		$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso");
		$frm->add_entity_select("id_salle", "Salle", $site->db, "salle");
		$frm->add_text_area("data","Tableau CSV","Nom; Serie (facultatif); Etat; Nombre de joueurs; Durée moyenne; Langue; Difficultée; Prix; Caution; Reservable (o/n)\n",80,12);
		$frm->add_submit("valide","Ajouter");
		$cts->add($frm,true);

	}

}


$site->add_contents($cts);
$site->end_page();



?>
