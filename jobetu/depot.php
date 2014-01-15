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
require_once("include/jobetu.inc.php");
require_once("include/cts/jobetu.inc.php");
require_once("include/jobuser_client.inc.php");
require_once("include/annonce.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/pays.inc.php");

define("GRP_JOBETU_CLIENT", 35);

$site = new site();
$site->start_page("services", "AE Job Etu");

$cts = new contents("Déposer une annonce");


/****************************************************************************************************
 *  Formulaire de dépôt de l'annonce
 */

if(!empty($_REQUEST['action']) && $_REQUEST['action']=="annonce")
{

	$site->allow_only_logged_users("jobetu");
	if(!$site->user->is_in_group("jobetu_client")) header("Location: depot.php?action=infos");
	$jobuser = new jobuser_client($site->db);
	$jobuser->load_by_id($site->user->id);
	$jobuser->load_prefs();

	$cts->add_paragraph("Veuillez à présent entrer la description de votre annonce.");
	$cts->add_paragraph("Soyez aussi précis que possible dans la description de vos besoins afin que nous puissions pleinement vous satisfaire.");

	$jobetu = new jobetu($site->db, $site->dbrw);
	$jobetu->get_job_types();

	$frm = new form("jobs", "depot.php?action=add", false, "POST", "Contenu de l'annonce");

	$frm->add_text_field("titre_ann", "Titre de l'annonce", false, true, 60);
	$frm->add( new jobtypes_select_field($jobetu, "job_type", "Catégorie") );
	$frm->add_info("<i>Si vous ne trouvez pas de categorie adequate, n'hesitez pas a <a href=\"mailto:ae.jobetu@utbm.fr?subject=Demande d'ajout de catégorie\">le signaler</a></i>");
	$frm->add_text_area("desc_ann", "Description de l'annonce", false, 60, 8, true);
	$frm->add_text_area("profil", "Profil recherché", false, 60, 3, true);
	$frm->add_date_field("date_debut", "Date de debut");
	$frm->add_text_field("duree", "Durée");
	$frm->add_text_field("lieu", "Lieu");
	$frm->add_text_field("remuneration", "Rémunération");
	$frm->add_text_field("nb_postes", "Nombre de postes disponibles", "1", true, 4);
	$frm->add_text_area("divers", "Autres informations", false, 60, 3);
	$frm->add_checkbox("allow_diff", "Diffuser mon numéro de téléphone aux candidats afin qu'ils puissent me contacter", ($jobuser->prefs) ? $jobuser->prefs['pub_num'] : false );
	$frm->add_submit("go", "Enregistrer mon annonce");
	$frm->add_info("Les champs marqués d'une astérisque (*) doivent être remplis.");

	$cts->add($frm, true);
}

/*******************************************************************************************************
 * Formulaire inscription/connexion
 */
else if(!empty($_REQUEST['action']) && $_REQUEST['action']=="edit")
{
	$site->allow_only_logged_users("jobetu");
	if(!$site->user->is_in_group("jobetu_client")) header("Location: depot.php?action=infos");

	$annonce = new annonce($site->db, $site->dbrw);
	$annonce->load_by_id($_REQUEST['id']);
	if( !$annonce->id || !($site->user->id == $annonce->id_client || $site->user->is_in_group("gestion_ae") || $site->user->is_in_group("jobetu_admin") ))
		header("Location: index.php");

	$cts->add_paragraph("Edition de l'annonce n°$annonce->id : \"$annonce->titre\" ");

	$jobetu = new jobetu($site->db, $site->dbrw);
	$jobetu->get_job_types();

	$frm = new form("jobs", "depot.php?action=save&id=$annonce->id", false, "POST", "Edition de l'annonce");

	$frm->add_text_field("titre_ann", "Titre de l'annonce", $annonce->titre, true, 60);
	$frm->add( new jobtypes_select_field($jobetu, "job_type", "Catégorie", $annonce->id_type) );
	$frm->add_info("<i>Si vous ne trouvez pas de categorie adequate, n'hesitez pas a <a href=\"mailto:ae-jobetu@utbm.fr?subject=Demande d'ajout de catégorie\">le signaler</a></i>");
	$frm->add_text_area("desc_ann", "Description de l'annonce", $annonce->desc, 60, 8, true);
	$frm->add_text_area("profil", "Profil recherché", $annonce->profil, 60, 3, true);
	$frm->add_date_field("date_debut", "Date de debut (facultatif)", $annonce->start_date);
	$frm->add_text_field("duree", "Durée", $annonce->duree);
	$frm->add_text_field("lieu", "Lieu", $annonce->lieu);
	$frm->add_text_field("remuneration", "Rémunération", $annonce->indemnite);
	$frm->add_text_field("nb_postes", "Nombre de postes disponibles", $annonce->nb_postes, true, 4);
	$frm->add_text_area("divers", "Autres informations", $annonce->divers, 60, 3);
	$frm->add_checkbox("allow_diff", "Diffuser mon numéro de téléphone aux candidats afin qu'ils puissent me contacter", (bool)$annonce->allow_diff);
	$frm->add_submit("go", "Sauvegarder mon annonce");
	$frm->puts("<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" class=\"isubmit\" onClick=\"javascript: window.location.replace('board_client.php');\" value=\"Revenir au tableau de bord\" /></div></div>");
	if($site->user->is_in_group("jobetu_admin"))
	  $frm->puts("<div class=\"formrow\"><div class=\"formlabel error\">admin</div><div class=\"formfield\"><input type=\"button\" class=\"isubmit\" onClick=\"javascript: window.location.replace('admin.php?view=annonces');\" value=\"Revenir à l'administration\" /></div></div>");
	$frm->add_info("Les champs marqués d'une astérisque (*) doivent être remplis.");

	$cts->add($frm, true);
}
/*******************************************************************************************************
 * Formulaire inscription/connexion
 */
else if(!empty($_REQUEST['action']) && $_REQUEST['action']=="infos")
{
	$site->allow_only_logged_users("services");
	$site->user->load_all_extra();

	if(isset($_REQUEST) && $_REQUEST['magicform']['name'] == "user_info")
	{
		if( !$_REQUEST['accept_cgu'] )
			$error = "Vous devez accepter les conditions générales d'utilisation pour poursuivre";
		else
		{
			$site->user->addresse = $_REQUEST['adresse'];
			$site->user->id_ville = $_REQUEST['ville'];
			$site->user->id_pays = $_REQUEST['pays'];
			$site->user->tel_maison = telephone_userinput($_REQUEST['tel_fixe']);
			$site->user->tel_portable = telephone_userinput($_REQUEST['tel_portable']);

			if( $site->user->saveinfos() )
			{
				$site->user->add_to_group(GRP_JOBETU_CLIENT);
				header("Location: depot.php?action=annonce");
				exit;
			}


		}
	}

	$ville = new ville($site->db);
	$pays = new pays($site->db);

	if(!empty($site->user->id_pays))
		$pays->load_by_id($site->user->id_pays);
	else
		$pays->load_by_id(1); //France par défaut

	if(!empty($site->user->id_pays))
		$ville->load_by_id($site->user->id_ville);


	$cts->add_paragraph("Vous êtes à présent inscrit sur le site de l'AE, nous vous remerçions de votre confiance.");
	$cts->add_paragraph("Afin de compléter votre profil, nous vous remerçions de bien vouloir prendre le temps de remplir les champs ci-dessous.<br />
												Vous devrez également accepter les conditions générales d'utilisation du service AE Job Etu pour valider cette inscription. <br />
												Vous pourrez passer votre annonce à la prochaine étape.");

	$frm = new form("user_info", "depot.php?action=infos", true, "POST", "Informations complémentaires (".$site->user->prenom." ".$site->user->nom.")");
	if(!empty($error))
		$frm->error($error);
	$frm->add_text_area("adresse", "Adresse", $site->user->addresse, 40, 1, true);
	$frm->add_entity_smartselect ("ville","Ville", $ville, true, true);
	$frm->add_entity_smartselect ("pays","Pays", $pays, true, true);
	$frm->add_text_field("tel_fixe", "Téléphone (fixe)", $site->user->tel_maison, false);
	$frm->add_text_field("tel_portable", "Télephone (portable)", $site->user->tel_portable, false);
	$frm->puts("");
	$frm->add_checkbox("accept_cgu", "J'ai lu et j'accepte les <a href=\"http://ae.utbm.fr/article.php?name=docs:jobetu:cgu\">conditions générales d'utilisation</a>", false);
	$frm->add_submit("go", "Etape suivante");
	$frm->set_focus("adresse");

	$cts->add($frm, true);


}

/*******************************************************************************************************
 * Traitement de l'annonce à enregistrer
 */
else if(!empty($_REQUEST['action']) && $_REQUEST['action']=="add" && $_REQUEST['magicform']['name'] == "jobs")
{
	$jobuser = new jobuser_client($site->db);
	$annonce = new annonce($site->db, $site->dbrw);
	$jobuser->load_by_id($site->user->id);

	$result = $annonce->add($jobuser, $_REQUEST['titre_ann'], $_REQUEST['job_type'], $_REQUEST['desc_ann'], $_REQUEST['profil'], $_REQUEST['divers'], $_REQUEST['date_debut'], $_REQUEST['duree'], $_REQUEST['nb_postes'], $_REQUEST['remuneration'], $_REQUEST['lieu'], null, $_REQUEST['allow_diff']);

	if($result)
	{
		$cts->add_paragraph("Votre annonce a bien été enregistrée sous le numéro $result. Elle sera désormais soumise aux candidatures des étudiants.");
		$cts->add_paragraph("Vous pouvez désormais gérer l'avancée de votre offre dans votre tableau de bord, les différents candidats vous y seront proposés à mesure que leurs candidatures nous parviennent, vous pourrez alors en sélectionner une pour répondre à votre attente");

		$frm = new form("go", "board_client.php", false, "POST", false);
		$frm->add_submit("next", "Aller à mon tableau de bord");
		$cts->add($frm);
	}
}
/*******************************************************************************************************
 * Traitement de l'annonce à éditer
 */
else if(!empty($_REQUEST['action']) && $_REQUEST['action']=="save" && $_REQUEST['magicform']['name'] == "jobs")
{
	$jobuser = new jobuser_client($site->db);
	$jobuser->load_by_id($annonce->id_client);
	$annonce = new annonce($site->db, $site->dbrw);
	$annonce->load_by_id($_REQUEST['id']);

	$result = $annonce->save($jobuser, $_REQUEST['titre_ann'], $_REQUEST['job_type'], $_REQUEST['desc_ann'], $_REQUEST['profil'], $_REQUEST['divers'], $_REQUEST['date_debut'], $_REQUEST['duree'], $_REQUEST['nb_postes'], $_REQUEST['remuneration'], $_REQUEST['lieu'], null, $_REQUEST['allow_diff']);

	if($result)
	{
		$cts->add_paragraph("Votre annonce \"$annonce->titre\" (n°$result) à bien été éditée.");
		$frm = new form("go", "board_client.php", false, "POST", false);
		$frm->add_submit("next", "Aller à mon tableau de bord");
		$cts->add($frm);
	}
}
else
{
	/******************************************************************************************************
	 * Onglets informations générales et conditions
	 */

	$cts->add_title(2, "Quelques details concernant votre depot");
	$cts->add_paragraph("Vous vous appretez à déposer une annonce sur AE Job Etu et nous vous en remercions");
	$cts->add_paragraph("Vous devrez tout d'abord vous inscrire si c'est la premiere fois que vous venez et puis allez y.");
	$cts->add_paragraph("A lire : <a href=\"http://ae.utbm.fr/article.php?name=docs:jobetu:cgu\">C.G.U.</a>");

	$frm = new form("go", "depot.php?action=annonce", false, "POST", false);
	$frm->add_submit("next", "Etape suivante");

	$cts->add($frm);

}


$site->add_contents($cts);

$site->end_page();

?>
