<?php

/* Copyright 2007
 * - Benjamin Collet - bcollet <at> oxynux <dot> org
 * Copyright 2009
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "./";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/mysql.inc.php");
require_once($topdir. "include/mysqlae.inc.php");

function error($apikey,$insc=false)
{
  $db = new mysqlae ("ro");

  if(!$db->dbh)
    return "DatabaseUnavailable";

  if($insc)
    $valid = new requete($db,
    "SELECT `https` ".
    "FROM `sso_api_keys` ".
    "WHERE `key` = '".mysql_real_escape_string($apikey)."' ".
    "AND `allow_inscription`='1'");
  else
    $valid = new requete($db,
      "SELECT `https` ".
      "FROM `sso_api_keys` ".
      "WHERE `key` = '".mysql_real_escape_string($apikey)."'");

  list($https)=$valid->get_row();
  if($https == 1 && !$GLOBALS["is_using_ssl"])
    return "httpsRequired";

  if($valid->lines != 1)
    return "KeyNotValid";

  return "ok";
}

function testLogin($message)
{
  $simplexml = new SimpleXMLElement($message->str);
  $apikey = $simplexml->apikey[0];
  $login = $simplexml->login[0];
  $password = $simplexml->password[0];

  $error = error($apikey);
  $update = -1;

  if($error == "ok")
  {
    $site = new site();
    $site->user->load_by_email($login);

    if($site->user->is_valid())
    {
      if($site->user->is_password($password))
      {
        $return = $site->user->id;
        $update = $site->user->date_maj;
      }
      else
        $return = 0;
    }
    else
      $return = 0;
  }
  else
    $return = $error;

  $response = <<<XML
<testLoginResponse>
  <result>$return</result>
  <lastUpdate>$update</lastUpdate>
</testLoginResponse>
XML;

  return $response;
}

function testAssoRole($message)
{
  $simplexml = new SimpleXMLElement($message->str);
  $apikey    = $simplexml->apikey[0];
  $uid       = $simplexml->uid[0];
  $asso      = $simplexml->aid[0];
  $role      = $simplexml->role[0];

  $error     = error($apikey);

  if($error == "ok")
  {
    $site = new site();
    $site->user->load_by_id($uid);

    if(!$site->user->is_valid())
      $return=-1;
    elseif($site->user->is_asso_role($asso, $role))
      $return = 1;
    else
      $return = 0;
  }
  else
    $return = $error;

  $response = <<<XML
<testAssoRoleResponse>
  <result>$return</result>
</testAssoRoleResponse>
XML;

  return $response;
}

function getUserInfo($message)
{
  $simplexml = new SimpleXMLElement($message->str);
  $apikey = $simplexml->apikey[0];
  $uid = $simplexml->uid[0];
  $error = error($apikey);
  if($error == "ok")
  {
    $site = new site();
    $site->user->load_by_id($uid);
    if(!$site->user->is_valid())
    {
      $error = 1;
      $return = '<errorDetail>UID not found</errorDetail>';
    }
    else
    {
      $user = &$site->user;
      $error = 0;
      $return = '<nom>'.$user->nom.'</nom>';
      $return.= '<prenom>'.$user->prenom.'</prenom>';
      $return.= '<email>'.$user->email.'</email>';
      $return.= '<date_maj>'.$user->date_maj.'</date_maj>';
    }
  }
  else
    $return = $error;

  $response = <<<XML
<getUserInfo>
  <error>$error</error>
  $return
</getUserInfo>
XML;

  return $response;
}

function getUpdate($message)
{
  $simplexml = new SimpleXMLElement($message->str);
  $apikey = $simplexml->apikey[0];
  $uid = $simplexml->uid[0];
  $maj=-1;
  if($error == "ok")
  {
    $error = 0;
    $site = new site();
    $site->user->load_by_id($uid);
    if(!$site->user->is_valid())
      $error = -1;
    else
      $maj = $site->user->date_maj;
  }
  $response = <<<XML
<testAssoRoleResponse>
  <error>$error</error>
  <maj>$maj</maj>
</testAssoRoleResponse>
XML;

  return $response;
}

/* info inscription */
function info_inscription($message)
{
  $simplexml = new SimpleXMLElement($message->str);
  $apikey = $simplexml->apikey[0];

  $error = error($apikey,true);
  if($error == "ok")
    $return = '<error>0</error>'.
              '<datas>'.
              '<textfield translate="Nom">nom</text>'.
              '<textfield translate="Prénom">prenom</text>'.
              '<textfield translate="E-mail">email</text>'.
              '<timestamp translate="Date de naissance">naissance</timestamp>'.
              '<select translate="Sexe" values_data="0,1" values_text="Femme,Homme">sexe</select>'.
              '</datas>'.
              '<infos>'.
              '<boolean>0=false,1=true</boolean>'.
              '</infos>';
  else
    $return = '<error>'.$error.'</error>';

  $response = <<<XML
<inscriptionResponse>
$return
</inscriptionResponse>
XML;
  return $response;
}
/* inscription */
function inscription($message)
{
  $simplexml = new SimpleXMLElement($message->str);
  $apikey = $simplexml->apikey[0];

  $error = error($apikey,true);

  if($error == "ok")
  {
    $site = new site();
    $user = new utilisateur($site->db,$site->dbrw);

    $utbm = false;
    $nom = $simplexml->nom[0];
    $prenom = $simplexml->prenom[0];
    $email = $simplexml->email[0];
    $naissance = $simplexml->naissance[0];
    $sexe = $simplexml->sexe[0];

    if(!$email)
      $return = "MailMissing";
    elseif(!ereg("^([A-Za-z0-9\._-]+)@([A-Za-z0-9_-]+)\.([A-Za-z0-9\._-]*)$", $email))
      $return = "MailNotValid";
    elseif($user->load_by_email($email))
      $return = "AccountsExists";
    elseif(!$nom)
      $return = "NameMissing";
    elseif(!$prenom)
      $return = "LastnameMissing";
    elseif(ereg("^([a-zA-Z0-9\.\-]+)@(utbm\.fr|assidu-utbm\.fr)$",$mail))
      $utbm = true;
    elseif(!$password)
      $return = "PasswordMissing";
    elseif($sexe != 1 && $sexe != 2)
      $return = "InvalidSex";
    else
    {
      $password = genere_pass(7);
      $password = crypt($password, "ae");
      $user->create_user($nom, $prenom, $email, $password, false, $naissance, $sexe, $utbm);
      $user->load_by_email($email);
      $return = $user->id;
    }
  }
  else
  {
    $return = $error;
  }

  $response = <<<XML
<inscriptionResponse>
<result>$return</result>
</inscriptionResponse>
XML;

  return $response;
}

$service = new WSService(array("operations" => array('testLogin',
                                                     'testAssoRole',
                                                     'getUpdate',
                                                     'getUserInfo',
                                                     'inscription',
                                                     'info_inscription')));
$service->reply();

?>
