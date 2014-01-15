<?php

/** @file
 *
 * @brief Classe d'accès à diverses ressources
 * en lecture seule pour les besoins des clubs
 *
 */

/* Copyright 2004
 * - Laurent COLNAT <laurent POINT colnat CHEZ utbm POINT fr>
 * - Simon Lopez <simon dot lopez at ayolo dot org>
 *
 * Auteur d'origine :
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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

 $topdir = "./../";

require_once("mysql.inc.php");
require_once("mysqlae.inc.php");
require_once("entities/std.inc.php");
require_once("entities/utilisateur.inc.php");
//require_once("watermark.inc.php");

class external_client
{

	var $db;

	var $img;

	/* Fonction check_if_connected
	 *
	 * Fonction permettant de s'assurer que l'utilisateur de la lib est bien connecté au site AE et surtout qu'il soit bien cotisant AE
	 *
	  * @Error Code :
	 * -2		Utilisateur non cotisant AE
	 * -1		Compte invalide ou désactivé
	 *  0		Utilisateur non connecté
	 */

	function check_if_connected()
	{
		if($_COOKIE['AE2_SESS_ID'])
		{
			$req = new requete($this->db, "SELECT `id_utilisateur`, `connecte_sess` FROM `site_sessions` WHERE `id_session` = '" .
			   mysql_escape_string($_COOKIE['AE2_SESS_ID']) . "'");
			list($uid,$connecte) = $req->get_row();

			if ( $connecte == 1 )
			{
				$lib_user = new utilisateur($this->db);
				$lib_user->load_by_id($uid);

				if( $lib_user->hash != "valid")
					$this->error ("Compte invalide ou désactivé sur le site AE");
				if ( !$lib_user->ae )
					$this->error ("Utilisateur de la librairie non cotisant AE");
			}
			else
				$this->error ("Utilisateur non connecté au site AE");
		}
		else
			$this->error ("Utilisateur non connecté au site AE");
	}

	/* Fonction error()
	 *
	 * Envoie une erreur sur la sortie standart au format text
	 *
	 * @params
	 *
	 *	$s	la chaine de caractères à afficher
	 *
	 */
	function error($s)
	{
		Header("Content-Type: text/plain");
		die($s);
	}

	/* Constructeur de la classe external_client */

	function external_client ($check=0)
	{
		$this->db = new mysqlae();
		if (!$this->db)
			$this->error("Error while connecting database !");

		if($check==1)
		  $valid_use=1;
		else
		  $valid_use = $this->check_if_connected();
		if ($valid_use < 0)
			$this->error("Don't use this API if you are'nt an AE customer !  Error Code ".$valid_use);

	}

	/* Fonction show_user_photo()
	 *
	 * Fonction permettant d'obtenir le nom et le chemin relatif
	 * de la photo d'identité, matmatronch ou matmatblouse de l'utilisateur
	 *
	 * @Params :
	 * $user	Un objet utilisateur
	 * $type	Type de photo (1 pour identité, 2 pour matmatronch, 3 pour matmablouse)
	 *
	 * @Error Code :
	 * -2		Type incompatible
	 * -1		Photo d'introuvable
	*/

	function show_user_photo ($user, $type = 1)
	{
		global $topdir;

		switch ($type)
		{
			case (1):
				$ext = ".identity";
			break;
			case (2):
				$ext = "";
			break;
			case (3):
				$ext = ".blouse";
			break;
			default:
				$this->error ("Type de photo demandé incompatible");
			break;
		}

		if ( !file_exists($topdir."data/matmatronch/".$user->id.$ext.".jpg") )
			$this->error("Photo inexsistante de type".$type);

		$this->img = imagecreatefromjpeg($topdir."data/matmatronch/".$user->id.$ext.".jpg");
		if (!$this->img)
			$this->error("Erreur dans la conversion de l'image en flux");

	    header ("Content-Type: image/jpg");
		imagejpeg ($this->img);
	}

	/* Fonction load_user_by_email()
	 *
	 * Fonction permettant de charger les infos classiques d'un utilisateur par son email
	 *
	 * @Params :
	 * $email				Adresse email de l'utilisateur
	 * $ae_user_required	Indique si l'utilisateur doit etre cotisant ae pour etre valide
	 *
	 * @Error Code :
	 * -3		Utilisateur NON UTBM
	 * -2		Utilisateur NON AE
	 * -1		Utilisateur INTROUVABLE
	*/
	function load_user_by_email ($email, $ae_user_required = 1)
	{

		$user = new utilisateur($this->db);

		if (!$user->load_by_email($email))
			$this->error ("Utilisateur INTROUVABLE");

		if ($ae_user_required && !$user->ae)
			$this->error ("Utilisateur NON cotistant AE");

		if (!$user->utbm)
			$this->error ("Utilisateur NON UTBM");

		return $user;
	}
}

?>
