<?php

/* Copyright 2007
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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

$topdir="../";

require_once($topdir. "include/site.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/entities/svn.inc.php");


$site = new site ();

$site->allow_only_logged_users("");

$site->start_page("accueil","Ma boite à outils / SVN");
$cts = new contents("<a href=\"outils.php\">Ma boite à outils</a> / SVN");

/*
if(empty($site->user->alias))
{
  if( isset($_REQUEST["alias"]) )
  {
    if ( !preg_match("#^([a-z0-9][a-z0-9\.]+[a-z0-9])$#i",strtolower($_REQUEST["alias"])) )
    {
      $ErreurMAJ = "Alias invalide, utilisez seulement des caractères alphanumériques, des points (jamais à la fin). L'alias doit comporter au moins trois caractères.";
    }
    elseif ( strtolower($_REQUEST["alias"]) && !$site->user->is_alias_available(strtolower($_REQUEST["alias"])) )
    {
      $ErreurMAJ = "Alias d&eacute;j&agrave;  utilis&eacute;";
    }
    else
    {
      $site->user->saveinfos();
      if(!empty($_REQUEST["pass"]))
        @exec("/usr/bin/htpasswd -sb ".SVN_PATH.PASSWORDFILE." ".strtolower($site->user->alias)." ".escapeshellarg($_REQUEST["pass"]));
    }
  }

  if( !isset($_REQUEST["alias"]) || isset($ErreurMAJ) )
  {
    $cts->add_paragraph("<b>Vous n'avez pas d'alias, il vous ets donc impossible d'utiliser les dépots" .
                        " subversions.</b>");
    $frm = new form("setalias","svn.php",false,"post","Créer un alias :");
    if ( isset($ErreurMAJ) )
      $frm->error($ErreurMAJ);
    $frm->add_text_field("alias","Alias");
    $find = @exec("grep \"^".strtolower($site->user->alias).":\" " .SVN_PATH.PASSWORDFILE);
    if( empty($find) )
      $frm->add_password_field("pass","Mot de passe");
    $frm->add_submit("valid","Valider");
    $cts->add($frm,true);

    $site->add_contents($cts);
    $site->end_page();
    exit();
  }
}

if( isset($_REQUEST["action"]) && $_REQUEST["action"]=="pass" )
{
  if(empty($_REQUEST["pass"]))
    $cts->add_paragraph("<b>Veuillez spécifier un mot de passe.</b>");
  else
    @exec("/usr/bin/htpasswd -sb ".SVN_PATH.PASSWORDFILE." ".strtolower($site->user->alias)." ".escapeshellarg($_REQUEST["pass"]));
}

$find = @exec("grep \"^".strtolower($site->user->alias).":\" " .SVN_PATH.PASSWORDFILE);
if( empty($find) )
{
  $cts->add_paragraph("<b>Vous n'avez pas de mot de passe, il vous est donc impossible d'utiliser les dépots" .
                      " subversions.</b>");
  $frm = new form("setmdp","svn.php",false,"post","Créer un mot de passe :");
  $frm->add_hidden("action","pass");
  $frm->add_password_field("pass","Mot de passe");
  $frm->add_submit("valid","Valider");
  $cts->add($frm,true);

  $site->add_contents($cts);
  $site->end_page();
  exit();
}
*/
$cts->add_paragraph("Votre alias de connexion svn est : ".strtolower($site->user->alias));
/*
$frm = new form("changemdp","svn.php",false,"post","Changer votre mot de passe :");
$frm->add_hidden("action","pass");
$frm->add_password_field("pass","Mot de passe");
$frm->add_submit("valid","Valider"); $cts->add($frm,true);
*/

/* ici faire la liste des dépots privés, publiques et aeinfo */
$req = new requete($site->db,"SELECT `nom`, CONCAT('https://ae.utbm.fr/svn/aeinfo/',`nom`) AS `url`, `right` FROM `svn_member_depot` ".
                             "INNER JOIN `svn_depot` USING(`id_depot`) ".
                             "WHERE `id_utilisateur`='".$site->user->id."' AND `type`='aeinfo'");
if($req->lines != 0)
{
  $cts->add_title(2,"Depots équipe info :");
  $cts->add(new sqltable("svn_member_depot",
                         "Membres des dépots",
                         $req,
                         "",
                         "id_depot",
                         array("nom"=>"Nom","right"=>"Droits","url"=>"URL"),
                         array(),
                         array(),
                         array()
                        ));
}

$req = new requete($site->db,"SELECT `nom`, CONCAT('https://ae.utbm.fr/svn/private/',`nom`) AS `url`, `right` FROM `svn_member_depot` ".
                             "INNER JOIN `svn_depot` USING(`id_depot`) ".
                             "WHERE `id_utilisateur`='".$site->user->id."' AND `type`='private'");
if($req->lines != 0)
{
  $cts->add_title(2,"Depots privés :");
  $cts->add(new sqltable("svn_member_depot",
                         "Membres des dépots privés",
                         $req,
                         "",
                         "id_depot",
                         array("nom"=>"Nom","right"=>"Droits","url"=>"URL"),
                         array(),
                         array(),
                         array()
                        ));
}

$req = new requete($site->db,"SELECT `nom`, CONCAT('https://ae.utbm.fr/svn/public/',`nom`) AS `url`, `right` FROM `svn_member_depot` ".
                             "INNER JOIN `svn_depot` USING(`id_depot`) ".
                             "WHERE `id_utilisateur`='".$site->user->id."' AND `type`='public'");
if($req->lines != 0)
{
  $cts->add_title(2,"Depots publics :");
  $cts->add(new sqltable("svn_member_depot",
                         "Membres des dépots",
                         $req,
                         "",
                         "id_depot",
                         array("nom"=>"Nom","right"=>"Droits","url"=>"URL"),
                         array(),
                         array(),
                         array()
                        ));
}

$site->add_contents($cts);
$site->end_page();

?>
