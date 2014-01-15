<?php
/* Copyright 2008
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
$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir.'include/entities/weekmail.inc.php');
require_once($topdir."include/entities/files.inc.php");
require_once($topdir."include/entities/folder.inc.php");


$site = new site ();
if (!$site->user->is_in_group ("moderateur_site"))
  $site->error_forbidden("accueil");
$site->start_page("accueil","Weekmail");
$cts = new contents('<a href="./index.php">gestion ae</a> / <a href="?">Weekmail</a>');
$list = new itemlist("Outils");
$list->add("<a href=\"?page=modere\">Modérer</a>");
$list->add("<a href=\"?page=custom\">Personaliser</a>");
$list->add("<a href=\"?page=addnews\">Ajouter une nouvelle</a>");
$list->add("<a href=\"?page=preview\">Prévisualiser</a>");
$list->add("<a href=\"?page=send\">Envoyer</a>");
$cts->add($list);
$site->add_contents($cts);
$weekmail = new weekmail($site->db,$site->dbrw);

if( isset($_REQUEST['get_preview']) )
{
  $weekmail->load_first_not_sent();
  $error = null;
  if(!isset($_REQUEST['id_asso']) || is_null($_REQUEST['id_asso']) || empty($_REQUEST['id_asso']))
    $error = 'Veuillez indiquer une association ou un club de référence.';
  elseif(!isset($_REQUEST['titre']) || empty($_REQUEST['titre']))
    $error = 'Veuillez indiquer un titre !';
  elseif(!isset($_REQUEST['content']) || empty($_REQUEST['content']))
    $error = 'Veuillez remplir le corps de la nouvelle.';
  elseif(!isset($_REQUEST['id_weekmail']))
    $error = 'Erreur indéterminée.';
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
  echo "<input type=\"submit\" id=\"valid_news\" name=\"valid_news\" value=\"Valider\" class=\"isubmit\" />";
  echo "</div></div>\n";
  echo "<h2>Prévisualisation</h2>";
  echo( $weekmail->preview_news($_REQUEST['id_asso'],
                                html_entity_decode($_REQUEST['titre'], ENT_NOQUOTES, 'UTF-8'),
                                html_entity_decode($_REQUEST['content'], ENT_NOQUOTES, 'UTF-8') ));
  exit();
}


if(isset($_REQUEST['action'])
   && $_REQUEST['action']=='create'
   && isset($_REQUEST['titre'])
   && !empty($_REQUEST['titre']))
{
  if($weekmail->can_create_new())
  {
    $file = new dfile($site->db);
    $weekmail->create($_REQUEST['titre']);
    if(isset($_REQUEST['id_file_header'])
       && $file->load_by_id($_REQUEST['id_file_header'])
      )
    {
      $file_info = getimagesize($file->get_real_filename());
      if($file_info)
      {
        list($width)=$file_info;
        if($width=='600')
          $weekmail->set_header($file->id);
      }
    }
    if(isset($_REQUEST['introduction']) && !empty($_REQUEST['introduction']))
      $weekmail->set_intro($_REQUEST['intro']);
    if(isset($_REQUEST['conclusion']) && !empty($_REQUEST['conclusion']))
      $weekmail->set_conclusion($_REQUEST['conclusion']);
    if(isset($_REQUEST['blague']) && !empty($_REQUEST['blague']))
      $weekmail->set_blague($_REQUEST['blague']);
    if(isset($_REQUEST['astuce']) && !empty($_REQUEST['astuce']))
      $weekmail->set_astuce($_REQUEST['astuce']);
    $site->add_contents(new contents(false,'Weekmail créé'));
  }
  else
    $site->add_contents(new error('','Il y\'a déjà un weekmail en attente d\'envoi et un weekmail ouvert à l\'ajout de nouvelles !'));
}

if($_REQUEST['action']
   && $_REQUEST['action']=='send'
   && $GLOBALS["svalid_call"]
   && $weekmail->load_first_not_sent())
{
  $_REQUEST['page']='custom';
  if(is_null($weekmail->id_header))
    $site->add_contents(new error('','Aucun header de défini !'));
  elseif(is_null($weekmail->titre) || empty($weekmail->titre))
    $site->add_contents(new error('','Aucun titre de défini !'));
  elseif(is_null($weekmail->introduction) || empty($weekmail->introduction))
    $site->add_contents(new error('','Aucune introduction de définie !'));
  elseif(is_null($weekmail->conclusion) || empty($weekmail->conclusion))
    $site->add_contents(new error('','Aucune conclusion de définie !'));
//  elseif($site->is_sure ( "","Envoyer le weekmail",null, 2 ))
  else
  {
    unset($_REQUEST['page']);
    $weekmail->send();
    $site->add_contents(new contents(false,'Weekmail envoyé avec succès'));
  }
}

$weekmail->load_first_not_sent();
if($_REQUEST['page'] && $weekmail->is_valid())
{
  $page = $_REQUEST['page'];
  // modération des news
  if($page =='modere')
  {
    if($_REQUEST['modere'])
    {
      $modere=$_REQUEST['modere'];
      if($modere=='delete' && $_REQUEST['id_news'])
      {
        new delete($site->dbrw,
                   'weekmail_news',
                   array('id_weekmail'=>$weekmail->id,'id_news'=>$_REQUEST['id_news']));
        unset($_REQUEST['id_news']);
      }
      elseif($modere=='deletes'
         && $_REQUEST['id_news']
         && is_array($_REQUEST['id_news'])
         && !empty($_REQUEST['id_news']))
      {
        foreach($_REQUEST['id_news'] as $id_news)
          new delete($site->dbrw,
                     'weekmail_news',
                     array('id_weekmail'=>$weekmail->id,'id_news'=>$id_news));
        unset($_REQUEST['id_news']);
      }
      elseif($modere=='moderes'
         && $_REQUEST['id_news']
         && is_array($_REQUEST['id_news'])
         && !empty($_REQUEST['id_news']))
      {
        foreach($_REQUEST['id_news'] as $id_news)
          new update($site->dbrw,
                   'weekmail_news',
                   array('modere'=>1),
                   array('id_weekmail'=>$weekmail->id,'id_news'=>$id_news));
        unset($_REQUEST['id_news']);
      }
      elseif($modere=='order'
         && $_REQUEST['id_news']
         && is_array($_REQUEST['id_news'])
         && !empty($_REQUEST['id_news']))
      {
        foreach($_REQUEST['id_news'] as $id_news)
          new update($site->dbrw,
                   'weekmail_news',
                   array('rank'=>intval($_REQUEST[$id_news.'_rank'])),
                   array('id_weekmail'=>$weekmail->id,'id_news'=>$id_news));
        unset($_REQUEST['id_news']);
      }
      elseif($modere=='update'
             && $_REQUEST['id_news']
             && $_REQUEST['id_asso']
             && $_REQUEST['titre']
             && !empty($_REQUEST['titre'])
             && $_REQUEST['content']
             && !empty($_REQUEST['content']))
      {
        new update($site->dbrw,
                   'weekmail_news',
                   array('id_asso'=>$_REQUEST['id_asso'],
                         'titre'=>$_REQUEST['titre'],
                         'content'=>$_REQUEST['content'],
                         'modere'=>1),
                   array('id_weekmail'=>$weekmail->id,'id_news'=>$_REQUEST['id_news']));
      }
    }
    if(isset($_REQUEST['id_news']))
    {
      $req = new requete($site->db,
                         'SELECT * '.
                         'FROM weekmail_news '.
                         'WHERE id_news=\''.intval($_REQUEST['id_news']).'\' '.
                         'AND id_weekmail=\''.$weekmail->id.'\'');
      if($req->lines==1)
      {
        $row = $req->get_row();
        $frm = new form('moderenews', '?', false, 'post', 'Modérer une nouvelle');
        $frm->add_hidden('id_weekmail',$weekmail->id);
        $frm->add_hidden('id_news',$row['id_news']);
        $frm->add_hidden('modere','update');
        $frm->add_hidden('page','modere');
        $frm->add_entity_select("id_asso", "Association concern&eacute;e", $site->db, "asso",$row['id_asso'],true);
        $frm->add_info('Le nom du club ou de l\'association sera automatiquement indiqué, il n\'est donc pas nécessaire de le préciser dans le titre !');
        $frm->add_text_field("titre", "Titre : ",$row['titre'],true,80);
        $frm->add_dokuwiki_toolbar('content',null,null,true);
        $frm->add_text_area("content", "contenu : ",$row['content'],80,20,true);
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
        $frm = new form('deletenews', '?', false, 'post', '');
        $frm->add_hidden('page','modere');
        $frm->add_hidden('id_weekmail',$weekmail->id);
        $frm->add_hidden('id_news',$row['id_news']);
        $frm->add_hidden('modere','delete');
        $frm->add_submit("suppr","Supprimer");
        $site->add_contents ($frm);
        $site->end_page ();
        exit();
      }
    }

    //liste des news et ordonnanceur
    $frm = new form('moderenews', '?', false, 'post', '');
    $frm->add_hidden('page','modere');
    $table = new table('Liste des nouvelles de ce weekmail');
    $table->add_row(array('','Titre','Modéré?','Rang'));
    $req = new requete($site->db,
                       'SELECT id_news, nom_asso, titre, modere, rank '.
                       'FROM `weekmail_news` '.
                       'LEFT JOIN `asso` USING(`id_asso`) '.
                       'WHERE `id_weekmail`=\''.$weekmail->id.'\' '.
                       'ORDER BY `rank` ASC');
    while(list($id_news,$asso,$titre,$modere,$rank)=$req->get_row())
    {
      if(!is_null($asso))
        $titre = '['.$asso.'] '.$titre;
      $mod = 'non';
      if($modere==1)
        $mod = 'oui';
      if(is_null($rank)) $rank='';
      $ln = array();
      $ln[]='<input type="checkbox" class="chkbox" name="id_news['.$id_news.']" value="'.$id_news.'"/>';//case à cocher
      $ln[]='<a href="?page=modere&id_news='.$id_news.'">'.$titre.'</a>';
      $ln[]=$mod;
      $ln[]='<input type="text" name="'.$id_news.'_rank" value="'.$rank.'" size="3" maxlength="3" />';//rank field
      $table->add_row($ln);
    }
    $frm->puts($table->html_render ());
    $frm->add_select_field('modere',
                           'Action',
                           array(''=>'',
                                 'moderes'=>'Accepter',
                                 'order'=>'Ordonner',
                                 '-'=>'',
                                 '--'=>'----',
                                 '---'=>'',
                                 'deletes'=>'Supprimer'));
    $frm->add_submit("suppr","Valider");
    $site->add_contents ($frm);
    $site->end_page ();
    exit();
  }
  elseif($page == 'addnews')
  {

    if(isset($_POST['valid_news']))
    {
      $error = null;
      if(!isset($_REQUEST['id_asso']) || is_null($_REQUEST['id_asso']) || empty($_REQUEST['id_asso']))
        $error = 'Veuillez indiquer une association ou un club de référence.';
      elseif(!isset($_REQUEST['titre']) || empty($_REQUEST['titre']))
        $error = 'Veuillez indiquer un titre !';
      elseif(!isset($_REQUEST['content']) || empty($_REQUEST['content']))
        $error = 'Veuillez remplir le corps de la nouvelle.';
      elseif($GLOBALS['svalid_call'])
      {
        if(is_null($error) && $GLOBALS['svalid_call'])
        {
          $automodere = $site->user->is_in_group ("moderateur_site") && isset ($_REQUEST['automodere']) && $_REQUEST['automodere'] ? 1 : 0;
          $weekmail->add_news($_REQUEST['id_utilisateur'],$_REQUEST['id_asso'],$_REQUEST['titre'],$_REQUEST['content'], $automodere);

          if ($automodere)
            $site->add_contents(new contents(false,'Nouvelle postée.'));
          else
            $site->add_contents(new contents(false,'Nouvelle postée et en attente de modération.'));
        }
        else
          $site->add_contents (new error('',$error));
      }
    }
    $frm = new form('addnews', '?', false, 'post', 'Proposer une nouvelle');
    $frm->allow_only_one_usage();
    $frm->add_hidden('page','addnews');
    $frm->add_hidden('id_weekmail',$weekmail->id);
    $utl = new utilisateur($site->db);
    $utl->load_by_id($site->user->id);
    $frm->add_entity_smartselect('id_utilisateur','Auteur',$utl,false,true);
    $frm->add_entity_select("id_asso", "Association concern&eacute;e", $site->db, "asso",1,true);
    $frm->add_info('Le nom du club ou de l\'association sera automatiquement indiqué, il n\'est donc pas nécessaire de le préciser dans le titre !');
    $frm->add_text_field("titre", "Titre : ",'',true,80);
    $frm->add_dokuwiki_toolbar('content',null,null,true);
    $frm->add_text_area("content", "contenu : ",'',80,20,true);
    if ($site->user->is_in_group ("moderateur_site"))
        $frm->add_checkbox ('automodere', "Automodération");
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
    $site->end_page ();
    exit();
  }
  elseif($page == 'custom')
  {
    if(isset($_POST['update']))
    {
      $file = new dfile($site->db);
      if(isset($_REQUEST['id_file_header'])
        && $file->load_by_id($_REQUEST['id_file_header']))
      {
        $file_info = getimagesize($file->get_real_filename());
        if($file_info)
        {
          list($width)=$file_info;
          if($width=='600')
            $weekmail->set_header($file->id);
        }
      }
      if(isset($_REQUEST['introduction']) && !empty($_REQUEST['introduction']))
        $weekmail->set_intro($_REQUEST['introduction']);
      if(isset($_REQUEST['conclusion']) && !empty($_REQUEST['conclusion']))
        $weekmail->set_conclusion($_REQUEST['conclusion']);
      if(isset($_REQUEST['blague']))
        $weekmail->set_blague($_REQUEST['blague']);
      if(isset($_REQUEST['astuce']))
        $weekmail->set_astuce($_REQUEST['astuce']);
    }
    $file = new dfile($site->db);
    if(!is_null($weekmail->id_header) && $weekmail->id_header>0)
      $file->load_by_id($weekmail->id_header);
    if($file->is_valid() && getimagesize($file->get_real_filename()))
      $site->add_contents(new image('header',$file->get_url()));
    else
      $file = new dfile($site->db);
    $frm = new form('custom', '?', false, 'post', 'Personalisation du weekmail');
    $frm->add_hidden('page','custom');
    $frm->add_entity_smartselect('id_file_header','Header',$file,false,true);
    $frm->add_text_field("titre", "Titre : ",$weekmail->titre,true,80);
    $frm->add_text_area("introduction", "introduction : ",$weekmail->introduction,80,10,true);
    $frm->add_text_area("conclusion", "conclusion : ",$weekmail->conclusion,80,10,true);
    $frm->add_text_area("blague", "blague : ",$weekmail->blague,80,10,false);
    $frm->add_text_area("astuce", "astuce : ",$weekmail->astuce,80,10,false);
    $frm->add_submit("update","Mettre à jour");
    $site->add_contents ($frm);
    $site->end_page ();
    exit();
  }
  elseif($page == 'preview')
  {
    header("Content-Type: text/html; charset=utf-8");
    echo str_replace('<html><body bgcolor="#333333" width="700px"><table bgcolor="#333333" width="700px">',
                     '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">'.
                     '<head>'.
                     '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'.
                     '<title>[weekmail] '.$weekmail->titre.'</title>'.
                     '</head>'.
                     '<body bgcolor="#333333"><table bgcolor="#333333" width="100%">',
                     $weekmail->test_render());
    exit();
  }
  elseif($page == 'send')
  {
    $frm = new form("envoyeweekmail", "?", false, "POST", "Envoyer le weekmail");
    $frm->allow_only_one_usage();
    $frm->add_hidden("action","send");
    $frm->add_submit("valid","Envoyer");
    $cts->add($frm,true);
  }
}


if($weekmail->can_create_new())
{
  $frm = new form('custom', '?', false, 'post', 'Verrouiller ce weekmail et en ouvrir un nouveau.');
  $frm->add_info('Cette procédure verrouille le weekmail actuel et crée un nouveau weekmail en attente de publication. '.
                 'Il vous est alors possible de continuer à éditer le weekmail courant sans modifications externes.');
  $frm->add_hidden('action','create');
  // header par défaut !
  $file = new dfile($site->db);
  $file->load_by_id(4693);
  $frm->add_entity_smartselect('id_file_header','Header',$file,false,true);
  $frm->add_text_field("titre", "Titre : ",'',true,80);
  $frm->add_text_area("introduction", "introduction : ",'',80,5,false);
  $frm->add_text_area("conclusion", "conclusion : ",'',80,5,false);
  $frm->add_text_area("blague", "blague : ",'',80,5,false);
  $frm->add_text_area("astuce", "astuce : ",'',80,5,false);
  $frm->add_submit("update","Vérouiller");
  $site->add_contents ($frm);
}

$site->end_page ();

?>
