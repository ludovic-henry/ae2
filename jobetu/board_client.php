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

require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/cts/special.inc.php");
require_once("include/jobetu.inc.php");
require_once("include/annonce.inc.php");
require_once("include/cts/jobetu.inc.php");
require_once("include/jobuser_client.inc.php");
require_once("include/jobuser_etu.inc.php");


$site = new site();
$site->allow_only_logged_users("jobetu");
if(!$site->user->is_in_group("jobetu_client")) header("Location: index.php");

$site->add_css("jobetu/jobetu.css");
$site->add_css("css/mmt.css");
$site->start_page("services", "AE Job Etu");

$usr = new jobuser_client($site->db, $site->dbrw);
if(isset($_REQUEST['id_utilisateur']) && ($site->user->is_in_group("gestion_ae") ||$site->user->is_in_group("root") || $site->user->is_in_group("jobetu_admin") ) )
	$usr->load_by_id($_REQUEST['id_utilisateur']);
else
	$usr->load_by_id($site->user->id);

$path = "<a href=\"".$topdir."jobetu/\" title=\"AE JobEtu\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" /> AE JobEtu</a>";
$path .= " / "."<a href=\"".$topdir."jobetu/board_client.php\" title=\"Tableau de bord\"><img src=\"".$topdir."images/icons/16/board.png\" class=\"icon\" /> Tableau de bord recruteur</a>";
$path .= " / "."<a href=\"".$topdir."user.php?id_utilisateur=$usr->id\" title=\"$usr->prenom $usr->nom\"><img src=\"".$topdir."images/icons/16/user.png\" class=\"icon\" /> $usr->prenom $usr->nom</a>";
$cts = new contents($path);

$tabs = array(
		      array("", "jobetu/board_client.php", "annonces"),
		      array("preferences", "jobetu/board_client.php?view=preferences", "préférences"),
		      array("annonce", "jobetu/depot.php?action=annonce", "nouvelle annonce")
	      	);
$cts->add(new tabshead($tabs, $_REQUEST['view']));


/*******************************************************************************
 * Onglet préférences
 */
if(isset($_REQUEST['view']) && $_REQUEST['view'] == "preferences")
{
	if( empty($usr->prefs) ) $usr->load_prefs();

	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "save_prefs")
	{
		$yeah = $usr->update_prefs( isset($_REQUEST['pub_profil']), $_REQUEST['mail_prefs'], isset($_REQUEST['pub_num']) );
		if(yeah)
		{
			$lst = new itemlist(false);
			$lst->add("Préférences correctements enregistrées", "ok");
			$cts->add($lst);
		}
	}

	$frm = new form("prefs_utl", "board_client.php?view=preferences&action=save_prefs", false, "POST", "Préférences");
	$frm->puts("<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" class=\"isubmit\" onClick=\"javascript: window.location.replace('../user.php?id_utilisateur=$usr->id&page=edit');\" value=\"Editer mon profil\" /></div></div>");
	$frm->add_checkbox("pub_profil", "Autoriser la consultation de mon profil sur le site", $usr->publique);
	$frm->add_checkbox("pub_num", "Publier mon numéro de téléphone dans mes annonces par défaut", ($usr->prefs) ? $usr->prefs['pub_num'] : "false");

	$mail_prefs_val = array("part" => "Faible (uniquement pour une action importante)", "full" => "Fréquent (à chaque candidature ou autre évenement)");
	$frm->add_radiobox_field("mail_prefs", "Envoi de mails", $mail_prefs_val, ($usr->prefs) ? $usr->prefs['mail_prefs'] : "full", false, false, null, false);
	//bouton pour envoyer "je fais des bisous à Pedrov" au 36375 (0.56cts par SMS plus cout d'un SMS)
	$frm->add_submit("go", "Enregistrer");
	$cts->add($frm, true);

}

/*******************************************************************************
 * Onglet accueil: annonces
 */
else
{
	$usr->load_annonces();

	if( isset($_REQUEST['action']) )
	{
		$annonce = new annonce($site->db, $site->dbrw);
		$annonce->load_by_id($_REQUEST['id']);

			if( $annonce->id_client != $usr->id )
			{
				$site->add_contents(new error("Erreur", "Soit vous essayez de frauder soit ya un bug, mais dans tout les cas ça peut pas se passer comme ça !"));
				$site->end_page();
				exit;
			}
			else
			{
				if( $_REQUEST['action'] == "select" )
				{
					$etu = new jobuser_client($site->db);
					$etu->load_by_id($_REQUEST['etu']);
					$annonce->set_winner($etu, $usr);
				}
				else if( $_REQUEST['action'] == "close" )
				{
					$annonce->set_closed($_REQUEST['close_eval'], $_REQUEST['close_comment']);
				}
				else if( $_REQUEST['action'] == "detail" )
				{
					$cts->add_title(3, "Détails de l'annonce n°".$annonce->id);
					$cts->add( new annonce_box($annonce) );
				}
			}
	}

	$cts->add_title(3, "Vous avez ".count($user->annonces)." annonce(s) en cours");

	foreach($usr->annonces as $ann)
	{
		$annonce = new annonce($site->db);
		$annonce->load_by_id($ann['id_annonce']);
		if( !($annonce->is_provided() ) );
			$annonce->load_applicants_fullobj();
		$box = new annonce_box($annonce);
		$cts->add($box);
	}

	$sql = new requete($site->db, "SELECT *,
																	`job_annonces`.`id_annonce` AS `id`,
																	DATE_FORMAT(`date`, '%e/%c/%Y') AS `date`
																	FROM job_annonces
																	WHERE id_client = $usr->id AND closed = '1'");
	if( $sql->lines > 0 )
	{
		$cts->puts("<a name=\"closed\"></a>");
		$table = new sqltable("closedlist", "Vos précédentes annonces", $sql, "board_client.php", "id", array("id" => "N°", "date" => "Date", "titre" => "Titre"), array("detail" => "Détails"), array(), array() );
		$cts->add($table, true);
	}
}

$site->add_contents($cts);
$site->end_page();

?>
