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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */
global $topdir;
require_once($topdir . "include/cts/special.inc.php");

class apply_annonce_box extends stdcontents
{
  function apply_annonce_box($annonce)
  {
    if( !($annonce instanceof annonce) ) exit("Namého ! mauvaise argumentation mon bonhomme ! :)");

    global $topdir;

    $this->buffer .= "<div class=\"annonce_table\">";

    $this->buffer .= "<div class=\"header\" onClick=\"javascript:on_off('annonce_".$annonce->id."');\">\n";
    $this->buffer .= "<div class=\"num\">";
    $this->buffer .= "n°".$annonce->id;
    $this->buffer .= "</div>\n";

    $this->buffer .= "<div class=\"title\">";
    $this->buffer .= $annonce->titre;
    $this->buffer .= ' ('.date("d/m/Y",strtotime($annonce->date_depot)).')';
    $this->buffer .= "</div>\n";

    $this->buffer .= "<div class=\"icons\">\n";
    $this->buffer .= "<a href=\"$topdir"."article.php?page=docs:jobetu:faq-candidats\" title=\"Aide\"><img src=\"../images/actions/info.png\" /></a> &nbsp;";
    $this->buffer .= "<a href=\"board_etu.php?action=reject&id=".$annonce->id."\" title=\"Ne plus me proposer\"><img src=\"../images/actions/delete.png\" /></a>";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>";

    /** Contenu  ************************************************************/
    $this->buffer .= "<div id=\"annonce_".$annonce->id."\" class=\"content\"> \n";
    $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Demandeur </div> \n <div class=\"desc_content\"> <a href=\"$topdir/user.php?id_utilisateur=$annonce->id_client\"><img src=\"http://ae.utbm.fr/images/icons/16/user.png\" /> ".$annonce->nom_client."</a></div> \n</div>";
    if( $annonce->allow_diff )
    {
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> </div> \n <div class=\"desc_content\"><i>La diffusion du numéro de téléphone du demandeur à été autorisée, pensez prendre contact afin d'augmenter vos chances</i></div> \n</div>";
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Téléphone </div> \n <div class=\"desc_content\">".telephone_display($annonce->tel_client)."</div> \n</div>";
    }
    $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Type </div> \n <div class=\"desc_content\">".$annonce->nom_type." (". $annonce->nom_main_cat .") </div> \n</div>";
    if( $annonce->start_date )
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Date de début </div> \n <div class=\"desc_content\">".$annonce->start_date."</div> \n</div>";
    if( !empty($annonce->indemnite) )
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Rémunération (€)</div> \n <div class=\"desc_content\">". $annonce->indemnite ."</div> \n</div>";
    if( !empty($annonce->lieu) )
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Lieu </div> \n <div class=\"desc_content\">". $annonce->lieu ."</div> \n</div>";
    if( $annonce->nb_postes != 1 )
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Nombre de postes </div> \n <div class=\"desc_content\">".$annonce->nb_postes." (".$annonce->remaining_positions()." places restantes)</div> \n</div>";
    if( !empty($annonce->duree) )
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Durée </div> \n <div class=\"desc_content\">".$annonce->duree."</div> \n</div>";
    if( !empty($annonce->desc) ) //enfin en théorie ça peut pas l'être
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Description </div> \n <div class=\"desc_content\">".nl2br(htmlentities($annonce->desc,ENT_NOQUOTES,"UTF-8"))."</div> \n</div>";
    if( !empty($annonce->profil) )
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Profil recherché </div> \n <div class=\"desc_content\">".nl2br(htmlentities($annonce->profil,ENT_NOQUOTES,"UTF-8"))."</div> \n</div>";
    if( !empty($annonce->divers) )
      $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Autres renseignements </div> \n <div class=\"desc_content\">".nl2br(htmlentities($annonce->divers,ENT_NOQUOTES,"UTF-8"))."</div> \n</div>";

    $this->buffer .= "<br />";

    global $usr;
    $lst = new itemlist(false);
    if( $annonce->is_closed() )
      $lst->add("Cette annonce est cloturée.", "ko");

    if( $annonce->is_provided() )
      $lst->add("Tous les postes pour cette annonce sont pourvus.", "ko");

    if( $annonce->is_applicant($usr->id) )
      $lst->add("Vous êtes déjà candidat à cette offre.", "ok");

    $this->buffer .= $lst->html_render();

    if( !$annonce->is_closed() && !$annonce->is_provided() && !$annonce->is_applicant($usr->id) && !(basename($_SERVER['PHP_SELF']) == "admin.php") )
    {
      $frm = new form("apply_".$annonce->id."", false, true, "POST");
      $frm->add_submit("clic", "Se porter candidat");
      $this->buffer .= "<div onClick=\"javascript:on_off('apply_".$annonce->id."');\">" . $frm->buffer . "</div>";

      $this->buffer .= "<div id=\"apply_".$annonce->id."\" style=\"display: none;\" class=\"apply_form\">";
      $frm = new form("application_".$annonce->id."", "board_etu.php?action=apply", true, "POST");
      $frm->puts("<p>Veuillez noter qu'en soumettant votre candidature, vous vous engagez et qu'il ne vous sera pas possible d'annuler cette candidature (sauf raison particulière ou l'acceptation d'une autre offre). Merci donc de ne pas vous porter candidat \"à la légère\".<p> ");
      $frm->puts("Ajouter un message à votre candidature <i>(facultatif)</i> :<br />");
      $frm->add_hidden("id", $annonce->id);
      $frm->add_text_area("comment", false, false, 80, 10);
      $frm->add_submit("send", "Envoyer la candidature");
      $this->buffer .= $frm->html_render();

      $this->buffer .= "</div>";
    }

    $this->buffer .= "</div>";

   /************************************************************************/
    $this->buffer .= "</div>";

  }

}

class annonce_box extends stdcontents
{

  function annonce_box($annonce)
  {
    global $topdir;
    global $i18n;

    if( !($annonce instanceof annonce) ) exit("Namého ! mauvaise argumentation mon bonhomme ! :)");

    $this->buffer .= "<div class=\"annonce_table\">\n";

    $this->buffer .= "<div class=\"header\">\n";
    $this->buffer .= "<div class=\"num\">";
    $this->buffer .= "n°".$annonce->id;
    $this->buffer .= "</div>\n";

    $this->buffer .= "<div class=\"title\">";
    $this->buffer .= $annonce->titre;
    $this->buffer .= ' ('.date("d/m/Y",strtotime($annonce->date_depot)).')';
    $this->buffer .= "</div>\n";

    $this->buffer .= "<div class=\"icons\">";
    $this->buffer .= "<a href=\"../article.php?name=docs:jobetu:faq-recruteurs\" title=\"Aide\"><img src=\"../images/actions/info.png\" /></a> &nbsp;";
    $this->buffer .= "<a href=\"depot.php?action=edit&id=".$annonce->id."\" title=\"Editer l'annonce\"><img src=\"../images/actions/edit.png\" /></a> &nbsp;";
    $this->buffer .= "<a href=\"#\" title=\"Clore cette annonce\" onClick=\"location.href = confirm('Etes vous sûr de vouloir clore cette annonce ?') ? 'board_client.php?action=close&id=".$annonce->id."' : '#' \"><img src=\"../images/actions/lock.png\" /></a>";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";

    $this->buffer .= "<div class=\"content\">\n";

    /** Candidatures ******************************************************/
    $n = 1; // Compteuràlacon


    if( $annonce->is_closed() ) /* Ca c'est fait */
    {
      $this->buffer .= "<p class=\"error\">Annonce rangée :)</p>";
    }
    else if( $annonce->is_provided() ) /* on attend que le contrat se fasse maintenant */
    {
      $this->buffer .= "<p>Votre annonce est fermée.</p>";
      $this->buffer .= "<p>Candidat(s) sélectionnées : </p>"; // $usr->prenom $usr->nom</p>";

      $list = new itemlist(false);  /* liste des personnes sélectionnées */
      foreach($annonce->winner as $id_winner)
      {
        $winner = new utilisateur($annonce->db);
        $winner->load_by_id($id_winner);
        $list->add("$winner->prenom $winner->nom", "ok");
      }
      $this->buffer .= $list->html_render();

      $this->buffer .= "<p> Vous devez avoir reçu un email vous confirmant votre choix ainsi que les informations vous permettant de contacter l'étudiant choisi.<br >\n Si ce n'est pas le cas, n'hésitez pas à <a href=\"\">nous le signaler</a>";
      $this->buffer .= "<p></p>";
      $this->buffer .= "<p> Votre annonce est actuellement considérée comme étant en cours d'éxécution, si le contrat est terminé, merci de bien vouloir penser à <a onClick=\"javascript:on_off('close_form_".$annonce->id."');\"  style=\"cursor: pointer\" >clore l'annonce</a>";

      $this->buffer .= "<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" id=\"clic\" name=\"clic\" value=\"Evaluer & clore\" class=\"isubmit\" onClick=\"javascript:on_off('close_form_".$annonce->id."');\" /></div></div>\n";

      $this->buffer .= "<div id=\"close_form_".$annonce->id."\" style=\"display: none;\">\n";

      $this->buffer .= "<h3>Evaluer et clore cette annonce</h3>";
      $frm = new form("close_$annonce->id", "board_client.php?action=close&id=".$annonce->id, false, "POST");
      $frm->add_radiobox_field("close_eval", "Evaluation de la prestation", array( "bof" => "Négative", "bleh" => "Neutre", "yeah" => "Positive") );
      $frm->add_text_area("close_comment", "Commentaire");
      $frm->add_submit("close_send", "Clore l'annonce");
      $this->buffer .= $frm->html_render();
      $this->buffer .= "</div>";
    }
    else if( empty($annonce->applicants) ) /* pas de bol mon gars */
    {
      $this->buffer .= "<p class=\"error\">Aucun candidat ne s'est pour l'instant présenté pour répondre à votre offre.</p>";
    }
    else /* et c'est là qu'on se marre */
    {
      $list = new itemlist(false);
      $list->add("Il y a pour l'instant ".(count($annonce->applicants) - count($annonce->winner))." candidature(s) pour votre annonce");
      if( !$annonce->allow_diff )
        $list->add("Attention : Vous n'avez pas demandé la diffusion de votre numéro de téléphone, aussi pensez à prendre contact avec les candidats si vous souhaitez les rencontrer", "ko");
      $this->buffer .= $list->html_render();

      if( $annonce->nb_postes > 1 && !empty($annonce->winner) )
      {
        $this->buffer .= "<p> Vous avez déjà sélectionné un/des candidat(s) : </p>"; // $usr->prenom $usr->nom</p>";

        $list = new itemlist(false);  /* liste des personnes sélectionnées */
        foreach($annonce->winner as $id_winner)
        {
          $winner = new utilisateur($annonce->db);
          $winner->load_by_id($id_winner);
          $list->add("$winner->prenom $winner->nom", "ok");
        }
        $list->add("Il reste actuellement ".$annonce->remaining_positions()." place(s) disponibles pour votre offre.");
        $this->buffer .= $list->html_render();
      }

      $this->buffer .= "<h3>Candidats :</h3>";
      /* debut 'liste' des candidats */
      foreach($annonce->applicants_fullobj as $usr)
      {
        if( sizeof($annonce->winner) != 0 && in_array($usr->id, $annonce->winner) ) /* on passe les étudiants déjà sélectionnés */
          continue;

        $usr->load_all_extra();
        $usr->load_pdf_cv();

        $ville = new ville($usr->db);
        $ville->load_by_id($usr->id_ville);

        $this->buffer .= "<div class=\"apply_table\">\n";
        $this->buffer .= "<div class=\"apply_title\" onClick=\"javascript:on_off('applicant_".$annonce->id."_".$n."');\">";
        $this->buffer .= $usr->prenom." ".$usr->nom." (département ".strtoupper($usr->departement).")";
        $this->buffer .= "</div>\n";

        $this->buffer .= "<div id=\"applicant_".$annonce->id."_".$n."\" class=\"apply_content\">";
        $this->buffer .= "<p>Votre annonce à reçu la candidature de cet étudiant :</p>";

        $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Nom </div> \n <div class=\"desc_content\"><b>".$usr->prenom ." ". $usr->nom."</b></div> \n</div>";
        $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Date de naissance </div> \n <div class=\"desc_content\">".date("d/m/Y", $usr->date_naissance)."</div> \n</div>";
        $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Branche </div> \n <div class=\"desc_content\">".strtoupper($usr->departement) ." ". $usr->semestre."</div> \n</div>";
        $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Email </div> \n <div class=\"desc_content\">".preg_replace('(@)', ' [at] ', $usr->email_utbm)."</div> \n</div>";
        $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Téléphone </div> \n <div class=\"desc_content\">".telephone_display($usr->tel_portable)."</div> \n</div>";
        $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Adresse </div> \n <div class=\"desc_content\">".nl2br(htmlentities($usr->addresse, ENT_NOQUOTES,"UTF-8")). "<br /> $ville->cpostal $ville->nom </div> \n</div>";

        if( !empty($usr->pdf_cvs) )
        {
          $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> CV(s) disponible(s) </div> \n <div class=\"desc_content\">";
          foreach( $usr->pdf_cvs as $cv )
            $this->buffer .= "<img src=\"$topdir/images/i18n/$cv.png\" />&nbsp; <a href=\"". $topdir . "var/cv/". $usr->id . "." . $cv .".pdf\"> CV en ". $i18n[ $cv ] ."</a> <br /> \n";
          $this->buffer .= "</div> \n</div>";
        }

        if( file_exists($topdir."data/matmatronch/".$usr->id.".identity.jpg") )
          $img = $topdir."data/matmatronch/".$usr->id.".identity.jpg";
        else
          $img = $topdir."/images/icons/128/unknown.png";
        $this->buffer .= "<div class=\"desc_user_photo\"> <img src=\"$img\" width=80 alt=\"Photo de $usr->prenom $usr->nom\" /></div>\n ";

        if( !empty($annonce->applicants[$n-1]['comment']) )
          $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Message </div> \n <div class=\"desc_content\">".nl2br(htmlentities($annonce->applicants[$n-1]['comment'],ENT_NOQUOTES,"UTF-8"))."</div> \n</div>";

        $this->buffer .= "<p></p>";

        $frm = new form("apply_".$annonce->id."", "?action=select", true, "POST");
        $frm->add_hidden("etu", $usr->id);
        $frm->add_hidden("id", $annonce->id);
        $frm->puts("<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" id=\"clic\" name=\"clic\" value=\"Choisir ce candidat\" class=\"isubmit\" onClick=\"javascript:if(confirm('Vous vous apprêtez à sélectionner ".$usr->prenom ." ". $usr->nom.", en êtes vous sûr ?')) this.form.submit();\" /></div></div>\n");
        $this->buffer .= $frm->html_render();

        $this->buffer .= "</div>\n";
        $this->buffer .= "</div>\n";
        $n++;
      }
      /* fin liste candidats */

      $this->buffer .= "<p></p>";
    }

    $this->buffer .= "<h3>Rappel de votre annonce</h3>";
    $this->buffer .= "<div class=\"desc_row\"> \n<div class=\"desc_label\"> Description </div> \n <div class=\"desc_content\">".nl2br(htmlentities($annonce->desc,ENT_NOQUOTES,"UTF-8"))."</div> \n</div>";

    $this->buffer .= "</div>\n";


    $this->buffer .= "</div>\n";

  }

}

class jobtypes_select_field extends form
{
  function jobtypes_select_field(&$jobetu, $name, $title, $value = false, $required = true, $enabled = true)
  {
    if( !($jobetu instanceof jobetu) ) return -1;
    if(empty($jobetu->job_types)) $jobetu->get_job_types();

    parent::form($name, false, true);

    if ( $frm->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];

    $this->buffer .= "<div class=\"formrow\" style=\"margin-left: -2em; padding-left: -2em\" >";
    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\">";
    $this->buffer .= "<select name=\"$name\" >\n";

    foreach ( $jobetu->job_types as $key => $item )
    {
      $this->buffer .= "<option value=\"$key\"";
      if(!($key%100))
        $this->buffer .= " disabled style=\"background: #D8E7F3; color: #000000; font-weight: bold;\"";
      if ( $value == $key )
        $this->buffer .= " selected=\"selected\"";
      if(!($key%100))
        $this->buffer .= ">".htmlentities($item,ENT_NOQUOTES,"UTF-8")."</option>\n";
      else
        $this->buffer .= ">&nbsp;&nbsp;&nbsp;&nbsp;".htmlentities($item,ENT_NOQUOTES,"UTF-8")."</option>\n";
    }

    $this->buffer .= "</select></div>\n";
    $this->buffer .= "</div>\n";

  }
}


class jobtypes_table extends stdcontents
{
  function jobtypes_table(&$jobetu, $user, $name, $title, $value = false, $required = true, $enabled = true)
  {
    if( !($jobetu instanceof jobetu) ) return -1;
    if( !($user instanceof jobuser_etu) ) return -1;
    if( empty($jobetu->job_types) ) $jobetu->get_job_types();
    if( empty($user->competences) ) $user->load_competences();

    $l = 1;
    $t = 0;
    static $num = 1;
    $id_name = "id_job";

    $this->buffer .= "<form name=\"$name\" action=\"board_etu.php?view=profil\" method=\"POST\">\n";
    $this->buffer .= "<input type=\"hidden\" name=\"magicform[name]\" value =\"$name\" />\n";
    $this->buffer .= "<table class=\"sqltable\">\n";

    foreach ( $jobetu->job_types as $key => $item )
    {
      if(!($key%100))
      {
        $this->buffer .= "<tr class=\"head\">\n";
          $this->buffer .= "<th colspan=\"2\" value=\"$key\">$item</th>";
        $this->buffer .= "</tr>\n";
      }
      else
      {
        $t = $t%2;

        if( in_array($key, $user->competences) )
          $check = "checked=\"checked\"";
        else
          $check = "";

        $this->buffer .= "<tr id=\"ln[$num]\" class=\"ln$t\" onMouseDown=\"setPointer('ln$t','$num','click','".$id_name."s[','".$name."');\" onMouseOut=\"setPointer('ln$t','$num','out');\" onMouseOver=\"setPointer('ln$t','$num','over');\">\n";
        $this->buffer .= "<td><input type=\"checkbox\" class=\"chkbox\" name=\"".$id_name."s[$num]\" value=\"".$key."\" $check onClick=\"setPointer('ln$t','$num','click','".$id_name."s[','".$name."');\"/></td>\n";
          $this->buffer .= "<td>$item</td>\n";
        $this->buffer .= "</tr>";

        $l++; $t++; $num++;
      }
    }
    $this->buffer .= "</table>\n";
    $this->buffer .= "</select>\n<input type=\"submit\" name=\"$formname\" value=\"Enregistrer\" class=\"isubmit\"/>\n</p>\n";

    $this->buffer .= "</form>\n";  }
}

?>
