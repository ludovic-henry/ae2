<?php
/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
 * - Benjamin Collet < bcollet at oxynux dot org >
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
require_once("include/sas.inc.php");
require_once($topdir."include/cts/gallery.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/cts/sas.inc.php");
require_once($topdir."include/cts/video.inc.php");
require_once($topdir."include/cts/react.inc.php");
require_once($topdir. "include/entities/page.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$site = new sas();
$site->add_css("css/doku.css");
$site->allow_only_logged_users("sas");

$asso = new asso($site->db);
$assoph = new asso($site->db);
$user = new utilisateur($site->db);
$userph = new utilisateur($site->db);
$userad = new utilisateur($site->db);

if ( isset($_REQUEST["id_asso"]) )
  $asso->load_by_id($_REQUEST["id_asso"]);

if ( isset($_REQUEST["id_asso_photographe"]) )
  $assoph->load_by_id($_REQUEST["id_asso_photographe"]);

if ( isset($_REQUEST["id_utilisateurs_presents"]) )
{
  $id_utilisateurs_presents = $_REQUEST["id_utilisateurs_presents"];

  if ( !is_array($id_utilisateurs_presents) )
    $id_utilisateurs_presents = array($id_utilisateurs_presents);

  if ( !empty($id_utilisateurs_presents) )
  {
    $id_utilisateurs_presents = array_unique($id_utilisateurs_presents);

    $utilisateurs_presents = array();

    foreach ( $id_utilisateurs_presents as $id_utilisateur_present )
    {
      $utilisateur_temporaire = new utilisateur($site->db);
      $utilisateur_temporaire->load_by_id($id_utilisateur_present);
      $utilisateurs_presents[] = $utilisateur_temporaire;
    }
  }
}

if ( isset($_REQUEST["id_utilisateur_photographe"]) )
  $userph->load_by_id($_REQUEST["id_utilisateur_photographe"]);

if ( isset($_REQUEST["id_utilisateur_contributeur"]) )
  $userad->load_by_id($_REQUEST["id_utilisateur_contributeur"]);

$site->add_css("css/sas.css");

$site->start_page("sas","Recherche - Stock à Souvenirs");

$cat = new catphoto($site->db);
$cat->load_by_id(1);
$cts = new contents($cat->get_html_link()." / Recherche");

$frm = new form("search","search.php",false,"POST","Paramètres de recherche");
$frm->add_hidden("action","search");
$frm->add_date_field("date_debut","Photos prises après le",$_REQUEST["date_debut"]?$_REQUEST["date_debut"]:null);
$frm->add_date_field("date_fin","Photos prises avant le",$_REQUEST["date_fin"]?$_REQUEST["date_fin"]:null);
$frm->add_text_field("tags","Tags",$_REQUEST["tags"]);
$frm->add_entity_smartselect ( "id_asso", "Association/Club", $asso, true );
$frm->add_entity_smartselect ( "id_asso_photographe", "Club photographe", $assoph, true );
$frm->add_entity_select('id_licence','Choix de la licence',$site->db,'licence',$_REQUEST['id_licence'],true,array(),'\'id_licence\' ASC');
$frm->add_checkbox('droitimage','Droit à l\'image non applicable',isset($_REQUEST['droitimage']));
if ( empty($utilisateurs_presents) )
{
  $la_fonction_veut_une_instance_de_classe = new utilisateur($site->db);
  $frm->add_entity_smartselect ( "id_utilisateurs_presents[]", "Personne sur la photo", $la_fonction_veut_une_instance_de_classe, true );
}
else
{
  foreach ( $utilisateurs_presents as $utilisateur_present )
    $frm->add_entity_smartselect ( "id_utilisateurs_presents[".$utilisateur_present->id."]", "Personne sur la photo", $utilisateur_present, true );
}
$frm->add_entity_smartselect ( "id_utilisateur_photographe", "Photographe", $userph, true );
$frm->add_entity_smartselect ( "id_utilisateur_contributeur", "Contributeur", $userad, true );
$frm->add_select_field("type","Type de média",array(0=>"Tous",MEDIA_PHOTO+1=>"Photo",MEDIA_VIDEOFLV+1=>"Video"),$_REQUEST["type"]);

$frm->add_select_field("order","Tri",
array(0=>"Type, Date de prise de vue (par défaut)",
1=>"Date de prise de vue",
2=>"Date de prise de vue inversée",
3=>"Date d'ajout",
4=>"Date d'ajout inversée"),$_REQUEST["order"]);


$frm->add_submit("go","Rechercher");

$cts->add($frm,true);

if ( $_REQUEST["action"] == "search" )
{
  $joins=array();
  $conds=array();
  $params="&order=".intval($_REQUEST["order"]);
  $fail=false;
  $order = "type_media_ph DESC, date_prise_vue";

  if ( $_REQUEST["order"] == 1 )
    $order = "date_prise_vue";
  elseif ( $_REQUEST["order"] == 2 )
    $order = "date_prise_vue DESC";
  elseif ( $_REQUEST["order"] == 3 )
    $order = "date_ajout_ph";
  elseif ( $_REQUEST["order"] == 4 )
    $order = "date_ajout_ph DESC";

  if ( $asso->is_valid() )
  {
    $conds[] = "sas_photos.meta_id_asso_ph='".mysql_escape_string($asso->id)."'";
    $params.="&id_asso=".$asso->id;
  }

  if ( $assoph->is_valid() )
  {
    $conds[] = "sas_photos.id_asso_photographe='".mysql_escape_string($assoph->id)."'";
    $params.="&id_asso_photographe=".$assoph->id;
  }

  if ( $user->is_valid() )
  {
    $joins[] = "INNER JOIN sas_personnes_photos AS `p2` ON ( sas_photos.id_photo=p2.id_photo AND p2.id_utilisateur='".mysql_escape_string($user->id)."') ";
    $params.="&id_utilisateur_present=".$user->id;
  }

  if ( $userph->is_valid() )
  {
    $conds[] = "sas_photos.id_utilisateur_photographe='".mysql_escape_string($userph->id)."'";
    $params.="&id_utilisateur_photographe=".$userph->id;
  }

  if ( $userad->is_valid() )
  {
    $conds[] = "sas_photos.id_utilisateur='".mysql_escape_string($userad->id)."'";
    $params.="&id_utilisateur_contributeur=".$userad->id;
  }

  if ( $_REQUEST["date_debut"] )
  {
    $conds[] = "sas_photos.date_prise_vue>='".date("Y-m-d H:i",$_REQUEST["date_debut"])."'";
    $params.="&date_debut=".$_REQUEST["date_debut"];
  }

  if ( $_REQUEST["date_fin"] )
  {
    $conds[] = "sas_photos.date_prise_vue<='".date("Y-m-d H:i",$_REQUEST["date_fin"])."'";
    $params.="&date_fin=".$_REQUEST["date_fin"];
  }

  if ( isset($_REQUEST["droitimage"]) )
  {
    $joins[] = "LEFT JOIN sas_personnes_photos AS droitimage ON droitimage.id_photo=sas_photos.id_photo";
    $conds[] = "droitimage.id_photo IS NULL AND sas_photos.incomplet=0 AND sas_photos.modere_ph=1 AND droits_acquis=1";
    $params.="&droitimage=1";
  }

  if ( $_REQUEST['id_licence'] )
  {
    $conds[] = "sas_photos.id_licence='".mysql_escape_string($_REQUEST["id_licence"])."'";
    $params.="&id_licence=".rawurlencode($_REQUEST["id_licence"]);
  }

  if ( $_REQUEST["type"] )
  {
    $conds[] = "sas_photos.type_media_ph='".mysql_escape_string($_REQUEST["type"]-1)."'";
    $params.="&type=".rawurlencode($_REQUEST["type"]);
  }

  if ( $_REQUEST["tags"] )
  {
    $tags=trim(strtolower($_REQUEST["tags"]));
    if ( !empty($tags) )
    {
      $tags = explode(",",$tags);
      $tconds=array();
      $missing=array();
      foreach ( $tags as $tag )
      {
        $tag = trim($tag);
        $tconds[] = "nom_tag='".mysql_escape_string($tag)."'";
        $missing[$tag]=$tag;
      }

      $tags=array();
      $req = new requete($site->db, "SELECT id_tag, nom_tag FROM tag WHERE ".implode(" OR ",$tconds));
      while ( list($id,$tag) = $req->get_row() )
      {
        $tags[$id]=$tag;
        unset($missing[$tag]);
      }
      if ( count($missing) == 0 )
      {
        foreach ( $tags as $id => $tag )
        {
          $id = intval($id); // On est jamais trop prudent
          $joins[] = "INNER JOIN sas_photos_tag AS tag$id ON ".
                     "( tag".$id.".id_photo=sas_photos.id_photo ".
                       "AND tag".$id.".id_tag='".$id."' )";
        }
        $params.="&tags=".rawurlencode($_REQUEST["tags"]);

      }
      else
        $fail=true;
    }
  }

  if ( !empty($utilisateurs_presents) )
  {
    foreach ( $utilisateurs_presents as $utilisateur_present )
    {
        if ( $utilisateur_present->is_valid() )
        {
          $joins[] = "INNER JOIN sas_personnes_photos AS `p".mysql_escape_string($utilisateur_present->id)."` ON ( sas_photos.id_photo=p".mysql_escape_string($utilisateur_present->id).".id_photo AND p".mysql_escape_string($utilisateur_present->id).".id_utilisateur='".mysql_escape_string($utilisateur_present->id)."') ";
          $params.="&id_utilisateurs_presents[]=".$utilisateur_present->id;
        }
    }
  }

  if ( $fail )
  {
    $count=0;
  }
  else
  {
    if ( count($conds) == 0 )
      $conds[]="1";

    $req = $cat->get_photos_search ( $site->user, implode(" AND ",$conds), implode(" ",$joins), "COUNT(*)");

    list($count) = $req->get_row();
  }

  if ( $count == 0 )
  {
    $cts->add_title(2,"Aucun resultat");
  }
  else
  {
    $cts->add_title(2,"$count réponse(s)");

    $npp=SAS_NPP;
    $page = intval($_REQUEST["page"]);

    if ( $page)
      $st=$page*$npp;
    else
      $st=0;

    if ( $st > $count )
      $st = floor($count/$npp)*$npp;

    $req = $cat->get_photos_search ( $site->user, implode(" AND ",$conds), implode(" ",$joins), "sas_photos.*", "LIMIT $st,$npp", $order);

    $photo = new photo($site->db);

    $gal = new gallery(false,"photos","phlist");
    while ( $row = $req->get_row() )
    {
      $photo->_load($row);
      $img = "images.php?/".$photo->id.".vignette.jpg";

      $titre="";

      if ( $photo->titre )
        $titre = htmlentities($photo->titre,ENT_COMPAT,"UTF-8");


      if ( $row['type_media_ph'] == 1 )
      $gal->add_item("<a href=\"./?id_photo=".$photo->id."\"><img src=\"$img\" alt=\"Photo\">".
        "<img src=\"".$wwwtopdir."images/icons/32/multimedia.png\" alt=\"Video\" class=\"ovideo\" /></a>",$titre);
      else
      $gal->add_item("<a href=\"./?id_photo=".$photo->id."\"><img src=\"$img\" alt=\"Photo\"></a>",$titre);
    }
    $cts->add($gal);

    $tabs = array();
    $i=0;
    $n=0;
    while ( $i < $count )
    {
      $tabs[]=array($n,"sas2/search.php?action=search&page=".$n.$params,$n+1 );
      $i+=$npp;
      $n++;
    }
    $cts->add(new tabshead($tabs, $page, "_bottom"));

  }



}

$site->add_contents($cts);

$site->end_page ();

?>
