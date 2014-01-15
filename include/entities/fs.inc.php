<?
/* Copyright 2007
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

require_once($topdir."include/entities/basedb.inc.php");

/**
 * Classe utilitaire pour les fichiers et dossier du système "drive"
 * @ingroup aedrive
 * @author Julien Etelain
 */
abstract class fs extends basedb
{

  function get_free_filename ( $id_folder, $filename, $except_id_file=null,$except_id_folder=null )
  {
    if ( is_null($id_folder) )
      $is_parent = " IS NULL ";
    else
      $is_parent = " = '" . mysql_real_escape_string($id_folder) . "' ";

    $expt_file ="";
    $expt_folder ="";

    if ( !is_null($except_id_file) )
      $expt_file = " AND id_file != '" . mysql_real_escape_string($except_id_file) . "' ";

    if ( !is_null($except_id_folder) )
      $expt_folder = " AND id_folder != '" . mysql_real_escape_string($except_id_folder) . "' ";

		$req = new requete($this->db, "SELECT id_file FROM `d_file`
				WHERE `nom_fichier_file` = '" . mysql_real_escape_string($filename) . "'
				AND `id_folder` $is_parent $expt_file
				LIMIT 1");

		if ( $req->lines == 0 )
		{
  		$req = new requete($this->db, "SELECT id_folder FROM `d_folder`
  				WHERE `nom_fichier_folder` = '" . mysql_real_escape_string($filename) . "'
  				AND `id_folder_parent` $is_parent $expt_folder
  				LIMIT 1");

  		if ( $req->lines == 0 )
        return $filename;
		}

		$p=strpos($filename, '.');
    $n=1;

    if ($pos === false)
    {
      $base=$filename;
      $ext="";
    }
    else
    {
      $base = substr($filename,0,$p);
      $ext = substr($filename,$p);
    }

		while ( $req->lines == 1 )
		{
      $filename = $base.$n.$ext;

  		$req = new requete($this->db, "SELECT id_file FROM `d_file`
  				WHERE `nom_fichier_file` = '" . mysql_real_escape_string($filename) . "'
  				AND `id_folder` $is_parent $expt_file
  				LIMIT 1");

  		if ( $req->lines == 0 )
  		  $req = new requete($this->db, "SELECT id_folder FROM `d_folder`
  				WHERE `nom_fichier_folder` = '" . mysql_real_escape_string($filename) . "'
  				AND `id_folder_parent` $is_parent $expt_folder
  				LIMIT 1");

  		$n++;
		}

    return $filename;
  }

}

?>
