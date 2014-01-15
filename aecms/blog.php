<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
 * Copyright 2009
 * - Simon lopez < simon dot lopez at ayolo dot org >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once("include/site.inc.php");
require_once("include/blog.inc.php");

$blog = new blog($site->db,$site->dbrw);

if(defined('CMS_ALTERNATE'))
  $blog->load($site->asso,CMS_ALTERNATE);
else
  $blog->load($site->asso);
if(!$blog->is_valid())
{
  header("Location: ".$site->pubUrl);
  exit();
}

$site->start_page ( CMS_PREFIX."blog", "Blog" );
$cts = new contents();
$cts->cssclass='article blogcts';
if ( $blog->is_writer($site->user) )
{
  if(!defined('ADMIN_SECTION'))
    define('ADMIN_SECTION',true);

  $tabs = array(
          array("","blog.php","Le blog"),
          array("bloguer","blog.php?view=bloguer", "Espace blogueur"));
  if( $blog->is_admin($site->user) )
    $tabs[]=array("admin","blog.php?view=admin","Administration");
  $cts->add(new tabshead($tabs,$_REQUEST["view"]));


  if( isset($_REQUEST['view']) )
  {
    if($_REQUEST['view']=='admin')
    {
      $user = new utilisateur($site->db);
      if(isset($_REQUEST['id_utilisateur']) )
        $user->load_by_id($_REQUEST['id_utilisateur']);
      /* cas simples */
      if ( $_REQUEST["action"] == "delete"
           && isset($_REQUEST['id_utilisateur']) )
          $blog->del_writer($user);
      elseif( $_REQUEST["action"] == "addwriter"
              && isset($_REQUEST['id_utilisateur']) )
          $blog->add_writer($user);
      elseif ( $_REQUEST["action"] == "delcat"
               && isset($_REQUEST['id_cat']) )
          $blog->del_cat($_REQUEST['id_cat']);
      elseif( $_REQUEST["action"] == "addcat"
              && isset($_REQUEST['cat_name'])
           && !empty($_REQUEST['cat_name']))
          $blog->add_cat($_REQUEST['cat_name']);
      /* cas multiples */
      elseif ( $_REQUEST["action"] == "deletes"
              && is_array($_REQUEST["id_utilisateurs"])
              && !empty($_REQUEST["id_utilisateurs"]) )
        foreach($_REQUEST["id_utilisateurs"] as $id )
        {
          if($user->load_by_id($id))
            $blog->del_writer($user);
        }
      elseif ( $_REQUEST["action"] == "deletes"
              && is_array($_REQUEST["id_cats"])
              && !empty($_REQUEST["id_cats"]) )
        foreach($_REQUEST["id_cats"] as $id )
          $blog->del_cat($id);

      $cts->add($blog->get_writers_cts('blog.php?view=admin&type=writer',
                                       array("delete"=>"Supprimer"),
                                       array("deletes"=>"Supprimer")),
                true);
      $frm = new form('addwriter',
                      'blog.php',
                      false,
                      'POST',
                      'Ajout d\'un  blogueur');
      $frm->add_hidden('view','admin');
      $frm->add_hidden('action','addwriter');
      $frm->add_user_fieldv2('id_utilisateur','Utilisateur');
      $frm->add_submit('submit','Ajouter');
      $cts->add($frm,true);

      $cts->add($blog->get_cats_cts('blog.php?view=admin',
                                    array("delete"=>"Supprimer"),
                                    array("deletes"=>"Supprimer")),
                true);

      $frm = new form('addcat',
                      'blog.php',
                      false,
                      'POST',
                      'Ajout d\'une catégorie');
      $frm->add_hidden('view','admin');
      $frm->add_hidden('action','addcat');
      $frm->add_text_field('cat_name','Nom');
      $frm->add_submit('submit','Ajouter');
      $cts->add($frm,true);

      $site->add_contents($cts);
      $site->end_page();
      exit();
    }
    elseif($_REQUEST['view']=='bloguer')
    {
      if($_REQUEST['action'])
      {
        if($_REQUEST['action']=='new')
        {
          if(!$_REQUEST['pub'])
            $_REQUEST['page']='edit';
          if(!$id=$blog->add_entry($site->user,
                                   $_REQUEST['titre'],
                                   $_REQUEST['intro'],
                                   $_REQUEST['contenu'],
                                   $_REQUEST['pub'],
                                   $_REQUEST['id_cat'],
                                   $_REQUEST['date']))
          {
            $error = "Une erreur s'est produite l'ors de l'enregistrement";
            $_REQUEST['page']='new';
          }
          $_REQUEST['id_entry']=$id;
        }
        elseif($_REQUEST['action']=='edit')
        {
          $_REQUEST['page']='edit';
          if(isset($_REQUEST['realupdate']))
          {
            if(!$blog->update_entry($_REQUEST['id_entry'],
                                    $_REQUEST['titre'],
                                    $_REQUEST['intro'],
                                    $_REQUEST['contenu'],
                                    $_REQUEST['pub'],
                                    $_REQUEST['id_cat'],
                                    $_REQUEST['date']))
              $error='Mise à jour impossible';
          }
        }
        elseif($_REQUEST['action']=='deletes')
        {
          $_REQUEST['page']='waiting';
          if(is_array($_REQUEST['id_entrys'])
             && !empty($_REQUEST['id_entrys']))
            foreach($_REQUEST['id_entrys'] as $id)
              $blog->delete_entry($id);
        }
        elseif($_REQUEST['action']=='delete')
        {
          $_REQUEST['page']='waiting';
          if($_REQUEST['id_entry'])
            $blog->delete_entry($_REQUEST['id_entry']);
        }
        elseif($_REQUEST['action']=='publishs')
        {
          $_REQUEST['page']='waiting';
          if(is_array($_REQUEST['id_entrys'])
             && !empty($_REQUEST['id_entrys']))
            foreach($_REQUEST['id_entrys'] as $id)
              $blog->publish_entry($id);
        }
        elseif($_REQUEST['action']=='publish')
        {
          $_REQUEST['action']='waiting';
          if($_REQUEST['id_entry'])
            $blog->publish_entry($_REQUEST['id_entry']);
        }
        elseif($_REQUEST['action']=='view'
               && isset($_REQUEST['id_entry']))
        {
          $toolbox = array("blog.php?view=bloguer&id_entry=".$_REQUEST['id_entry']."&page=edit"=>"Editer",
                           "blog.php?view=bloguer&id_entry=".$_REQUEST['id_entry']."&action=delete"=>"Supprimer",);
          $entry = $blog->get_cts_entry($_REQUEST['id_entry'],$site->user);
          $entry->set_toolbox(new toolbox($toolbox));
          $cts->add($entry);
          $cts->add($blog->cts_comments($_REQUEST['id_entry'],$site->user));
          $site->add_contents($cts);
          $site->end_page();
          exit();
        }
      }

      if($_REQUEST['page']=='new')
      {
        if(isset($error))
          $cts->add(new error('',$error));
        $frm = new form('addentry',
                        'blog.php',
                        true,
                        'POST',
                        'Édition de billet');
        $frm->add_hidden('view','bloguer');
        $frm->add_hidden('action','new');
        $frm->add_text_field('titre','Titre','',true,50);
        $frm->add_dokuwiki_toolbar("intro");
        $cats = $blog->get_cats();
        if(count($cats)>0)
        {
          $_cats=array(''=>'Aucune');
          foreach($cats as $id_cat=>$cat)
            $_cats[$id_cat]=$cat;
          $frm->add_select_field('id_cat','Catégorie',$_cats);
        }
        $frm->add_text_area("intro","Introduction",'',80,10,true);
        $frm->add_dokuwiki_toolbar("contenu");
        $frm->add_text_area("contenu","Contenu",'',80,20,true);
        $frm->add_checkbox("pub","Publier",true);
        $frm->add_datetime_field('date','Date de publication',time());
        $frm->add_submit("submit","Poster");
        $cts->add($frm,true);
        $site->add_contents($cts);
        $site->end_page();
        exit();
      }
      elseif( $_REQUEST['page']=='edit'
              && isset($_REQUEST['id_entry'])
              && $billet=$blog->get_entry_row($_REQUEST['id_entry']))
      {
        if(isset($error))
          $cts->add(new error('',$error));
        $frm = new form('updateentry',
                        'blog.php',
                        true,
                        'POST',
                        'Édition de billet');
        $frm->add_hidden('id_entry',$billet['id_entry']);
        $frm->add_hidden('view','bloguer');
        $frm->add_hidden('action','edit');
        $frm->add_hidden('realupdate','realupdate');
        $frm->add_text_field('titre','Titre',$billet['titre'],true,50);
        $cats = $blog->get_cats();
        if(count($cats)>0)
        {
          $_cats=array(''=>'Aucune');
          foreach($cats as $id_cat=>$cat)
            $_cats[$id_cat]=$cat;
          $frm->add_select_field('id_cat','Catégorie',$_cats,$billet['id_cat']);
        }
        $frm->add_dokuwiki_toolbar("intro");
        $frm->add_text_area("intro","Introduction",$billet['intro'],80,10,true);
        $frm->add_dokuwiki_toolbar("contenu");
        $frm->add_text_area("contenu","Contenu",$billet['contenu'],80,20,true);
        $frm->add_checkbox("pub","Publier",$billet['pub']=='y');
        $frm->add_datetime_field('date','Date de publication',datetime_to_timestamp($billet['date']));
        $frm->add_submit("submit","Modifier");
        $cts->add($frm,true);
        $site->add_contents($cts);
        $site->end_page();
        exit();
      }
      elseif($_REQUEST['page']=='waiting')
      {
        $cts->add($blog->get_cts_waiting_entries(
                      'blog.php?view=bloguer',
                      array("edit"=>"Editer",
                            "publish"=>"Publier",
                            "view"=>"Voir",
                            "delete"=>"Supprimer"),
                      array("publishs"=>"Publier",
                            "deletes"=>"Supprimer")),
                  true);
        $site->add_contents($cts);
        $site->end_page();
        exit();
      }
      else
      {
        $list = new itemlist("Et maintenant, on fait quoi ?");
        $list->add("<a href=\"blog.php?view=bloguer&page=new\">Poster un nouveau billet</a>");
        $list->add("<a href=\"blog.php?view=bloguer&page=waiting\">Billet en attente</a>");
        $cts->add($list,true);
        $site->add_contents($cts);
        $site->end_page();
        exit();
      }
    }
  }
}
if(isset($_REQUEST['id_entry']))
{
  $entry = $blog->get_cts_entry($_REQUEST['id_entry'],$site->user);
  if($cats=$blog->get_cats_cts_list('blog.php',$_REQUEST['id_cat']))
    $cts->add($cats);
  if($blog->is_writer($site->user))
  {
    $toolbox = array("blog.php?view=bloguer&id_entry=".$_REQUEST['id_entry']."&page=edit"=>"Editer",
                     "blog.php?view=bloguer&id_entry=".$_REQUEST['id_entry']."&action=delete"=>"Supprimer",);
    $entry->set_toolbox(new toolbox($toolbox));
  }
  $cts->add($entry);
  $cts->add($blog->cts_comments($_REQUEST['id_entry'],$site->user));
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

if(isset($_REQUEST['id_cat']) && $blog->is_cat($_REQUEST['id_cat']))
{
  if($cats=$blog->get_cats_cts_list('blog.php',$_REQUEST['id_cat']))
    $cts->add($cats);
  $page=0;
  if(isset($_REQUEST['id_page']))
    $page=intval($_REQUEST['id_page']);
  $cts->add($blog->get_cts_cat($_REQUEST['id_cat'],$page));
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

if($cats=$blog->get_cats_cts_list('blog.php'))
  $cts->add($cats);
$page=0;
if(isset($_REQUEST['id_page']))
  $page=intval($_REQUEST['id_page']);
$cts->add($blog->get_cts($page));
$site->add_contents($cts);
$site->end_page();

?>
