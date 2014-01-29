<?php

/* Copyright 2006
 *
 * - Maxime Petazzoni < sam at bulix dot org >
 * - Laurent Colnat < laurent dot colnat at utbm dot fr >
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

require_once($topdir. "include/cts/special.inc.php");

/**
 * @file
 */

$UserBranches = array("TC"             => "TC",
                      "GI"             => "GI",
                      "GSP"            => "IMAP",
                      "GSC"            => "GESC",
                      "GMC"            => "GMC",
                      "Enseig"     => "Enseignant",
                      "Admini" => "Administration",
                      "Autre"          => "Autre");

/**
 * Conteneur de fiche d'information sur un utilisateur
 * @ingroup display_cts
 * @author Laurent Colnat
 * @author Maxime Petazzoni
 * @deprecated
 */
class userinfo extends stdcontents
{
  /**
   * Génère une fiche pour un utilisateur
   * @param $user instance de la classe utilisateur
   * @param $link ajoute un lien vers la page utilisateur
   * @param $solde ajoute le solde de l'utilisateur
   * @param $vcard ajoute le lien vers la vCard de l'utilisateur
   * @param $extraadmin ajoute les liens d'administration
   * @param $brief diminue la quantité d'informations affichés
   * @param $identity force l'affichage de la photo d'identité au lieu de la photo matmatronch
   */
  function userinfo ( $user, $link=false, $solde=false, $vcard=false, $extraadmin=false, $brief=true, $identity=false )
  {
    global $topdir, $UserBranches;
    $sub=substr_count($urldest, "/");
    $realpath="";
    if($sub>0)
    {
      for($i=0;$i<$sub;$i++)
        $realpath.= "../";
    }
    require_once($topdir . "include/entities/ville.inc.php");
    require_once($topdir . "include/entities/pays.inc.php");

    if (!isset($this->surnom))
      $user->load_all_extra();

    $this->title = $user->prenom." ".$user->nom;

    $ville = new ville($user->db);
    $pays = new pays($user->db);
    $ville->load_by_id($user->id_ville);
    $pays->load_by_id($user->id_pays);

    if ( $brief )
    {
      if ( $user->promo_utbm > 0 )
        $this->buffer .= "<div class=\"userinfo userinfopromo".sprintf("%02d",$user->promo_utbm)."\">\n";
      else
        $this->buffer .= "<div class=\"userinfo\">\n";

      if ( $link )
      {
        $this->buffer .= "<div class=\"links\" style=\"position: relative; float: right;\">";
        /*if ($user->utbm)
          {
            $this->buffer .= "<a href=\"/edt.php?id_utilisateur=".$user->id."\"><img src=\"/images/actions/schedule.png\" title=\"Emploi du temps\" alt=\"Emploi du temps\"></a>";
            $this->buffer .= "&nbsp;";
          }*/
        $this->buffer .= "<a href=\"/user.php?id_utilisateur=".$user->id."\"><img src=\"/images/actions/view.png\" title=\"Voir la fiche complète\" alt=\"Voir la fiche complète\"></a>";
        $this->buffer .= "</div>";
      }
      $this->buffer .= "<p><b>". $user->prenom . " " . $user->nom . " </b>";
      if ( $user->surnom )
        $this->buffer .= "<i>&laquo;". $user->surnom . "&raquo;</i><br/></p>";
      elseif ( $user->alias )
        $this->buffer .= "<i>&laquo;". $user->alias . "&raquo;</i><br/></p>";
      $this->buffer .= "<div class=\"clearboth\"></div>";

      $this->buffer .= "<div class=\"photo\" style=\"height: 120px; float: left;\">";

      if ($user->id && file_exists($topdir."data/matmatronch/".$user->id.".identity.jpg"))
      {
        $date_prise_vue = "";
        $exif = @exif_read_data("/data/matmatronch/".$user->id.".identity.jpg", 0, true);
        if ( $exif["FILE"]["FileDateTime"] )
          $date_prise_vue = $exif["FILE"]["FileDateTime"];
        $size = getimagesize($topdir."data/matmatronch/".$user->id.".identity.jpg");
        /* laissons une marge de 50px */
        $width = $size[0] + 50;
        $height = $size[1] + 75;
        $this->buffer .= "<a href=\"javascript:openMatmatronch('".
        $user->id."','".$width."','".$height."')\"><img src=\"/data/matmatronch/".$user->id.".identity.jpg?".$date_prise_vue."\" alt=\"\" class=\"fiche_image\" title=\"Cliquez pour agrandir l'image\" alt=\"Cliquez pour agrandir l'image\"/></a>\n";
      }
      elseif ( $user->id && file_exists($topdir."data/matmatronch/".$user->id.".jpg"))
      {
        $date_prise_vue = "";
        $exif = @exif_read_data("/data/matmatronch/".$user->id.".jpg", 0, true);
        if ( $exif["FILE"]["FileDateTime"] )
          $date_prise_vue = $exif["FILE"]["FileDateTime"];
        $size = getimagesize($topdir."data/matmatronch/".$user->id.".jpg");
        /* laissons une marge de 50px */
        $width = $size[0] + 50;
        $height = $size[1] + 75;
        $this->buffer .= "<a href=\"javascript:openMatmatronch('/','".
          $user->id."','".$width."','".$height."')\"><img src=\"/data/matmatronch/".$user->id.".jpg?".$date_prise_vue."\" alt=\"\" class=\"fiche_image\" title=\"Cliquez pour agrandir l'image\" alt=\"Cliquez pour agrandire l'image\"/></a>\n";

      }
      else
        $this->buffer .= "<img src=\"/data/matmatronch/na.gif"."\" alt=\"\" class=\"fiche_image\" />\n";

      $this->buffer .= "</div>";

  if (! is_null($user->date_naissance))
  {
    if ( $user->sexe == 1 )
      $this->buffer .= "N&eacute; ";
    else
      $this->buffer .= "N&eacute;e ";
      $this->buffer .= "le : ". date("d/m/Y", $user->date_naissance) . "<br />\n";
  }
  if ( $user->addresse || $ville->is_valid() )
  {
    $this->buffer .= "<br><img src=\"/images/icons/16/batiment.png\" width=\"14\" height=\"14\" style=\"margin-right:0.5em;\">";
    $this->buffer .= $user->addresse;

    if ( $ville->is_valid() )
      $this->buffer .= "<br/>".$ville->get_html_link()." (".sprintf("%05d", $ville->cpostal).")";

    if ( $pays->is_valid() && $pays->id != 1)
      $this->buffer .= "<br/>".$pays->get_html_link();
  }

  if ( $pays->is_valid() && $pays->id != 1)
    $this->buffer .= "<br/>".$pays->get_html_link();

    $this->buffer .="<br />";
    //$this->buffer .="<br />";

    if ( $user->branche )
    {
      $this->buffer .= "<b>".$UserBranches[$user->branche];
      if ( $user->branche!="Enseignant" && $user->branche!="Administration" && $user->branche!="Autre" )
        {
          $this->buffer .= sprintf("%02d",$user->semestre)."</b>";
          if ( $user->filiere )
            {
              $this->buffer .= "<br/>Filière : <br/>";
              $this->buffer .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$user->filiere;
            }
        }
        $this->buffer .="</b>";
      }

      $this->buffer .= "<div class=\"clearboth\"></div>";
      $this->buffer .= "<div class=\"phones\" style=\"position: relative; float: left;\">";
      if ( $user->tel_maison )
        $this->buffer .= "<img src=\"/images/usr_fixe.png\" width=\"18\" height=\"16\" style=\"margin-right: 0.5em;\">".telephone_display($user->tel_maison);
      if ( $user->tel_portable )
        $this->buffer .= "<br><img src=\"§/images/usr_portable.png\" style=\"margin-right: 0.5em;\">".telephone_display($user->tel_portable);
      $this->buffer .= "</div>";
      $this->buffer .= "<div class=\"mails\" style=\"position: relative; float: right; margin-top: 14px;\">";
      if ( $user->email || $user->email_utbm )
      {
        if ( $user->email == $user->email_utbm ) $user->email = false;

        if ( $user->email )
          $this->buffer .= "<a href=\"mailto:".$user->email."\"><img src=\"/images/email_perso.png\" style=\"margin-right: 5px;\" alt=\"Email perso\" title=\"Email perso\"></a>";

        if ( $user->email_utbm )
          $this->buffer .= "<a href=\"mailto:".$user->email_utbm."\"><img src=\"/images/mail_UTBM.png\" style=\"margin-right: 5px;\" alt=\"Email UTBM\" title=\"Email UTBM\"></a>";

      }
      if ( $vcard )
      {
        $this->buffer .= "<a href=\"/matmatronch/index.php/vcf/".$user->id."\"><img src=\"/images/vcard.png\" alt=\"vCard\" title=\"vCard\"></a>";
      }
      $this->buffer .= "</div>";
      $this->buffer .= "<div class=\"clearboth\"></div>\n";

      if ( $solde )
      {
        $this->buffer .= "<p>";
        $this->buffer .= "Solde: ".number_format($user->montant_compte/100, 2)." €\n";
        $this->buffer .= "</p>";
      }

      $this->buffer .= "</div>\n";
    }
    else
    {
      /*if ( $user->etudiant || $user->ancien_etudiant )
              {
                      $this->buffer .= $user->citation;
                      $this->buffer .= $user->adresse_parents;
                      $this->buffer .= $user->ville_parents;
                      $this->buffer .= $user->cpostal_parents;
                      $this->buffer .= $user->tel_parents;
                      $this->buffer .= $user->nom_ecole_etudiant;
              }
              if ( $user->utbm )
              {
                      $this->buffer .= $user->surnom;
                      $this->buffer .= $user->semestre;
                      $this->buffer .= $user->branche;
                      $this->buffer .= $user->filiere;
                      $this->buffer .= $user->promo_utbm;
              }*/
      $this->buffer .= "<div class=\"clearboth\"></div>\n";

      $this->buffer .= "<div class=\"userinfo\">\n";

      $this->buffer .= "<div class=\"denomination\" style=\"position: relative; float: left;\">";
      if ( $solde && $user->montant_compte/100 != 0)
      {
        $this->buffer .= "<div class=\"compt_ae\" style=\"color: red; float: right; text-align: center;\">";
        $this->buffer .= "<a href=\"/user/compteae.php?id_utilisateur=".$user->id."\" alt=\"Consulter son compte AE\" title=\"Consulter son compte AE\"><img src=\"".$topdir."images/money.png\"></a><br/>".($user->montant_compte/100)." €\n";
          $this->buffer .= "</div>";
      }
      $this->buffer .= "<p>";
      if ( $user->sexe == 1 )
        $this->buffer .= "<img src=\"/images/icon_homme.png\" style=\"height: 18px; width; 18px; margin-right: 0.5em;\">";
      else
        $this->buffer .= "<img src=\"/images/icon_femme.png\" style=\"height: 18px; width; 18px; margin-right: 0.5em;\">";
      $this->buffer .= "<b>". $user->prenom . " " . $user->nom . " </b>";
      if ( $user->surnom )
        $this->buffer .= "alias <i>&laquo;". $user->surnom . "&raquo;</i><br/></p>";
      elseif ( $user->alias )
        $this->buffer .= "alias <i>&laquo;". $user->alias . "&raquo;</i><br/></p>";



      $this->buffer .= "<div class=\"others_infos\" style=\"position: relative; float: left;\">";
      $this->buffer .= "<br/>";

  if (! is_null($user->date_naissance))
  {
    if ( $user->sexe == 1 )
      $this->buffer .= "N&eacute; ";
    else
      $this->buffer .= "N&eacute;e ";
    $this->buffer .= "le " . strftime("%A %d %B %Y", $user->date_naissance) . "<br />";
  }
    if ( $user->hash != "valid" )
      $this->buffer .= "<span style=\"font-color: red;\">Compte non valid&eacute; !</span><br />\n";

    $this->buffer .= "<br /><br />";
    if ( $user->branche && $user->nom_ecole_etudiant == "UTBM")
    {
      $this->buffer .= "<div class=\"branches\" style=\"float: left; width: 200px;\">";
      $this->buffer .= "<img src=\"/images/utbmlogo.gif\" style=\"position: relative; float: left; width: 65px;\">";
      $this->buffer .= "<div class=\"branches_info\" style=\"position: relative; width: 300px;\">";
      $this->buffer .= "<br/><b>".$UserBranches[$user->branche];
      if ( $user->branche!="Enseignant" && $user->branche!="Administration" && $user->branche!="Autre" && $user->branche!="TC")
      {
        if (isset($user->date_diplome_utbm) && !(empty($user->date_diplome_utbm)) && ($user->date_diplome_utbm != "0000-00-00") && ($user->date_diplome_utbm < time()))
          if ($user->sexe == 1)
            $this->buffer .= " Dipl&ocirc;m&eacute;</b>";
          else
            $this->buffer .= " Dipl&ocirc;m&eacute;e</b>";
        else
          $this->buffer .= sprintf("%02d",$user->semestre)."</b>";

        if ( $user->filiere )
        {
          $this->buffer .= "<br/>Filière : <br/>";
          $this->buffer .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$user->filiere;
        }
      }
      $this->buffer .="</b>";
      $this->buffer .= "</div>";

      $this->buffer .= "</div>";
    }
    $this->buffer .= "<div class=\"clearboth\"></div>\n";

    $this->buffer .= "<div class=\"adresse_perso\" style=\"float: left; width: 200px; text-align:center;\">";
  if ( $user->addresse || $ville->is_valid() )
  {
  $this->buffer .= "<br><img src=\"/images/icons/16/batiment.png\" width=\"14\" height=\"14\" style=\"margin-right:0.5em;\">";
  $this->buffer .= $user->addresse;

  if ( $ville->is_valid() )
    $this->buffer .= "<br/>".$ville->get_html_link()." (".sprintf("%05d", $ville->cpostal).")";

  if ( $pays->is_valid() && $pays->id != 1)
    $this->buffer .= "<br/>".$pays->get_html_link();
  }

  if ( $pays->is_valid() && $pays->id != 1)
    $this->buffer .= "<br/>".$pays->get_html_link();

    if ( $user->tel_maison )
      $this->buffer .= "<br/><br/><img src=\"/images/usr_fixe.png\" width=\"18\" height=\"16\" style=\"margin-right: 0.5em;\">".telephone_display($user->tel_maison);

    if ( $user->tel_portable )
    {
      if (!$user->tel_maison)
        $this->buffer .= "<br/>";
      $this->buffer .= "<br/><img src=\"/images/usr_portable.png\" style=\"margin-right: 0.5em;\">".telephone_display($user->tel_portable);
    }
    $this->buffer .= "</div>";

    $this->buffer .= "<div class=\"clearboth\"></div>\n";
    $this->buffer .= "<br/><br/>";
    $this->buffer .= "<div class=\"admin\" style=\"position : relative; float: left;\">";
    if ( $user->promo_utbm && file_exists($topdir."images/promo_".sprintf("%02d",$user->promo_utbm).".png") )
    {
      $this->buffer .= "<a href=\"promo".sprintf("%02d",$user->promo_utbm)."\"><img src=\"/images/promo_".sprintf("%02d",$user->promo_utbm).".png\" style=\"position: relative; float: left;\"></a>";
    }
    if ($user->promo_utbm)
      $this->buffer .= "<p style=\"padding-top: 5px;\"><a href=\"promo".sprintf("%02d",$user->promo_utbm)."\">Le Site de la Promo ".sprintf("%02d",$user->promo_utbm)."</p>";
    if ( $extraadmin )
    {
      $this->buffer .= "<p><a href=\"/ae/cotisations.php?action=searchstudent&amp;id_utilisateur=".$user->id."\">&nbsp;&nbsp;Nouvelle cotisation à l'AE</a></p>";
      $this->buffer .= "<a href=\"/user/compteae.php?id_utilisateur=".$user->id."\">Consulter compte AE</a>";
    }

    $this->buffer .= "</div>";

    $this->buffer .= "</div>";

    // droite
    $this->buffer .= "<div class=\"right\" style=\"text-align: center; float: right; width: 280px;\">";
    $this->buffer .= "<div class=\"mails\">";
    if ( $user->email || $user->email_utbm )
    {
      if ( $user->email == $user->email_utbm ) $user->email = false;

      if ( $user->email )
        $this->buffer .= "<a href=\"mailto:".$user->email."\"><img src=\"/images/email_perso.png\" style=\"margin-bottom: 5px; margin-right: 5px;\" alt=\"Email perso\" title=\"Email perso\"></a>";

      if ( $user->email_utbm )
        $this->buffer .= "<a href=\"mailto:".$user->email_utbm."\"><img src=\"/images/mail_UTBM.png\" style=\"margin-bottom: 5px; margin-right: 5px;\" alt=\"Email UTBM\" title=\"Email UTBM\"></a>";
    }
    if ( $vcard )
    {
      $this->buffer .= "<a href=\"/matmatronch/index.php/vcf/".$user->id."\"><img src=\"/images/vcard.png\"  style=\"margin-bottom: 5px;\" alt=\"vCard\" title=\"vCard\"></a>";
    }
    $this->buffer .= "</div>";

    if (file_exists($topdir."data/matmatronch/".$user->id.".jpg"))
      $this->buffer .= "<img src=\"/data/matmatronch/".$user->id.".jpg\" class=\"fiche_image_full\"/>\n";
    elseif (file_exists($topdir."data/matmatronch/".$user->id.".identity.jpg"))
      $this->buffer .= "<img src=\"/data/matmatronch/".$user->id.".identity.jpg\" class=\"fiche_image_full\"/>\n<br /><i>(Photo MatMatronch non pr&eacute;sente&nbsp;!)</i>\n";
    else
      $this->buffer .= "<img src=\"/data/matmatronch/na.gif"."\" alt=\"\" class=\"fiche_image_full\" />\n";

      $this->buffer .= "<br/><br/><div class=\"citation\" style=\"width: 250px; text-align: center;\"><i>".$user->citation."</i></div>";
      $this->buffer .= "</div>";

      $this->buffer .= "</div>";

      $this->buffer .= "<div class=\"clearboth\"></div>\n";
      $this->buffer .= "</div>\n";
    }
  }
}


/**
 * Conteneur de fiche d'information sur un utilisateur
 * @ingroup display_cts
 * @author Julien Etelain
 */
class userinfov2 extends stdcontents
{
  /**
   * Génère une fiche matmatronch userinfov2
   * @param $user instance de la classe utilisateur
   * @param $display "small" (listing), "full" (fiche utilisateur), "summary" (comptoirs)
   * @param $admin En mode "full" affiche ou nom les liens d'administration sur l'utilisateur
   */
  function userinfov2 ( $user, $display = "small", $admin = false, $urldest="user.php", $view_trombi=false )
  {
    global $topdir, $UserBranches;
    $sub=substr_count($urldest, "/");
    $realpath="";
    if($sub>0)
    {
      for($i=0;$i<$sub;$i++)
        $realpath.= "../";
    }
    require_once($topdir . "include/entities/ville.inc.php");
    require_once($topdir . "include/entities/pays.inc.php");

    $ville = new ville($user->db);
    $pays = new pays($user->db);
    $ville_parents = new ville($user->db);
    $pays_parents = new pays($user->db);

    $ville->load_by_id($user->id_ville);
    $pays->load_by_id($user->id_pays);

    if ( $user->etudiant || $user->ancien_etudiant )
    {
      $ville_parents->load_by_id($user->id_ville_parents);
      $pays_parents->load_by_id($user->id_pays_parents);
    }

    static $numFiche=0;
    $numFiche++;

    $this->title = $user->prenom." ".$user->nom;

    $imgclass="noimg";
    $img = "/images/icons/128/unknown.png";
    $date_prise_vue = "";

    if (file_exists("/data/matmatronch/".$user->id.".identity.jpg"))
    {
      $exif = @exif_read_data("/data/matmatronch/".$user->id.".identity.jpg", 0, true);
      if ( $exif["FILE"]["FileDateTime"] )
        $date_prise_vue = $exif["FILE"]["FileDateTime"];

      $img = "/data/matmatronch/".$user->id.".identity.jpg?".$date_prise_vue;
      $imgclass="idimg";
    }

    if ( $display == "full" )
      $this->buffer .= "<div class=\"userfullinfo\">";
    else
      $this->buffer .= "<div class=\"userinfov2\">";

    $this->buffer .= "<h2 class=\"nom\">".htmlentities($user->prenom,ENT_COMPAT,"UTF-8")." <span class=\"nomfamille\">".htmlentities($user->nom,ENT_COMPAT,"UTF-8")."</span></h2>\n";

    $this->buffer .= "<div class=\"photo\">";
    if ($display != "full")
      $this->buffer .= "<a href=\"/".$urldest."?id_utilisateur=".$user->id."\">";
    $this->buffer .= "<img src=\"$img\" id=\"mmtphoto$numFiche\" class=\"$imgclass\" alt=\"Photo de ".htmlentities($user->prenom." ".$user->nom,ENT_COMPAT,"UTF-8")."\" />";
    if ($display != "full")
      $this->buffer .= "</a>\n";
    $this->buffer .= "</div>\n";

    if ( $display == "full" )
    {
      $this->buffer .= "<div class=\"switchphoto\">";

      $this->buffer .= "<div class=\"photommt\">";
      if (file_exists($topdir."data/matmatronch/".$user->id.".jpg"))
      {
        $exif = @exif_read_data("/data/matmatronch/".$user->id.".jpg", 0, true);
        if ( $exif["FILE"]["FileDateTime"] )
          $date_prise_vue = $exif["FILE"]["FileDateTime"];

        $this->buffer .= "<a href=\"#\" onclick=\"switchphoto('mmtphoto$numFiche','".$realpath."data/matmatronch/".$user->id.".jpg?".$date_prise_vue."'); return false;\">";
        $this->buffer .= "<img src=\"/data/matmatronch/".$user->id.".jpg?".$date_prise_vue."\" class=\"switch\" alt=\"Photo\" /></a>";
      }
      else
        $this->buffer .= "<img src=\"/images/icons/128/blackbox.png"."\" class=\"switchnone\" alt=\"Photo\" />";
      $this->buffer .= "</div>\n";

      $this->buffer .= "<div class=\"photoid\">";
      if (file_exists($topdir."data/matmatronch/".$user->id.".identity.jpg"))
      {
        $exif = @exif_read_data("/data/matmatronch/".$user->id.".identity.jpg", 0, true);
        if ( $exif["FILE"]["FileDateTime"] )
          $date_prise_vue = $exif["FILE"]["FileDateTime"];

        $this->buffer .= "<a href=\"#\" onclick=\"switchphoto('mmtphoto$numFiche','".$realpath."data/matmatronch/".$user->id.".identity.jpg?".$date_prise_vue."'); return false;\">";
        $this->buffer .= "<img src=\"/data/matmatronch/".$user->id.".identity.jpg?".$date_prise_vue."\" class=\"switch\" alt=\"Photo d'identite\" /></a>";
      }
      else
        $this->buffer .= "<img src=\"/images/icons/128/blackbox.png"."\" class=\"switchnone\" alt=\"Photo d'identite\" />";
      $this->buffer .= "</div>\n";

      $this->buffer .= "<div class=\"photoblouse\">";
      if (file_exists("/data/matmatronch/".$user->id.".blouse.mini.jpg"))
      {
        $exif = @exif_read_data("/data/matmatronch/".$user->id.".blouse.mini.jpg", 0, true);
        if ( $exif["FILE"]["FileDateTime"] )
          $date_prise_vue = $exif["FILE"]["FileDateTime"];

        $this->buffer .= "<a href=\"#\" onclick=\"switchphoto('mmtphoto$numFiche','".$realpath."data/matmatronch/".$user->id.".blouse.mini.jpg?".$date_prise_vue."'); return false;\">";
        $this->buffer .= "<img src=\"/data/matmatronch/".$user->id.".blouse.mini.jpg?".$date_prise_vue."\" class=\"switch\" alt=\"Photo de la blouse\" /></a>";
      }
      else
        $this->buffer .= "<img src=\"/images/icons/128/blackbox.png"."\" class=\"switchnone\" alt=\"Photo de la blouse\" />";
      $this->buffer .= "</div>\n";
      $this->buffer .= "</div>\n";
    }

    if ( $user->surnom )
      $this->buffer .= "<p class=\"surnom\">&laquo; ". htmlentities($user->surnom,ENT_COMPAT,"UTF-8") . " &raquo;</p>";

    elseif ( $user->alias )
      $this->buffer .= "<p class=\"surnom\">&laquo; ". htmlentities($user->alias,ENT_COMPAT,"UTF-8") . " &raquo;</p>";

    if ( $user->utbm )
    {
      $this->buffer .= "<p class=\"departement\">";

      if ( $user->role == "etu" && $user->departement != "na" )
      {
        $this->buffer .= $GLOBALS["utbm_departements"][$user->departement];

        if ( !is_null($user->date_diplome_utbm) && $user->date_diplome_utbm < time() )
        {
          if ($user->sexe == 1)
            $this->buffer .= " Dipl&ocirc;m&eacute;";
          else
            $this->buffer .= " Dipl&ocirc;m&eacute;e";
        }
        else
          $this->buffer .= sprintf("%02d",$user->semestre);

        if ( $user->filiere )
          $this->buffer .= "<span class=\"filiere\">Filière : ".htmlentities($user->filiere,ENT_COMPAT,"UTF-8")."</span>";
      }
      else
      {
        $this->buffer .= $GLOBALS["utbm_roles"][$user->role];

        if ( $user->departement != "na" )
          $this->buffer .= " ".$GLOBALS["utbm_departements"][$user->departement];
        else
          $this->buffer .= " ".$user->nom_ecole_etudiant;
      }

      $this->buffer .= "</p>\n";
    }

    $this->buffer .= "<p class=\"naissance\">";
    if ( $user->date_naissance )
    {
      if ( $user->sexe == 1 )
        $this->buffer .= "N&eacute; ";
      else
        $this->buffer .= "N&eacute;e ";

      $this->buffer .= "le : ". date("d/m/Y", $user->date_naissance);
    }
    $this->buffer .= "</p>\n";

    if ( $ville->is_valid() || $user->addresse )
    {
      $this->buffer .= "<p class=\"adresse\">";
      $this->buffer .= htmlentities($user->addresse,ENT_COMPAT,"UTF-8");

      if ( $ville->is_valid() )
       $this->buffer .= "<br/>".$ville->get_html_link()." (".sprintf("%05d", $ville->cpostal).")";

      if ( $pays->is_valid() && $pays->id != 1)
        $this->buffer .= "<br/>".$pays->get_html_link();

      $this->buffer .= "</p>\n";
    }
    elseif ( $pays->is_valid() )
      $this->buffer .= "<p class=\"adresse\">".$pays->get_html_link()."</p>\n";

    if ( $user->promo_utbm > 0 )
    {
      $this->buffer .= "<p class=\"promo\">";
      if ($display == "full")
      {
        $this->buffer .= "Promo ".sprintf("%02d",$user->promo_utbm)."\n";
        if (file_exists($topdir."images/promo_".sprintf("%02d",$user->promo_utbm).".png"))
          $this->buffer .= "<img src=\"/images/promo_".sprintf("%02d",$user->promo_utbm).".png\" alt=\"Promo ".sprintf("%02d",$user->promo_utbm)."\" />\n";
      }
      else
      {
        if ($view_trombi)
          $this->buffer .= "<a href=\"/trombi/index.php?id_utilisateur=".$user->id."\">Promo ".sprintf("%02d",$user->promo_utbm)."</a>\n";
        else
          $this->buffer .= "Promo ".sprintf("%02d",$user->promo_utbm)."\n";
      }
      $this->buffer .= "</p>\n";

      if (($display == "full") && $view_trombi)
        $this->buffer .= "<p class=\"trombi\"><a href=\"/trombi/index.php?id_utilisateur=".$user->id."\">".
        "Voir sur le trombinoscope</a></p>";
    }

    if ( $user->tel_maison )
      $this->buffer .= "<p class=\"telfixe\">" . telephone_display($user->tel_maison). "</p>\n";

    if ( $user->tel_portable )
      $this->buffer .= "<p class=\"telportable\">" . telephone_display($user->tel_portable) . "</p>\n";


    if ( $display == "full" )
    {

      if ( $ville_parents->is_valid() || $user->adresse_parents )
      {
        $this->buffer .= "<p class=\"adresseparents\">";
        $this->buffer .= htmlentities($user->adresse_parents,ENT_COMPAT,"UTF-8");

        if ( $ville_parents->is_valid() )
          $this->buffer .= "<br/>".$ville_parents->get_html_link()." (".sprintf("%05d", $ville_parents->cpostal).")";

        if ( $pays_parents->is_valid() && $pays_parents->id != 1)
          $this->buffer .= "<br/>".$pays_parents->get_html_link();

        $this->buffer .= "</p>\n";
      }
      elseif ( $pays_parents->is_valid() )
        $this->buffer .= "<p class=\"adresseparents\">".$pays_parents->get_html_link()."</p>\n";

      if ( $user->tel_parents )
        $this->buffer .= "<p class=\"telparents\">" . telephone_display($user->tel_parents) . "</p>\n";

    }


    $this->buffer .= "<p class=\"outils\">";
    $this->buffer .= "<a class=\"vcard\" href=\"/matmatronch/index.php/vcf/".$user->id."\"><img src=\"/images/vcard.png\" alt=\"vCard\" title=\"vCard\"></a>";

    if ( $user->email_utbm )
      $this->buffer .= "<a class=\"mailutbm\" href=\"mailto:".htmlentities($user->email_utbm,ENT_COMPAT,"UTF-8")."\"><img src=\"/images/mail_UTBM.png\" alt=\"Email UTBM\" title=\"Email UTBM\"></a>";

    if ( $user->email && $user->email != $user->email_utbm  )
      $this->buffer .= "<a class=\"mailperso\" href=\"mailto:".htmlentities($user->email,ENT_COMPAT,"UTF-8")."\"><img src=\"/images/email_perso.png\" alt=\"Email Perso\" title=\"Email Perso\"></a>";

    if ( $user->jabber )
      $this->buffer .= "<a class=\"jabber\" href=\"xmpp:".htmlentities($user->jabber,ENT_COMPAT,"UTF-8")."\"><img src=\"/images/jabber.png\" alt=\"Jabber\" title=\"Jabber\"></a>";

    if ( $display != "full" )
      $this->buffer .= "<a class=\"fiche\" href=\"/".$urldest."?id_utilisateur=".$user->id."\"><img src=\"/images/actions/view.png\" alt=\"Fiche\" title=\"Fiche\"></a>";

    /*$this->buffer .= "<a class=\"edt\" href=\"/edt.php?id_utilisateur=".$user->id."\"><img src=\"/images/actions/schedule.png\" alt=\"Emploi du temps\" title=\"Emploi du temps\"></a>";*/
    $this->buffer .= "</p>\n";

    if ( $display == "full" )
    {
      if ( $user->surnom )
        $this->buffer .= "<p class=\"citation\">". htmlentities($user->citation,ENT_COMPAT,"UTF-8") . "</p>";

      if ( $admin )
      {
        $this->buffer .= "<ul class=\"useradmin\">";
        $this->buffer .= "<li><a href=\"/ae/cotisations.php?action=searchstudent&amp;id_utilisateur=".$user->id."\">Nouvelle cotisation à l'AE</a></li>";
        $this->buffer .= "<li><a href=\"/user/compteae.php?id_utilisateur=".$user->id."\">Consulter compte AE</a></li>";
        $this->buffer .= "</ul>";
      }
    }

    $this->buffer .= "</div>";

  }
}


?>
