<?
/* Copyright 2009
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";
require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/entities/weekmail.inc.php");
require_once($topdir . "include/entities/asso.inc.php");

$site = new site();
$site->allow_only_logged_users('services');
$site->start_page("services",'Weekmail');
// On récupère les asso dont le gus est au moins secrétaire
$req = new requete($site->db,
                   'SELECT `nom_asso`, `id_asso` '.
                   'FROM `asso_membre` '.
                   'INNER JOIN `asso` USING(`id_asso`) '.
                   'WHERE `id_utilisateur`=\''.$site->user->id.'\' '.
                   'AND `date_fin` is NULL '.
                   'AND `role` >= \''.ROLEASSO_SECRETAIRE.'\'');

if($req->lines==0)
{
  $site->add_contents (new error('','Vous devez avoir un rôle de secrétaire ou plus dans au moins un club ou association.'));
  $site->end_page();
  exit();
}

$assos = array();
while(list($nom,$id)=$req->get_row())
  $assos[$id]=$nom;

$site->add_css("css/doku.css");
$weekmail = new weekmail($site->db,$site->dbrw);
if(!$weekmail->load_latest_not_sent())
{
  $site->add_contents (new error('','Aucun weekmail en préparation pour le moment.'));
  $site->end_page();
  exit();
}

$asso = new asso($site->db);
if(isset($_REQUEST['id_asso']))
{
  $asso->load_by_id($_REQUEST['id_asso']);
  if(!$asso->is_member_role($site->user->id,ROLEASSO_SECRETAIRE))
  {
    $asso = new asso($site->db);
    unset($_REQUEST['id_asso']);
  }
}


$can_edit = false;
if(isset($_REQUEST['id_news']))
{
  $req = new requete($site->db,
                     'SELECT `id_utilisateur`, `id_asso` '.
                     'FROM `weekmail_news` '.
                     'WHERE `id_weekmail`=\''.$weekmail->id.'\' '.
                     'AND `id_news`=\''.intval($_REQUEST['id_news']).'\' ');
  if($req->lines == 1)
  {
    list($id_utl, $id_asso)=$req->get_row();
    if($id_utl==$site->user->id || (!is_null($id_asso) && isset($assos[$id_asso])))
    {
      //édition
    }
  }
}

if( isset($_REQUEST['get_preview']) )
{
  $error = null;
  if(!isset($_REQUEST['id_asso']) || is_null($_REQUEST['id_asso']) || empty($_REQUEST['id_asso']))
    $error = 'Veuillez indiquer une association ou un club de référence.';
  elseif(!isset($assos[$_REQUEST['id_asso']]))
    $error = 'Veuillez indiquer une association ou un club dont vous êtes au moins secrétaire.';
  elseif(!isset($_REQUEST['titre']) || empty($_REQUEST['titre']))
    $error = 'Veuillez indiquer un titre !';
  elseif(!isset($_REQUEST['content']) || empty($_REQUEST['content']))
    $error = 'Veuillez remplir le corps de la nouvelle.';
  elseif(!isset($_REQUEST['id_weekmail']))
    $error = 'Erreur indéterminée.';
  elseif($_REQUEST['id_weekmail']!=$weekmail->id)
    $error = 'Weekmail déjà expédié, vous n\'avez sans doute pas respecté la date limite.';
  if(!is_null($error))
  {
    header("Content-Type: text/javascript; charset=utf-8");
    echo $error;
    exit;
  }

  header("Content-Type: text/javascript; charset=utf-8");
  echo "<div class=\"formrow\">";
  echo "<div class=\"formlabel\"></div>";
  echo "<div class=\"formfield\">";
  echo "<input type=\"submit\" id=\"add_news\" name=\"add_news\" value=\"Valider\" class=\"isubmit\" />";
  echo "</div></div>\n";
  echo "<h2>Prévisualisation</h2>";
  echo( $weekmail->preview_news($_REQUEST['id_asso'],
                                html_entity_decode($_REQUEST['titre'], ENT_NOQUOTES, 'UTF-8'),
                                html_entity_decode($_REQUEST['content'], ENT_NOQUOTES, 'UTF-8') ));
  exit();
}

if(isset($_REQUEST['add_news']))
{
  $error = null;
  if(!isset($_REQUEST['id_asso']) || is_null($_REQUEST['id_asso']) || empty($_REQUEST['id_asso']))
    $error = 'Veuillez indiquer une association ou un club de référence.';
  elseif(!isset($assos[$_REQUEST['id_asso']]))
    $error = 'Veuillez indiquer une association ou un club dont vous êtes au moins secrétaire.';
  elseif(!isset($_REQUEST['titre']) || empty($_REQUEST['titre']))
    $error = 'Veuillez indiquer un titre !';
  elseif(!isset($_REQUEST['content']) || empty($_REQUEST['content']))
    $error = 'Veuillez remplir le corps de la nouvelle.';
  elseif(!isset($_REQUEST['id_weekmail']))
    $error = 'Erreur indéterminée.';
  elseif($_REQUEST['id_weekmail']!=$weekmail->id)
    $error = 'Weekmail déjà expédié, vous n\'avez sans doute pas respecté la date limite.';
  elseif($GLOBALS['svalid_call'])
  {
    if(is_null($error) && $GLOBALS['svalid_call'])
    {
      $weekmail->add_news($site->user->id,$_REQUEST['id_asso'],$_REQUEST['titre'],$_REQUEST['content']);
      $site->add_contents(new contents(false,'Nouvelle postée et en attente de modération.'));
    }
    else
      $site->add_contents (new error('',$error));
  }
}

//formulaire
$frm = new form('addnews', '?', false, 'post', 'Proposer une nouvelle');
$frm->add_info('Vous postez pour le weekmail "'.$weekmail->titre.'"');
$frm->allow_only_one_usage();
$frm->add_hidden('id_weekmail',$weekmail->id);
$frm->add_select_field('id_asso',
                       'asso/club concerné ',
                       $assos,
                       $asso->id,'',true);
$frm->add_info('Le nom du club ou de l\'association sera automatiquement indiqué, il n\'est donc pas nécessaire de le préciser dans le titre !');
$frm->add_text_field("titre", "Titre : ",'',true,80);
$frm->add_dokuwiki_toolbar('content',$asso->id,null,true);
$frm->add_text_area("content", "contenu : ",'',80,20,true);
$frm->add_button('preview','Prévisualiser','javascript:make_preview();');
$frm->puts("
<script language=\"javascript\">
  function make_preview()
  {
    titre = document.".$frm->name.".titre.value;
    id_asso = document.".$frm->name.".id_asso.value;
    content = document.".$frm->name.".content.value;
    id_weekmail = ".$weekmail->id."
    user = ".$site->user->id.";
    openInContents('news_preview', './weekmail.php', 'get_preview&titre='+encodeURIComponent(titre)+'&content='+encodeURIComponent(content)+'&user='+user+'&id_asso='+id_asso+'&id_weekmail='+id_weekmail);
  }
</script>
<div class=\"formrow\"><div id=\"news_preview\"></div></div>\n");
$site->add_contents ($frm);

// liste des news en attente de weekmailisation :)

$site->end_page ();

?>
