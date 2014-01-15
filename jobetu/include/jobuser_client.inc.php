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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

require_once($topdir. "include/cts/user.inc.php");

class jobuser_client extends utilisateur
{
  var $annonces = array();
  var $prefs = array();

  function new_particulier( $nom, $prenom, $email, $password, $droit_image, $date_naissance, $sexe)
  {
    $this->create_user( $nom, $prenom, $email, $password, $droit_image, $date_naissance, $sexe);

  }

  function new_societe( $nom, $prenom, $email, $password, $droit_image, $date_naissance, $sexe) //Ne prévoit pas la France de demain :(
  {
    //En attente du flag sur la table utilisateur
    $this->create_user( $nom, $prenom, $email, $password, $droit_image, $date_naissance, $sexe);
  }


  function is_jobetu_client()
  {
    return $this->is_in_group("jobetu_client");
  }


  function load_annonces()
  {
   // if( is_jobetu_client() )
      {
        $sql = new requete($this->db, "SELECT * FROM job_annonces WHERE id_client = $this->id AND closed <> '1' ORDER BY date DESC");

        while($line = $sql->get_row())
          $this->annonces[] = $line;
      }
  }

  function load_prefs()
  {
    $sql = new requete($this->db, "SELECT mail_prefs, pub_num FROM `job_prefs` WHERE `id_utilisateur` = $this->id LIMIT 1");
    $row = $sql->get_row();

    if($sql->lines == 0)
      $this->prefs = null;
    else
    {
      $this->prefs['mail_prefs'] = $row['mail_prefs'];
      $this->prefs['pub_num'] = $row['pub_num'];
    }
  }

  function update_prefs($new_pub_profil, $new_mail_prefs, $new_pub_num)
  {
    $this->publique = $new_pub_profil;
    $this->saveinfos(); //fonction matmatronch

    if(empty($this->prefs))
      $sql = new insert($this->dbrw, "job_prefs", array("id_utilisateur" => $this->id, "mail_prefs" => $new_mail_prefs, "pub_num" => $new_pub_num));
    else
      $sql = new update($this->dbrw, "job_prefs", array("mail_prefs" => $new_mail_prefs, "pub_num" => $new_pub_num), array("id_utilisateur" => $this->id));

    $this->load_prefs();

    if($sql)
      return true;
    else
      return false;
  }
}
?>
