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

$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/cts/newsflow.inc.php");

function qpchar($c)
{
   $n=ord($c);
   if ($c==" ") return "_";
   if ($n>=48 && $n<=57) return $c;
   if ($n>=65 && $n<=90) return $c;
   if ($n>=97 && $n<=122) return $c;
   return "=".($n<16 ? "0" : "").strtoupper(dechex($n));
}

function encodeMimeSubject($s)
{
  $lastspace=-1;
   $r="";
   $buff="";

   $mode=1;

   for ($i=0; $i<strlen($s); $i++) {
       $c=substr($s,$i,1);
       if ($mode==1) {
           $n=ord($c);
           if ($n & 128) {
               $r.="=?UTF-8?Q?";
               $i=$lastspace;
               $mode=2;
           } else {
               $buff.=$c;
               if ($c==" ") {
                   $r.=$buff;
                   $buff="";
                   $lastspace=$i;
               }
           }
       } else if ($mode==2) {
           $r.=qpchar($c);
       }
   }
   if ($mode==2) $r.="?=";

   return $r;

}

$site = new site ();
$asso = new asso($site->db,$site->dbrw);
$asso->load_by_id($_REQUEST["id_asso"]);

if ( $asso->id < 1 )
{
  $site->error_not_found("services");
  exit();
}

if ( !$site->user->is_in_group("gestion_ae")&&!$asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU))
  $site->error_forbidden("presentation","role","bureau");

$sucess=null;

if ( $_REQUEST["action"] == "sendmail")
{
  if (!$_REQUEST["title"] || !$_REQUEST["contents"])
    $Error = "Sujet ou contenu vide !";
  elseif ( ($_FILES['file']['error'] != 4) &&
           !is_uploaded_file($_FILES['file']['tmp_name']) )
    $Error = "Echec d'upload !";
  else
    {
      $req = new requete($site->db,
                         "SELECT `utilisateurs`.`id_utilisateur`, " .
                         "`utilisateurs`.`email_utl` " .
                         "FROM `asso_membre` " .
                         "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`asso_membre`.`id_utilisateur` " .
                         "WHERE `asso_membre`.`date_fin` IS NULL " .
                         "AND `asso_membre`.`id_asso`='".$asso->id."' " .
                         "AND `asso_membre`.`role` >= ".intval($_REQUEST["role"])."");

      $message = wordwrap($_REQUEST["contents"], 70);
      $sucess = new itemlist("Envoy&eacute;");

      while ( list($id,$mail) = $req->get_row() )
        {
          if ( $mail )
            {
              if ( is_readable($_FILES['file']['tmp_name']) )
                {
                  $boundary = "----=AE_" . time();
                  $filename = strtr($_FILES['file']['name'], "\"", "");

                  $headers = "Return-Path: " . $site->user->email . "\n" .
                    "MIME-Version: 1.0\n" .
                    "Content-Type: multipart/mixed;\n" .
                    "        boundary=\"" . $boundary . "\"\n" .
                    "Reply-to: " . $site->user->email . "\n" .
                    "From: " . $site->user->email;

                  $message = "--" . $boundary . "\n".
                    "Content-Type: text/plain; charset=UTF-8\n".
                    "Content-Disposition: inline\n\n".
                    $message . "\n\n".
                    "--" . $boundary . "\n".
                    "Content-Type: " . mime_content_type($_FILES['file']['tmp_name']) . ";\n" .
                    "        name=\"" . $filename . "\"\n".
                    "Content-Disposition: attachment;\n" .
                    "        filename=\"" . $filename . "\"\n" .
                    "Content-Transfer-Encoding: base64\n" .
                    base64_encode(file_get_contents($_FILES['file']['tmp_name'])) . "\n" .
                    "--" . $boundary . "--\n";
                }
              else
                $headers = "Return-Path: " . $site->user->email . "\n" .
                  "MIME-Version: 1.0\n" .
                  "Content-Type: text/plain; charset=UTF-8\n" .
                  "Reply-to: " . $site->user->email . "\n" .
                  "From: " . $site->user->email;

              $ret = mail( $mail, encodeMimeSubject ("[".$asso->nom."] " . $_REQUEST["title"]),
                           $message, $headers, "-f".$site->user->email );
              if ($ret)
                $sucess->add("$mail");
              else
                $sucess->add("Echec ($mail)");
            }
        }
    }
}

$site->start_page("presentation", "Mailing: " . $asso->nom);

$cts = new contents($asso->nom);

$cts->add(new tabshead($asso->get_tabs($site->user),"mebs"));

$subtabs = array();
$subtabs[] = array("mailing","asso/mailing.php?id_asso=".$asso->id,"Mailing aux membres");
$subtabs[] = array("mldiff","asso/mldiff.php?id_asso=".$asso->id,"Gérer les mailings-lists");
$subtabs[] = array("trombino","asso/membres.php?view=trombino&id_asso=".$asso->id,"Trombino (membres actuels)");
$subtabs[] = array("vcards","asso/membres.php?action=getallvcards&id_asso=".$asso->id,"Télécharger les vCard (membres actuels)");
$subtabs[] = array("anciens","asso/membres.php?view=anciens&id_asso=".$asso->id,"Anciens membres");

$cts->add(new tabshead($subtabs,"mailing","","subtab"));

if ( $asso->is_mailing_allowed() )
{
  $mailings = array($asso->nom_unix.".bureau"=>$asso->nom_unix.".bureau");

  $cts->add_title(2,"Mailing listes");

  if ( !is_null($asso->id_parent) )
  {
    $cts->add_paragraph($asso->nom_unix.".membres@ml.aeinfo.net : Mailing liste de tous les membres (inscription libre).");
    $cts->add_paragraph($asso->nom_unix.".bureau@ml.aeinfo.net : Mailing liste de tous les membres ayant un role supérieur ou égal à \"Membre du bureau\".");

    $cts->add_paragraph("Pour utiliser les mailing listes, chaque utilisateur doit envoyer ses messages depuis son adresse principale configuré sur le site, il peut la changer si elle ne convient pas. Cette adresse peut être différente de votre adresse utbm.");

    $cts->add_paragraph("Votre adresse principale est ".$site->user->email.". <a href=\"../user.php?page=edit&amp;see=email\">Changer d'adresse principale</a>");

    $mailings[$asso->nom_unix.".membres"] = $asso->nom_unix.".membres";
  }

  $cts->add_title(2,"Inscription manuelle");
  if ( $_REQUEST["action"] == "subscribe" )
  {
    if ( !CheckEmail($_REQUEST["email"],3) )
      $cts->add_paragraph("Adresse e-mail invalide","error");
    elseif ( in_array($_REQUEST["liste"],$mailings) )
    {
      asso::_ml_subscribe ( $site->dbrw, $_REQUEST["liste"], $_REQUEST["email"] );
      $cts->add_paragraph("Demande d'inscription de ".$_REQUEST["email"]." à ".$_REQUEST["liste"]." enregistrée.");
    }
  }
  $cts->add_paragraph("Comptez un délai de 60 minutes pour que l'inscription soit effective.");
  $frm = new form("subscribe","mailing.php?id_asso=".$asso->id,is_null($sucess),"POST");
  $frm->add_hidden("action","subscribe");
  $frm->add_select_field("liste","Liste",$mailings);
  $frm->add_text_field("email","Adresse e-mail","",true);
  $frm->add_submit("valid","Inscrire");
  $cts->add($frm);

  $cts->add_title(2,"Desinscription manuelle");
  if ( $_REQUEST["action"] == "unsubscribe" )
  {
    if ( !CheckEmail($_REQUEST["email"],3) )
      $cts->add_paragraph("Adresse e-mail invalide","error");
    elseif ( in_array($_REQUEST["liste"],$mailings) )
    {
      asso::_ml_unsubscribe ( $site->dbrw, $_REQUEST["liste"], $_REQUEST["email"] );
      $cts->add_paragraph("Demande de desinscription de ".$_REQUEST["email"]." à ".$_REQUEST["liste"]." enregistrée.");
    }
  }
  $cts->add_paragraph("Comptez un délai de 60 minutes pour que la desinscription soit effective.");
  $frm = new form("unsubscribe","mailing.php?id_asso=".$asso->id,is_null($sucess),"POST");
  $frm->add_hidden("action","unsubscribe");
  $frm->add_select_field("liste","Liste",$mailings);
  $frm->add_text_field("email","Adresse e-mail","",true);
  $frm->add_submit("valid","Desinscrire");
  $cts->add($frm);

}

$cts->add_title(2,"Envoyer un message");

if ( $sucess )
  $cts->add($sucess,true);

$frm = new form("sendmembers","mailing.php?id_asso=".$asso->id,is_null($sucess),"POST");
$frm->add_hidden("action","sendmail");
if ( $Error )
  $frm->error($Error);
$frm->add_select_field("role","Destinataires (ceux qui ont un r&ocirc;le sup&eacute;rieur ou &eacute;gal &agrave)",
                       $GLOBALS['ROLEASSO']);
$frm->add_text_field("title","Titre du message","",true,80);
$frm->add_text_area("contents","Contenu du message","",80,20,true);
$frm->add_file_field("file","Fichier joint",false);
$frm->add_submit("valid","Envoyer");
$cts->add($frm);

$site->add_contents($cts);
$site->end_page();

?>
