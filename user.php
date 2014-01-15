<?php
/* Copyright 2006,2007
 *
 * - Maxime Petazzoni < sam at bulix dot org >
 * - Laurent Colnat < laurent dot colnat at utbm dot fr >
 * - Julien Etelain < julien at pmad dot net >
 * - Benjamin Collet < bcollet at oxynux dot org >
 * - Pierre Mauduit <pierre dot mauduit at utbm dot fr>
 * - Manuel Vonthron <manuel dot vonthron at acadis dot org>
 *
 * Ce fichier fait partie du site de l'Association des étudiant
 * de l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

$topdir = "./";
require_once($topdir. "include/site.inc.php");
require_once($topdir . "include/cts/special.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/cts/user.inc.php");
require_once($topdir . "include/entities/carteae.inc.php");
require_once($topdir . "include/entities/cotisation.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/pays.inc.php");
require_once($topdir . "include/entities/edt.inc.php");
require_once($topdir . "jobetu/include/jobuser_etu.inc.php");

$site = new site ();
$site->add_css("css/userfullinfo.css");

$site->allow_only_logged_users("matmatronch");

if ( isset($_REQUEST['id_utilisateur']) )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);

  if ( !$user->is_valid() )
    $site->error_not_found("matmatronch");

  // Peut éditer une fiche:
  // - l'utilisateur en question
  // - les admins (gestion_ae)
  // - les membres du matmatonch qui ont un rôlesupérieur ou à égal à membre actif
  $can_edit = ( $user->id==$site->user->id || $site->user->is_in_group("gestion_ae") || $site->user->is_asso_role ( 27, 1 ));

  // Pour accdéder aux fiches matmatronch faut être cotisant, ou être utbm
  // ou vouloir consulter sa propre fiche
  if ( $user->id != $site->user->id && !$site->user->utbm && !$site->user->cotisant)
    $site->error_forbidden("matmatronch","group",10001);

  // Si la fiche n'est pas public, et qu'on ne peut pas l'éditer,
  // cela veut dire que l'on est i admin, ni l'utilisateur en question
  // donc on a pas le droit de la consulter
  if ( (($user->publique == 0) || ($user->publique == 1 && !$site->user->cotisant)) && !$can_edit )
    $site->error_forbidden("matmatronch","private");

}
else
{
  $user = &$site->user;
  $can_edit = true;
}

$ville = new ville($site->db);
$pays = new pays($site->db);

$ville_parents = new ville($site->db);
$pays_parents = new pays($site->db);

// Reinitialisation d'un compte
if ( $_REQUEST['action'] == "reinit" && $site->user->is_in_group("gestion_ae") )
{
  if ( $GLOBALS["svalid_call"] && ( !empty($user->email_utbm) || !empty($user->email) ) )
  {
    if ( $user->email_utbm )
      $email = $user->email_utbm;
    else
      $email = $user->email;
    $pass = genere_pass(10);
    $user->invalidate();
    $user->change_password($pass);

    $user->send_autopassword_email($email,$pass);
    $Notice = "Compte re-initialisé";
  }
}
// Suppresion d'une participation dans une activité ou association
elseif ( $_REQUEST["action"] == "delete" && $can_edit && isset($_REQUEST["id_membership"]))
{
  $_REQUEST["view"]="assos";
  list($id_asso,$date_debut) = explode(",",$_REQUEST["id_membership"]);
  $asso = new asso($site->db,$site->dbrw);
  $asso->load_by_id($id_asso);
  $asso->remove_member($user->id, strtotime($date_debut));
}
// Passage en ancien membre dans une activité ou association dans la quelle l'utilisateur
// est actuellement membre
elseif ( $_REQUEST["action"] == "stop" && $can_edit && isset($_REQUEST["id_membership"]))
{
  $_REQUEST["view"]="assos";
  list($id_asso,$date_debut) = explode(",",$_REQUEST["id_membership"]);
  $asso = new asso($site->db,$site->dbrw);
  $asso->load_by_id($id_asso);
  $asso->make_former_member($user->id, time());
}
// Sauvgarde des information personelle
elseif ( $_REQUEST["action"] == "saveinfos" && $can_edit )
{
  if(empty($_REQUEST["alias"]) || (preg_match("#^([a-z0-9][a-z0-9\.]+[a-z0-9])$#i",$user->alias) && !$site->user->is_in_group("root")))
    $_REQUEST["alias"] = $user->alias;

  if ( $_REQUEST["alias"] && !preg_match("#^([a-z0-9][a-z0-9\.]+[a-z0-9])$#i",$_REQUEST["alias"]) )
  {
    $ErreurMAJ = "Alias invalide, utilisez seulement des caractères alphanumériques, des points (jamais à la fin). L'alias doit avoir au moins trois caractères.";
    $_REQUEST["page"] = "edit";
  }
  elseif ( $_REQUEST["alias"] && !$user->is_alias_avaible($_REQUEST["alias"]) )
  {
    $ErreurMAJ = "Alias d&eacute;j&agrave;  utilis&eacute;";
    $_REQUEST["page"] = "edit";
  }
  elseif ( $_REQUEST['jabber'] && !CheckEmail($_REQUEST['jabber'], 3) )
  {
    $ErreurMAJ = "Adresse jabber invalide.";
    $_REQUEST["page"] = "edit";
  }
  else
  {
    $user->nom = $_REQUEST['nom'];
    $user->prenom = $_REQUEST['prenom'];
    $user->alias = $_REQUEST['alias'];
    if($site->user->is_in_group("gestion_ae") || $site->user->is_asso_role(27,1))
      $user->sexe = $_REQUEST['sexe'];
    $user->date_naissance = $_REQUEST['date_naissance'];
    $user->addresse = $_REQUEST['addresse'];
    if ( $_REQUEST['id_ville'] )
    {
      $ville->load_by_id($_REQUEST['id_ville']);
      $user->id_ville = $ville->id;
      $user->id_pays = $ville->id_pays;
    }
    else
    {
      $user->id_ville = null;
      $user->id_pays = $_REQUEST['id_pays'];
    }
    $user->tel_maison = telephone_userinput($_REQUEST['tel_maison']);
    $user->tel_portable = telephone_userinput($_REQUEST['tel_portable']);
    $user->date_maj = time();

    $user->publique = $_REQUEST["publique"];
    $user->publique_mmtpapier = isset($_REQUEST["publique_mmtpapier"]);

    $user->signature = $_REQUEST['signature'];

    $user->musicien = isset($_REQUEST['musicien']);
    $user->taille_tshirt = $_REQUEST['taille_tshirt'];
    $user->permis_conduire = isset($_REQUEST['permis_conduire']);
    $user->date_permis_conduire = $_REQUEST['date_permis_conduire'];
    $user->hab_elect = isset($_REQUEST['hab_elect']);
    $user->afps = isset($_REQUEST['afps']);
    $user->sst = isset($_REQUEST['sst']);

    $user->jabber = $_REQUEST['jabber'];

    $req = new requete($site->db,"SELECT mmt_instru_musique.id_instru_musique, ".
      "utl_joue_instru.id_utilisateur ".
      "FROM mmt_instru_musique ".
      "LEFT JOIN utl_joue_instru ".
        "ON (`utl_joue_instru`.`id_instru_musique`=`mmt_instru_musique`.`id_instru_musique`" .
        " AND `utl_joue_instru`.`id_utilisateur`='".$user->id."' )".
      "ORDER BY nom_instru_musique");

    while ( $row = $req->get_row() )
    {
      if ( isset($_REQUEST['instru'][$row['id_instru_musique']]) && is_null($row['id_utilisateur']) )
        $user->add_instrument($row['id_instru_musique']);
      elseif ( !isset($_REQUEST['instru'][$row['id_instru_musique']]) && !is_null($row['id_utilisateur']) )
        $user->delete_instrument($row['id_instru_musique']);
    }

    if ( $user->etudiant || $user->ancien_etudiant )
    {
      $user->citation = $_REQUEST['citation'];
      $user->adresse_parents = $_REQUEST['adresse_parents'];
      $user->tel_parents = telephone_userinput($_REQUEST['tel_parents']);
      $user->nom_ecole_etudiant = $_REQUEST['nom_ecole'];

      if ( $_REQUEST['id_ville_parents'] )
      {
        $ville_parents->load_by_id($_REQUEST['id_ville_parents']);
        $user->id_ville_parents = $ville_parents->id;
        $user->id_pays_parents = $ville_parents->id_pays;
      }
      else
      {
        $user->id_ville_parents = null;
        $user->id_pays_parents = $_REQUEST['id_pays_parents'];
      }
    }
    if ( $user->utbm )
    {
      $user->surnom = $_REQUEST['surnom'];
      $user->semestre = $_REQUEST['semestre'];
      $user->role = $_REQUEST['role'];
      $user->departement = $_REQUEST['departement'];
      $user->filiere = $_REQUEST['filiere'];
      $user->promo_utbm = $_REQUEST['promo'];

      if ( $_REQUEST['date_diplome'] < time()
        && $_REQUEST['date_diplome'] != 0
        && $_REQUEST['date_diplome'] != "" )
        $user->date_diplome_utbm = $_REQUEST['date_diplome'];
      else
        $user->date_diplome_utbm = NULL;
    }
    if ($user->saveinfos())
    {
      if ( $site->user->id != $user->id )
        _log($site->dbrw,"Édition d'une fiche matmatronch par un tierce","Fiche matmatronch de <a href=\"../user.php?id_utilisateur=".$user->id."\" >".$user->nom." ".$user->prenom." (id : ".$user->id.")</a> modifiée","Fiche MMT",$site->user);
      header("Location: ".$topdir."user.php?id_utilisateur=".$user->id);
      exit();
    }
  }
}
// Changement de mot de passe
elseif ( $_REQUEST["action"] == "changepassword" && $can_edit )
{
  if ( $_REQUEST["ae2_password"] && ($_REQUEST["ae2_password"] == $_REQUEST["ae2_password2"]) )
    $user->change_password($_REQUEST["ae2_password"]);
  else
    $_REQUEST["page"] = "edit";
}
// Ajout d'un parrain
elseif ( $_REQUEST["action"] == "addparrain" && $can_edit )
{
  $user2 = new utilisateur($site->db);
  $user2->load_by_id($_REQUEST["id_utilisateur_parrain"]);
  if ( $user2->id > 0 )
    {
      if ( $user2->id == $user->id )
        $ErreurParrain = "On joue pas au boulet !";
      else
        $user->add_parrain($user2->id);
    }
  else
    $ErreurParrain = "Utilisateur inconnu.";
}
// Ajout d'un fillot
elseif ( $_REQUEST["action"] == "addfillot" && $can_edit )
{
  $user2 = new utilisateur($site->db);
  $user2->load_by_id($_REQUEST["id_utilisateur_fillot"]);
  if ( $user2->id > 0 )
  {
    if ( $user2->id == $user->id )
      $ErreurParrain = "On joue pas au boulet !";
    else
      $user->add_fillot($user2->id);
  }
  else
    $ErreurFillot = "Utilisateur inconnu.";
}
// Definition des groupe
elseif ( $_REQUEST["action"] == "setgroups" &&
         ($site->user->is_in_group("gestion_ae")
         ||$site->user->is_in_group("root")) )
{
  $req = new requete($site->db,
                     "SELECT `groupe`.`id_groupe`, `groupe`.`nom_groupe`, `utl_groupe`.`id_utilisateur` ".
                     "FROM `groupe` " .
                     "LEFT JOIN `utl_groupe` ON (`groupe`.`id_groupe`=`utl_groupe`.`id_groupe` " .
                     " AND `utl_groupe`.`id_utilisateur`='".$user->id."' ) " .
                     "ORDER BY `groupe`.`nom_groupe`");

  while ( $row=$req->get_row())
  {
    $new=$_REQUEST["groups"][$row["id_groupe"]]==true;
    $old=$row["id_utilisateur"]!="";
    if ( $new != $old )
    {
      $safe_groups = array (50 /* visu_cotisants */, 29 /* blacklist_machines */,
                      30 /* gestion_machines */, 52 /* gestion_fimu */,
                      42 /* nouveaux_diplomes */ );

      if (!$site->user->is_in_group_id ($row["id_groupe"]) && !$site->user->is_in_group("root")
          && !($site->user->is_in_group("gestion_ae") && in_array ($row["id_groupe"], $safe_groups)))
        continue;

      if ( $new )
      {
        if ( ($row["id_groupe"] != 7 && $row["id_groupe"] != 46 && $row["id_groupe"] != 47) || $site->user->is_in_group("root") )
        {
          $user->add_to_group($row["id_groupe"]);
          _log($site->dbrw,"Ajout d'un utilisateur au groupe ". $row["nom_groupe"],"Ajout de l'utilisateur ".$user->nom." ".$user->prenom." (id : ".$user->id.") au groupe ". $row["nom_groupe"] ." (id : ".$row["id_groupe"].")","Groupes",$site->user);
        }
      }
      else
      {
        if ( ($row["id_groupe"] != 7 && $row["id_groupe"] != 46 && $row["id_groupe"] != 47) || $site->user->is_in_group("root") )
        {
          $user->remove_from_group($row["id_groupe"]);
          _log($site->dbrw,"Retrait d'un utilisateur du groupe ". $row["nom_groupe"],"Retrait de l'utilisateur ".$user->nom." ".$user->prenom." (id : ".$user->id.") du groupe ". $row["nom_groupe"] ." (id : ".$row["id_groupe"].")","Groupes",$site->user);
        }
      }
    }
  }
}
// Definition des flag
elseif ( $_REQUEST["action"] == "setattributes" &&
         (($site->user->is_in_group("gestion_ae") && $site->user->id != $user->id )
         ||$site->user->is_in_group("root")) )
{
  if ( isset($_REQUEST["etudiant"]) || isset($_REQUEST["ancien_etudiant"]) )
    $user->became_etudiant (
        is_null($user->nom_ecole_etudiant)?"":$user->nom_ecole_etudiant,
        isset($_REQUEST["ancien_etudiant"]),
        true );
}
// Ajout de l'utilisateur comme membre d'une activité ou association
// Vu que cette opération est faite sans contrôle, le seul rôle possible est ROLEASSO_MEMBRE
elseif ( $_REQUEST["action"]=="addme" )
{
  $asso = new asso($site->db,$site->dbrw);
  $asso->load_by_id($_REQUEST["id_asso"]);

  if ( $asso->id > 0 && $asso->id_parent )
  {
    if ( ($_REQUEST["date_debut"] <= time()) && ($_REQUEST["date_debut"] > 0) )
      $asso->add_actual_member ( $user->id, $_REQUEST["date_debut"], ROLEASSO_MEMBRE, $_REQUEST["role_desc"] );
    else
      $ErreurAddMe = "Donn&eacute;es invalides";
  }
  else
    $ErreurAddMe = "Non autoris&eacute; sur cette association.";

}
// Ajout de l'utilisateur comme ancien membre d'une activité ou association
elseif ( $_REQUEST["action"]=="addmeformer" )
{
  $asso = new asso($site->db,$site->dbrw);
  $asso->load_by_id($_REQUEST["id_asso"]);

  if ( $asso->id > 0 )
  {
    if ($asso->id_parent < 1 && $_REQUEST["role"] < 2)
      $ErreurAddMeFormer = "Non autoris&eacute; sur cette association.";

    elseif ( isset($GLOBALS['ROLEASSO'][$_REQUEST["role"]]) &&
              ($_REQUEST["former_date_debut"] < $_REQUEST["former_date_fin"]) &&
              ($_REQUEST["former_date_fin"] < time()) && ($_REQUEST["former_date_debut"] > 0) )
      $asso->add_former_member ( $user->id, $_REQUEST["former_date_debut"],
                                  $_REQUEST["former_date_fin"], $_REQUEST["role"], $_REQUEST["role_desc"] );
    else
      $ErreurAddMeFormer = "Données invalides";
  }
}
// Suppression d'un parrain
elseif ( $_REQUEST["action"] == "delete" && $_REQUEST["mode"] == "parrain" && $can_edit  )
{
  $user->remove_parrain($_REQUEST["id_utilisateur2"]);
}
// Surppression d'un fillot
elseif ( $_REQUEST["action"] == "delete" && $_REQUEST["mode"] == "fillot" && $can_edit  )
{
  $user->remove_fillot($_REQUEST["id_utilisateur2"]);
}
// Changemement d'adresse e-mail principale
elseif ( $_REQUEST["action"] == "changeemail" && $can_edit  )
{
  if ( !CheckEmail($_POST["email"], 3) )
  {
    $ErreurMail="Adresse email invalide.";
    $_REQUEST["page"] = "edit";
    $_REQUEST["open"]="email";
  }
  else
  {
    $user->set_email($_POST["email"], $site->user->is_in_group("gestion_ae"));

    if ( $site->user->is_in_group("gestion_ae") )
      $Notice = "Adresse e-mail principale modifiée";
    else
    {
      $site->start_page("matmatronch",$user->prenom." ".$user->nom);
      $cts = new contents ($user->prenom . " " . $user->nom );

      $cts->add_paragraph("Votre adresse e-mail principale a été modifiée");

      $cts->add_paragraph("Vous allez recevoir un e-mail de vérification à l'adresse ".$_POST["email"].". Vous devrez cliquer sur le lien se trouvant dans cet e-mail piur pouvoir utiliser de nouveau le site.");

      $cts->add_paragraph("Pour plus d'informations, ou si vous ne recevez pas l'email, consultez la documentation : <a href=\"article.php?name=docs:profil\">Documentation : Profil personnel : Questions et problèmes fréquents</a>");

      $site->add_contents($cts);
      $site->end_page();
      exit();
    }
  }
}
// Definition ou changement d'adresse e-mail utbm
elseif ( $_REQUEST["action"] == "changeemailutbm" && $can_edit  )
{
  if ( !CheckEmail($_POST["email_utbm"], 1) && !CheckEmail($_POST["email_utbm"], 2)
       && !($site->user->ancien_etudiant && CheckEmail($_POST["email_utbm"], 3)) ) /* Si la personne est ancien étudiant, lui laissé mettre une adresse générique post-diplome */
  {
    $ErreurMailUtbm="Adresse email invalide : prenom.nom@utbm.fr ou prenom.nom@assidu-utbm.fr";
    $_REQUEST["page"] = "edit";
    $_REQUEST["open"]="email";
  }
  else
  {
    if ( !$user->utbm )
    {
      $user->became_utbm($_POST["email_utbm"], $site->user->is_in_group("gestion_ae"));
      $lex = "définie";
    }
    else
    {
      $user->set_email_utbm($_POST["email_utbm"], $site->user->is_in_group("gestion_ae"));
      $lex = "modifiée";
    }

    if ( $site->user->is_in_group("gestion_ae") )
      $Notice = "Adresse e-mail utbm $lex";
    else
    {
      $site->start_page("matmatronch",$user->prenom." ".$user->nom);
      $cts = new contents ($user->prenom . " " . $user->nom );

      $cts->add_paragraph("Votre adresse e-mail utbm a été $lex");

      $cts->add_paragraph("Vous allez recevoir un e-mail de vérification à l'adresse ".$_POST["email"].". Vous devrez cliquer sur le lien se trouvant dans cet e-mail piur pouvoir utiliser de nouveau le site.");

      $cts->add_paragraph("Pour plus d'informations, ou si vous ne recevez pas l'email, consultez la documentation : <a href=\"article.php?name=docs:profil\">Documentation : Profil personnel : Questions et problèmes fréquents</a>");

      $site->add_contents($cts);
      $site->end_page();
      exit();
    }
  }
}
elseif ( $_REQUEST["action"] == "reprint" && $site->user->is_in_group("gestion_ae") )
{
  $carte = new carteae($site->db,$site->dbrw);
  $carte->load_by_utilisateur($user->id);
  $carte->set_state(CETAT_ATTENTE);
}
elseif ( $_REQUEST["action"] == "retrait" && $site->user->is_in_group("gestion_ae") )
{
  $carte = new carteae($site->db,$site->dbrw);
  $carte->load_by_utilisateur($user->id);
  $carte->set_state(CETAT_CIRCULATION);
}
elseif ( $_REQUEST["action"] == "cadeau" && $site->user->is_in_group("gestion_ae") )
{
  $carte = new carteae($site->db,$site->dbrw);
  $cotiz = new cotisation($site->db,$site->dbrw);
  $carte->load_by_utilisateur($user->id);
  $cotiz->load_by_id($carte->id_cotisation);
  $cotiz->mark_cadeau();
}
elseif ( $_REQUEST["action"] == "serviceident" && $can_edit  )
{
  $user->gen_serviceident();
}

if ( $_REQUEST["action"] == "setphotos" && $can_edit && is_dir("/data/matmatronch/") )
{
  $dest_idt = "/data/matmatronch/".$user->id.".identity.jpg";
  if ( is_uploaded_file($_FILES['idtfile']['tmp_name'])  )
  {
    $src = $_FILES['idtfile']['tmp_name'];
    if ( !file_exists($dest_idt) ||  // S'il n'y a pas de photo
         ($site->user->is_asso_role ( 27, 1 )) || // ou MMT
         ($site->user->is_in_group("gestion_ae"))) // ou gestion_ae
    {
      exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 225x300 $dest_idt"));
    }
  }

  $dest_mmt = "/data/matmatronch/".$user->id.".jpg";
  if( isset($_REQUEST['delete_mmt']) && file_exists($dest_mmt))
    unlink($dest_mmt);
  if ( is_uploaded_file($_FILES['mmtfile']['tmp_name'])  )
  {
    $src = $_FILES['mmtfile']['tmp_name'];
    exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 225x300 $dest_mmt"));
  }

  $dest_idt = "/data/matmatronch/".$user->id.".identity.jpg";
  if(isset($_REQUEST['delete_idt']) && file_exists($dest_idt)
     && ($site->user->is_asso_role ( 27, 1 )
   || $site->user->is_in_group("gestion_ae")))
    unlink($dest_idt);

  $_REQUEST["page"] = "edit";
  $_REQUEST["open"] = "photo";
}

if ( $_REQUEST["action"] == "setblouse" && $can_edit )
{
  $dest = "/data/matmatronch/".$user->id.".blouse.jpg";
  $dest_mini = "/data/matmatronch/".$user->id.".blouse.mini.jpg";
  if( isset($_REQUEST['delete_blouse']) && file_exists($dest))
  {
    unlink($dest);
    unlink($dest_mini);
  }
  if ( is_uploaded_file($_FILES['blousefile']['tmp_name'])  )
  {
    $src = $_FILES['blousefile']['tmp_name'];
    exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 1600x1600 -quality 80 $dest"));
    exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 225x300 -quality 90 $dest_mini"));
  }
  $_REQUEST["page"] = "edit";
  $_REQUEST["open"] = "blouse";
}

if ( $_REQUEST['action'] == 'settrombi' && $can_edit ) {
  require_once ($topdir . 'include/entities/trombino.inc.php');

  $trb = new trombino($site->db, $site->dbrw);
  $result = $trb->load_by_id ($user->id);
  $autorisation = $trb->autorisation;

  $trb->autorisation = $_REQUEST['autorisation'] == true;
  if ($autorisation) {
    $trb->photo = $_REQUEST['photo'] == true;
    $trb->infos_personnelles = $_REQUEST['infos_personnelles'] == true;
    $trb->famille = $_REQUEST['famille'] == true;
    $trb->associatif = $_REQUEST['associatif'] == true;
    $trb->commentaires = $_REQUEST['commentaires'] == true;
  }

  if ($result)
    $trb->update();
  else
    $trb->create($user->id);

  $_REQUEST['see'] = 'trombi';
  $_REQUEST['page'] = 'edit';
}

$tabs = $user->get_tabs($site->user);

if ( $_REQUEST["page"] == "edit" && $can_edit )
{
  $site->start_page("matmatronch",$user->prenom." ".$user->nom);

  $user->load_all_extra();

  $ville->load_by_id($user->id_ville);
  $pays->load_by_id($user->id_pays);

  $cts = new contents($user->prenom." ".$user->nom);

  // Legacy support
  if ( isset($_REQUEST["open"]) && ( $_REQUEST["open"]=="email" || $_REQUEST["open"]=="emailutbm") )
    $_REQUEST["see"] = "email";

  $cts->add(new tabshead($tabs,$_REQUEST["view"]));

  $cts->add(new tabshead(array(
    array("","user.php?page=edit&id_utilisateur=".$user->id,"Information personnelles"),
    array("email","user.php?see=email&page=edit&id_utilisateur=".$user->id,"Adresses E-Mail"),
    array("passwd","user.php?see=passwd&page=edit&id_utilisateur=".$user->id,"Mot de passe"),
    array("photos","user.php?see=photos&page=edit&id_utilisateur=".$user->id,"Photo/Avatar/Blouse"),
    array('trombi', 'user.php?see=trombi&page=edit&id_utilisateur='.$user->id,'Trombinoscope')
    ),
    isset($_REQUEST["see"])?$_REQUEST["see"]:"","","subtab"));

  if ( !isset($_REQUEST["see"]) || empty($_REQUEST["see"]) )
  {
    $frm = new form("infoperso","user.php?id_utilisateur=".$user->id,true,"POST","Informations personelles");
    $frm->add_hidden("action","saveinfos");
    if ( $ErreurMAJ )
      $frm->error($ErreurMAJ);
    if ($site->user->is_asso_role ( 27, 1 ) || $site->user->is_in_group("gestion_ae") )
     {
      $frm->add_text_field("nom","Nom",$user->nom,true,false,false,true);
      $frm->add_text_field("prenom","Prenom",$user->prenom,true,false,false,true);
    }
    else
    {
      $frm->add_text_field("nom","Nom",$user->nom,true,false,false,false);
      $frm->add_text_field("prenom","Prenom",$user->prenom,true,false,false,false);
      $frm->add_hidden("nom", $user->nom);
      $frm->add_hidden("prenom", $user->prenom);
    }

    $req = new requete($site->db,"SELECT `id_utilisateur` FROM `svn_member_depot` WHERE `id_utilisateur`='".$user->id."'");
    if($req->lines != 0 && $site->user->is_in_group("root"))
      $can_edit_alias = false;
    else
      $can_edit_alias = true;

    if (empty($user->alias) || !preg_match("#^([a-z0-9][a-z0-9\.]+[a-z0-9])$#i",strtolower($user->alias)))
      $frm->add_text_field("alias","Alias",$user->alias);
    else // seul root a le droit de modifier l'alias s'il est déjà renseigné
      $frm->add_text_field("alias","Alias",$user->alias,false,false,false,$can_edit_alias);

    $frm->add_text_field("jabber","Jabber/Google Talk",$user->jabber);

    if ( $user->utbm )
      $frm->add_text_field("surnom","Surnom (utbm)",$user->surnom);
    $frm->add_select_field("sexe","Sexe",array(1=>"Homme",2=>"Femme"),$user->sexe,false,false,($site->user->is_in_group("gestion_ae") || $site->user->is_asso_role(27,1)));
    $frm->add_date_field("date_naissance","Date de naissance",$user->date_naissance);

    $frm->add_select_field("taille_tshirt","Taille de t-shirt (non publié***)",array(0=>"-",
      "XS"=>"XS","S"=>"S","M"=>"M","L"=>"L","XL"=>"XL","XXL"=>"XXL","XXXL"=>"XXXL"),$user->taille_tshirt);

    if ( $user->utbm )
    {
      $frm->add_select_field("role","Role",$GLOBALS["utbm_roles"],$user->role);
      $frm->add_select_field("departement","Departement",$GLOBALS["utbm_departements"],$user->departement);

      $frm->add_text_field("semestre","Semestre",$user->semestre);
    }

    // Permis de conduire
    $subfrm = new form("permis_conduire",null,null,null,"Permis de conduire (informations non publiées**)");
    $subfrm->add_date_field("date_permis_conduire","Date d'obtention (non publiée)", $user->date_permis_conduire);
    $frm->add ( $subfrm, true, false, $user->permis_conduire, false, false, true );

    // Musicien
    $subfrm = new form("musicien",null,null,null,"Musicien");
    $req = new requete($site->db,"SELECT mmt_instru_musique.id_instru_musique, ".
      "mmt_instru_musique.nom_instru_musique, ".
      "utl_joue_instru.id_utilisateur ".
      "FROM mmt_instru_musique ".
      "LEFT JOIN utl_joue_instru ".
        "ON (`utl_joue_instru`.`id_instru_musique`=`mmt_instru_musique`.`id_instru_musique`" .
        " AND `utl_joue_instru`.`id_utilisateur`='".$user->id."' )".
      "ORDER BY nom_instru_musique");

    while ( $row = $req->get_row() )
      $subfrm->add_checkbox("instru[".$row['id_instru_musique']."]",$row['nom_instru_musique'], !is_null($row['id_utilisateur']));
    $frm->add ( $subfrm, true, false, $user->musicien, false, false, true );

    $subfrm1 = new form("infocontact",null,null,null,"Adresse et téléphone");

    $subfrm1->add_text_field("addresse","Adresse",$user->addresse);

    $subfrm1->add_entity_smartselect ("id_pays","ou pays", $pays,true);
    $subfrm1->add_entity_smartselect ("id_ville","Ville", $ville,true,false,array('id_pays'=>'id_pays_id'),true);

    $subfrm1->add_text_field("tel_maison","Telephone (fixe)",$user->tel_maison);
    $subfrm1->add_text_field("tel_portable","Telephone (portable)",$user->tel_portable);
    $frm->add ( $subfrm1, false, false, false, false, false, true, false );

    if ( $user->etudiant || $user->ancien_etudiant )
    {
      $ville_parents->load_by_id($user->id_ville_parents);
      $pays_parents->load_by_id($user->id_pays_parents);

      $subfrm2 = new form("infoextra",null,null,null,"Informations suppl&eacute;mentaires");
      $subfrm2->add_text_field("citation","Citation",$user->citation,false,"60");
      $subfrm2->add_text_field("nom_ecole","Ecole",$user->nom_ecole_etudiant);
      $frm->add ( $subfrm2, false, false, false, false, false, true, false );

      $subfrm3 = new form("infoparents",null,null,null,"Informations sur les parents");
      $subfrm3->add_text_field("adresse_parents","Adresse parents",$user->adresse_parents);

      $subfrm3->add_entity_smartselect ("id_pays_parents","ou pays parents", $pays_parents,true);
      $subfrm3->add_entity_smartselect ("id_ville_parents","Ville parents", $ville_parents,true,false,array('id_pays'=>'id_pays_parents_id'),true);

      $subfrm3->add_text_field("tel_parents","T&eacute;l&eacute;phone parents",$user->tel_parents);
      $frm->add ( $subfrm3, false, false, false, false, false, true, false );
    }

    if ( $user->utbm )
    {
      $subfrm4 = new form("infoutbm",null,null,null,"Informations UTBM");

      $subfrm4->add_text_field("filiere","Filiere",$user->filiere);

      $subfrm4->add_select_field("promo","Promo",$user->liste_promos("-"),$user->promo_utbm);
      $subfrm4->add_date_field("date_diplome","Date d'obtention du diplome",($user->date_diplome_utbm!=NULL)?$user->date_diplome_utbm:null);
      $frm->add ( $subfrm4, false, false, false, false, false, true, false );
    }



    $subfrm = new form(null,null,null,null,"Habilitations (informations non publiées**)");
    $subfrm->add_checkbox ( "hab_elect", "Habilitation électrique", $user->hab_elect );
    $subfrm->add_checkbox ( "afps", "Attestation de Formation aux Permiers Secours (AFPS)", $user->afps );
    $subfrm->add_checkbox ( "sst", "Sauveteur Secouriste du Travail (SST)", $user->sst );
    $frm->add ( $subfrm, false, false, false, false, false, true, false );

    //signature
    $frm->add_text_area("signature","Signature (forum)",$user->signature);

    $frm->add_radiobox_field ( "publique", "Publicité de mon profil",
      array(2=>"Permettre à tous les membres de l'AE, de l'utbm ou anciens de l'utbm de voir mon profil",
            1=>"Limiter l'accès à mon profil aux membres de l'AE",
            0=>"Ne pas rendre mon profil public"),
      $user->publique, -1, false, array(), false );

    $frm->add_checkbox ( "publique_mmtpapier", "Autoriser la publication de mon profil dans le matmatronch papier.", $user->publique_mmtpapier );

    $frm->add_submit("save","Enregistrer");
    $cts->add($frm,true);

    $cts->add_paragraph("** Ces informations ne seront pas rendues publiques, elles pourrons être utilisées pour pouvoir vous contacter si l'association recherche des bénévoles particuliers.");
    $cts->add_paragraph("*** La taille de t-shirt est collectée à des fins statistiques, pour commander le nombre de t-shirt par taille au plus juste pour le cadeau offert avec une cotisation, ou lors des différents évenements.");
    $cts->add_paragraph("&nbsp;");

    $cts->add(new itemlist("Modification des autres informations",false,array(
    "<a href=\"user.php?see=email&amp;page=edit&amp;id_utilisateur=".$user->id."\">Adresses e-mail (personelle et utbm)</a>",
    "<a href=\"user.php?see=passwd&amp;page=edit&amp;id_utilisateur=".$user->id."\">Mot de passe</a>",
    "<a href=\"user.php?see=photos&amp;page=edit&amp;id_utilisateur=".$user->id."\">Photo d'identité, avatar et blouse</a>"
    )),true);

  }
  elseif ( $_REQUEST["see"] == "email" )
  {

    $frm = new form("changeemail","user.php?id_utilisateur=".$user->id,true,"POST","Adresse email principale");
    if ( $ErreurMail )
      $frm->error($ErreurMail);
    $frm->add_hidden("action","changeemail");
    $frm->add_info("<b>Attention:</b> Votre compte sera d&eacute;sactiv&eacute; et votre session sera ferm&eacute;e jusqu'&agrave; validation du lien qui vous sera envoye par email &agrave; l'adresse que vous pr&eacute;ciserez !");

    $frm->add_text_field("email","Adresse email",$user->email,true);
    $frm->add_submit("save","Enregistrer");
    $cts->add($frm,true);

    $cts->add_paragraph("<b>Remarque:</b> Votre adresse e-mail principale est utilisée pour les mailing listes. Si vous changer votre adresse, les mailing listes seront mises à jours au bout de 60 minutes environs.");
    $cts->add_paragraph("<b>Attention:</b> Pour envoyer des messages sur les mailing listes vous devez le faire depuis votre adresse e-mail principale.");

    $frm = new form("changeemailutbm","user.php?id_utilisateur=".$user->id,true,"POST","Adresse email UTBM ou ASSIDU");
    if ( $ErreurMailUtbm )
      $frm->error($ErreurMailUtbm);
    $frm->add_hidden("action","changeemailutbm");
    $frm->add_info("<b>Attention:</b> Votre compte sera d&eacute;sactiv&eacute; et votre session sera ferm&eacute;e jusqu'&agrave; validation du lien qui vous sera envoye par email &agrave; l'adresse que vous pr&eacute;ciserez !");
    $frm->add_text_field("email_utbm","Adresse email",$user->email_utbm?$user->email_utbm:"prenom.nom@utbm.fr",true);

    $frm->add_submit("save","Enregistrer");
    $cts->add($frm,true);

  }
  elseif ( $_REQUEST["see"] == "passwd" )
  {

    $frm = new form("changepassword","user.php?id_utilisateur=".$user->id,true,"POST","Changer de mot de passe");
    $frm->add_hidden("action","changepassword");
    $frm->add_password_field("ae2_password","Mot de passe","",true);
    $frm->add_password_field("ae2_password2","Repetez le mot de passe","",true);
    $frm->add_submit("save","Enregistrer");
    $cts->add($frm,true);

  }
  elseif ( $_REQUEST["see"] == "photos" )
  {

    $frm = new form("setphotos","user.php?id_utilisateur=".$user->id."#setphotos",true,"POST","Changer mes photos persos");
    $frm->add_hidden("action","setphotos");

    $subfrm = new form("mmt",null,null,null,"Avatar");
    if ( file_exists( $topdir."data/matmatronch/".$user->id.".jpg") )
    {
      $subfrm->add_info("<img src=\"".$topdir."data/matmatronch/".$user->id.".jpg?".filemtime($topdir."data/matmatronch/".$user->id.".jpg")."\" alt=\"\" width=\"100\" /><br/>");
    }
    $subfrm->add_file_field ( "mmtfile", "Fichier" );
    $subfrm->add_checkbox("delete_mmt","Supprimer mon avatar");
    $frm->add ( $subfrm );

    $subfrm = new form("idt",null,null,null,"Photo identit&eacute; (carte AE et matmatronch)");

    if ( file_exists( $topdir."data/matmatronch/".$user->id.".identity.jpg") )
    {
      $subfrm->add_info("<img src=\"".$topdir."data/matmatronch/".$user->id.".identity.jpg?".filemtime($topdir."data/matmatronch/".$user->id.".identity.jpg")."\" alt=\"\" width=\"100\" /><br/>");

      if ($site->user->is_asso_role ( 27, 1 ) || $site->user->is_in_group("gestion_ae"))
      {
        $subfrm->add_file_field ( "idtfile", "Fichier" );
        $carte = new carteae($site->db);
        $carte->load_by_utilisateur($site->user->id);
        // feature request tatid : suppression de la photo d'identité
        //if ( !$carte->is_validcard() )
        $subfrm->add_checkbox("delete_idt","Supprimer la photo d'identit&eacute;");
      }
    }
    else
    {
      $subfrm->add_file_field ( "idtfile", "Fichier" );
      $subfrm->add_info("Vous devez être reconnaissable sur la photo. Dans le cas contraire, celle-ci sera supprimée.");
    }

    $frm->add ( $subfrm );
    $frm->add_submit("save","Enregistrer");

    $cts->add($frm,true);

    $frm = new form("setblouse","user.php?id_utilisateur=".$user->id."#setblouse",true,"POST","Changer la photo de ma blouse");
    $frm->add_hidden("action","setblouse");
    $subfrm = new form("blouse",null,null,null,"Photo de la blouse");

    if ( file_exists( $topdir."data/matmatronch/".$user->id.".blouse.mini.jpg") )
      $subfrm->add_info("<img src=\"".$topdir."data/matmatronch/".$user->id.".blouse.mini.jpg\" alt=\"\" width=\"100\" /><br/>");

    $subfrm->add_file_field ( "blousefile", "Fichier" );
    $subfrm->add_checkbox("delete_blouse","Supprimer la photo de ma blouse");
    $frm->add ( $subfrm );
    $frm->add_submit("save","Enregistrer");

    $cts->add($frm,true);
  }
  elseif ( $_REQUEST['see'] == 'trombi' ) {
    require_once($topdir . 'include/entities/trombino.inc.php');

    $cts->add_paragraph('Grâce à cette page, vous pouvez modifier les options de confidentialité associées à votre profil Matmatronch qui seront utilisées pour la création du trombino papier de votre promo');

    $trb = new trombino ($site->db, $site->dbrw);
    $result = $trb->load_by_id($user->id);
    $autorisation = $result ? $trb->autorisation : false;

    $frm = new form('settrombi', 'user.php?id_utilisateur='.$user->id, true, 'POST', 'Changer mes paramètres du trombino');
    $frm->add_hidden('action', 'settrombi');
    $frm->add_info('<h4>Autorisation</h4>');
    $frm->add_checkbox('autorisation', 'Publier mon profil Matmatronch dans le trombino de promo (nom et prénom au minimum)', $autorisation);
    $frm->add_info('<h4>Options de confidentialité</h4>');
    $frm->add_info("Note : il est nécessaire d'enregistrer votre autorisation de publication dans le trombino de promo pour pouvoir modifier ces paramètres");
    $frm->add_checkbox('photo', 'Autoriser ma photo d\'identité à apparaitre', $trb->photo, !$autorisation);
    $frm->add_checkbox('infos_personnelles', 'Autoriser mes informations personnelles à apparaitre (date de naissance, email, ...)', $trb->infos_personnelles, !$autorisation);
    $frm->add_checkbox('famille', 'Autoriser la mention de mes parrain(e)(s)/fillot(e)(s)', $trb->famille, !$autorisation);
    $frm->add_checkbox('associatif', 'Autoriser mon parcours associatif à apparaitre', $trb->associatif, !$autorisation);
    $frm->add_checkbox('commentaires', 'Autoriser les commentaires de mon profil à apparaitre', $trb->commentaires, !$autorisation);
    $frm->add_submit('save', 'Enregistrer');

    $cts->add($frm);
  }

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("matmatronch", $user->prenom . " " . $user->nom );

$cts = new contents ($user->prenom . " " . $user->nom );

$cts->add(new tabshead($tabs,$_REQUEST["view"]));

if ( $_REQUEST["view"]=="parrain" )
{
  $cts->add_paragraph("<a href=\"family.php?id_utilisateur=".$user->id."\">".
                      "Arbre g&eacute;n&eacute;alogique parrains/fillots</a>");

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur` AS `id_utilisateur2`, " .
    "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur2` " .
    "FROM `parrains` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`parrains`.`id_utilisateur` " .
    "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
    "WHERE `parrains`.`id_utilisateur_fillot`='".$user->id."'");

  $tbl = new sqltable(
    "listasso",
    "Parrain(s)/Marraine(s)", $req, "user.php?view=parrain&mode=parrain&id_utilisateur=".$user->id,
    "id_utilisateur2",
    array("nom_utilisateur2"=>"Parrain/Marraine"),
    array("delete"=>"Enlever"), array(), array( )
    );
  $cts->add($tbl,true);

  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur` AS `id_utilisateur2`, " .
    "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur2` " .
    "FROM `parrains` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`parrains`.`id_utilisateur_fillot` " .
    "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
    "WHERE `parrains`.`id_utilisateur`='".$user->id."'");

  $tbl = new sqltable(
    "listasso",
    "Fillot(s)/Fillote(s)", $req, "user.php?view=parrain&mode=fillot&id_utilisateur=".$user->id,
    "id_utilisateur2",
    array("nom_utilisateur2"=>"Fillot/Fillote"),
    array("delete"=>"Enlever"), array(), array( )
    );
  $cts->add($tbl,true);

  if ( $can_edit )
  {
    $frm = new form("addparrain","user.php?view=parrain&id_utilisateur=".$user->id,true,"POST","Ajouter un parrain/une marraine");
    $frm->add_hidden("action","addparrain");
    if ( $ErreurParrain ) $frm->error($ErreurParrain);
    $frm->add_user_fieldv2("id_utilisateur_parrain","Parrain");
    $frm->add_submit("addresp","Ajouter");
    $cts->add($frm,true);


    $frm = new form("addfillot","user.php?view=parrain&id_utilisateur=".$user->id,true,"POST","Ajouter un fillot/une fillote");
    $frm->add_hidden("action","addfillot");
    if ( $ErreurFillot ) $frm->error($ErreurFillot);
    $frm->add_user_fieldv2("id_utilisateur_fillot","Fillot");
    $frm->add_submit("addresp","Ajouter");
    $cts->add($frm,true);
  }

}

elseif ( $_REQUEST["view"]=="pedagogie" )
{
  require_once($topdir."pedagogie/include/pedag_user.inc.php");
  require_once($topdir."pedagogie/include/pedagogie.inc.php");
  $site->add_js("pedagogie/pedagogie.js");
  $p_user = new pedag_user($site->db);
  $p_user->load_by_id($user->id);

  $tab = array();
  $edts = $p_user->get_edt_list();
  if(!empty($edts))
  {
    foreach($edts as $edt)
    {
      $tab[$edt]['semestre'] = $edt;
      $tab[$edt]['semestre_bold'] = "<b>".$edt."</b>";
      $i=0;
      foreach($p_user->get_edt_detail($edt) as $uv){
        $tab[$edt]['code_'.++$i] = $uv['code'];
        $tab[$edt]['id_uv_'.$i] = $uv['id_uv'];
      }
    }
  }

  if(count($tab) > 1)
    sort_by_semester($tab, 'semestre');

  $cts->add(new sqltable("edtlist", "Liste des emplois du temps", $tab, $topdir."pedagogie/edt.php?id_utilisateur=".$user->id, 'semestre',
                          array("semestre_bold"=>"Semestre",
                                "code_1" => "UV 1",
                                "code_2" => "UV 2",
                                "code_3" => "UV 3",
                                "code_4" => "UV 4",
                                "code_5" => "UV 5",
                                "code_6" => "UV 6",
                                "code_7" => "UV 7"),
                          array("view" => "Voir détails",
                                "print" => "Format imprimable",
                                "schedule" => "Format iCal",
                                "delete" => "Supprimer"),
                          array(), array(), false), true);

  if ($site->user->id == $user->id) {
    $cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" "
                        ."value=\"+ Importer emploi du temps depuis le mail du SME\" "
                        ."onclick=\"edt.add_auto('pedagogie/');\" "
                        ."name=\"add_edt_auto\" id=\"add_edt_auto\"/>");

    $cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" "
                        ."value=\"+ Ajouter un emploi du temps\" "
                        ."onclick=\"edt.add('pedagogie/');\" "
                        ."name=\"add_edt\" id=\"add_edt\"/>");
  }

  /**
   * Affichage des CV
   */
  $cts->add_title(2, "CVs");
  $jobuser = new jobuser_etu($site->db);
  $jobuser->load_by_id( $user->id );
  if( $jobuser->is_jobetu_user() )
  {
                if($jobuser->load_pdf_cv() &&  $jobuser->public_cv)
                {
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

                        $lst = new itemlist(sizeof($jobuser->pdf_cvs) . " CV(s) disponible(s)");
                        foreach($jobuser->pdf_cvs as $cv)
                                $lst->add("<img src=\"$topdir/images/i18n/$cv.png\" />&nbsp; <a href=\"". $topdir . "var/cv/". $jobuser->id . "." . $cv .".pdf\"> CV en ". $i18n[ $cv ] ."</a>");

                        $cts->add($lst);
                }else{
                        $cts->add_paragraph("<p>Cet utilisateur n'a pas mis de CV en ligne ou n'a pas souhaité qu'ils soient publics</b>");
                }
  }else{
                $cts->add_paragraph("<p>Cet utilisateur n'a pas activé son compte Jobetu</b>");
  }

}
elseif ( $_REQUEST["view"]=="assos" )
{

  /* Associations en cours */
  $req = new requete($site->db,
    "SELECT `asso`.`id_asso`, `asso`.`nom_asso`, " .
    "IF(`asso`.`id_asso_parent` IS NULL,`asso_membre`.`role`+100,`asso_membre`.`role`) AS `role`, ".
    "`asso_membre`.`date_debut`, `asso_membre`.`desc_role`, " .
    "CONCAT(`asso`.`id_asso`,',',`asso_membre`.`date_debut`) as `id_membership` " .
    "FROM `asso_membre` " .
    "INNER JOIN `asso` ON `asso`.`id_asso`=`asso_membre`.`id_asso` " .
    "WHERE `asso_membre`.`id_utilisateur`='".$user->id."' " .
    "AND `asso_membre`.`date_fin` is NULL " .
    "AND `asso_membre`.`role` > '".ROLEASSO_MEMBRE."' " .
    "ORDER BY `asso`.`nom_asso`");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
      "listasso",
      "Associations et activités actuelles", $req, "user.php?id_utilisateur=".$user->id,
      "id_membership",
      array("nom_asso"=>"Association","role"=>"Role","desc_role"=>"","date_debut"=>"Depuis"),
      $can_edit?array("delete"=>"Supprimer","stop"=>"Arreter à la date de ce jour"):array(),
      array(), array("role"=>$GLOBALS['ROLEASSO100'])
      );
    $cts->add($tbl,true);
  }

  /* Inscriptions aux mailing-lists */
  $req = new requete($site->db,
    "SELECT `asso`.`id_asso`, `asso`.`nom_asso`, " .
    "CONCAT(`asso`.`id_asso`,',',`asso_membre`.`date_debut`) as `id_membership` " .
    "FROM `asso_membre` " .
    "INNER JOIN `asso` ON `asso`.`id_asso`=`asso_membre`.`id_asso` " .
    "WHERE `asso_membre`.`id_utilisateur`='".$user->id."' " .
    "AND `asso_membre`.`date_fin` is NULL " .
    "AND `asso_membre`.`role` = '".ROLEASSO_MEMBRE."' " .
    "ORDER BY `asso`.`nom_asso`");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
      "listml",
      "Inscription aux nouvelles des activités", $req, "user.php?id_utilisateur=".$user->id,
      "id_membership",
      array("nom_asso"=>"Association"),
      $can_edit?array("delete"=>"Désinscrire"):array(),
      array(), array("role"=>$GLOBALS['ROLEASSO100'])
      );
    $cts->add($tbl,true);
  }

  if ( $can_edit )
  {
    $frm = new form("addme","user.php?view=assos&id_utilisateur=".$user->id,false,"POST","S'inscrire aux nouvelles d'une activité");
    if ( $ErreurAddMe )
      $frm->error($ErreurAddMe);
    $frm->add_hidden("action","addme");
    $frm->add_info("<b>Attention</b> : Si vous &ecirc;tes membre du bureau (tresorier, secretaire...) ou membre actif veuillez vous adresser au responsable de l'association/du club. Si vous &ecirc;tes le responsable, merci de vous adresser à l'équipe informatique.");
    $frm->add_info("En tant que membre vous serez inscrit à la mailing liste de l'activité, vous receverez donc par e-mail toutes les informations sur l'activité.");
    $frm->add_entity_select ( "id_asso", "Association/Club", $site->db, "asso");
    $frm->add_date_field("date_debut","Depuis le",time(),true);
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }

  /* Anciennes assos */
  $req = new requete($site->db,
    "SELECT `asso`.`id_asso`, `asso`.`nom_asso`, " .
    "IF(`asso`.`id_asso_parent` IS NULL,`asso_membre`.`role`+100,`asso_membre`.`role`) AS `role`, ".
    "`asso_membre`.`date_debut`, `asso_membre`.`desc_role`, `asso_membre`.`date_fin`, " .
    "CONCAT(`asso`.`id_asso`,',',`asso_membre`.`date_debut`) as `id_membership` " .
    "FROM `asso_membre` " .
    "INNER JOIN `asso` ON `asso`.`id_asso`=`asso_membre`.`id_asso` " .
    "WHERE `asso_membre`.`id_utilisateur`='".$user->id."' " .
    "AND `asso_membre`.`date_fin` is NOT NULL " .
    "ORDER BY `asso`.`nom_asso`,`asso_membre`.`date_debut`");
  if ( $req->lines > 0 )
  {
    $tbl = new sqltable(
      "listassoformer",
      "Anciennes participation aux associations et activités", $req, "user.php?id_utilisateur=".$user->id,
      "id_membership",
      array("nom_asso"=>"Association","role"=>"Role","desc_role"=>"","date_debut"=>"Date de début","date_fin"=>"Date de fin"),
      $can_edit?array("delete"=>"Supprimer"):array(), array(), array("role"=>$GLOBALS['ROLEASSO100'] )
      );
    $cts->add($tbl,true);
  }

  unset($GLOBALS['ROLEASSO'][ROLEASSO_MEMBRE]);
  unset($GLOBALS['ROLEASSO'][ROLEASSO_MEMBREACTIF]);

  if ( $can_edit )
  {
    $frm = new form("addmeformer","user.php?view=assos&id_utilisateur=".$user->id,false,"POST","Ajouter une ancienne participation");
    $frm->add_hidden("action","addmeformer");
    if ( $ErreurAddMeFormer )
      $frm->error($ErreurAddMeFormer);
    $frm->add_entity_select ( "id_asso", "Association/Club", $site->db, "asso");
    $frm->add_text_field("role_desc","Role (champ libre)","");
    $frm->add_select_field("role","Role",$GLOBALS['ROLEASSO']);
    $frm->add_date_field("former_date_debut","Date de d&eacute;but",-1,true);
    $frm->add_date_field("former_date_fin","Date de fin",-1,true);
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }
}

elseif ( ($_REQUEST["view"]=="groups") &&
         (($site->user->is_in_group("gestion_ae") && $site->user->id != $user->id )
         ||$site->user->is_in_group("root")) )
{
  $user->load_all_extra();
  /* groupes */
  $frm = new form("setattributes","user.php?view=groups&id_utilisateur=".$user->id,false,"POST","Attributs");
  $frm->add_hidden("action","setattributes");
  $frm->add_checkbox("ae","ae",$user->ae,true);
  $frm->add_checkbox("assidu","assidu",$user->assidu,true);
  $frm->add_checkbox("amicale","amicale",$user->amicale,true);
  $frm->add_checkbox("crous","crous",$user->crous,true);
  $frm->add_checkbox("utbm","utbm",$user->utbm, !$user->email_utbm);
  $frm->add_checkbox("etudiant","etudiant",$user->etudiant);
  $frm->add_checkbox("ancien_etudiant","ancien_etudiant",$user->ancien_etudiant);

  $frm->add_submit("save","Enregistrer");
  $cts->add($frm,true);


  $req = new requete($site->db,
                     "SELECT `groupe`.`id_groupe`, `groupe`.`type_groupe`, `groupe`.`nom_groupe`, `groupe`.`description_groupe`, `utl_groupe`.`id_utilisateur` ".
                     "FROM `groupe` " .
                     "LEFT JOIN `utl_groupe` ON (`groupe`.`id_groupe`=`utl_groupe`.`id_groupe`" .
                     " AND `utl_groupe`.`id_utilisateur`='".$user->id."' ) " .
                     "ORDER BY `groupe`.`type_groupe` DESC, `groupe`.`nom_groupe`");

  $frm = new form("setgroups","user.php?view=groups&id_utilisateur=".$user->id,true,"POST","Groupes");
  $frm->add_hidden("action","setgroups");
  $grp = new group($site->db);

  $lastType = -1;
  while ( $row=$req->get_row())
  {
    $grp->_load($row);

    if ($grp->type != $lastType)
    {
      if ($lastType >= 0)
        $frm->add($subfrm);

      $lastType = $grp->type;
      $subfrm = new subform($grp->type, $grp->get_type_desc());
    }

    if ( ($row["id_groupe"] == 7 || $row["id_groupe"] == 46 || $row["id_groupe"] == 47) && !$site->user->is_in_group("root") )
      $subfrm->add_checkbox("groups|".$row["id_groupe"],$grp->get_html_link(),$row["id_utilisateur"]!="",true);
    else
      $subfrm->add_checkbox("groups|".$row["id_groupe"],$grp->get_html_link(),$row["id_utilisateur"]!="");
  }
  if ($lastType >= 0)
    $frm->add($subfrm);

  $frm->add_submit("save","Enregistrer");
  $cts->add($frm,true);

  if (isset ($_REQUEST['all'])) {
      $user->load_groups ();
      $frm = new form("dummygrps", "user.php?view=groups&id_utilisateur=".$user->id,true,"POST","Groupes (tout)");
      $frm->add_select_field ("allgrps", "Groupes (tout)", $user->groupes);
      $cts->add($frm, true);
  }
}
elseif ( ($_REQUEST["view"]=="stats") && $_REQUEST["graph"]=="stat_comptoir_jour" && ($user->etudiant || $user->ancien_etudiant) &&
         ($site->user->is_in_group("gestion_ae") || $site->user->id == $user->id ))
{	
	require_once($topdir . "include/graph.inc.php");
	$req = new requete($site->db, "SELECT SUM(montant_facture/100) as somme, HOUR(TIME(date_facture)) as heure 
			FROM `cpt_debitfacture` WHERE id_utilisateur_client = $user->id AND mode_paiement = 'AE' GROUP BY heure");
	$datas = array("Consommation" => "Consommation");
      	while ($row = $req->get_row())
        	$datas[$row['heure']] = $row['somme'];

      	$hist = new histogram($datas, "Consommation par heure");
	$hist->png_render();
      	$hist->destroy();

      	exit();
}
elseif ( ($_REQUEST["view"]=="stats") && $_REQUEST["graph"]=="stat_comptoir_semaine" && ($user->etudiant || $user->ancien_etudiant) &&
         ($site->user->is_in_group("gestion_ae") || $site->user->id == $user->id ))
{	
	require_once($topdir . "include/graph.inc.php");
	$req = new requete($site->db, "SELECT SUM(montant_facture/100) as somme, DAYOFWEEK(DATE(date_facture)) as jour 
			FROM `cpt_debitfacture` WHERE id_utilisateur_client = $user->id AND mode_paiement = 'AE' GROUP BY jour");
	$datas = array("Consommation" => "Consommation");
	$jour = array( 1 => "Dim", 2 => "Lun", 3 => "Mar", 4 => "Mer", 5 => "Jeu", 6 => "Ven", 7 => "Sam" );
      	while ($row = $req->get_row())
        	$datas[$jour[$row['jour']]] = $row['somme'];

      	$hist = new histogram($datas, "Consommation par jour de la semaine");
	$hist->png_render();
      	$hist->destroy();

      	exit();
}
elseif ( ($_REQUEST["view"]=="stats") && $_REQUEST["graph"]=="stat_comptoir_mois" && ($user->etudiant || $user->ancien_etudiant) &&
         ($site->user->is_in_group("gestion_ae") || $site->user->id == $user->id ))
{	
	require_once($topdir . "include/graph.inc.php");
	$req = new requete($site->db, "SELECT SUM(montant_facture/100) as somme, DATE_FORMAT(date_facture,'%m/%y') as mois, 
			date_facture as date
			FROM `cpt_debitfacture` WHERE id_utilisateur_client = $user->id AND mode_paiement = 'AE' GROUP BY mois ORDER BY date");
	$datas = array("Consommation" => "Consommation");
      	while ($row = $req->get_row())
        	$datas[$row['mois']] = $row['somme'];

      	$hist = new histogram($datas, "Consommation par mois", true);
	$hist->png_render();
      	$hist->destroy();

      	exit();
}
elseif ( ($_REQUEST["view"]=="stats") && ($user->etudiant || $user->ancien_etudiant) &&
         ($site->user->is_in_group("gestion_ae") || $site->user->id == $user->id ))
{

	$req = new requete($site->db, "SELECT COUNT(*) FROM sas_personnes_photos WHERE `id_utilisateur`=".$user->id);
	list( $photos ) = $req->get_row();
	$cts->add_paragraph("Vous avez été marqué sur $photos photos.");

	$cts2 = new contents("Historique des consommations");
	$cts2->add_paragraph("<center><img src=\"./user.php?id_utilisateur=$user->id&view=stats&graph=stat_comptoir_jour\" alt=\"Stats conso par heure\" /></center>");
	$cts2->add_paragraph("<center><img src=\"./user.php?id_utilisateur=$user->id&view=stats&graph=stat_comptoir_semaine\" alt=\"Stats conso par jour de la semaine\" /></center>");
	$cts2->add_paragraph("<center><img src=\"./user.php?id_utilisateur=$user->id&view=stats&graph=stat_comptoir_mois\" alt=\"Stats conso par mois\" /></center>");
	$cts->add($cts2,true);

	$req = new requete($site->db, "SELECT cpt_produits.id_produit as id, cpt_produits.nom_prod as nom, 
					SUM(if(cpt_vendu.prix_unit > 0, 1, 0)) as nombre_commande, SUM(cpt_vendu.quantite) as nombre, 
					SUM(cpt_vendu.quantite)/SUM(if(cpt_vendu.prix_unit > 0, 1, 0)) as moyenne
					FROM cpt_produits
					JOIN cpt_vendu ON cpt_vendu.id_produit = cpt_produits.id_produit
					JOIN cpt_debitfacture ON cpt_debitfacture.id_facture = cpt_vendu.id_facture
					WHERE cpt_debitfacture.id_utilisateur_client = $user->id
					AND (cpt_produits.id_typeprod < 10 OR cpt_produits.id_typeprod = 27)
					GROUP BY id ORDER BY nombre DESC LIMIT 10");
		
    $cts->add(new sqltable(
      "topconso",
      "Top 10 de vos consommations", $req,
      $topdir."user.php?view=stats",
      "id",
      array(
        "nom"=>"Produit",
        "nombre"=>"Quantité",
	"nombre_commande" => "Nombre de commande",
	"moyenne" => "Moyenne par commande"),
      array(),
      array(),
      array()), true);

	$req = new requete($site->db, "SELECT cpt_debitfacture.id_utilisateur as id_utilisateur, 
					IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,
						utl_etu_utbm.surnom_utbm, 
						CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) 
					as `nom_utilisateur`,
					COUNT(*) as nombre_commande FROM cpt_debitfacture
					JOIN utilisateurs ON utilisateurs.id_utilisateur = cpt_debitfacture.id_utilisateur
					JOIN utl_etu_utbm ON utl_etu_utbm.id_utilisateur = cpt_debitfacture.id_utilisateur
					JOIN cpt_vendu ON cpt_vendu.id_facture = cpt_debitfacture.id_facture
					JOIN cpt_produits ON cpt_vendu.id_produit = cpt_produits.id_produit
					WHERE cpt_debitfacture.id_utilisateur_client = $user->id
					AND (cpt_produits.id_typeprod < 10 OR cpt_produits.id_typeprod = 27)
					GROUP BY id_utilisateur ORDER BY nombre_commande DESC LIMIT 10");
		
    $cts->add(new sqltable(
      "topconso",
      "Top 10 de vos barmans", $req,
      $topdir."user.php",
      "id_utilisateur",
      array(
        "nom_utilisateur"=>"Nom",
        "nombre_commande"=>"Nombre de commande"),
      array(),
      array(),
      array()), true);
}
else
{
  $user->load_all_extra();

  if (($user->publique == 0) && ($site->user->id != $user->id))
    $cts->add_paragraph("Attention, fiche matmatronch privée : les informations présentes sur cette page ne doivent pas être communiquées.", "matmatronch_warning");

  $same_promo = ($user->promo_utbm == $site->user->promo_utbm);
  $info = new userinfov2($user,"full",$site->user->is_in_group("gestion_ae"), "user.php", $same_promo);

  if ( $can_edit )
    $info->set_toolbox(new toolbox(array("user.php?id_utilisateur=".$user->id."&page=edit"=>"Modifier")));

  $cts->add($info);

  if ( $site->user->id == $user->id && !$user->cotisant )
  {
    $cts->add_title(2, "Cotisation AE");
    $cts->add_paragraph("<img src=\"" . $topdir . "images/carteae/mini_non_ae.png\">" .
                        "<b><font color=\"red\">&nbsp;&nbsp;Attention, aucune cotisation " .
                        "&agrave; l'AE trouv&eacute;e !</font></b>");

    $cts->add_paragraph("<br/>R&eacute;flexe E-boutic ! <a href=\"" . $topdir .
                        "e-boutic/?cat=23\">Renouveler sa cotisation en ligne : </a><br /><br />");
    $cts->puts("<center><a href=\"".$topdir."e-boutic/?act=add&item=94&cat=23\"><img src=\"" .
                $topdir . "d.php?id_file=768&action=download&download=thumb\"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
    $cts->puts("<a href=\"".$topdir."e-boutic/?act=add&item=93&cat=23\"><img src=\"" . $topdir .
                "d.php?id_file=769&action=download&download=thumb\"></a></center>");
  }

  $req = new requete($site->db, "SELECT " .
    "CONCAT(`cpt_debitfacture`.`id_facture`,',',`cpt_produits`.`id_produit`) AS `id_factprod`, " .
    "`cpt_debitfacture`.`id_facture`, " .
    "`cpt_debitfacture`.`date_facture`, " .
    "`asso`.`id_asso`, " .
    "`asso`.`nom_asso`, " .
    "`cpt_vendu`.`a_retirer_vente`, " .
    "`cpt_produits`.`a_retirer_info`, " .
    "`cpt_vendu`.`a_expedier_vente`, " .
    "`cpt_vendu`.`quantite`, " .
    "`cpt_vendu`.`prix_unit`/100 AS `prix_unit`, " .
    "`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`/100 AS `total`," .
    "`cpt_produits`.`nom_prod`, " .
    "`cpt_produits`.`id_produit` " .
    "FROM `cpt_vendu` " .
    "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
    "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
    "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
    "WHERE `id_utilisateur_client`='".$user->id."' ".
    "AND (`cpt_vendu`.`a_retirer_vente`='1' OR `cpt_vendu`.`a_expedier_vente`='1') " .
    "ORDER BY `cpt_debitfacture`.`date_facture` DESC");

  $items=array();
  while ( $item = $req->get_row() )
  {
    if ($site->user->is_in_group("gestion_ae") || $site->user->is_asso_role($item['id_asso'], 2))
    {
      if ( $item['a_retirer_vente'])
      {
        if ($item['a_retirer_info'] != null)
          $item["info"] = "À venir retirer : ".$item['a_retirer_info'];
        else
          $item["info"] = "À venir retirer aux bureaux AE";
      }
      else if ( $item['a_expedier_vente'])
        $item["info"] = "En preparation";

      $items[]=$item;
    }
  }

  if(sizeof($items) > 0)
  {
    $cts->add(new sqltable(
      "listresp",
      "Commandes à retirer", $items,
      $topdir."comptoir/encours.php?id_utilisateur=".$user->id,
      "id_factprod",
      array(
        "nom_prod"=>"Produit",
        "quantite"=>"Quantité",
        "prix_unit"=>"Prix unitaire",
        "total"=>"Total",
        "info"=>""),
      array(),
      array("retires"=>"Marquer comme retiré"),
      array()), true);
  }

  /* l'onglet AE */
  if ( ($can_edit || $site->user->is_in_group("visu_cotisants") || sizeof($site->user->get_assos(7, true)) > 0) && $user->cotisant )
  {
    $cts->add_title(2, "Cotisation AE");

    if ($can_edit && !file_exists("/data/matmatronch/" . $user->id .".identity.jpg"))
      $cts->add_paragraph("<img src=\"".$topdir."images/actions/delete.png\"><b>ATTENTION</b>: " .
                          "<a href=\"user.php?see=photos&amp;page=edit&amp;id_utilisateur=".$user->id.
                          "\">Photo d'identit&eacute; non pr&eacute;sente !</a>");

    $req = new requete($site->db, "SELECT `date_fin_cotis` FROM `ae_cotisations`
                                      WHERE `id_utilisateur`='".$user->id."'
                                      AND `date_fin_cotis` >= '" . date("Y-m-d") . "'
                                      ORDER BY `date_fin_cotis` DESC LIMIT 1");
    if ($req->lines > 1)
      $cts->add_paragraph("ATTENTION: Plusieurs cotisations en cours.");
    elseif ($req->lines != 1)
      $cts->add_paragraph("ATTENTION: Cotisation non enregistr&eacute;e ou etat non &agrave; jour.");
    else
    {
      $res = $req->get_row();

      $year = explode("-", $res['date_fin_cotis']);
      $year = $year[0];
      if ($user->ae)
        $cts->add_paragraph("<img src=\"" . $topdir . "images/carteae/mini_ae.png\">&nbsp;&nbsp;" .
                            "Cotisant(e) AE jusqu'au " .
                            HumanReadableDate($res['date_fin_cotis'], null, false) . " $year !");
      elseif ($user->assidu)
        $cts->add_paragraph("<img src=\"" . $topdir . "images/carteae/mini_ae.png\">&nbsp;&nbsp;" .
                            "Cotisant(e) par Assidu jusqu'au " .
                            HumanReadableDate($res['date_fin_cotis'], null, false) . " $year !");
      elseif ($user->amicale)
        $cts->add_paragraph("<img src=\"" . $topdir . "images/carteae/mini_ae.png\">&nbsp;&nbsp;" .
                            "Cotisant(e) par l'Amicale jusqu'au " .
                            HumanReadableDate($res['date_fin_cotis'], null, false) . " $year !");

      elseif ($user->crous)
        $cts->add_paragraph("<img src=\"" . $topdir . "images/carteae/mini_ae.png\">&nbsp;&nbsp;" .
                            "Cotisant(e) CROUS jusqu'au " .
                            HumanReadableDate($res['date_fin_cotis'], null, false) . " $year !");

      if ( $can_edit )
      {
        $req = new requete($site->db,"SELECT `id_carte_ae`, `etat_vie_carte_ae`, `cle_carteae`, `a_pris_cadeau` FROM `ae_carte` INNER JOIN `ae_cotisations` ON `ae_cotisations`.`id_cotisation`=`ae_carte`.`id_cotisation` WHERE `ae_cotisations`.`id_utilisateur`='".$user->id."' AND `ae_carte`.`etat_vie_carte_ae`<".CETAT_EXPIRE."");

        $item = $req->get_row();

        $tab = array("reprint"=>"Re-imprimer carte");
        if($item['etat_vie_carte_ae']==CETAT_AU_BUREAU_AE)
        {
          $ret = array("retrait" => "Retrait carte");
          $tab += $ret;
        }
        if($item['a_pris_cadeau']==0)
        {
          $cad = array("cadeau" => "Retrait cadeau");
          $tab += $cad;
        }

        $tbl = new sqltable(
          "listasso",
          "Ma carte AE", array($item), "user.php?id_utilisateur=".$user->id,
          "id_carte_ae",
          array("id_carte_ae"=>"N°","cle_carteae"=>"Lettre clé","etat_vie_carte_ae"=>"Etat"),
          $site->user->is_in_group("gestion_ae")?$tab:array(),
          array(), array("etat_vie_carte_ae"=>$EtatsCarteAE )
          );
        $cts->add($tbl,true);
      }
    }
  }

  if ( $can_edit )
  {
    $cts->add(new itemlist("Modification du profil",false,array(
    "<a href=\"user.php?page=edit&amp;id_utilisateur=".$user->id."\">Informations personelles</a>",
    "<a href=\"user.php?see=email&amp;page=edit&amp;id_utilisateur=".$user->id."\">Adresses e-mail (personelle et utbm)</a>",
    "<a href=\"user.php?see=passwd&amp;page=edit&amp;id_utilisateur=".$user->id."\">Mot de passe</a>",
    "<a href=\"user.php?see=photos&amp;page=edit&amp;id_utilisateur=".$user->id."\">Photo d'identité, avatar et blouse</a>",
    "<a href=\"user.php?action=serviceident&amp;id_utilisateur=".$user->id."\">Générer un identifiant de service (Utilisable pour les flux RSS par exemple)</a>"
    )),true);
  }

  if ( $site->user->is_in_group("gestion_ae") )
  {
    $frm = new form("pass_reinit", "user.php?id_utilisateur=".$user->id, true, "POST", "R&eacute;initialiser le mot de passe");
    $frm->allow_only_one_usage();
    $frm->add_hidden("action","reinit");
    $frm->add_submit("valid","R&eacute;initialiser !");
    $cts->add($frm,true);
  }
}

/* c'est tout */
$site->add_contents($cts);

$site->end_page();

?>
