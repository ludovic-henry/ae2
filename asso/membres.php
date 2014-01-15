<?php
/* Copyright 2006
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
$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/cts/newsflow.inc.php");
$site = new site ();
$asso = new asso($site->db,$site->dbrw);
$asso->load_by_id($_REQUEST["id_asso"]);
if ( $asso->id < 1 )
{
  $site->error_not_found("presentation");
  exit();
}

$limited=false;

$can_admin=$site->user->is_in_group("gestion_ae")|| $asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU);

if ( !$site->user->is_valid() || ( !$site->user->ae && !$can_admin ) )
  $limited = true;


$ae_sphere=false;
$asso_parent = new asso($site->db);
$asso_parent->load_by_id($asso->id_parent);
while ( $asso_parent->id > 0 )
{
  if ( $asso_parent->id == 1 )
    $ae_sphere = true;
  $asso_parent->load_by_id($asso_parent->id_parent);
}

/*if( !$site->user->ae && !$can_admin )
  $site->error_forbidden("presentation","reserveAE");*/

if ( $_REQUEST["action"]=="getallvcards" && !$limited )
{

  header("Content-Type: text/x-vcard");
  header('Content-Disposition: attachment; filename="'.$asso->nom_unix.'-membres.vcf"');

  $user = new utilisateur($site->db);

  $req = new requete($site->db,
    "SELECT `utilisateurs`.*,`utl_etu`.*,`utl_etu_utbm`.* " .
    "FROM `asso_membre` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
    "LEFT JOIN `utl_etu` ON `utilisateurs`.`id_utilisateur`=`utl_etu`.`id_utilisateur` " .
    "LEFT JOIN `utl_etu_utbm` ON `utilisateurs`.`id_utilisateur`=`utl_etu_utbm`.`id_utilisateur` " .
    "WHERE `asso_membre`.`date_fin` IS NULL " .
    "AND `asso_membre`.`id_asso`='".$asso->id."'");

  while ( $row = $req->get_row() )
  {
    $user->_load_all($row);
    $user->output_vcard();
  }
  exit();
}
elseif ( $_REQUEST["action"]=="add" && $can_admin)
{
  $user = new utilisateur($site->db);
  $user->load_by_id($_REQUEST["id_utilisateur"]);
  if ( $user->id < 1 )
    $ErreurAdd = "Utilisateur inconnu";

  else if ( $ae_sphere && !$user->ae )
    $ErreurAdd = "Utilisateur NON Cotisant AE";

  else if ( ($_REQUEST["date_debut"] <= time()) && ($_REQUEST["date_debut"] > 0) )
    $asso->add_actual_member ( $user->id, $_REQUEST["date_debut"], $_REQUEST["role"], $_REQUEST["role_desc"] );

  else
    $ErreurAddMe = "Données invalides";

}
elseif ( $_REQUEST["action"]=="addformer" && $can_admin)
{
  $user = new utilisateur($site->db);
  $_REQUEST["view"] = "anciens";
  $user->load_by_id($_REQUEST["id_utilisateur"]);

  if ( $user->id < 1 )
    $ErreurAddFormer = "Utilisateur inconnu";

  elseif ( isset($GLOBALS['ROLEASSO'][$_REQUEST["role"]]) &&
    ($_REQUEST["date_debut"] < $_REQUEST["date_fin"]) &&
    ($_REQUEST["date_fin"] < time()) && ($_REQUEST["date_debut"] > 0) )
    $asso->add_former_member ( $user->id, $_REQUEST["date_debut"], $_REQUEST["date_fin"], $_REQUEST["role"], $_REQUEST["role_desc"] );

  else
    $ErreurAddFormer = "Données invalides";

}
elseif ( $_REQUEST["action"]=="delete" && $can_admin)
{
  list($id_utilisateur,$date_debut) = explode(",",$_REQUEST["id_membership"]);
  $date_debut = strtotime($date_debut);
  $asso->remove_member($id_utilisateur,$date_debut);
}
elseif ( $_REQUEST["action"]=="ancien" && $can_admin)
{
  list($id_utilisateur,$date_debut) = explode(",",$_REQUEST["id_membership"]);
  $asso->make_former_member($id_utilisateur,time());
}
elseif ( $_REQUEST["action"]=="deletes" && $can_admin)
{
  foreach($_REQUEST["id_memberships"] as $id_membership )
  {
    list($id_utilisateur,$date_debut) = explode(",",$id_membership);
    $date_debut = strtotime($date_debut);
    $asso->remove_member($id_utilisateur,$date_debut);
  }
}
elseif ( $_REQUEST["action"]=="anciens" && $can_admin)
{
  foreach($_REQUEST["id_memberships"] as $id_membership )
  {
    list($id_utilisateur,$date_debut) = explode(",",$id_membership);
    $asso->make_former_member($id_utilisateur,time());
  }
}
elseif ( $_REQUEST["action"]=="addme" && $asso->id_parent )
{
  if ( ($_REQUEST["date_debut"] <= time()) && ($_REQUEST["date_debut"] > 0) )
    $asso->add_actual_member ( $site->user->id, $_REQUEST["date_debut"], ROLEASSO_MEMBRE, $_REQUEST["role_desc"] );

  else
    $ErreurAddMe = "Données invalides";

}
elseif ( $_REQUEST["action"]=="addmeformer" && !$limited )
{
  $_REQUEST["view"] = "anciens";

  if (  $asso->id_parent < 1 &&
    $_REQUEST["role"] < 2

      )
    $ErreurAddMeFormer = "Non autorisé sur cette association.";

  elseif ( isset($GLOBALS['ROLEASSO'][$_REQUEST["role"]]) &&
    ($_REQUEST["date_debut"] < $_REQUEST["date_fin"]) &&
    ($_REQUEST["date_fin"] < time()) && ($_REQUEST["date_debut"] > 0) )
    $asso->add_former_member( $site->user->id, $_REQUEST["date_debut"], $_REQUEST["date_fin"], $_REQUEST["role"], $_REQUEST["role_desc"] );

  else
    $ErreurAddMeFormer = "Données invalides";

}

// Correction du vocabulaire
if ( !is_null($asso->id_parent) )
{
  $GLOBALS['ROLEASSO'][ROLEASSO_PRESIDENT] = "Responsable";
  $GLOBALS['ROLEASSO'][ROLEASSO_VICEPRESIDENT] = "Vice-responsable";
}
else
{
  $GLOBALS['ROLEASSO'][ROLEASSO_PRESIDENT] = "Président";
  $GLOBALS['ROLEASSO'][ROLEASSO_VICEPRESIDENT] = "Vice-président";
}

$site->start_page("presentation",$asso->nom);

$cts = new contents($asso->get_html_path());

$cts->add(new tabshead($asso->get_tabs($site->user),"mebs"));

$extracond="";

if ( !$limited )
{
  $subtabs = array();

  if ( $can_admin )
  {
    $subtabs[] = array("mailing","asso/mailing.php?id_asso=".$asso->id,"Mailing aux membres");
    $subtabs[] = array("mldiff","asso/mldiff.php?id_asso=".$asso->id,"Gérer les mailings-lists");
  }

  $subtabs[] = array("trombino","asso/membres.php?view=trombino&id_asso=".$asso->id,"Trombino (membres actuels)");
  $subtabs[] = array("vcards","asso/membres.php?action=getallvcards&id_asso=".$asso->id,"Télécharger les vCard (membres actuels)");
  $subtabs[] = array("anciens","asso/membres.php?view=anciens&id_asso=".$asso->id,"Anciens membres");

  $cts->add(new tabshead($subtabs,$_REQUEST["view"],"","subtab"));
}
else
{
  if ( is_null($asso->id_parent) )
    $extracond .= "AND `asso_membre`.`role` > '".ROLEASSO_MEMBREACTIF."' ";
  else
    $extracond .= "AND `asso_membre`.`role` > '".ROLEASSO_TRESORIER."' ";
}

if ( $_REQUEST["view"] == "trombino" || $limited )
{

  require_once($topdir."include/cts/gallery.inc.php");

  $site->add_css("css/sas.css");

  if ( !is_null($asso->id_parent) && (!$site->user->is_valid() || !$asso->is_member($site->user->id)) && !$asso->hidden )
    $cts->add_paragraph("Inscrivez vous pour recevoir les nouvelles de ".$asso->nom." par e-mail et participer aux discussions, c'est simple et rapide : <a href=\"../asso.php?id_asso=".$asso->id."&amp;action=selfenroll\">cliquez ici</a>");

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "`asso_membre`.`role`, " .
    "`asso_membre`.`desc_role`, " .
    "`asso_membre`.`date_debut`, " .
    "CONCAT(`asso_membre`.`id_utilisateur`,',',`asso_membre`.`date_debut`) as `id_membership` " .
    "FROM `asso_membre` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
    "WHERE `asso_membre`.`date_fin` IS NULL " .
    "AND `asso_membre`.`id_asso`='".$asso->id."' " .
    $extracond .
    "ORDER BY `asso_membre`.`role` DESC, `asso_membre`.`desc_role`,`utilisateurs`.`nom_utl`,`utilisateurs`.`prenom_utl` ");

  $gal = new gallery();
  while ( $row = $req->get_row() )
  {

    $img = $topdir."images/icons/128/user.png";
    if ( file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg") )
      $img = $topdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg";
    elseif ( file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".jpg") )
      $img = $topdir."data/matmatronch/".$row['id_utilisateur'].".jpg";

    if ( $row['desc_role'] )
      $role = $row['desc_role'];
    else
      $role = $GLOBALS['ROLEASSO'][$row['role']];

    $gal->add_item(
    "<a href=\"../user.php?id_utilisateur=".$row['id_utilisateur']."\"><img src=\"$img\" alt=\"Photo\" height=\"105\"></a>",
    "<a href=\"../user.php?id_utilisateur=".$row['id_utilisateur']."\">".htmlentities($row['nom_utilisateur'],ENT_NOQUOTES,"UTF-8")."</a> (".htmlentities($role,ENT_NOQUOTES,"UTF-8").")");
  }
  $cts->add($gal);

  if ( $limited && !$site->user->is_valid() && !is_null($asso->id_parent) )
  {
    $cts->add_title(2,"Plus d'informations");
    $cts->add_paragraph("Pour acceder à plus d'informations, veuillez vous connecter.");
  }
}
elseif ( $_REQUEST["view"] == "anciens" )
{

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "`asso_membre`.`role`, " .
    "`asso_membre`.`desc_role`, " .
    "`asso_membre`.`date_debut`," .
    "`asso_membre`.`date_fin`, " .
    "`utl_etu_utbm`.`surnom_utbm`, " .
    "CONCAT(`asso_membre`.`id_utilisateur`,',',`asso_membre`.`date_debut`) as `id_membership` " .
    "FROM `asso_membre` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
    "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
    "WHERE `asso_membre`.`date_fin` IS NOT NULL " .
    "AND `asso_membre`.`id_asso`='".$asso->id."' " .
    "ORDER BY `asso_membre`.`role` DESC, `asso_membre`.`desc_role`,`utilisateurs`.`nom_utl`,`utilisateurs`.`prenom_utl` ");

  $tbl = new sqltable(
      "listresp",
      "Anciens", $req, "membres.php?id_asso=".$asso->id,
      "id_membership",
      array("nom_utilisateur"=>"Utilisateur","surnom_utbm"=>"Surnom","role"=>"Role","desc_role"=>"Role","date_debut"=>"Du","date_fin"=>"Au"),
      $can_admin?array("delete"=>"Supprimer"):array(),
      $can_admin?array("deletes"=>"Supprimer"):array(),
      array("role"=>$GLOBALS['ROLEASSO'] )
      );

  $cts->add($tbl,true);

  if ( $can_admin )
  {
    $frm = new form("addformer","membres.php?view=anciens&id_asso=".$asso->id,false,"POST","Ajouter un ancien membre");
    $frm->add_hidden("action","addformer");
    if ( $ErreurAddFormer )
      $frm->error($ErreurAddFormer);
    $frm->add_user_fieldv2("id_utilisateur","Membre");
    $frm->add_text_field("role_desc","Role (champ libre)","");
    $frm->add_select_field("role","Role",$GLOBALS['ROLEASSO']);
    $frm->add_date_field("date_debut","Date de début",-1,true);
    $frm->add_date_field("date_fin","Date de fin",-1,true);
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }
  else
  {
    if ( ($asso->id_parent < 1) )
    {
      unset($GLOBALS['ROLEASSO'][0]);
      unset($GLOBALS['ROLEASSO'][1]);
    }

    $frm = new form("addmeformer","membres.php?view=anciens&id_asso=".$asso->id,false,"POST","M'ajouter comme un ancien membre");
    $frm->add_hidden("action","addmeformer");
    if ( $ErreurAddMeFormer )
      $frm->error($ErreurAddMeFormer);
    $frm->add_text_field("role_desc","Role (champ libre)","");
    $frm->add_select_field("role","Role",$GLOBALS['ROLEASSO']);
    $frm->add_date_field("date_debut","Date de début",-1,true);
    $frm->add_date_field("date_fin","Date de fin",-1,true);
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }

}
else
{

  if ( $asso->is_mailing_allowed() && !is_null($asso->id_parent) && (!$site->user->is_valid() || !$asso->is_member($site->user->id)) && !$asso->hidden )
    $cts->add_paragraph("Inscrivez vous pour recevoir les nouvelles de ".$asso->nom." par e-mail et participer aux discussions, c'est simple et rapide : <a href=\"../asso.php?id_asso=".$asso->id."&amp;action=selfenroll\">cliquez ici</a>");
  elseif ($asso->is_mailing_allowed() && !is_null($asso->id_parent) && (!$site->user->is_valid() || $asso->is_member($site->user->id)) )
    $cts->add_paragraph("Vous êtes inscrits à la mailing-list de ".$asso->nom);

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
    "`asso_membre`.`role`, " .
    "`asso_membre`.`desc_role`, " .
    "`asso_membre`.`date_debut`, " .
    "`utl_etu_utbm`.`surnom_utbm`, " .
    "CONCAT(`asso_membre`.`id_utilisateur`,',',`asso_membre`.`date_debut`) as `id_membership` " .
    "FROM `asso_membre` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
    "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
    "WHERE `asso_membre`.`date_fin` IS NULL " .
    "AND `asso_membre`.`id_asso`='".$asso->id."' " .
    "AND `asso_membre`.`role` > '".ROLEASSO_MEMBREACTIF."' ".
    $extracond .
    "ORDER BY `asso_membre`.`role` DESC, `asso_membre`.`desc_role`,`utilisateurs`.`nom_utl`,`utilisateurs`.`prenom_utl` ");

  $tbl = new sqltable(
      "listresp",
      "Bureau / Equipe", $req, "membres.php?id_asso=".$asso->id,
      "id_membership",
      array("nom_utilisateur"=>"Utilisateur","surnom_utbm"=>"Surnom","role"=>"Role","desc_role"=>"Role","date_debut"=>"Depuis le"),
      $can_admin?array("ancien"=>"Marquer comme ancien","delete"=>"Supprimer"):array(),
      $can_admin?array("anciens"=>"Marquer comme ancien","deletes"=>"Supprimer"):array(),
      array("role"=>$GLOBALS['ROLEASSO'] )
      );
  $cts->add($tbl,true);

  $members_role = ROLEASSO_MEMBREACTIF;

  if ( $asso->distinct_benevole )
  {
    $req = new requete($site->db,
      "SELECT `utilisateurs`.`id_utilisateur`, " .
      "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
          "`asso_membre`.`role`, " .
    "`asso_membre`.`desc_role`, " .
    "`asso_membre`.`date_debut`, " .
    "`utl_etu_utbm`.`surnom_utbm`, " .
      "CONCAT(`asso_membre`.`id_utilisateur`,',',`asso_membre`.`date_debut`) as `id_membership` " .
      "FROM `asso_membre` " .
      "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
      "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
      "WHERE `asso_membre`.`date_fin` IS NULL " .
      "AND `asso_membre`.`id_asso`='".$asso->id."' " .
      "AND `asso_membre`.`role` = '".ROLEASSO_MEMBREACTIF."' ".
      $extracond .
      "ORDER BY `asso_membre`.`role` DESC, `asso_membre`.`desc_role`,`utilisateurs`.`nom_utl`,`utilisateurs`.`prenom_utl` ");
    if ( $req->lines > 0 )
    {
      $tbl = new sqltable(
           "listmebm",
           "Bénévoles", $req, "membres.php?id_asso=".$asso->id,
           "id_membership",
            array("nom_utilisateur"=>"Utilisateur","surnom_utbm"=>"Surnom","role"=>"Role","desc_role"=>"Role","date_debut"=>"Depuis le"),
            $can_admin?array("ancien"=>"Marquer comme ancien","delete"=>"Supprimer"):array(),
            $can_admin?array("anciens"=>"Marquer comme ancien","deletes"=>"Supprimer"):array(),
            array("role"=>$GLOBALS['ROLEASSO'] )
           );
      $cts->add($tbl,true);
    }

    $members_role = ROLEASSO_MEMBRE;
  }

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
        "`asso_membre`.`role`, " .
    "`asso_membre`.`desc_role`, " .
    "`asso_membre`.`date_debut`, " .
    "`utl_etu_utbm`.`surnom_utbm`, " .
    "CONCAT(`asso_membre`.`id_utilisateur`,',',`asso_membre`.`date_debut`) as `id_membership` " .
    "FROM `asso_membre` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
    "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
    "WHERE `asso_membre`.`date_fin` IS NULL " .
    "AND `asso_membre`.`id_asso`='".$asso->id."' " .
    "AND `asso_membre`.`role` <= '".$members_role."' ".
    $extracond .
    "ORDER BY `asso_membre`.`role` DESC, `asso_membre`.`desc_role`,`utilisateurs`.`nom_utl`,`utilisateurs`.`prenom_utl` ");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
         "listmebm",
         "Membres", $req, "membres.php?id_asso=".$asso->id,
         "id_membership",
          array("nom_utilisateur"=>"Utilisateur","surnom_utbm"=>"Surnom","role"=>"Role","desc_role"=>"Role","date_debut"=>"Depuis le"),
          $can_admin?array("ancien"=>"Marquer comme ancien","delete"=>"Supprimer"):array(),
          $can_admin?array("anciens"=>"Marquer comme ancien","deletes"=>"Supprimer"):array(),
          array("role"=>$GLOBALS['ROLEASSO'] )
         );
    $cts->add($tbl,true);
  }

  if ( $can_admin )
  {
    $frm = new form("add","membres.php?id_asso=".$asso->id,false,"POST","Ajouter un membre");
    $frm->add_hidden("action","add");
    if ( $ErreurAdd )
      $frm->error($ErreurAdd);
    $frm->add_user_fieldv2("id_utilisateur","Membre");
    $frm->add_text_field("role_desc","Role (champ libre)","");

    //unset($GLOBALS['ROLEASSO'][ROLEASSO_MEMBRE]);
    //unset($GLOBALS['ROLEASSO'][ROLEASSO_MEMBREACTIF]);

    $frm->add_select_field("role","Role",$GLOBALS['ROLEASSO']);
    $frm->add_date_field("date_debut","Depuis le",time(),true);
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }
  elseif ( $asso->id_parent && !$limited )
  {
    $frm = new form("addme","membres.php?id_asso=".$asso->id,false,"POST","M'ajouter comme membre");
    if ( $ErreurAddMe )
      $frm->error($ErreurAddMe);
    $frm->add_hidden("action","addme");
    $frm->add_info("<b>Attention</b> : Si vous êtes membre du bureau (tresorier, secretaire...) ou membre actif veuillez vous adresser au responsable de l'association/du club. Si vous êtes le responsable, merci de vous adresser à l'AE. ");
    $frm->add_date_field("date_debut","Depuis le",time(),true);
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }
}


$site->add_contents($cts);
$site->end_page();

?>
