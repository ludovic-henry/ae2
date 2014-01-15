<?
/* Copyright 2007
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */


$topdir = "../";

require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once("include/jobetu.inc.php");
require_once("include/annonce.inc.php");
require_once("include/cts/jobetu.inc.php");
require_once("include/jobuser_etu.inc.php");

$i18n = array("ar" => "Arabe",
							"cn" => "Chinois",
							"de" => "Allemand",
							"en" => "Anglais",
							"es" => "Espagnol",
							"fr" => "Français",
							"it" => "Italien",
							"kr" => "Coréen",
							"pt" => "Portugais"
							);

$site = new site();
$site->allow_only_logged_users("services");
if(!$site->user->is_in_group("jobetu_etu")) header("Location: index.php");

$usr = new jobuser_etu($site->db, $site->dbrw);
if(isset($_REQUEST['id_utilisateur']) && ($site->user->is_in_group("gestion_ae") ||$site->user->is_in_group("root") || $site->user->is_in_group("jobetu_admin") ) )
	$usr->load_by_id($_REQUEST['id_utilisateur']);
else
	$usr->load_by_id($site->user->id);

$site->add_css("jobetu/jobetu.css");
$site->add_rss("Les dernières annonces de JobEtu","rss.php");

$site->start_page("services", "AE Job Etu");

$path = "<a href=\"".$topdir."jobetu/\" title=\"AE JobEtu\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" /> AE JobEtu</a>";
$path .= " / "."<a href=\"".$topdir."jobetu/board_etu.php\" title=\"Tableau de bord\"><img src=\"".$topdir."images/icons/16/board.png\" class=\"icon\" /> Tableau de bord candidat</a>";
$path .= " / "."<a href=\"".$topdir."user.php?id_utilisateur=$usr->id\" title=\"$usr->prenom $usr->nom\"><img src=\"".$topdir."images/icons/16/user.png\" class=\"icon\" /> $usr->prenom $usr->nom</a>";
$cts = new contents($path);

$tabs = array(
		      array("", "jobetu/board_etu.php", "mes annonces"),
		      array("candidatures", "jobetu/board_etu.php?view=candidatures", "mes candidatures"),
		      array("general", "jobetu/board_etu.php?view=general", "tout job-etu"),
		      array("profil", "jobetu/board_etu.php?view=profil", "profil"),
		      array("preferences", "jobetu/board_etu.php?view=preferences", "préférences")
	      );
$cts->add(new tabshead($tabs, $_REQUEST['view']));


/*******************************************************************************
 * Onglet profil
 */
if(isset($_REQUEST['view']) && $_REQUEST['view'] == "profil")
{
		$jobetu = new jobetu($site->db, $site->dbrw);
		$usr->load_competences();

		$lst = new itemlist("Résultats");
		/**
		 * Gestion des données recues sur la mise à jour du profil
		 */
		if(isset($_REQUEST['magicform']) && $_REQUEST['magicform']['name'] == "jobtypes_table")
		{
			$usr->update_competences($_REQUEST['id_jobs']);
		}
		else if(isset($_REQUEST['magicform']) && $_REQUEST['magicform']['name'] == "job_cvs")
		{
			$i = 1;
			foreach($_FILES as $file)
			{
				$usr->load_pdf_cv();
				if( in_array($_REQUEST['lang_'.$i], $usr->pdf_cvs) )
					$usr->del_pdf_cv($_REQUEST['lang_'.$i]);
				if( $file['type'] != "application/pdf")
					$lst->add("Veuillez envoyer un fichier au format PDF.", "ko");
				else if( $usr->add_pdf_cv($file, $_REQUEST['lang_'.$i]) )
					$lst->add("Votre CV en ".$i18n[ $_REQUEST['lang_'.$i] ]." a été correctement envoyé", "ok");
				else
					$lst->add("Une erreur s'est produite", "ko");

				$i++;
			}
		}
		else if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete")
		{
			if( $usr->del_pdf_cv($_REQUEST['cv']) )
				$lst->add("Votre CV en ".$i18n[ $_REQUEST['cv'] ] ." à bien été supprimé.", "ok");
			else
				$lst->add("Une erreur s'est produite.", "ko");
		}

		$cts->add($lst);

		$cts->add_title(2, "Modifiez vos informations");
	  $cts->add_paragraph("Toutes vos informations personnelles, telles que votre adresse, téléphone, date de naissance... sont celles de votre fiche Matmatronch, pour les modifier, <a href=\"$topdir./user.php?id_utilisateur=$usr->id&page=edit\">cliquez ici</a>");

	/**
	 * sqltable des compétences
	 */

	$cts->add_title(3, "De quoi êtes vous capable ?");
	$cts->add( new jobtypes_table($jobetu, $usr, "jobtypes_table", "Vos compétences") );

	/**
	 * Envoi de CV en PDF
	 */

	$usr->load_pdf_cv();
	$cts->add_title(2, "Vos CV \"traditionnels\" en ligne");
	$cts->add_paragraph("Vous avez ici la possiblité d'envoyer vos CV sur le site afin qu'ils soient consultés par les recruteurs <br />");
	$cts->add_paragraph("Attention : Vous ne pouvez envoyer que des fichiers PDF, si vous n'avez pas la possiblité d'en produire un par votre traitement de texte, vous pouvez vous tourner vers des outils de conversion tels <a href=\"http://www.zamzar.com\"> le site Zamzar</a>. <br />
											Vous pouvez envoyer un CV par langue, si vous envoyez un deuxième CV dans une même langue, celui-ci remplacera le précédent.");

	$cts->add_title(3, "Vos fichiers actuellement disponibles");
	if( empty($usr->pdf_cvs) )
		$cts->add_paragraph("Vous n'avez envoyé aucun CV pour l'instant.");
	else
	{
		$lst = new itemlist(false);
		foreach($usr->pdf_cvs as $cv)
			$lst->add("<img src=\"$topdir/images/i18n/$cv.png\" />&nbsp; CV PDF en " . $i18n[ $cv ] . ".&nbsp;&nbsp;&nbsp; [<a href=\"". $topdir . "var/cv/". $usr->id . "." . $cv .".pdf\">voir</a>] [<a href=\"board_etu.php?view=profil&action=delete&cv=$cv\">supprimer</a>]");
		$cts->add($lst, false);
	}

	$cts->add_title(3, "Envoyer un nouveau fichier");
	$cts->puts("<script langage=\"javascript\">
								function add_cv_field(){
										if ( typeof this.counter == 'undefined' ) this.counter = 1;
										this.counter++;
										document.getElementById(\"jobcvs\").innerHTML += '<div class=\"formrow\" name=\"cv_item_row\" id=\"cv_item_row\"><div class=\"linedrow\"><div class=\"subformlabel\"></div><div class=\"subforminline\" id=\"cv_item_contents\"> <!-- cv_item_contents --><div class=\"formrow\"><div class=\"formlabel\">Un autre CV &nbsp;&nbsp;</div><div class=\"formfield\"><input type=\"file\" name=\"cv_' + this.counter + '\" /></div></div><div class=\"formrow\"><div class=\"formlabel\">Langue &nbsp;&nbsp;</div><div class=\"formfield\"><select name=\"lang_' + this.counter + '\" ><option value=\"ar\">Arabe</option>	<option value=\"ch\">Chinois</option>	<option value=\"de\">Allemand</option>	<option value=\"en\">Anglais</option>	<option value=\"es\">Espagnol</option>	<option value=\"fr\" selected=\"selected\">Fran&ccedil;ais</option>	<option value=\"it\">Italien</option>	<option value=\"kr\">Cor&eacute;en</option>	<option value=\"pt\">Portugais</option></select></div></div></div><!-- end of cv_item_contents --></div><!-- end of fullrow/linedrow --></div></div>';
							}
							</script>");

	$frm = new form("job_cvs", "board_etu.php?view=profil", true, "POST");
		$frm->puts("<div name=\"jobcvs\" id=\"jobcvs\">");

		$subfrm = new form("cv_item", false, false, "POST");
		$subfrm->add_file_field("cv_1", "Envoyez un CV &nbsp;&nbsp;");
		$subfrm->add_select_field("lang_1", "Langue &nbsp;&nbsp;", $i18n, "fr");

		$frm->add($subfrm, false, false, false, false, true);
		$frm->puts("</div>");
		$frm->puts("<input type=\"button\" onclick=\"add_cv_field();\" value=\"Ajouter un champ\"/>");
	$frm->add_submit("go", "Envoyer les CVs");
	$cts->add($frm);

}

/*******************************************************************************
 * Onglet candidatures
 */
else if(isset($_REQUEST['view']) && $_REQUEST['view'] == "candidatures")
{
	$sql = new requete($site->db, "SELECT `job_annonces_etu`.*,
																	`job_annonces`.`titre`,
																	DATE_FORMAT(`job_annonces`.`date`, '%e/%c/%Y') AS `date`,
																	CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) AS `nom_utilisateur`,
																	`utilisateurs`.`id_utilisateur`,
																	'en cours' AS `etat`
																	FROM `job_annonces_etu`
																	NATURAL JOIN `job_annonces`
																	LEFT JOIN `utilisateurs`
																	ON `job_annonces`.`id_client` = `utilisateurs`.`id_utilisateur`
																	WHERE `job_annonces_etu`.`id_etu` = $usr->id
																	AND `job_annonces_etu`.`relation` = 'apply'
																	AND `job_annonces`.`provided` = 'false'
																");
	//faudrait trouver aussi un moyen de compter le nombre de concurrents
	$cts->add(new sqltable("candidatures", "Candidatures en cours", $sql, "board_etu.php?view=general", 'id_annonce', array("id_annonce"=>"N°", "titre" => "Annonce", "date" => "Déposée le", "nom_utilisateur" => "Par", "etat" => "Etat"), array("detail" => "Détails"), array("detail" => "Détails")), true);

	$sql = new requete($site->db, "SELECT `job_annonces_etu`.*,
																	`job_annonces`.`titre`,
																	DATE_FORMAT(`job_annonces`.`date`, '%e/%c/%Y') AS `date`
																	FROM `job_annonces_etu`
																	NATURAL JOIN `job_annonces`
																	WHERE `job_annonces_etu`.`id_etu` = $usr->id
																	AND `job_annonces_etu`.`relation` = 'selected'
																");
	$cts->add(new sqltable("candidatures", "Candidatures victorieuses", $sql, "board_etu.php?view=general", 'id_annonce', array("id_annonce"=>"N°", "titre" => "Annonce", "date" => "Déposée le"), array("detail" => "Détails"), array("detail" => "Détails")), true);

	$sql = new requete($site->db, "SELECT `job_annonces_etu`.*,
																	`job_annonces`.`titre`,
																	DATE_FORMAT(`job_annonces`.`date`, '%e/%c/%Y') AS `date`,
																	'Vous saurez pas gniak gniak gniak !' AS `people`
																	FROM `job_annonces_etu`
																	NATURAL JOIN `job_annonces`
																	WHERE `job_annonces_etu`.`id_etu` = $usr->id
																	AND `job_annonces_etu`.`relation` = 'apply'
																	AND `job_annonces`.`provided` = 'true'
																");
	$cts->add(new sqltable("candidatures", "Candidatures perdues", $sql, "board_etu.php?view=general", 'id_annonce', array("id_annonce"=>"N°", "titre" => "Annonce", "date" => "Déposée le", "people" => "Etudiant sélectionné"), array("detail" => "Détails"), array("detail" => "Détails")), true);
}

/*******************************************************************************
 * Onglet tout jobetu
 */
else if(isset($_REQUEST['view']) && $_REQUEST['view'] == "general")
{
	if(isset($_REQUEST['action']))
	{
		$ids = array();
		if(isset($_REQUEST['id_annonce']))
			$ids[] = $_REQUEST['id_annonce'];
		if(isset($_REQUEST['id_annonces']))
			foreach ($_REQUEST['id_annonces'] as $id)
				$ids[] = $id;

		if($_REQUEST['action'] == "detail")
		{
			foreach ($ids as $id_annonce)
			{
				$annonce = new annonce($site->db);
				$annonce->load_by_id($id_annonce);
				$cts->add( new apply_annonce_box($annonce) );
			}

		}
		else if($_REQUEST['action'] == "reject")
		{
			foreach ($ids as $id_annonce)
			{
				$annonce = new annonce($site->db, $site->dbrw);
				$annonce->load_by_id($id_annonce);
				if( $annonce->reject($usr) )
					$cts->add_paragraph("Votre souhait de ne plus voir l'annonce n°".$annonce->id." vous être proposée à bien été enregistré.\n");;
			}
		}
		else if($_REQUEST['action'] == "apply")
		{
			$cts->add_paragraph("Namého ! tu te crois chez mémé ? ca se passe pas comme ça nondidiou !!");
		}
	}

	$sql = new requete($site->db, "SELECT `job_annonces`.*,
																		CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) AS `nom_utilisateur`,
																		`utilisateurs`.`id_utilisateur`,
																		`job_types`.`nom` AS `job_nom`
																		FROM `job_annonces`
																		LEFT JOIN `utilisateurs`
																		ON `job_annonces`.`id_client` = `utilisateurs`.`id_utilisateur`
																		LEFT JOIN `job_types`
																		ON `job_types`.`id_type` = `job_annonces`.`job_type`
																		WHERE `job_annonces`.`id_annonce`
																		NOT IN (SELECT id_annonce FROM job_annonces_etu WHERE id_etu = $usr->id)
																		", false);

		/**
		 * @todo possibilité de trier par catégorie (voire utilisateur, date...)
		 */
		$table = new sqltable("annlist", "Liste des annonces en cours", $sql, "board_etu.php?view=general", "id_annonce",
													array(
														"id_annonce" => "N°",
														"nom_utilisateur" => "Client",
														"job_nom" => "Catégorie",
														"titre" => "Titre"
													),
													array("detail" => "Détails", "reject" => "Ne plus me montrer"),
													array("detail" => "Détails", "reject" => "Ne plus me montrer"),
													array()
												);

		$cts->add($table, true);

}

/*******************************************************************************
 * Onglet préférénce
 */
else if(isset($_REQUEST['view']) && $_REQUEST['view'] == "preferences")
{
	if( empty($usr->prefs) ) $usr->load_prefs();

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "save_prefs")
	{
	  if(isset($_REQUEST['pub_cv']) && $_REQUEST['pub_cv'] == "1")
	    $pub_cv = "true";
	  else
	    $pub_cv = "false";

		$yeah = $usr->update_prefs($pub_cv, $_REQUEST['mail_prefs'] );
		if($yeah)
		{
			$lst = new itemlist(false);
			$lst->add("Préférences correctements enregistrées", "ok");
			$cts->add($lst);
		}
	}

	$frm = new form("prefs_utl", "board_etu.php?view=preferences&action=save_prefs", false, "POST", "Préférences");
	$frm->add_checkbox("pub_cv", "Autoriser la diffusion de mon CV (lien sur la fiche Matmatronch)", $usr->prefs['pub_cv']);
	//checkbox recevoir un mail dès qu'une annonce est déposée
	$mail_prefs_val = array("part" => "Faible (uniquement en confirmation d'actions importantes)", "full" => "Fréquent (à chaque annonce vous concernant ou autre évenement)");
	$frm->add_radiobox_field("mail_prefs", "Envoi de mails", $mail_prefs_val, ($usr->prefs) ? $usr->prefs['mail_prefs'] : "part", false, false, null, false);
	//bouton pour envoyer "je fais des bisous à Pedrov" au 36375 (0.56cts par SMS plus cout d'un SMS)
	$frm->add_submit("go", "Enregistrer");
	$cts->add($frm, true);

}

/*******************************************************************************
 * Onglet d'accueil sinon
 */
else
{
	if( isset($_REQUEST['action']) )
	{
		$annonce = new annonce($site->db, $site->dbrw);
		$annonce->load_by_id($_REQUEST['id']);

		if($_REQUEST['action'] == "apply")
		{
			if( $annonce->apply_to($usr, $_REQUEST['comment']) )
			{
				$cts->add_paragraph("Votre candidature à bien été enregistrée pour l'annonce n°".$annonce->id." : <i>\" ".$annonce->titre." \"</i>\n");
			}
		}
		else if($_REQUEST['action'] == "reject")
		{
			if( $annonce->reject($usr) )
			{
				$cts->add_paragraph("Votre souhait de ne plus voir l'annonce n°".$annonce->id." vous être proposée à bien été enregistré.\n");
			}
		}

	} //fin 'actions'

	$usr->load_annonces();

	if(empty($usr->annonces))
	{
		$cts->add_paragraph("<b>Nous n'avons trouvé aucune annonce correspondant à votre profil</b>.");
		$cts->add_paragraph("Vérifiez d'avoir correctement rempli votre tableau de compétences dans la <a href=\"board_etu.php?view=profil\">section \"profil\"</a>.");
		// ou pas pour l instant $cts->add_paragraph("Si vous pensez avoir découvert un bug, merci de <a href=\"https://ae.utbm.fr/trac/ae2/newticket?component=jobetu\">le signaler</a>.");
	}
	else
	{
		$cts->add_title(3, "Nous avons trouvé ".count($usr->annonces)." annonce(s) correspondant à votre <a href=\"board_etu.php?view=profil\">profil</a> :");

		foreach($usr->annonces as $id_annonce)
		{
			$annonce = new annonce($site->db);
			$annonce->load_by_id($id_annonce);
			$cts->add( new apply_annonce_box($annonce) );
		}
	}

}


$site->add_contents($cts);

$site->end_page();

?>
