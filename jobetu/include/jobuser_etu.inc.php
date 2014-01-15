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


class jobuser_etu extends utilisateur
{
	var $competences =  array();
	var $annonces = array();
	var $pdf_cvs = array();
	var $public_cv;
	var $prefs = array();

	function is_jobetu_user()
	{
		return $this->is_in_group('jobetu_etu');
	}

	function load_competences()
	{
		$sql = new requete($this->db, "SELECT id_type FROM job_types_etu WHERE id_utilisateur = $this->id");

		while($line = $sql->get_row())
			    $this->competences[] = $line[0];
	}

	function update_competences($new_values)
	{
		$add = array_diff($new_values, $this->competences);
		$del = array_diff($this->competences, $new_values);

		foreach ($del as $value)
			$sql = new delete($this->dbrw, "job_types_etu", array("id_type" => $value, "id_utilisateur" => $this->id));

		foreach ($add as $value)
			$sql = new insert($this->dbrw, "job_types_etu", array("id_type" => $value, "id_utilisateur" => $this->id));

		$this->competences = array();
		$this->load_competences();
	}

	function load_annonces()
	{
		if(empty($this->competences)) $this->load_competences();
		if(!empty($this->annonces)) $this->annonces = null;
	   // if( is_jobetu_etu() )
      {
      	$sql = new requete($this->db, "SELECT id_annonce FROM job_annonces
																				WHERE job_type IN ('".implode('\', \'', $this->competences)."')
																				AND `job_annonces`.`provided` = 'false'
																				AND `job_annonces`.`closed` = 'false'
																				AND `job_annonces`.`id_annonce` NOT IN (SELECT id_annonce FROM job_annonces_etu WHERE id_etu = $this->id)
																				", false);

        while($line = $sql->get_row())
			    $this->annonces[] = $line[0];
	    }
	}

	function load_pdf_cv()
	{
		$sql = new requete($this->db, "SELECT `lang` FROM `job_pdf_cv` WHERE id_utl = $this->id");
		$this->pdf_cvs = array(); /* remise a 0 */

		while($line = $sql->get_row())
			    $this->pdf_cvs[] = $line[0];


		$sql = new requete($this->db, "SELECT '1' FROM `job_prefs` WHERE id_utilisateur = $this->id AND `pub_cv`='true' LIMIT 1", false);
		if( $sql->lines == 1)
			$this->public_cv = true;
		else
			$this->public_cv = false;

		return sizeof($this->pdf_cvs);
	}

	function add_pdf_cv($file, $lang = 'fr')
	{
		global $topdir;

	  if ( !is_uploaded_file($file['tmp_name']) )
    {
      return false;
    }
    if( $file['type'] != "application/pdf" )
    {
    	return false;
    }

    if( move_uploaded_file($file['tmp_name'], $topdir ."var/cv/". $this->id . "." . $lang . ".pdf") )
    {
    	$sql = new insert($this->dbrw, "job_pdf_cv", array("id_utl" => $this->id, "date" => date("Y-m-d"), "lang" => $lang) );

    	return true;
    }

    return false;
	}

	function del_pdf_cv($lang)
	{
		global $topdir;
		$lang = mysql_real_escape_string($lang);

		$sql = new requete($this->db, "SELECT `lang` FROM `job_pdf_cv` WHERE `id_utl` = $this->id AND `lang` = '".$lang."'");
		if($sql->lines == 1)
		{
			if( unlink($topdir ."var/cv/". $this->id . "." . $lang . ".pdf") )
			{
				$sql = new delete($this->dbrw, "job_pdf_cv", array("id_utl" => $this->id, "lang" => $lang));
				return true;
			}
		}
		return false;
	}

	function load_prefs()
	{
		$sql = new requete($this->db, "SELECT pub_cv, mail_prefs FROM `job_prefs` WHERE `id_utilisateur` = $this->id LIMIT 1");
		$row = $sql->get_row();

		if($sql->lines == 0)
			$this->prefs = null;
		else
		{
		  if($row['pub_cv'] == "true")
		    $pub_cv = true;
		  else
		    $pub_cv = false;
			$this->prefs['pub_cv'] = $pub_cv;
			$this->prefs['mail_prefs'] = $row['mail_prefs'];
		}
	}

	function update_prefs($new_pub_cv, $new_mail_prefs)
	{
		if(empty($this->prefs))
			$sql = new insert($this->dbrw, "job_prefs", array("id_utilisateur" => $this->id, "pub_cv" => $new_pub_cv, "mail_prefs" => $new_mail_prefs));
		else
			$sql = new update($this->dbrw, "job_prefs", array("pub_cv" => $new_pub_cv, "mail_prefs" => $new_mail_prefs), array("id_utilisateur" => $this->id));

		$this->load_prefs();

		if($sql)
			return true;
		else
			return false;
	}

}

?>
