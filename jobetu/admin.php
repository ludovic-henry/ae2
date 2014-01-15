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
require_once("include/cts/jobetu.inc.php");
require_once("include/annonce.inc.php");

define("GRP_JOBETU_CLIENT", 35);
define("GRP_JOBETU_ETU", 36);

$site = new site();
$site->start_page("services", "AE Job Etu");

if( !($site->user->is_in_group("jobetu_admin") || $site->user->is_in_group("gestion_ae") || $site->user->is_in_group("root") ) )
  header("Location: ../403.php");

$site->add_css("jobetu/jobetu.css");
$site->add_rss("Les dernières annonces de JobEtu","rss.php");

$cts = new contents("Administration AE Job Etu");

$jobetu = new jobetu($site->db, $site->dbrw);

$tabs = array(
		      array("", "jobetu/admin.php", "vue générale"),
		      array("annonces", "jobetu/admin.php?view=annonces", "annonces"),
		      array("clients", "jobetu/admin.php?view=clients", "clients"),
		      array("etudiants", "jobetu/admin.php?view=etudiants", "étudiants"),
		      array("categories", "jobetu/admin.php?view=categories", "catégories")
	      );
$cts->add(new tabshead($tabs, $_REQUEST['view']));


/**
 * Actions
 */
if(isset($_REQUEST['action']) && $_REQUEST['action'] == "edit")  // edition préférences/annonce => redirection
{
  if($_REQUEST['view'] == "clients")
    header("Location: board_client.php?view=preferences&id_utilisateur=".$_REQUEST['id_utilisateur']);
  else if($_REQUEST['view'] == "etudiants")
    header("Location: board_etu.php?view=preferences&id_utilisateur=".$_REQUEST['id_utilisateur']);
  else if($_REQUEST['view'] == "annonces")
    header("Location: depot.php?action=edit&id=".$_REQUEST['id_annonce']);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == "info")
{
  $header = new contents("Détails des annonces");

		if(isset($_REQUEST['id_annonce']))
			$ids[] = $_REQUEST['id_annonce'];
		if(isset($_REQUEST['id_annonces']))
			foreach ($_REQUEST['id_annonces'] as $id)
				$ids[] = $id;

		foreach ($ids as $id_annonce)
		{
			$annonce = new annonce($site->db);
			$annonce->load_by_id($id_annonce);
			$header->add( new apply_annonce_box($annonce) );

			$sql = new requete($site->db, "SELECT `job_annonces_etu`.*, `id_utilisateur`, CONCAT(prenom_utl,' ',nom_utl) as nom_utilisateur FROM `job_annonces_etu` LEFT JOIN `utilisateurs` ON `id_utilisateur`=`id_etu` WHERE id_annonce = $annonce->id");
			$table = new sqltable("annonce_etu", "Enregistrements", $sql, "admin.php?view=annonces", "id_relation", array("id_relation" => "Id", "nom_utilisateur" => "Utilisateur", "relation" => "Relation"), array("delete" => "Supprimer"), array(), array("relation" => array("apply" => "Candidat", "reject" => "Rejet", "selected" => "Sélectionné")));
			$header->add($table, true);
		}
  $site->add_contents($header);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == "convention") // vieux truandage => convention = profil
{
  header("Location: board_etu.php?view=profil&id_utilisateur=".$_REQUEST['id_utilisateur']);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == "set_ozone_hole")
{
	if($_REQUEST['set'])
	{
		$lst = new itemlist(false);
		$lst->add("Le trou de la couche d'ozone a été correctement réglé", "ok");
		$cts->add($lst);
	}

	$frm = new form("ozone", "admin.php?action=set_ozone_hole", false, "post", "Régler le trou de la couche d'ozone");
	$val = array("the" => "Piti", "big" => "Moyen", "leb" => "Normal", "ow" => "Moult", "ski" => "Gargantuesque");
	$frm->add_radiobox_field("hole", "Taille", $val, ($_REQUEST['hole'])?$_REQUEST['hole']:"ow", false, false, null, false);
	$frm->add_submit("set", "Régler");
	$cts->add($frm, true);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == "mail")
{
	if($_REQUEST['send'])
	{
		$ret = mail( utf8_decode($_REQUEST['mailto']), utf8_decode($_REQUEST['subject']), utf8_decode($_REQUEST['content']), "From: \"AE JobEtu\" <ae.jobetu@utbm.fr>" );
		$lst = new itemlist(false);
		if($ret)
			$lst->add("Le mail à été correctement envoyé", "ok");
		else
			$lst->add("Erreur lors de l'envoi du mail", "ko");
		$cts->add($lst);
	}
	else
	{
	if($_REQUEST['id_utilisateur'])
	{
		$sql = new requete($site->db, "SELECT email_utl FROM utilisateurs WHERE id_utilisateur = '".$_REQUEST['id_utilisateur']."'");
		list($mailto) = $sql->get_row();
	}
	else if($_REQUEST['id_utilisateurs'])
	{
		$sql = new requete($site->db, "SELECT email_utl FROM utilisateurs WHERE id_utilisateur IN('".implode('\', \'', $_REQUEST['id_utilisateurs'])."')");
		while( list($tmpto) = $sql->get_row() )
			$mailto .= $tmpto."; ";
	}

	$frm = new form("job_mail", "admin.php?view=".$_REQUEST['view']."&action=mail", false, "post", "Envoi de mail");
	$frm->add_text_field("subject", "Sujet", "[AE JobEtu] ...", true, 80);
	$frm->add_text_field("__mailto", "Destinataire(s)", $mailto, true, 80, false, false);
	$frm->add_hidden("mailto", $mailto);
	$frm->add_text_area("content", "Contenu", "", 80, 20, true);
	$frm->add_submit("send", "Envoyer");

	$cts->add($frm, true);
	}

}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete")
{
  /**
   * Suppression annonces
   */
  if( isset($_REQUEST['id_annonce']) || isset($_REQUEST['id_annonces']) )
  {
    $header = new contents("Suppression d'annonces");

    if( isset($_REQUEST['confirm']) ) //on passe a l'attaque
    {
      $id_annonces = explode("|", $_REQUEST['id_annonces']);
      if(!is_array($id_annonces)) exit("Fatal error (comme dirait l'autre) : ".__FILE__." \t ".__LINE__);

      foreach($id_annonces as $tmp)
      {
        $annonce = new annonce($site->db, $site->dbrw);
        $annonce->load_by_id($tmp);
       if( $annonce->destroy() )
          $msg = "Opération effectuée";
       else
          $msg = "La suppression n'a pu être réalisée. Peut-être un étudiant a-t-il déjà été sélectionnée. Dans ce cas veuillez prendre contact avec les différentes personnes pour clôre l'annonce";
      }
      $header->add(new itemlist(false, false, array( $msg )));
    }
    else //on demande confirmation (boolay proofing)
    {

      $header->add_paragraph("Merci de ne supprimer d'annonce que vous si vous êtes sûr de ce que vous faites. Seules les annonces pour lesquelles aucune sélection de candidat n'aura été faite pourront être supprimées, s'il y a des candidats, ceux-ci seront avertis de la suppression de l'offre (à condition qu'ils aient réglé l'envoi des mails sur `full`)");

      if( isset($_REQUEST['id_annonce']) )
    	  $sql = new requete($site->db, "SELECT id_annonce, titre FROM job_annonces WHERE id_annonce = '".$_REQUEST['id_annonce']."'");
	    else if( isset($_REQUEST['id_annonces']) )
	    	$sql = new requete($site->db, "SELECT id_annonce, titre FROM job_annonces WHERE id_annonce IN('".implode('\', \'', $_REQUEST['id_annonces'])."')");

      $lst = new itemlist("Vous vous appretez à supprimer les annonces :");
      while($row = $sql->get_row())
      {
        $lst->add("N°".$row['id_annonce']." : \"".$row['titre']."\"", "ko");
        $ids[] = $row['id_annonce'];
      }
      $header->add($lst, true);

      $frm = new form(false, "?action=".$_REQUEST['action']."&view=".$_REQUEST['view']."&confirm");
    	$frm->add_hidden("id_annonces", implode("|", $ids) );
    	$frm->add_submit(false, "Confirmer");

    	$header->add($frm);
  	}
  }

  /**
   * Désactivation de compte JobEtu
   */
  else if( isset($_REQUEST['id_utilisateur']) || isset($_REQUEST['id_utilisateurs']) )
  {
    $header = new contents("Désactivation comptes AE JobEtu");

    if( isset($_REQUEST['confirm']) ) //on passe a l'attaque
    {
      $id_utilisateurs = explode("|", $_REQUEST['id_utilisateurs']);
      if(!is_array($id_utilisateurs)) exit("Fatal error (comme dirait l'autre) : ".__FILE__." \t ".__LINE__);

      foreach($id_utilisateurs as $tmp)
      {
        $usr = new utilisateur($site->db, $site->dbrw);
        $usr->load_by_id($tmp);
        $usr->remove_from_group( ($_REQUEST['view'] == "clients") ? GRP_JOBETU_CLIENT : GRP_JOBETU_ETU );
      }

      $header->add(new itemlist(false, false, array("Opération effectuée")));

    }
    else //on demande confirmation (boolay proofing)
    {

    	if($_REQUEST['id_utilisateur'])
    	  $sql = new requete($site->db, "SELECT id_utilisateur, CONCAT(prenom_utl,' ',nom_utl) as nom_utilisateur FROM utilisateurs WHERE id_utilisateur = '".$_REQUEST['id_utilisateur']."'");
	    else if($_REQUEST['id_utilisateurs'])
	    	$sql = new requete($site->db, "SELECT id_utilisateur, CONCAT(prenom_utl,' ',nom_utl) as nom_utilisateur FROM utilisateurs WHERE id_utilisateur IN('".implode('\', \'', $_REQUEST['id_utilisateurs'])."')");
	    else
	      exit("Erreur arguments");

	    $lst = new itemlist("Vous vous appretez à désactiver le compte JobEtu de :");
	    while( $row = $sql->get_row() )
	    {
	      $lst->add($row['nom_utilisateur'], "ko");
	      $ids[] = $row['id_utilisateur'];
	    }

    	$header->add($lst, true);

    	$frm = new form(false, "?action=".$_REQUEST['action']."&view=".$_REQUEST['view']."&confirm");
    	$frm->add_hidden("id_utilisateurs", implode("|", $ids) );
    	$frm->add_submit(false, "Confirmer");

    	$header->add($frm);
  	}
  }

  /**
   * Suppression de relations annonce-etudiant (candidature, rejet...)
   */
  else if( isset($_REQUEST['id_relation']) ) //Désactivation de comptes
  {
    $header = new contents("Suppression de relation");

    $annonce = new annonce($site->db, $site->dbrw);
    $res = $annonce->delete_relation($_REQUEST['id_relation']);

    if( !$res ) $header->add( new itemlist(false, false, array("L'enregistrement (n°".$_REQUEST['id_relation'].") à bien été supprimé.")) );
    else $header->add( new itemlist(false, false, array("Erreur lors de la suppression de l'enregistrement n°".$_REQUEST['id_relation'].".")) );
  }

	$site->add_contents($header, true); //$header section 'delete'
}

/***************************************************************
 * Onglet de gestion des catégories et sous catégories
 */
if(isset($_REQUEST['view']) && $_REQUEST['view'] == "categories")
{
	/*
	 * Traitement des réponses
	 */
		if(isset($_REQUEST['magicform']) && $_REQUEST['magicform']['name'] == "jobtypes")
		{
			if($_REQUEST['type_cat'] == "main")
				$jobetu->add_cat_type($_REQUEST['name_main']);
			else if($_REQUEST['type_cat'] == "sub")
				$jobetu->add_subtype($_REQUEST['name_sub'], $_REQUEST['mama_cat']);
		}
		if(isset($_REQUEST['action']) && $_REQUEST['action'] == "delete")
		{
			$jobetu->del_subtype($_REQUEST['id_types']);
		}

	$cts->add_title(2, "Gestion de la liste des catégories");
	$jobetu->get_job_types();
//	$cts->add($jobetu->job_types);

	$sql = new requete($site->db, "SELECT id_type, nom, COUNT(id_type) AS nb_etu
																FROM `job_types_etu`
																NATURAL JOIN `job_types`
																GROUP BY id_type
																ORDER BY id_type ASC");
	$table = new sqltable("typetable", "Catégorie des jobs", $sql, null, "id_types", array("id_type" => "Num", "nom" => "Nom de la catégorie", "nb_etu" => "Nb d'étudiant"), array("delete" => "Supprimer"), array(), array());
	$cts->add($table);


	$frm = new form("jobtypes", "admin.php?view=categories", false, "post", "Ajouter une catégorie/sous-catégorie");
		$sfrm = new form("type_cat",null,null,null,"Catégorie principale");
		$sfrm->add_text_field("name_main", "Intitulé");
		$sfrm->add_submit("go", "Envoyer");
	$frm->add($sfrm,false,true,1,"main",false,true,true);

		$sfrm = new form("type_cat",null,null,null,"Sous catégorie");
		$sfrm->add_text_field("name_sub", "Intitulé");
		$sfrm->add_select_field("mama_cat","Catégorie mère",$jobetu->job_main_cat);
		$sfrm->add_submit("go", "Envoyer");
	$frm->add($sfrm,false,true,0,"sub",false,true);


	$cts->add($frm, true);

}

/***************************************************************
 * Onglet de gestion des annonces
 */
else if(isset($_REQUEST['get_ann_table']))
{
  if( isset($_REQUEST['hide_closed']) && $_REQUEST['hide_closed'] == 'true')
    $append_sql = "WHERE closed = '0'";
  else $append_sql = "";

  $sql = new requete($site->db, "SELECT utilisateurs.id_utilisateur,
																	CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl) AS `nom_utilisateur`,
																	id_annonce, titre, provided, closed, nb_postes,
																	`job_types`.`nom` as `nom_type`
																	FROM `job_annonces`
																	LEFT JOIN `utilisateurs`
																	ON `job_annonces`.`id_client` = `utilisateurs`.`id_utilisateur`
																	LEFT JOIN `job_types`
																	ON `job_types`.`id_type` = `job_annonces`.`job_type`
																	$append_sql ", false);


  $table = new sqltable("list_annonces", "Annonces présentes sur AE JobEtu", $sql, "admin.php?view=annonces", "id_annonce",
	                      array("id_annonce" => "ID", "titre" => "Titre", "nom_utilisateur" => "Client", "nom_type" => "Catégorie", "nb_postes" => "Nb postes", "provided" => "Pourvue", "closed" => "Etat"),
	                      array("info" => "Détails", "edit" => "Editer", "delete" => "Supprimer"),
	                      array("info" => "Détails", "delete" => "Supprimer"),
	                      array("provided" => array("true" => "Oui", "false" => ""), "closed" => array('0' => "", '1' => "Fermée"))
	                      );

  echo "<h2>Liste des annonces</h2>";
  echo $table->html_render();
  exit;
}
else if(isset($_REQUEST['view']) && $_REQUEST['view'] == "annonces")
{
  $cts->puts("<div id=\"ann_table\"></div>".
	     "<script language=\"javascript\">openInContents('ann_table', './admin.php', 'get_ann_table&hide_closed=true');</script>".
	     "\n");

  $frm = new form("hide_closed", null, false, null);
  $frm->puts("<input type=\"checkbox\" name=\"hide_box\" value=\"true\" checked=\"checked\" onClick=\"openInContents('ann_table', './admin.php', 'get_ann_table&hide_closed='+this.checked);\"/><label for=\"hide_box\">Cacher les annonces fermées");
  $cts->add($frm);

  /** Listing vieilles annonces */
  $sql = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`,
                                   CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl) AS `nom_utilisateur`,
                                   id_annonce, titre, nb_postes,
                                   `job_types`.`nom` as `nom_type`,
                                   DATEDIFF(NOW(), `date`) AS `nb_jours`
                                   FROM `job_annonces`
                                   LEFT JOIN `utilisateurs`
                                   ON `job_annonces`.`id_client` = `utilisateurs`.`id_utilisateur`
                                   LEFT JOIN `job_types`
                                   ON `job_types`.`id_type` = `job_annonces`.`job_type`
                                   WHERE closed = '0' AND provided = 'false'  AND DATEDIFF(NOW(), `date`) >= 30"); // c'est quand meme pas normal qu il veuille pas avec nb_jours
  $cts->add_title(2, "Le coin M. Propre");
  $cts->add_paragraph("<b>Lorsqu'une annonce se fait vieille, prévenez par mail le dépositaire en cliquant sur \"Envoyer un mail\" pour qu'il fasse le nécessaire (clore l'annonce, la laisser encore). Si nécessaire vous pourrez par la suite supprimer l'annonce via la table ci dessus.</b>");
  $table = new sqltable("list_ann_nclose_nprovided", "Annonces **non pourvues** et non closes de plus de 30 jours", $sql, "admin.php?view=clients", "id_utilisateur",
                  array("id_annonce" => "ID", "titre" => "Titre", "nom_utilisateur" => "Client", "nom_type" => "Catégorie", "nb_jours" => "Jours"),
                  array("mail" => "Envoyer un mail"),
                  array("mail" => "Envoyer un mail"),
                  array()
                  );
  $cts->add($table, true);
  /************************/
  $sql = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`,
                                   CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl) AS `nom_utilisateur`,
                                   id_annonce, titre, nb_postes,
                                   `job_types`.`nom` as `nom_type`,
                                   DATEDIFF(NOW(), `date`) AS `nb_jours`
                                   FROM `job_annonces`
                                   LEFT JOIN `utilisateurs`
                                   ON `job_annonces`.`id_client` = `utilisateurs`.`id_utilisateur`
                                   LEFT JOIN `job_types`
                                   ON `job_types`.`id_type` = `job_annonces`.`job_type`
                                   WHERE closed = '0' AND provided = 'true'  AND DATEDIFF(NOW(), `date`) >= 90"); // c'est quand meme pas normal qu il veuille pas avec nb_jours

  $table = new sqltable("list_ann_nclose_nprovided", "Annonces **pourvues** et non closes de plus de 90 jours", $sql, "admin.php?view=clients", "id_utilisateur",
                  array("id_annonce" => "ID", "titre" => "Titre", "nom_utilisateur" => "Client", "nom_type" => "Catégorie", "nb_jours" => "Jours"),
                  array("mail" => "Envoyer un mail"),
                  array("mail" => "Envoyer un mail"),
                  array()
                  );
  $cts->add($table, true);
}

/***************************************************************
 * Onglet des recruteurs
 */
else if(isset($_REQUEST['view']) && $_REQUEST['view'] == "clients")
{
	$sql = new requete($site->db, "SELECT utilisateurs.id_utilisateur,
																	CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl) AS `nom_utilisateur`
																	FROM `utilisateurs`
																	NATURAL JOIN `utl_groupe`
																	WHERE id_groupe = ".GRP_JOBETU_CLIENT."
																	GROUP BY utilisateurs.id_utilisateur", false);

	$cts->add( new sqltable("list_clients", "Clients de AE JobEtu", $sql, "admin.php?view=clients", "id_utilisateur", array("id_utilisateur" => "ID", "nom_utilisateur" => "Nom"), array("mail" => "Envoyer un mail", "delete" => "Désactiver compte", "edit" => "Editer préférences"), array("mail" => "Envoyer un mail", "delete" => "Désactiver compte")), true );
}

/***************************************************************
 * Onglet de gestion des étudiants
 */
else if(isset($_REQUEST['view']) && $_REQUEST['view'] == "etudiants")
{
	$sql = new requete($site->db, "SELECT utilisateurs.id_utilisateur,
																	CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl) AS `nom_utilisateur`
																	FROM `utilisateurs`
																	NATURAL JOIN `utl_groupe`
																	WHERE id_groupe = ".GRP_JOBETU_ETU."
																	GROUP BY utilisateurs.id_utilisateur", false);

	$cts->add( new sqltable("list_clients", "Etudiants inscrits à AE JobEtu", $sql, "admin.php?view=etudiants", "id_utilisateur", array("id_utilisateur" => "ID", "nom_utilisateur" => "Nom"), array("mail" => "Envoyer un mail", "convention" => "Editer profil", "edit" => "Editer préférences", "delete" => "Désactiver le compte"), array("mail" => "Envoyer un mail", "delete" => "Désactiver compte")), true );
}

/***************************************************************
 * Onglet d'accueil
 */
else
{
	$cts->add_paragraph("Imagine ici le contenu que tu souhaites voir apparaitre.");
}



$site->add_contents($cts);
$site->end_page();



?>
